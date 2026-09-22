import { ArrowLeftRight } from "lucide-react";
import { ModulePlaceholder } from "@/components/dashboard/module-placeholder";

export default function TransactionsPage() {
  return (
    <ModulePlaceholder
      title="Transaction"
      description="View the full ledger of sales, purchases, expenses and payments."
      icon={ArrowLeftRight}
    />
  );
}
