import { getParties, getProducts } from "@/actions/sales-bills";
import { PurchaseBillForm } from "./purchase-bill-form";

export default async function CreatePurchaseBillPage() {
  // We can reuse getParties and getProducts from sales-bills actions
  const parties = await getParties();
  const products = await getProducts();
  
  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6 bg-slate-50 min-h-screen">
      <h2 className="text-xl font-medium tracking-tight flex items-center">
        <span className="mr-2">←</span> Add Purchase Bill
      </h2>
      <PurchaseBillForm parties={parties} products={products} />
    </div>
  );
}
