import {
  FileSpreadsheet,
  Receipt,
  CircleArrowUp,
  TrendingUp,
  Clock,
  PiggyBank,
} from "lucide-react";
import { createClient } from "@/lib/supabase/server";
import { getDashboardStats } from "@/lib/data/dashboard";
import { StatCard } from "@/components/dashboard/stat-card";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { navItems } from "@/components/layout/nav-config";
import Link from "next/link";

export default async function DashboardPage() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  const stats = user
    ? await getDashboardStats(supabase, user.id)
    : {
        totalSales: 0,
        totalPurchase: 0,
        totalExpenses: 0,
        totalProfit: 0,
        pendingPayments: 0,
        monthlyRevenue: 0,
      };

  return (
    <div className="grid gap-6">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
        <p className="text-sm text-muted-foreground">
          Overview of your sales, purchases and expenses.
        </p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <StatCard label="Total Sales" value={stats.totalSales} icon={FileSpreadsheet} tone="sales" />
        <StatCard label="Total Purchase" value={stats.totalPurchase} icon={Receipt} tone="purchase" />
        <StatCard label="Total Expenses" value={stats.totalExpenses} icon={CircleArrowUp} tone="expense" />
        <StatCard label="Total Profit" value={stats.totalProfit} icon={TrendingUp} tone="profit" />
        <StatCard label="Pending Payments" value={stats.pendingPayments} icon={Clock} tone="pending" />
        <StatCard label="Monthly Revenue" value={stats.monthlyRevenue} icon={PiggyBank} tone="revenue" />
      </div>

      <Card>
        <CardHeader>
          <CardTitle>ERP Module Control Center</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {navItems
            .filter((item) => item.href !== "/dashboard")
            .map((item) => {
              const Icon = item.icon;
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className="flex items-center gap-3 rounded-lg border p-4 text-sm font-medium transition-colors hover:bg-accent"
                >
                  <Icon className="size-4 text-primary" />
                  {item.label}
                </Link>
              );
            })}
        </CardContent>
      </Card>
    </div>
  );
}
