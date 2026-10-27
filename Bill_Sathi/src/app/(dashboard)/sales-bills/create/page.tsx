import { getParties, getProducts, getNextSalesBillNumber } from "@/actions/sales-bills";
import { SalesBillForm } from "./sales-bill-form";

export default async function CreateSalesBillPage() {
  const [parties, products, nextBillNumber] = await Promise.all([
    getParties(),
    getProducts(),
    getNextSalesBillNumber(),
  ]);
  
  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6">
      <h2 className="text-3xl font-bold tracking-tight">Create Sales Bill</h2>
      <SalesBillForm parties={parties} products={products} nextBillNumber={nextBillNumber} />
    </div>
  );
}
