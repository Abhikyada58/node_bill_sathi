import Link from "next/link";
import { Users, LayoutDashboard, Settings } from "lucide-react";

export function AdminSidebar() {
  return (
    <aside className="hidden w-64 flex-col border-r bg-slate-900 md:flex">
      <div className="flex h-16 items-center border-b border-slate-800 px-6">
        <Link href="/admin" className="flex items-center gap-2 font-bold tracking-tight text-white">
          <div className="flex size-8 items-center justify-center rounded-lg bg-blue-600 text-white">
            <Settings className="size-5" />
          </div>
          Admin Panel
        </Link>
      </div>
      <div className="flex-1 py-4 overflow-y-auto">
        <nav className="grid gap-1 px-4 text-sm font-medium">
          <Link
            href="/admin"
            className="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-300 transition-all hover:bg-slate-800 hover:text-white"
          >
            <LayoutDashboard className="size-4" />
            Overview
          </Link>
          <Link
            href="/admin/users"
            className="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-300 transition-all hover:bg-slate-800 hover:text-white"
          >
            <Users className="size-4" />
            Users & Shops
          </Link>
        </nav>
      </div>
      <div className="p-4">
        <Link
          href="/dashboard"
          className="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 transition-all hover:bg-slate-800 hover:text-white"
        >
          &larr; Back to Dashboard
        </Link>
      </div>
    </aside>
  );
}
