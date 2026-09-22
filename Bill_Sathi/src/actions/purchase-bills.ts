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

export async function createPurchaseBill(input: unknown) {
  const parsed = purchaseBillSchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const supabase = await createClient()
  const { data: userData, error: userError } = await supabase.auth.getUser()
  if (userError || !userData.user) return { error: "Unauthorized" }

  const { items, ...billData } = parsed.data
  
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

  // Create items
  const itemsToInsert = items.map((item: any) => {
    // Note: discount_percent isn't in the purchase_bill_items schema, 
    // so we omit it before inserting.
    const { discount_percent, ...itemData } = item;
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
