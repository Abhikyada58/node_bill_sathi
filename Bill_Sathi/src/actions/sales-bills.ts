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

export async function getSalesBillById(id: number) {
  const supabase = await createClient()
  const { data, error } = await supabase
    .from("sales_bills")
    .select(`
      *,
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

  const { items, ...billData } = parsed.data
  
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

  // Create items
  const itemsToInsert = items.map(item => ({
    ...item,
    sales_bill_id: bill.id,
  }))

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
