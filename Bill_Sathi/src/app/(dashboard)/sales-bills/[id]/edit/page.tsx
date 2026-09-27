import { getParties, getProducts, getSalesBillById } from "@/actions/sales-bills"
import { SalesBillForm } from "../../create/sales-bill-form"
import { notFound } from "next/navigation"

export default async function EditSalesBillPage({ params }: { params: Promise<{ id: string }> }) {
  const resolvedParams = await params
  const id = Number(resolvedParams.id)
  
  if (isNaN(id)) {
    return notFound()
  }
  
  const [parties, products, bill] = await Promise.all([
    getParties(),
    getProducts(),
    getSalesBillById(id)
  ])

  if (!bill) {
    return notFound()
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between border-b pb-4">
        <h2 className="text-3xl font-bold tracking-tight">Edit Sales Bill</h2>
      </div>
      <SalesBillForm parties={parties} products={products} initialData={bill} />
    </div>
  )
}
