import { getPartiesWithBalances } from "@/actions/parties";
import { PartyClient } from "./party-client";

export default async function ManagePartyPage() {
  const parties = await getPartiesWithBalances();
  
  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6 bg-slate-50 min-h-screen">
      <PartyClient initialData={parties} />
    </div>
  );
}
