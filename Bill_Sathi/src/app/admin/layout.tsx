import { AdminSidebar } from "@/components/layout/admin-sidebar";
import { Topbar } from "@/components/layout/topbar";
import { checkIsAdmin } from "@/actions/admin";
import { redirect } from "next/navigation";
import { cookies } from "next/headers";
import { AdminPasswordPrompt } from "@/components/admin/password-prompt";

export default async function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const isAdmin = await checkIsAdmin();
  if (!isAdmin) {
    redirect("/dashboard");
  }

  const cookieStore = await cookies();
  const isUnlocked = cookieStore.get("admin_unlocked")?.value === "true";

  if (!isUnlocked) {
    return <AdminPasswordPrompt />;
  }

  return (
    <div className="flex min-h-svh">
      <AdminSidebar />
      <div className="flex flex-1 flex-col">
        <Topbar />
        <main className="flex-1 overflow-y-auto bg-slate-50 p-4 md:p-8">
          {children}
        </main>
      </div>
    </div>
  );
}
