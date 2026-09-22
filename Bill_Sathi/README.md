# Bill Sathi

Finance ERP dashboard — sales bills, purchase bills, parties, products, expenses and transactions.

The app is being migrated from a legacy PHP/MySQL codebase (kept for reference in
[`legacy-php/`](./legacy-php)) to:

- **Frontend:** Next.js 16 (App Router) + React 19 + TypeScript 5
- **Styling/UI:** Tailwind CSS v4 + `tw-animate-css` + shadcn/ui (Radix UI base) + Lucide icons + `next-themes`
- **State:** Zustand
- **Forms:** React Hook Form + Zod
- **Toasts:** Sonner
- **Backend/DB:** Supabase (Postgres + Auth via `@supabase/ssr`)
- **Rate limiting:** Upstash Redis + `@upstash/ratelimit`

## Migration status

| Module | Status |
| --- | --- |
| Auth (login, register, forgot/reset password) | ✅ Migrated to Supabase Auth |
| Dashboard (stat cards, module control center) | ✅ Migrated, reads live data from Supabase |
| Sales Bill, Purchase Bill, Delivery Challan, Manage Firm, Manage Party, Product, Expense Tracker, Transaction, Reports, Setting | 🚧 Route + layout scaffolded, shows a "migration in progress" placeholder — port these from `legacy-php/*.php` in follow-up passes |

No functionality or features were removed — the legacy PHP app is preserved as-is under
`legacy-php/` for reference while each module is ported.

## Getting started

1. Copy `.env.local.example` to `.env.local` and fill in:
   - A Supabase project URL + anon key (`NEXT_PUBLIC_SUPABASE_URL`, `NEXT_PUBLIC_SUPABASE_ANON_KEY`, `SUPABASE_SERVICE_ROLE_KEY`)
   - An Upstash Redis REST URL + token (`UPSTASH_REDIS_REST_URL`, `UPSTASH_REDIS_REST_TOKEN`)
2. Apply the database schema: run [`supabase/migrations/0001_init.sql`](./supabase/migrations/0001_init.sql)
   against your Supabase project (via the SQL editor, or `supabase db push` if using the CLI). It
   ports the legacy MySQL schema 1:1 to Postgres, adds a `profiles` table synced to `auth.users`,
   and enables row-level security scoped to `auth.uid()`.
3. Install dependencies and run the dev server:

```bash
npm install
npm run dev
```

4. Visit `http://localhost:3000` — unauthenticated visitors are redirected to `/login`.

## Notes

- The legacy `.env` (MySQL + SMTP credentials) now lives at `legacy-php/.env`. It was already
  committed to git history — rotate the SMTP app password there since it's been exposed publicly.
- `legacy-php/` is excluded from ESLint/TypeScript checks and is not part of the Next.js build.
