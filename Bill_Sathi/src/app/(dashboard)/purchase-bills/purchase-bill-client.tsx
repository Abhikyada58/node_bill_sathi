"use client"

import { useState } from "react"
import Link from "next/link"
import { format } from "date-fns"
import { Plus, MoreVertical, Printer, Download, Eye, Edit, Trash, CreditCard } from "lucide-react"
import { toast } from "sonner"

import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Badge } from "@/components/ui/badge"
import { deletePurchaseBill } from "@/actions/purchase-bills"
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs"

type PurchaseBill = any

interface PurchaseBillClientProps {
  initialData: PurchaseBill[]
}

export function PurchaseBillClient({ initialData }: PurchaseBillClientProps) {
  const [bills, setBills] = useState(initialData)
  const [tab, setTab] = useState("all")

  const filteredBills = bills.filter(bill => {
    if (tab === "with-tax") return bill.apply_gst === true
    if (tab === "without-tax") return bill.apply_gst === false
    return true
  })

  const handleDelete = async (id: number) => {
    if (!confirm("Are you sure you want to delete this bill?")) return
    
    const res = await deletePurchaseBill(id)
    if (res.error) {
      toast.error(res.error)
    } else {
      toast.success("Bill deleted successfully")
      setBills(bills.filter(b => b.id !== id))
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-3xl font-bold tracking-tight">Purchase Bill</h2>
      </div>

      <Card>
        <CardContent className="p-0 pt-4">
          <div className="px-4 pb-4 flex justify-between items-center">
            <Tabs defaultValue="all" onValueChange={setTab} className="w-[400px]">
              <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="all">All</TabsTrigger>
                <TabsTrigger value="with-tax">With Tax</TabsTrigger>
                <TabsTrigger value="without-tax">Without Tax</TabsTrigger>
              </TabsList>
            </Tabs>
            <div className="flex items-center space-x-2">
              <Button variant="outline">Export Excel</Button>
              <Link href="/purchase-bills/create">
                <Button>
                  <Plus className="mr-2 h-4 w-4" />
                  ADD PURCHASE BILL
                </Button>
              </Link>
            </div>
          </div>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Bill Date</TableHead>
                <TableHead>Bill No.</TableHead>
                <TableHead>Party Name</TableHead>
                <TableHead>Bill Type</TableHead>
                <TableHead className="text-right">Total Amount</TableHead>
                <TableHead className="text-right">Pending Amount</TableHead>
                <TableHead className="text-center">Status</TableHead>
                <TableHead className="text-center">Due Days</TableHead>
                <TableHead className="text-center">Action</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredBills.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={9} className="text-center h-24 text-muted-foreground">
                    No purchase bills found.
                  </TableCell>
                </TableRow>
              ) : (
                filteredBills.map((bill) => {
                  const pendingAmount = Number(bill.amount) - Number(bill.paid_amount)
                  
                  return (
                    <TableRow key={bill.id}>
                      <TableCell>{format(new Date(bill.bill_date), "dd/MM/yyyy")}</TableCell>
                      <TableCell>{bill.bill_number}</TableCell>
                      <TableCell>{bill.parties?.name}</TableCell>
                      <TableCell>
                        <Badge variant="outline" className="uppercase text-[10px] tracking-wider">
                          {bill.apply_gst ? "With Tax" : "Without Tax"}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right font-medium">{Number(bill.amount).toFixed(2)}</TableCell>
                      <TableCell className="text-right font-medium">{pendingAmount.toFixed(2)}</TableCell>
                      <TableCell className="text-center">
                        <Badge 
                          variant={bill.status === "PAID" ? "default" : bill.status === "PARTIAL" ? "secondary" : "destructive"}
                          className="w-[70px] justify-center bg-green-100 text-green-700 border-green-200"
                        >
                          {bill.status}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-center font-medium">
                        {bill.due_days || "-"}
                      </TableCell>
                      <TableCell className="text-center">
                        <div className="flex items-center justify-center space-x-2">
                          <Button variant="ghost" size="icon">
                            <CreditCard className="h-4 w-4 text-gray-500" />
                          </Button>
                          <Button variant="ghost" size="icon">
                            <Printer className="h-4 w-4 text-gray-500" />
                          </Button>
                          <Button variant="ghost" size="icon">
                            <Download className="h-4 w-4 text-gray-500" />
                          </Button>
                          <Button variant="ghost" size="icon">
                            <Eye className="h-4 w-4 text-gray-500" />
                          </Button>
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button variant="ghost" size="icon">
                                <MoreVertical className="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <DropdownMenuItem className="text-destructive" onClick={() => handleDelete(bill.id)}>
                                <Trash className="mr-2 h-4 w-4" /> Delete
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </div>
                      </TableCell>
                    </TableRow>
                  )
                })
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  )
}
