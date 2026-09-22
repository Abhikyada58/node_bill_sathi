import { Users } from "lucide-react";
import { ModulePlaceholder } from "@/components/dashboard/module-placeholder";

export default function PartiesPage() {
  return (
    <ModulePlaceholder
      title="Manage Party"
      description="Maintain your customers and suppliers in one place."
      icon={Users}
    />
  );
}
