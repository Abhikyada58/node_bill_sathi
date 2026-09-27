"use server"

import { cookies } from "next/headers"
import { createClient } from "@/lib/supabase/server"
import { createAdminClient } from "@/lib/supabase/admin"

export async function checkIsAdmin() {
  const supabase = await createClient()
  const { data: { user } } = await supabase.auth.getUser()
  if (!user) return false

  const { data: profile } = await supabase
    .from("profiles")
    .select("is_admin")
    .eq("id", user.id)
    .single()

  return profile?.is_admin === true
}

export async function getAdminPlatformStats() {
  const isAdmin = await checkIsAdmin()
  if (!isAdmin) return { error: "Unauthorized" }

  const adminClient = createAdminClient()

  // Run queries in parallel
  const [
    { count: totalUsers },
    { count: totalSalesBills },
    { count: totalPurchaseBills },
    { data: revenueData }
  ] = await Promise.all([
    adminClient.from("profiles").select("*", { count: "exact", head: true }),
    adminClient.from("sales_bills").select("*", { count: "exact", head: true }),
    adminClient.from("purchases").select("*", { count: "exact", head: true }),
    adminClient.from("sales_bills").select("grand_total")
  ])

  // Calculate total revenue
  const totalRevenue = revenueData?.reduce((sum, bill) => sum + Number(bill.grand_total || 0), 0) || 0

  return {
    totalUsers: totalUsers || 0,
    totalSalesBills: totalSalesBills || 0,
    totalPurchaseBills: totalPurchaseBills || 0,
    totalRevenue
  }
}

export async function getAdminAllUsers() {
  const isAdmin = await checkIsAdmin()
  if (!isAdmin) return { error: "Unauthorized" }

  const adminClient = createAdminClient()

  const { data, error } = await adminClient
    .from("profiles")
    .select("id, full_name, email, shop_name, shop_mobile, created_at, is_admin")
    .order("created_at", { ascending: false })

  if (error) return { error: error.message }
  return { users: data }
}

export async function verifyAdminPassword(password: string) {
  if (password === "shivansh") {
    const cookieStore = await cookies()
    cookieStore.set("admin_unlocked", "true", { path: "/admin", maxAge: 60 * 60 * 24 })
    return { success: true }
  }
  return { error: "Incorrect password" }
}
