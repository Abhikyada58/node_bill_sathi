import { createClient } from "@/lib/supabase/server";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Button } from "@/components/ui/button";
import { LogOut, Settings, UserRound } from "lucide-react";
import Link from "next/link";
import { logout } from "@/actions/auth";
import { MobileNav } from "./mobile-nav";

function initials(name: string) {
  return name
    .split(" ")
    .map((part) => part[0])
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

export async function Topbar() {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();

  const { data: profile } = user
    ? await supabase.from("profiles").select("full_name, email, shop_name").eq("id", user.id).single()
    : { data: null };

  const displayName = profile?.full_name ?? user?.email ?? "Account";
  const shopName = profile?.shop_name || profile?.full_name || "SHREE KHODIYAR TEX";

  return (
    <header className="flex h-16 items-center justify-between gap-4 border-b bg-[#007bff] text-white px-4 md:px-6">
      <div className="flex items-center gap-4">
        <MobileNav />
        {/* We can hide standard mobile nav toggle here if we wanted, but we keep it */}
        
        {/* Shop Name Badge */}
        <div className="flex items-center gap-2 bg-[#4da3ff] hover:bg-[#66b3ff] transition-colors rounded-lg px-3 py-2 cursor-pointer shadow-sm">
          <div className="bg-white rounded-md w-6 h-6 flex items-center justify-center font-bold text-[#007bff] text-sm">
            {shopName.charAt(0).toUpperCase()}
          </div>
          <span className="font-bold text-sm tracking-wide">
            {shopName.toUpperCase()}
          </span>
        </div>
      </div>
      
      <div className="flex-1" />
      
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" className="flex items-center gap-2 px-2 hover:bg-[#0056b3] text-white hover:text-white">
            <Avatar className="size-8 border-2 border-white/20">
              <AvatarFallback className="bg-[#0056b3] text-white">{initials(displayName)}</AvatarFallback>
            </Avatar>
            <span className="hidden text-sm font-medium sm:inline">{displayName}</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-56">
          <DropdownMenuLabel>{profile?.email ?? user?.email}</DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuItem asChild>
            <Link href="/settings">
              <UserRound className="mr-2 size-4" />
              Profile
            </Link>
          </DropdownMenuItem>
          <DropdownMenuItem asChild>
            <Link href="/settings">
              <Settings className="mr-2 size-4" />
              Settings
            </Link>
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <form action={logout}>
            <DropdownMenuItem asChild>
              <button type="submit" className="w-full text-left">
                <LogOut className="mr-2 size-4" />
                Log out
              </button>
            </DropdownMenuItem>
          </form>
        </DropdownMenuContent>
      </DropdownMenu>
    </header>
  );
}
