import { getParties, getProducts } from "@/actions/sales-bills";
import { SalesBillForm } from "./sales-bill-form";

export default async function CreateSalesBillPage() {
  const parties = await getParties();
  const products = await getProducts();
  
  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6">
      <h2 className="text-3xl font-bold tracking-tight">Create Sales Bill</h2>
      <SalesBillForm parties={parties} products={products} />
    </div>
  );
}
