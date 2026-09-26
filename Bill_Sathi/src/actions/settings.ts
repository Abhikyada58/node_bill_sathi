"use server"

import { revalidatePath } from "next/cache"
import { createClient } from "@/lib/supabase/server"
import { settingsSchema } from "@/lib/validations/settings"

export async function getProfileSettings() {
  const supabase = await createClient()
  
  const { data: userData, error: userError } = await supabase.auth.getUser()
  if (userError || !userData.user) throw new Error("Unauthorized")

  const { data, error } = await supabase
    .from("profiles")
    .select("*")
    .eq("id", userData.user.id)
    .single()

  if (error) throw new Error(error.message)

  return data
}

export async function updateProfileSettings(input: unknown) {
  const parsed = settingsSchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const supabase = await createClient()
  const { data: userData, error: userError } = await supabase.auth.getUser()
  if (userError || !userData.user) return { error: "Unauthorized" }

  const { error } = await supabase
    .from("profiles")
    .update(parsed.data)
    .eq("id", userData.user.id)

  if (error) return { error: error.message }

  revalidatePath("/settings")
  return { success: true }
}
