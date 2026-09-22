import { BarChart3 } from "lucide-react";
import { ModulePlaceholder } from "@/components/dashboard/module-placeholder";

export default function ReportsPage() {
  return (
    <ModulePlaceholder
      title="Reports"
      description="Analytics and reports across sales, purchases and expenses."
      icon={BarChart3}
    />
  );
}
