"use server"

import { revalidatePath } from "next/cache"
import { createClient } from "@/lib/supabase/server"
import { partySchema } from "@/lib/validations/party"

export async function getPartiesWithBalances() {
  const supabase = await createClient()
  
  // Fetch parties with their related sales bills and purchases to calculate balances
  const { data, error } = await supabase
    .from("parties")
    .select(`
      *,
      sales_bills(grand_total, paid_amount, status),
      purchases(amount, paid_amount, status)
    `)
    .order("name", { ascending: true })

  if (error) throw new Error(error.message)

  // Calculate Pay/Receive for each party
  const partiesWithBalance = data.map((party: any) => {
    let totalReceive = 0 // Money owed to us (from sales)
    let totalPay = 0 // Money we owe (from purchases)

    // Sum unpaid sales bills
    party.sales_bills?.forEach((bill: any) => {
      if (bill.status !== "PAID") {
        totalReceive += (Number(bill.grand_total) - Number(bill.paid_amount))
      }
    })

    // Sum unpaid purchase bills
    party.purchases?.forEach((bill: any) => {
      if (bill.status !== "PAID") {
        totalPay += (Number(bill.amount) - Number(bill.paid_amount))
      }
    })

    const netBalance = totalReceive - totalPay

    return {
      ...party,
      balance: netBalance
    }
  })

  return partiesWithBalance
}

export async function createParty(input: unknown) {
  const parsed = partySchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const supabase = await createClient()
  const { data: userData, error: userError } = await supabase.auth.getUser()
  if (userError || !userData.user) return { error: "Unauthorized" }

  const { error } = await supabase
    .from("parties")
    .insert({
      ...parsed.data,
      user_id: userData.user.id
    })

  if (error) return { error: error.message }

  revalidatePath("/parties")
  return { success: true }
}

export async function updateParty(id: number, input: unknown) {
  const parsed = partySchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const supabase = await createClient()
  
  const { error } = await supabase
    .from("parties")
    .update(parsed.data)
    .eq("id", id)

  if (error) return { error: error.message }

  revalidatePath("/parties")
  return { success: true }
}

export async function deleteParty(id: number) {
  const supabase = await createClient()
  
  const { error } = await supabase
    .from("parties")
    .delete()
    .eq("id", id)

  if (error) return { error: error.message }

  revalidatePath("/parties")
  return { success: true }
}
