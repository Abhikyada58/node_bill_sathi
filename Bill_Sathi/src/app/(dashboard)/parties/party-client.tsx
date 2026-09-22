"use client"

import { useState } from "react"
import { Plus, MoreVertical, Edit, Trash, ArrowUp, ArrowDown } from "lucide-react"
import { toast } from "sonner"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Card, CardContent } from "@/components/ui/card"

import { PartyModal } from "./party-modal"
import { deleteParty } from "@/actions/parties"

interface PartyClientProps {
  initialData: any[]
}

export function PartyClient({ initialData }: PartyClientProps) {
  const [parties, setParties] = useState(initialData)
  const [modalOpen, setModalOpen] = useState(false)
  const [selectedParty, setSelectedParty] = useState<any | null>(null)
  
  const [searchQuery, setSearchQuery] = useState("")

  const filteredParties = parties.filter(party => 
    party.name?.toLowerCase().includes(searchQuery.toLowerCase())
  )

  const handleEdit = (party: any) => {
    setSelectedParty(party)
    setModalOpen(true)
  }

  const handleAdd = () => {
    setSelectedParty(null)
    setModalOpen(true)
  }

  const handleDelete = async (id: number) => {
    if (!confirm("Are you sure you want to delete this party?")) return
    
    const res = await deleteParty(id)
    if (res.error) {
      toast.error(res.error)
    } else {
      toast.success("Party deleted successfully")
      setParties(parties.filter(p => p.id !== id))
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-3xl font-bold tracking-tight">Party</h2>
      </div>

      <Card className="border-none shadow-sm">
        <CardContent className="p-0 pt-4">
          <div className="px-4 pb-4 flex justify-between items-center">
            <div className="flex space-x-2 w-full max-w-xl">
              <Select defaultValue="100">
                <SelectTrigger className="w-[120px]">
                  <SelectValue placeholder="Show" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="50">Show 50</SelectItem>
                  <SelectItem value="100">Show 100</SelectItem>
                  <SelectItem value="500">Show 500</SelectItem>
                </SelectContent>
              </Select>
              
              <Input 
                placeholder="Search" 
                className="w-[200px]"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
              
              <Select defaultValue="all">
                <SelectTrigger className="w-[180px]">
                  <SelectValue placeholder="All Brokers" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Brokers</SelectItem>
                  {/* Dynamic brokers can be added here later */}
                </SelectContent>
              </Select>
            </div>
            
            <Button onClick={handleAdd} className="bg-blue-500 hover:bg-blue-600">
              <Plus className="mr-2 h-4 w-4" />
              ADD PARTY
            </Button>
          </div>

          <Table>
            <TableHeader>
              <TableRow className="bg-slate-50 border-t">
                <TableHead className="font-semibold text-black">Party Name</TableHead>
                <TableHead className="font-semibold text-black">GST No</TableHead>
                <TableHead className="font-semibold text-black text-center">Pan<br/>number</TableHead>
                <TableHead className="font-semibold text-black text-center">Pay/Receive</TableHead>
                <TableHead className="font-semibold text-black text-center">Action</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredParties.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className="text-center h-24 text-muted-foreground">
                    No parties found. Add one to get started.
                  </TableCell>
                </TableRow>
              ) : (
                filteredParties.map((party) => {
                  const balance = party.balance || 0;
                  
                  return (
                    <TableRow key={party.id}>
                      <TableCell className="font-medium text-xs uppercase text-slate-700">
                        {party.name}
                      </TableCell>
                      <TableCell className="font-medium text-xs uppercase text-slate-700">
                        {party.gst_number || "-"}
                      </TableCell>
                      <TableCell className="text-center font-medium text-xs uppercase text-slate-700">
                        {party.pan_number || "-"}
                      </TableCell>
                      <TableCell className="text-center font-medium text-xs">
                        {balance === 0 ? (
                          <span className="text-gray-400">No Dues</span>
                        ) : balance > 0 ? (
                          <span className="text-green-500 flex items-center justify-center font-bold">
                            <ArrowUp className="h-3 w-3 mr-1" /> ₹ {balance.toFixed(2)}
                          </span>
                        ) : (
                          <span className="text-red-500 flex items-center justify-center font-bold">
                            <ArrowDown className="h-3 w-3 mr-1" /> ₹ {Math.abs(balance).toFixed(2)}
                          </span>
                        )}
                      </TableCell>
                      <TableCell className="text-center">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-500">
                              <MoreVertical className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => handleEdit(party)}>
                              <Edit className="mr-2 h-4 w-4" /> Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem className="text-destructive" onClick={() => handleDelete(party.id)}>
                              <Trash className="mr-2 h-4 w-4" /> Delete
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  )
                })
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <PartyModal 
        isOpen={modalOpen} 
        onClose={() => setModalOpen(false)} 
        party={selectedParty} 
      />
    </div>
  )
}
