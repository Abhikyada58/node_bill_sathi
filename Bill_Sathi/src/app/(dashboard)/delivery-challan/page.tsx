import { Truck } from "lucide-react";
import { ModulePlaceholder } from "@/components/dashboard/module-placeholder";

export default function DeliveryChallanPage() {
  return (
    <ModulePlaceholder
      title="Delivery Challan"
      description="Generate delivery challans for outgoing shipments."
      icon={Truck}
    />
  );
}
