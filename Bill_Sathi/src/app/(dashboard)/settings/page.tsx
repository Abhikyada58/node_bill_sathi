import { getProfileSettings } from "@/actions/settings";
import { SettingsForm } from "./settings-form";
import { redirect } from "next/navigation";

export default async function SettingsPage() {
  let profile = null;
  try {
    profile = await getProfileSettings();
  } catch (error) {
    redirect("/login");
  }
  
  return (
    <div className="flex-1 p-4 md:p-8 pt-6 bg-slate-50 min-h-screen">
      <SettingsForm initialData={profile} />
    </div>
  );
}
