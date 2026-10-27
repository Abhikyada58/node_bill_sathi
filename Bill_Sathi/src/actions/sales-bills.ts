"use server"

import { revalidatePath } from "next/cache"
import { createClient } from "@/lib/supabase/server"
import { salesBillSchema, paymentSchema } from "@/lib/validations/sales-bill"

export async function getSalesBills() {
  const supabase = await createClient()
  const { data, error } = await supabase
    .from("sales_bills")
    .select(`
      *,
      parties ( name )
    `)
    .order("created_at", { ascending: false })

  if (error) throw new Error(error.message)
  return data
}

export async function getNextSalesBillNumber(): Promise<string> {
  const supabase = await createClient()
  const { data: userData } = await supabase.auth.getUser()
  if (!userData?.user) return "INV-001"

  const { data, error } = await supabase
    .from("sales_bills")
    .select("bill_number")
    .eq("user_id", userData.user.id)
    .order("created_at", { ascending: false })
    .limit(50)

  if (error || !data || data.length === 0) {
    return "INV-001"
  }

  let maxNum = 0
  let prefix = "INV-"
  let padLength = 3

  for (const row of data) {
    const billNum = (row.bill_number ?? "").toString().trim()
    const match = billNum.match(/^(.*?)(\d+)$/)
    if (match) {
      const p = match[1]
      const num = parseInt(match[2], 10)
      if (num > maxNum) {
        maxNum = num
        prefix = p
        padLength = match[2].length
      }
    }
  }

  if (maxNum > 0) {
    const nextNum = (maxNum + 1).toString().padStart(padLength, "0")
    return `${prefix}${nextNum}`
  }

  return `INV-${(data.length + 1).toString().padStart(3, "0")}`
}

export async function getSalesBillById(id: number) {
  const supabase = await createClient()
  const { data, error } = await supabase
    .from("sales_bills")
    .select(`
      *,
      parties (*),
      sales_bill_items (*)
    `)
    .eq("id", id)
    .single()

  if (error) throw new Error(error.message)
  return data
}

export async function getParties() {
  const supabase = await createClient()
  const { data, error } = await supabase
    .from("parties")
    .select("id, name")
    .order("name", { ascending: true })

  if (error) throw new Error(error.message)
  return data
}

export async function getProducts() {
  const supabase = await createClient()
  const { data, error } = await supabase
    .from("products")
    .select("*")
    .order("name", { ascending: true })

  if (error) throw new Error(error.message)
  return data
}

export async function createSalesBill(input: unknown) {
  const parsed = salesBillSchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const supabase = await createClient()
  const { data: userData, error: userError } = await supabase.auth.getUser()
  if (userError || !userData.user) return { error: "Unauthorized" }

  const { items, id: _billId, ...billData } = parsed.data
  
  // Create bill
  const { data: bill, error: billError } = await supabase
    .from("sales_bills")
    .insert({
      ...billData,
      user_id: userData.user.id,
      bill_date: billData.bill_date.toISOString().split("T")[0],
      due_date: billData.due_date.toISOString().split("T")[0],
      challan_date: billData.challan_date ? billData.challan_date.toISOString().split("T")[0] : null,
      status: "UNPAID"
    })
    .select()
    .single()

  if (billError) return { error: billError.message }

  // Create items without 'id' so Postgres can auto-generate identity
  const itemsToInsert = items.map(item => {
    const { id: _itemId, ...itemData } = item;
    return {
      ...itemData,
      sales_bill_id: bill.id,
    };
  })

  const { error: itemsError } = await supabase
    .from("sales_bill_items")
    .insert(itemsToInsert)

  if (itemsError) {
    // Optional: Delete bill if items fail to maintain consistency
    await supabase.from("sales_bills").delete().eq("id", bill.id)
    return { error: itemsError.message }
  }

  revalidatePath("/sales-bills")
  return { success: true, data: bill }
}

export async function recordPayment(input: unknown) {
  const parsed = paymentSchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const supabase = await createClient()
  const { data: userData, error: userError } = await supabase.auth.getUser()
  if (userError || !userData.user) return { error: "Unauthorized" }

  const { bill_id, transaction_amount, payment_mode, reference_number, settlement_amount, tds_amount, notes, payment_date } = parsed.data

  // Fetch current bill to update status and paid_amount
  const { data: bill, error: fetchError } = await supabase
    .from("sales_bills")
    .select("paid_amount, grand_total")
    .eq("id", bill_id)
    .single()

  if (fetchError) return { error: fetchError.message }

  const newPaidAmount = Number(bill.paid_amount) + transaction_amount + settlement_amount + tds_amount
  let status = "PARTIAL"
  if (newPaidAmount >= Number(bill.grand_total)) {
    status = "PAID"
  }

  // Update bill
  const { error: updateError } = await supabase
    .from("sales_bills")
    .update({ paid_amount: newPaidAmount, status })
    .eq("id", bill_id)

  if (updateError) return { error: updateError.message }

  // Insert payment record
  const { error: paymentError } = await supabase
    .from("payments")
    .insert({
      user_id: userData.user.id,
      sales_bill_id: bill_id,
      amount: transaction_amount,
      payment_mode,
      reference_number,
      settlement_amount,
      tds_amount,
      notes,
      payment_date: payment_date.toISOString().split("T")[0]
    })

  if (paymentError) return { error: paymentError.message }

  revalidatePath("/sales-bills")
  return { success: true }
}

export async function deleteSalesBill(id: number) {
  const supabase = await createClient()
  const { error } = await supabase
    .from("sales_bills")
    .delete()
    .eq("id", id)

  if (error) return { error: error.message }
  
  revalidatePath("/sales-bills")
  return { success: true }
}

export async function updateSalesBill(id: number, input: unknown) {
  const parsed = salesBillSchema.safeParse(input);
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" };
  }

  const supabase = await createClient();
  const { data: userData, error: userError } = await supabase.auth.getUser();
  if (userError || !userData.user) return { error: "Unauthorized" };

  const { items, id: _billId, ...billData } = parsed.data;

  // Update bill without 'id'
  const { error: billError } = await supabase
    .from("sales_bills")
    .update({
      ...billData,
      bill_date: billData.bill_date.toISOString().split("T")[0],
      due_date: billData.due_date.toISOString().split("T")[0],
      challan_date: billData.challan_date ? billData.challan_date.toISOString().split("T")[0] : null,
    })
    .eq("id", id)
    .eq("user_id", userData.user.id);

  if (billError) return { error: billError.message };

  // Sync items: first delete all existing items for this bill
  const { error: deleteError } = await supabase
    .from("sales_bill_items")
    .delete()
    .eq("sales_bill_id", id);

  if (deleteError) return { error: deleteError.message };

  // Insert the new items without 'id' so Postgres can generate new IDs
  const itemsToInsert = items.map(item => {
    const { id: _itemId, ...itemData } = item;
    return {
      ...itemData,
      sales_bill_id: id,
    };
  });

  const { error: itemsError } = await supabase
    .from("sales_bill_items")
    .insert(itemsToInsert);

  if (itemsError) {
    return { error: itemsError.message };
  }

  revalidatePath("/sales-bills");
  return { success: true };
}
