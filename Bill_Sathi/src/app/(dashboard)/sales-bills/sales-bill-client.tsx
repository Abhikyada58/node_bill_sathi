"use client"

import { useState } from "react"
import Link from "next/link"
import { format } from "date-fns"
import { Plus, MoreVertical, Printer, Download, CreditCard, Edit, Trash } from "lucide-react"
import { toast } from "sonner"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Badge } from "@/components/ui/badge"
import { deleteSalesBill } from "@/actions/sales-bills"
import { RecordPaymentModal } from "./record-payment-modal"

type SalesBill = any // Replace with proper type later

interface SalesBillClientProps {
  initialData: SalesBill[]
}

export function SalesBillClient({ initialData }: SalesBillClientProps) {
  const [bills, setBills] = useState(initialData)
  const [paymentModalOpen, setPaymentModalOpen] = useState(false)
  const [selectedBill, setSelectedBill] = useState<SalesBill | null>(null)

  const handleDelete = async (id: number) => {
    if (!confirm("Are you sure you want to delete this bill?")) return
    
    const res = await deleteSalesBill(id)
    if (res.error) {
      toast.error(res.error)
    } else {
      toast.success("Bill deleted successfully")
      setBills(bills.filter(b => b.id !== id))
    }
  }

  const handlePayment = (bill: SalesBill) => {
    setSelectedBill(bill)
    setPaymentModalOpen(true)
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-3xl font-bold tracking-tight">Sales Bill</h2>
        <Link href="/sales-bills/create">
          <Button>
            <Plus className="mr-2 h-4 w-4" />
            ADD BILL
          </Button>
        </Link>
      </div>

      <Card>
        <CardContent className="p-0">
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
              {bills.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={9} className="text-center h-24 text-muted-foreground">
                    No sales bills found. Create one to get started.
                  </TableCell>
                </TableRow>
              ) : (
                bills.map((bill) => {
                  const pendingAmount = Number(bill.grand_total) - Number(bill.paid_amount)
                  
                  return (
                    <TableRow key={bill.id}>
                      <TableCell>{format(new Date(bill.bill_date), "dd/MM/yyyy")}</TableCell>
                      <TableCell>{bill.bill_number}</TableCell>
                      <TableCell>{bill.parties?.name}</TableCell>
                      <TableCell>
                        <Badge variant="outline" className="uppercase text-[10px] tracking-wider">
                          Tax Invoice
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right font-medium">{Number(bill.grand_total).toFixed(2)}</TableCell>
                      <TableCell className="text-right font-medium">{pendingAmount.toFixed(2)}</TableCell>
                      <TableCell className="text-center">
                        <Badge 
                          variant={bill.status === "PAID" ? "default" : bill.status === "PARTIAL" ? "secondary" : "destructive"}
                          className="w-[70px] justify-center"
                        >
                          {bill.status}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-center text-destructive font-medium">
                        {bill.due_days}
                      </TableCell>
                      <TableCell className="text-center">
                        <div className="flex items-center justify-center space-x-2">
                          <Button variant="ghost" size="icon" onClick={() => handlePayment(bill)}>
                            <CreditCard className="h-4 w-4 text-blue-500" />
                          </Button>
                          <Button variant="ghost" size="icon">
                            <Printer className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon">
                            <Download className="h-4 w-4" />
                          </Button>
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button variant="ghost" size="icon">
                                <MoreVertical className="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <Link href={`/sales-bills/${bill.id}/edit`}>
                                <DropdownMenuItem>
                                  <Edit className="mr-2 h-4 w-4" /> Edit
                                </DropdownMenuItem>
                              </Link>
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

      {selectedBill && (
        <RecordPaymentModal 
          isOpen={paymentModalOpen} 
          onClose={() => setPaymentModalOpen(false)} 
          bill={selectedBill} 
        />
      )}
    </div>
  )
}
