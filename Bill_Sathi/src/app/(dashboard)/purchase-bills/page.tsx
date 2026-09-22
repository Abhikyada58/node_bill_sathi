import { Receipt } from "lucide-react";
import { ModulePlaceholder } from "@/components/dashboard/module-placeholder";

export default function PurchaseBillsPage() {
  return (
    <ModulePlaceholder
      title="Purchase Bill"
      description="Record supplier purchases, GST and payment status."
      icon={Receipt}
    />
  );
}
