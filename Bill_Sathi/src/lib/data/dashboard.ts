import type { SupabaseClient } from "@supabase/supabase-js";

export type DashboardStats = {
  totalSales: number;
  totalPurchase: number;
  totalExpenses: number;
  totalProfit: number;
  pendingPayments: number;
  monthlyRevenue: number;
};

export async function getDashboardStats(
  supabase: SupabaseClient,
  userId: string
): Promise<DashboardStats> {
  const now = new Date();
  const monthStart = new Date(now.getFullYear(), now.getMonth(), 1)
    .toISOString()
    .slice(0, 10);

  const [salesRes, purchasesRes, expensesRes] = await Promise.all([
    supabase
      .from("sales_bills")
      .select("grand_total, paid_amount, bill_date")
      .eq("user_id", userId),
    supabase
      .from("purchases")
      .select("amount, paid_amount")
      .eq("user_id", userId),
    supabase.from("expenses").select("amount, paid_amount").eq("user_id", userId),
  ]);

  const sales = salesRes.data ?? [];
  const purchases = purchasesRes.data ?? [];
  const expenses = expensesRes.data ?? [];

  const totalSales = sales.reduce((sum, row) => sum + Number(row.grand_total ?? 0), 0);
  const totalPurchase = purchases.reduce((sum, row) => sum + Number(row.amount ?? 0), 0);
  const totalExpenses = expenses.reduce((sum, row) => sum + Number(row.amount ?? 0), 0);
  const totalProfit = totalSales - totalPurchase - totalExpenses;

  const salesPending = sales.reduce(
    (sum, row) => sum + (Number(row.grand_total ?? 0) - Number(row.paid_amount ?? 0)),
    0
  );
  const purchasePending = purchases.reduce(
    (sum, row) => sum + (Number(row.amount ?? 0) - Number(row.paid_amount ?? 0)),
    0
  );
  const pendingPayments = salesPending + purchasePending;

  const monthlyRevenue = sales
    .filter((row) => (row.bill_date as string) >= monthStart)
    .reduce((sum, row) => sum + Number(row.grand_total ?? 0), 0);

  return {
    totalSales,
    totalPurchase,
    totalExpenses,
    totalProfit,
    pendingPayments,
    monthlyRevenue,
  };
}
