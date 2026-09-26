import { getPurchaseBillById } from "@/actions/purchase-bills";
import { getProfileSettings } from "@/actions/settings";
import { PurchasePrintTemplate } from "./print-template";
import { notFound } from "next/navigation";

export default async function PurchasePrintPage({ params }: { params: Promise<{ id: string }> }) {
  try {
    const resolvedParams = await params;
    const id = Number(resolvedParams.id);
    if (isNaN(id)) {
      return notFound();
    }
    
    const bill = await getPurchaseBillById(id);
    const profile = await getProfileSettings();

    if (!bill) {
      return notFound();
    }

    return (
      <div className="min-h-screen bg-gray-100 py-8 print:bg-white print:py-0">
        <PurchasePrintTemplate bill={bill} profile={profile} />
      </div>
    );
  } catch (error) {
    console.error(error);
    return notFound();
  }
}
