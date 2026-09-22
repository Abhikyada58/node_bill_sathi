import { getPurchaseBills } from "@/actions/purchase-bills";
import { PurchaseBillClient } from "./purchase-bill-client";

export default async function PurchaseBillsPage() {
  const bills = await getPurchaseBills();
  
  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6">
      <PurchaseBillClient initialData={bills} />
    </div>
  );
}
