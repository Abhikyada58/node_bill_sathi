import { getPurchaseBillById } from "@/actions/purchase-bills"
import { getParties, getProducts } from "@/actions/sales-bills"
import { PurchaseBillForm } from "../../create/purchase-bill-form"
import { notFound } from "next/navigation"

export default async function EditPurchaseBillPage({ params }: { params: Promise<{ id: string }> }) {
  const resolvedParams = await params
  const id = Number(resolvedParams.id)
  
  if (isNaN(id)) {
    return notFound()
  }
  
  const [parties, products, bill] = await Promise.all([
    getParties(),
    getProducts(),
    getPurchaseBillById(id)
  ])

  if (!bill) {
    return notFound()
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between border-b pb-4">
        <h2 className="text-3xl font-bold tracking-tight">Edit Purchase Bill</h2>
      </div>
      <PurchaseBillForm parties={parties} products={products} initialData={bill} />
    </div>
  )
}
