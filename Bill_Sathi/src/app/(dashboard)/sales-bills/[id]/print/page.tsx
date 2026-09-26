import { getSalesBillById } from "@/actions/sales-bills";
import { getProfileSettings } from "@/actions/settings";
import { PrintTemplate } from "./print-template";
import { notFound } from "next/navigation";

export default async function PrintInvoicePage({ params }: { params: Promise<{ id: string }> }) {
  try {
    const resolvedParams = await params;
    const id = Number(resolvedParams.id);
    if (isNaN(id)) {
      return notFound();
    }
    
    const bill = await getSalesBillById(id);
    const profile = await getProfileSettings();

    if (!bill) {
      return notFound();
    }

    return (
      <div className="min-h-screen bg-gray-100 py-8 print:bg-white print:py-0">
        <PrintTemplate bill={bill} profile={profile} />
      </div>
    );
  } catch (error) {
    console.error(error);
    return notFound();
  }
}
