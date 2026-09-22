"use server"

import { headers } from "next/headers"
import { redirect } from "next/navigation"
import { createClient } from "@/lib/supabase/server"
import { authRatelimit } from "@/lib/redis/ratelimit"
import {
  loginSchema,
  registerSchema,
  forgotPasswordSchema,
  resetPasswordSchema,
} from "@/lib/validations/auth"

type ActionResult = { error: string } | { success: true }

async function rateLimitOrError(key: string): Promise<string | null> {
  try {
    const { success } = await authRatelimit.limit(key)
    if (!success) return "Too many attempts. Please try again in a minute."
    return null
  } catch {
    // Redis not configured in local dev — fail open
    return null
  }
}

export async function login(input: unknown): Promise<ActionResult> {
  const parsed = loginSchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const headerList = await headers()
  const ip = headerList.get("x-forwarded-for")?.split(",")[0]?.trim() ?? "local"
  const limitError = await rateLimitOrError(`login:${ip}`)
  if (limitError) return { error: limitError }

  const supabase = await createClient()
  const { error } = await supabase.auth.signInWithPassword(parsed.data)
  if (error) return { error: error.message }

  redirect("/dashboard")
}

export async function register(input: unknown): Promise<ActionResult> {
  const parsed = registerSchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const headerList = await headers()
  const ip = headerList.get("x-forwarded-for")?.split(",")[0]?.trim() ?? "local"
  const limitError = await rateLimitOrError(`register:${ip}`)
  if (limitError) return { error: limitError }

  const supabase = await createClient()
  const { fullName, email, password } = parsed.data
  const { error } = await supabase.auth.signUp({
    email,
    password,
    options: { data: { full_name: fullName } },
  })
  if (error) return { error: error.message }

  return { success: true }
}

export async function forgotPassword(input: unknown): Promise<ActionResult> {
  const parsed = forgotPasswordSchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const headerList = await headers()
  const ip = headerList.get("x-forwarded-for")?.split(",")[0]?.trim() ?? "local"
  const limitError = await rateLimitOrError(`forgot:${ip}`)
  if (limitError) return { error: limitError }

  const origin = headerList.get("origin") ?? ""
  const supabase = await createClient()
  const { error } = await supabase.auth.resetPasswordForEmail(parsed.data.email, {
    redirectTo: `${origin}/reset-password`,
  })
  if (error) return { error: error.message }

  return { success: true }
}

export async function resetPassword(input: unknown): Promise<ActionResult> {
  const parsed = resetPasswordSchema.safeParse(input)
  if (!parsed.success) {
    return { error: parsed.error.issues[0]?.message ?? "Invalid input" }
  }

  const supabase = await createClient()
  const { error } = await supabase.auth.updateUser({
    password: parsed.data.password,
  })
  if (error) return { error: error.message }

  redirect("/login")
}

export async function logout() {
  const supabase = await createClient()
  await supabase.auth.signOut()
  redirect("/login")
}
