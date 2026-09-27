import { createClient } from "@supabase/supabase-js"

// Use this ONLY on the server side in admin actions to bypass RLS.
export function createAdminClient() {
  return createClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.SUPABASE_SERVICE_ROLE_KEY!
  )
}
