"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Menu, Wallet } from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { navItems } from "./nav-config";
import { useState } from "react";

export function MobileNav() {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="ghost" size="icon" className="md:hidden">
          <Menu className="size-5" />
        </Button>
      </DialogTrigger>
      <DialogContent className="p-0 sm:max-w-xs" showCloseButton>
        <DialogTitle className="flex items-center gap-2 border-b px-6 py-4 font-semibold">
          <Wallet className="size-5 text-primary" />
          Bill Sathi
        </DialogTitle>
        <nav className="space-y-1 p-3">
          {navItems.map((item) => {
            const active =
              pathname === item.href || pathname.startsWith(`${item.href}/`);
            const Icon = item.icon;
            return (
              <Link
                key={item.href}
                href={item.href}
                onClick={() => setOpen(false)}
                className={cn(
                  "flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium",
                  active
                    ? "bg-accent text-accent-foreground"
                    : "text-foreground/70 hover:bg-accent hover:text-accent-foreground"
                )}
              >
                <Icon className="size-4" />
                {item.label}
              </Link>
            );
          })}
        </nav>
      </DialogContent>
    </Dialog>
  );
}
