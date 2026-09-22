import { getSalesBills } from "@/actions/sales-bills";
import { SalesBillClient } from "./sales-bill-client";

export default async function SalesBillsPage() {
  const bills = await getSalesBills();
  
  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6">
      <SalesBillClient initialData={bills} />
    </div>
  );
}
