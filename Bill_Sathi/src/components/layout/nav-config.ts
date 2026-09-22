import type { LucideIcon } from "lucide-react";
import {
  PieChart,
  FileText,
  Receipt,
  Truck,
  Building2,
  Users,
  Box,
  Wallet,
  ArrowLeftRight,
  BarChart3,
  Settings,
} from "lucide-react";

export type NavItem = {
  label: string;
  href: string;
  icon: LucideIcon;
};

export const navItems: NavItem[] = [
  { label: "Dashboard", href: "/dashboard", icon: PieChart },
  { label: "Sales Bill", href: "/sales-bills", icon: FileText },
  { label: "Purchase Bill", href: "/purchase-bills", icon: Receipt },
  { label: "Delivery Challan", href: "/delivery-challan", icon: Truck },
  { label: "Manage Firm", href: "/manage-firm", icon: Building2 },
  { label: "Manage Party", href: "/parties", icon: Users },
  { label: "Product", href: "/products", icon: Box },
  { label: "Expense Tracker", href: "/expenses", icon: Wallet },
  { label: "Transaction", href: "/transactions", icon: ArrowLeftRight },
  { label: "Reports", href: "/reports", icon: BarChart3 },
  { label: "Setting", href: "/settings", icon: Settings },
];
