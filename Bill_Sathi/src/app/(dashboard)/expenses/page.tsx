import { Wallet } from "lucide-react";
import { ModulePlaceholder } from "@/components/dashboard/module-placeholder";

export default function ExpensesPage() {
  return (
    <ModulePlaceholder
      title="Expense Tracker"
      description="Track and categorize business expenses."
      icon={Wallet}
    />
  );
}
