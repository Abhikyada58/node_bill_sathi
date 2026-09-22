import { Settings } from "lucide-react";
import { ModulePlaceholder } from "@/components/dashboard/module-placeholder";

export default function SettingsPage() {
  return (
    <ModulePlaceholder
      title="Setting"
      description="Manage your profile, security and application preferences."
      icon={Settings}
    />
  );
}
