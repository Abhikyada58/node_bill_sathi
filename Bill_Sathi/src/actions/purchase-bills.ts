"use server"

import { revalidatePath } from "next/cache"
import { createClient } from "@/lib/supabase/server"
import { purchaseBillSchema } from "@/lib/validations/purchase-bill"

export async function getPurchaseBills() {
  const supabase = await createClient()
  const { data, error } = await supabase
    .from("purchases")
    .select(`
      *,
      parties ( name )
    `)
    .order("created_at", { ascending: false })

  if (error) throw new Error(error.message)
  return data
}

export async function getNextPurchaseBillNumber(): Promise<string> {
  const supabase = await createClient()
  const { data: userData } = await supabase.auth.getUser()
  if (!userData?.user) return "PUR-001"

  const { data, error } = await supabase
    .from("purchases")
    .select("bill_number")
    .eq("user_id", userData.user.id)
    .order("created_at", { ascending: false })
    .limit(50)

  if (error || !data || data.length === 0) {
    return "PUR-001"
  }

  let maxNum = 0
  let prefix = "PUR-"
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

  return `PUR-${(data.length + 1).toString().padStart(3, "0")}`
}

export async function createPurchaseBill(input: unknown) {
  const parsed = purchaseBillSchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const supabase = await createClient()
  const { data: userData, error: userError } = await supabase.auth.getUser()
  if (userError || !userData.user) return { error: "Unauthorized" }

  const { items, id: _billId, ...billData } = parsed.data
  
  // Create bill
  const { data: bill, error: billError } = await supabase
    .from("purchases")
    .insert({
      ...billData,
      user_id: userData.user.id,
      bill_date: billData.bill_date.toISOString().split("T")[0],
      due_date: billData.due_date.toISOString().split("T")[0],
      status: "UNPAID"
    })
    .select()
    .single()

  if (billError) return { error: billError.message }

  // Create items without 'id'
  const itemsToInsert = items.map((item: any) => {
    const { id: _itemId, discount_percent, ...itemData } = item;
    return {
      ...itemData,
      purchase_bill_id: bill.id,
    };
  })

  const { error: itemsError } = await supabase
    .from("purchase_bill_items")
    .insert(itemsToInsert)

  if (itemsError) {
    await supabase.from("purchases").delete().eq("id", bill.id)
    return { error: itemsError.message }
  }

  revalidatePath("/purchase-bills")
  return { success: true, data: bill }
}

export async function deletePurchaseBill(id: number) {
  const supabase = await createClient()
  const { error } = await supabase
    .from("purchases")
    .delete()
    .eq("id", id)

  if (error) return { error: error.message }
  
  revalidatePath("/purchase-bills")
  return { success: true }
}

export async function getPurchaseBillById(id: number) {
  const supabase = await createClient()
  const { data, error } = await supabase
    .from("purchases")
    .select(`
      *,
      parties (*),
      purchase_bill_items (*)
    `)
    .eq("id", id)
    .single()

  if (error) throw new Error(error.message)
  return data
}

export async function updatePurchaseBill(id: number, input: unknown) {
  const parsed = purchaseBillSchema.safeParse(input);
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" };
  }

  const supabase = await createClient();
  const { data: userData, error: userError } = await supabase.auth.getUser();
  if (userError || !userData.user) return { error: "Unauthorized" };

  const { items, id: _billId, ...billData } = parsed.data;

  // Update bill without 'id'
  const { error: billError } = await supabase
    .from("purchases")
    .update({
      ...billData,
      bill_date: billData.bill_date.toISOString().split("T")[0],
      due_date: billData.due_date.toISOString().split("T")[0],
    })
    .eq("id", id)
    .eq("user_id", userData.user.id);

  if (billError) return { error: billError.message };

  // Sync items: first delete all existing items for this bill
  const { error: deleteError } = await supabase
    .from("purchase_bill_items")
    .delete()
    .eq("purchase_bill_id", id);

  if (deleteError) return { error: deleteError.message };

  // Insert the new items without 'id'
  const itemsToInsert = items.map((item: any) => {
    const { id: _itemId, discount_percent, ...itemData } = item;
    return {
      ...itemData,
      purchase_bill_id: id,
    };
  });

  const { error: itemsError } = await supabase
    .from("purchase_bill_items")
    .insert(itemsToInsert);

  if (itemsError) {
    return { error: itemsError.message };
  }

  revalidatePath("/purchase-bills");
  return { success: true };
}
