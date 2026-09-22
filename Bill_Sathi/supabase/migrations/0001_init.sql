-- ============================================
-- Bill Sathi - Postgres schema (Supabase)
-- Ported 1:1 from legacy-php/database/schema.sql (MySQL)
-- ============================================

create extension if not exists "pgcrypto";

create type bill_status as enum ('PAID', 'PARTIAL', 'UNPAID');
create type tds_tcs_type as enum ('NONE', 'TDS', 'TCS');
create type ledger_type as enum ('Sale', 'Purchase', 'Expense', 'Payment');
create type payment_mode as enum ('Cash', 'Bank Transfer', 'UPI', 'Cheque');

-- 1. Profiles (mirrors legacy `users` table; 1:1 with auth.users)
create table if not exists profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  full_name varchar(100) not null,
  email varchar(255) not null unique,
  avatar_url varchar(500),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

-- 2. Parties (unified customers & suppliers)
create table if not exists parties (
  id bigint generated always as identity primary key,
  user_id uuid not null references profiles(id) on delete cascade,
  name varchar(150) not null,
  email varchar(255),
  phone varchar(20),
  address text,
  gst_number varchar(15),
  pan_number varchar(10),
  owner_name varchar(150),
  state varchar(100),
  city varchar(100),
  pincode varchar(10),
  discount numeric(5,2) not null default 0,
  due_days integer not null default 45,
  broker_name varchar(150),
  broker_mobile varchar(20),
  created_at timestamptz not null default now()
);

-- 3. Products
create table if not exists products (
  id bigint generated always as identity primary key,
  user_id uuid not null references profiles(id) on delete cascade,
  name varchar(150) not null,
  category varchar(100) not null,
  price numeric(15,2) not null default 0,
  gst_percent numeric(5,2) not null default 0,
  stock_quantity integer not null default 0,
  item_code varchar(50),
  hsn_code varchar(20),
  unit varchar(20) not null default 'Pcs',
  description text,
  created_at timestamptz not null default now()
);

-- 4. Sales bills
create table if not exists sales_bills (
  id bigint generated always as identity primary key,
  user_id uuid not null references profiles(id) on delete cascade,
  customer_id bigint not null references parties(id) on delete cascade,
  bill_number varchar(50) not null,
  bill_date date not null,
  due_days integer not null default 0,
  due_date date not null,
  challan_no varchar(100),
  challan_date date,
  apply_gst boolean not null default true,
  discount_percent numeric(5,2) not null default 0,
  discount_amount numeric(15,2) not null default 0,
  gst_percent numeric(5,2) not null default 0,
  gst_amount numeric(15,2) not null default 0,
  taxable_amount numeric(15,2) not null default 0,
  grand_total numeric(15,2) not null default 0,
  paid_amount numeric(15,2) not null default 0,
  status bill_status not null default 'UNPAID',
  remarks text,
  tds_tcs_type tds_tcs_type not null default 'NONE',
  tds_tcs_percent numeric(5,2) not null default 0,
  tds_tcs_amount numeric(15,2) not null default 0,
  created_at timestamptz not null default now(),
  unique (user_id, bill_number)
);

-- 5. Sales bill items
create table if not exists sales_bill_items (
  id bigint generated always as identity primary key,
  sales_bill_id bigint not null references sales_bills(id) on delete cascade,
  product_id bigint not null references products(id) on delete cascade,
  product_name varchar(150) not null,
  item_code varchar(50),
  hsn_code varchar(20),
  quantity numeric(15,2) not null default 1,
  unit varchar(20) not null default 'Pcs',
  rate numeric(15,2) not null default 0,
  amount numeric(15,2) not null default 0,
  created_at timestamptz not null default now()
);

-- 6. Purchases
create table if not exists purchases (
  id bigint generated always as identity primary key,
  user_id uuid not null references profiles(id) on delete cascade,
  supplier_id bigint not null references parties(id) on delete cascade,
  bill_number varchar(50) not null,
  bill_date date not null,
  due_days integer not null default 0,
  due_date date not null,
  apply_gst boolean not null default true,
  discount_percent numeric(5,2) not null default 0,
  discount_amount numeric(15,2) not null default 0,
  gst_percent numeric(5,2) not null default 0,
  gst_amount numeric(15,2) not null default 0,
  taxable_amount numeric(15,2) not null default 0,
  amount numeric(15,2) not null default 0,
  paid_amount numeric(15,2) not null default 0,
  status bill_status not null default 'UNPAID',
  remarks text,
  attachment_path varchar(500),
  created_at timestamptz not null default now(),
  unique (user_id, bill_number)
);

-- 7. Purchase bill items
create table if not exists purchase_bill_items (
  id bigint generated always as identity primary key,
  purchase_bill_id bigint not null references purchases(id) on delete cascade,
  product_id bigint not null references products(id) on delete cascade,
  product_name varchar(150) not null,
  item_code varchar(50),
  hsn_code varchar(20),
  quantity numeric(15,2) not null default 1,
  unit varchar(20) not null default 'Pcs',
  rate numeric(15,2) not null default 0,
  amount numeric(15,2) not null default 0,
  gst_percent numeric(5,2) not null default 0,
  gst_amount numeric(15,2) not null default 0,
  created_at timestamptz not null default now()
);

-- 8. Expenses
create table if not exists expenses (
  id bigint generated always as identity primary key,
  user_id uuid not null references profiles(id) on delete cascade,
  expense_date date not null,
  category varchar(100) not null,
  supplier_id bigint references parties(id) on delete set null,
  amount numeric(15,2) not null default 0,
  paid_amount numeric(15,2) not null default 0,
  status bill_status not null default 'UNPAID',
  attachment_path varchar(500),
  notes text,
  created_at timestamptz not null default now()
);

-- 9. Expense items
create table if not exists expense_items (
  id bigint generated always as identity primary key,
  expense_id bigint not null references expenses(id) on delete cascade,
  description varchar(255) not null,
  quantity numeric(15,2) not null default 1,
  rate numeric(15,2) not null default 0,
  amount numeric(15,2) not null default 0,
  created_at timestamptz not null default now()
);

-- 10. Transactions (general ledger)
create table if not exists transactions (
  id bigint generated always as identity primary key,
  user_id uuid not null references profiles(id) on delete cascade,
  reference_id bigint,
  type ledger_type not null,
  amount numeric(15,2) not null default 0,
  description varchar(255),
  date date not null,
  created_at timestamptz not null default now()
);

-- 11. Payments
create table if not exists payments (
  id bigint generated always as identity primary key,
  user_id uuid not null references profiles(id) on delete cascade,
  sales_bill_id bigint references sales_bills(id) on delete cascade,
  purchase_bill_id bigint references purchases(id) on delete cascade,
  transaction_id bigint references transactions(id) on delete set null,
  payment_date date not null,
  payment_mode payment_mode not null,
  reference_number varchar(100),
  amount numeric(15,2) not null default 0,
  tds_amount numeric(15,2) not null default 0,
  settlement_amount numeric(15,2) not null default 0,
  tds_percent numeric(5,2) not null default 0,
  notes text,
  created_at timestamptz not null default now()
);

-- 12. Audit logs
create table if not exists audit_logs (
  id bigint generated always as identity primary key,
  user_id uuid not null references profiles(id) on delete cascade,
  action varchar(255) not null,
  details text,
  created_at timestamptz not null default now()
);

-- ============================================
-- Indexes
-- ============================================
create index if not exists idx_parties_user on parties(user_id);
create index if not exists idx_products_user on products(user_id);
create index if not exists idx_sales_bills_user on sales_bills(user_id);
create index if not exists idx_sales_bills_customer on sales_bills(customer_id);
create index if not exists idx_sales_bill_items_bill on sales_bill_items(sales_bill_id);
create index if not exists idx_purchases_user on purchases(user_id);
create index if not exists idx_purchases_supplier on purchases(supplier_id);
create index if not exists idx_purchase_bill_items_bill on purchase_bill_items(purchase_bill_id);
create index if not exists idx_expenses_user on expenses(user_id);
create index if not exists idx_expense_items_expense on expense_items(expense_id);
create index if not exists idx_transactions_user on transactions(user_id);
create index if not exists idx_payments_user on payments(user_id);
create index if not exists idx_audit_logs_user on audit_logs(user_id);

-- ============================================
-- updated_at trigger for profiles
-- ============================================
create or replace function set_updated_at()
returns trigger as $$
begin
  new.updated_at = now();
  return new;
end;
$$ language plpgsql;

create trigger trg_profiles_updated_at
before update on profiles
for each row execute function set_updated_at();

-- Auto-create a profile row when a new auth user signs up
create or replace function handle_new_user()
returns trigger as $$
begin
  insert into public.profiles (id, full_name, email)
  values (
    new.id,
    coalesce(new.raw_user_meta_data ->> 'full_name', split_part(new.email, '@', 1)),
    new.email
  );
  return new;
end;
$$ language plpgsql security definer set search_path = public;

create trigger on_auth_user_created
after insert on auth.users
for each row execute function handle_new_user();

-- ============================================
-- Row Level Security
-- ============================================
alter table profiles enable row level security;
alter table parties enable row level security;
alter table products enable row level security;
alter table sales_bills enable row level security;
alter table sales_bill_items enable row level security;
alter table purchases enable row level security;
alter table purchase_bill_items enable row level security;
alter table expenses enable row level security;
alter table expense_items enable row level security;
alter table transactions enable row level security;
alter table payments enable row level security;
alter table audit_logs enable row level security;

create policy "profiles: read/update own" on profiles
  for all using (auth.uid() = id) with check (auth.uid() = id);

create policy "parties: owner access" on parties
  for all using (auth.uid() = user_id) with check (auth.uid() = user_id);

create policy "products: owner access" on products
  for all using (auth.uid() = user_id) with check (auth.uid() = user_id);

create policy "sales_bills: owner access" on sales_bills
  for all using (auth.uid() = user_id) with check (auth.uid() = user_id);

create policy "sales_bill_items: owner access" on sales_bill_items
  for all using (
    exists (select 1 from sales_bills b where b.id = sales_bill_id and b.user_id = auth.uid())
  ) with check (
    exists (select 1 from sales_bills b where b.id = sales_bill_id and b.user_id = auth.uid())
  );

create policy "purchases: owner access" on purchases
  for all using (auth.uid() = user_id) with check (auth.uid() = user_id);

create policy "purchase_bill_items: owner access" on purchase_bill_items
  for all using (
    exists (select 1 from purchases p where p.id = purchase_bill_id and p.user_id = auth.uid())
  ) with check (
    exists (select 1 from purchases p where p.id = purchase_bill_id and p.user_id = auth.uid())
  );

create policy "expenses: owner access" on expenses
  for all using (auth.uid() = user_id) with check (auth.uid() = user_id);

create policy "expense_items: owner access" on expense_items
  for all using (
    exists (select 1 from expenses e where e.id = expense_id and e.user_id = auth.uid())
  ) with check (
    exists (select 1 from expenses e where e.id = expense_id and e.user_id = auth.uid())
  );

create policy "transactions: owner access" on transactions
  for all using (auth.uid() = user_id) with check (auth.uid() = user_id);

create policy "payments: owner access" on payments
  for all using (auth.uid() = user_id) with check (auth.uid() = user_id);

create policy "audit_logs: owner access" on audit_logs
  for all using (auth.uid() = user_id) with check (auth.uid() = user_id);
