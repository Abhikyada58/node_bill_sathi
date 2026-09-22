import type { LucideIcon } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import { formatCurrency } from "@/lib/format";

const toneClasses: Record<string, string> = {
  sales: "bg-blue-500/10 text-blue-600 dark:text-blue-400",
  purchase: "bg-violet-500/10 text-violet-600 dark:text-violet-400",
  expense: "bg-rose-500/10 text-rose-600 dark:text-rose-400",
  profit: "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400",
  pending: "bg-amber-500/10 text-amber-600 dark:text-amber-400",
  revenue: "bg-cyan-500/10 text-cyan-600 dark:text-cyan-400",
};

export function StatCard({
  label,
  value,
  icon: Icon,
  tone,
}: {
  label: string;
  value: number;
  icon: LucideIcon;
  tone: keyof typeof toneClasses;
}) {
  return (
    <Card>
      <CardContent className="flex items-center justify-between gap-4 py-2">
        <div>
          <p className="text-sm text-muted-foreground">{label}</p>
          <p className="mt-1 text-2xl font-semibold tracking-tight">
            {formatCurrency(value)}
          </p>
        </div>
        <div className={cn("flex size-11 shrink-0 items-center justify-center rounded-xl", toneClasses[tone])}>
          <Icon className="size-5" />
        </div>
      </CardContent>
    </Card>
  );
}
