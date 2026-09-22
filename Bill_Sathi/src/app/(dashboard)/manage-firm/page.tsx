import { Building2 } from "lucide-react";
import { ModulePlaceholder } from "@/components/dashboard/module-placeholder";

export default function ManageFirmPage() {
  return (
    <ModulePlaceholder
      title="Manage Firm"
      description="Configure your firm's profile, GSTIN and billing details."
      icon={Building2}
    />
  );
}
