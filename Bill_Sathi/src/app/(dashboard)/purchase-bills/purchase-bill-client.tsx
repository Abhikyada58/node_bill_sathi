"use client"

import { useState, useMemo } from "react"
import Link from "next/link"
import { format } from "date-fns"
import { Plus, MoreVertical, Printer, Download, Eye, Edit, Trash, CreditCard, Filter, FileSpreadsheet, X } from "lucide-react"
import { toast } from "sonner"
import * as XLSX from "xlsx"

import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Badge } from "@/components/ui/badge"
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"

import { deletePurchaseBill } from "@/actions/purchase-bills"

type PurchaseBill = any

interface PurchaseBillClientProps {
  initialData: PurchaseBill[]
}

export function PurchaseBillClient({ initialData }: PurchaseBillClientProps) {
  const [bills, setBills] = useState(initialData)
  const [tab, setTab] = useState("all")

  // Filter States
  const [search, setSearch] = useState("")
  const [fromDate, setFromDate] = useState("")
  const [toDate, setToDate] = useState("")
  const [partySearch, setPartySearch] = useState("")
  const [statusFilter, setStatusFilter] = useState("ALL")
  const [billNoFilter, setBillNoFilter] = useState("")
  const [sortBy, setSortBy] = useState("Date")
  const [sortOrder, setSortOrder] = useState("Desc")
  const [popoverOpen, setPopoverOpen] = useState(false)

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

  // Filter Logic
  const filteredBills = useMemo(() => {
    let filtered = [...bills]

    // Tab Filter
    if (tab === "with-tax") filtered = filtered.filter(b => b.apply_gst === true)
    if (tab === "without-tax") filtered = filtered.filter(b => b.apply_gst === false)

    // Search (General)
    if (search) {
      const q = search.toLowerCase()
      filtered = filtered.filter(b => 
        b.bill_number?.toLowerCase().includes(q) || 
        b.parties?.name?.toLowerCase().includes(q) ||
        b.amount?.toString().includes(q)
      )
    }

    // Date Range
    if (fromDate) {
      filtered = filtered.filter(b => new Date(b.bill_date) >= new Date(fromDate))
    }
    if (toDate) {
      filtered = filtered.filter(b => new Date(b.bill_date) <= new Date(toDate))
    }

    // Party Search
    if (partySearch) {
      filtered = filtered.filter(b => b.parties?.name?.toLowerCase().includes(partySearch.toLowerCase()))
    }

    // Status Filter
    if (statusFilter !== "ALL") {
      filtered = filtered.filter(b => b.status === statusFilter)
    }

    // Bill No Filter
    if (billNoFilter) {
      filtered = filtered.filter(b => b.bill_number?.toLowerCase().includes(billNoFilter.toLowerCase()))
    }

    // Sorting
    filtered.sort((a, b) => {
      let valA, valB
      
      if (sortBy === "Date") {
        valA = new Date(a.bill_date).getTime()
        valB = new Date(b.bill_date).getTime()
      } else if (sortBy === "BillNo") {
        valA = a.bill_number
        valB = b.bill_number
      } else if (sortBy === "TotalAmount") {
        valA = Number(a.amount)
        valB = Number(b.amount)
      } else {
        valA = a.id
        valB = b.id
      }

      if (valA < valB) return sortOrder === "Asc" ? -1 : 1
      if (valA > valB) return sortOrder === "Asc" ? 1 : -1
      return 0
    })

    return filtered
  }, [bills, tab, search, fromDate, toDate, partySearch, statusFilter, billNoFilter, sortBy, sortOrder])

  const handleExportExcel = () => {
    const exportData = filteredBills.map(bill => ({
      "Bill Date": format(new Date(bill.bill_date), "dd/MM/yyyy"),
      "Bill No.": bill.bill_number,
      "Party Name": bill.parties?.name || "",
      "Bill Type": bill.apply_gst ? "With Tax" : "Without Tax",
      "Total Amount": Number(bill.amount).toFixed(2),
      "Pending Amount": (Number(bill.amount) - Number(bill.paid_amount)).toFixed(2),
      "Status": bill.status,
      "Due Days": bill.due_days || "-"
    }))

    const worksheet = XLSX.utils.json_to_sheet(exportData)
    const workbook = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(workbook, worksheet, "Purchase Bills")
    
    XLSX.writeFile(workbook, "Purchase_Bills_Export.xlsx")
    toast.success("Excel exported successfully")
  }

  const resetFilters = () => {
    setSearch("")
    setFromDate("")
    setToDate("")
    setPartySearch("")
    setStatusFilter("ALL")
    setBillNoFilter("")
    setSortBy("Date")
    setSortOrder("Desc")
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-3xl font-bold tracking-tight">Purchase Bill</h2>
      </div>

      <Card>
        <CardContent className="p-0 pt-4">
          <div className="px-4 pb-4 flex justify-between items-center gap-4">
            <Tabs defaultValue="all" onValueChange={setTab} className="w-[400px]">
              <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="all">All</TabsTrigger>
                <TabsTrigger value="with-tax">With Tax</TabsTrigger>
                <TabsTrigger value="without-tax">Without Tax</TabsTrigger>
              </TabsList>
            </Tabs>
            <div className="flex items-center space-x-2">
              
              {/* Filter Popover */}
              <Popover open={popoverOpen} onOpenChange={setPopoverOpen}>
                <PopoverTrigger asChild>
                  <Button variant="outline" size="icon">
                    <Filter className="h-4 w-4" />
                  </Button>
                </PopoverTrigger>
                <PopoverContent align="end" className="w-80 p-4 space-y-4">
                  <div className="flex items-center justify-between font-semibold border-b pb-2">
                    Filter & Sort
                    <Button variant="ghost" size="icon" className="h-6 w-6" onClick={() => setPopoverOpen(false)}>
                      <X className="h-4 w-4" />
                    </Button>
                  </div>

                  <div className="space-y-3 text-sm">
                    <Input placeholder="Search..." value={search} onChange={e => setSearch(e.target.value)} />

                    <div className="flex items-center gap-2">
                      <div className="space-y-1 w-full">
                        <label className="text-xs text-muted-foreground">From Date</label>
                        <Input type="date" value={fromDate} onChange={e => setFromDate(e.target.value)} />
                      </div>
                      <div className="space-y-1 w-full">
                        <label className="text-xs text-muted-foreground">To Date</label>
                        <Input type="date" value={toDate} onChange={e => setToDate(e.target.value)} />
                      </div>
                    </div>

                    <Input placeholder="Search Party..." value={partySearch} onChange={e => setPartySearch(e.target.value)} />

                    <Select value={statusFilter} onValueChange={setStatusFilter}>
                      <SelectTrigger>
                        <SelectValue placeholder="Payment Status" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="ALL">All Status</SelectItem>
                        <SelectItem value="PAID">Paid</SelectItem>
                        <SelectItem value="UNPAID">Unpaid</SelectItem>
                        <SelectItem value="PARTIAL">Partial</SelectItem>
                      </SelectContent>
                    </Select>

                    <Input placeholder="Bill No." value={billNoFilter} onChange={e => setBillNoFilter(e.target.value)} />

                    <div className="flex items-center gap-2">
                      <Select value={sortBy} onValueChange={setSortBy}>
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Sort By" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="Date">Date</SelectItem>
                          <SelectItem value="BillNo">Bill No.</SelectItem>
                          <SelectItem value="TotalAmount">Total Amount</SelectItem>
                        </SelectContent>
                      </Select>
                      
                      <Select value={sortOrder} onValueChange={setSortOrder}>
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Order" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="Asc">Asc</SelectItem>
                          <SelectItem value="Desc">Desc</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  <div className="flex justify-end gap-2 pt-4 border-t mt-4">
                    <Button variant="outline" className="text-red-500 border-red-200 hover:bg-red-50" onClick={resetFilters}>
                      RESET
                    </Button>
                    <Button variant="outline" className="text-green-600 border-green-500 hover:bg-green-50" onClick={() => setPopoverOpen(false)}>
                      APPLY
                    </Button>
                  </div>
                </PopoverContent>
              </Popover>

              <Button variant="outline" className="border-green-500 text-green-600 hover:bg-green-50 hover:text-green-700" onClick={handleExportExcel}>
                <FileSpreadsheet className="mr-2 h-4 w-4" />
                Export Excel
              </Button>
              <Link href="/purchase-bills/create">
                <Button className="bg-blue-600 hover:bg-blue-700 text-white">
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
                          className={`w-[70px] justify-center ${bill.status === "PAID" ? "bg-green-100 text-green-700 border-green-200" : ""}`}
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
                          <Link href={`/purchase-bills/${bill.id}/print`}>
                            <Button variant="ghost" size="icon">
                              <Printer className="h-4 w-4 text-gray-500" />
                            </Button>
                          </Link>
                          <Link href={`/purchase-bills/${bill.id}/print?download=true`}>
                            <Button variant="ghost" size="icon">
                              <Download className="h-4 w-4 text-gray-500" />
                            </Button>
                          </Link>
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button variant="ghost" size="icon">
                                <MoreVertical className="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <Link href={`/purchase-bills/${bill.id}/edit`}>
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
    </div>
  )
}
