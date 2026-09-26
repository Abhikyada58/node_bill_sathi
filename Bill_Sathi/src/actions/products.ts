"use server"

import { revalidatePath } from "next/cache"
import { createClient } from "@/lib/supabase/server"
import { productSchema } from "@/lib/validations/product"

export async function getProducts() {
  const supabase = await createClient()
  
  const { data, error } = await supabase
    .from("products")
    .select("*")
    .order("name", { ascending: true })

  if (error) throw new Error(error.message)

  return data
}

export async function createProduct(input: unknown) {
  const parsed = productSchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const supabase = await createClient()
  const { data: userData, error: userError } = await supabase.auth.getUser()
  if (userError || !userData.user) return { error: "Unauthorized" }

  const { error } = await supabase
    .from("products")
    .insert({
      ...parsed.data,
      category: "General", // Default value
      user_id: userData.user.id
    })

  if (error) return { error: error.message }

  revalidatePath("/products")
  return { success: true }
}

export async function updateProduct(id: number, input: unknown) {
  const parsed = productSchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const supabase = await createClient()
  
  const { error } = await supabase
    .from("products")
    .update(parsed.data)
    .eq("id", id)

  if (error) return { error: error.message }

  revalidatePath("/products")
  return { success: true }
}

export async function deleteProduct(id: number) {
  const supabase = await createClient()
  
  const { error } = await supabase
    .from("products")
    .delete()
    .eq("id", id)

  if (error) return { error: error.message }

  revalidatePath("/products")
  return { success: true }
}
