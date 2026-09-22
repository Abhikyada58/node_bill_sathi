import { Box } from "lucide-react";
import { ModulePlaceholder } from "@/components/dashboard/module-placeholder";

export default function ProductsPage() {
  return (
    <ModulePlaceholder
      title="Product"
      description="Manage your product catalog, pricing and stock."
      icon={Box}
    />
  );
}
