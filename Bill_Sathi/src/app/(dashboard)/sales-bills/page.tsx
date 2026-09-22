import { FileText } from "lucide-react";
import { ModulePlaceholder } from "@/components/dashboard/module-placeholder";

export default function SalesBillsPage() {
  return (
    <ModulePlaceholder
      title="Sales Bill"
      description="Create and manage customer sales invoices with GST and TDS/TCS."
      icon={FileText}
    />
  );
}
