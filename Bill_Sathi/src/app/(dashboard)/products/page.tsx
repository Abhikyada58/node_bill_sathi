import { getProducts } from "@/actions/products";
import { ProductClient } from "./product-client";

export default async function ProductsPage() {
  const products = await getProducts();
  
  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6 bg-slate-50 min-h-screen">
      <ProductClient initialData={products} />
    </div>
  );
}
