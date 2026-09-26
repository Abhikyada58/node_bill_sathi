"use client"

import { useState } from "react"
import { Plus, MoreVertical, Edit, Trash } from "lucide-react"
import { toast } from "sonner"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Card, CardContent } from "@/components/ui/card"

import { ProductModal } from "./product-modal"
import { deleteProduct } from "@/actions/products"

interface ProductClientProps {
  initialData: any[]
}

export function ProductClient({ initialData }: ProductClientProps) {
  const [products, setProducts] = useState(initialData)
  const [modalOpen, setModalOpen] = useState(false)
  const [selectedProduct, setSelectedProduct] = useState<any | null>(null)
  
  const [searchQuery, setSearchQuery] = useState("")

  const filteredProducts = products.filter(product => 
    product.name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    product.item_code?.toLowerCase().includes(searchQuery.toLowerCase()) ||
    product.hsn_code?.toLowerCase().includes(searchQuery.toLowerCase())
  )

  const handleEdit = (product: any) => {
    setSelectedProduct(product)
    setModalOpen(true)
  }

  const handleAdd = () => {
    setSelectedProduct(null)
    setModalOpen(true)
  }

  const handleDelete = async (id: number) => {
    if (!confirm("Are you sure you want to delete this product?")) return
    
    const res = await deleteProduct(id)
    if (res.error) {
      toast.error(res.error)
    } else {
      toast.success("Product deleted successfully")
      setProducts(products.filter(p => p.id !== id))
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-3xl font-bold tracking-tight text-slate-800">Product</h2>
      </div>

      <Card className="border-none shadow-sm">
        <CardContent className="p-0 pt-4">
          <div className="px-4 pb-4 flex justify-between items-center">
            <div className="flex space-x-4 w-full max-w-sm">
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
                className="w-full"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
            
            <Button onClick={handleAdd} className="bg-blue-500 hover:bg-blue-600 font-medium">
              <Plus className="mr-2 h-4 w-4 stroke-[3]" />
              ADD PRODUCT
            </Button>
          </div>

          <div className="px-4">
            <Table>
              <TableHeader>
                <TableRow className="border-t border-b hover:bg-transparent">
                  <TableHead className="font-bold text-slate-800 h-12">Product Name</TableHead>
                  <TableHead className="font-bold text-slate-800 text-center h-12 w-32">Item Code</TableHead>
                  <TableHead className="font-bold text-slate-800 text-center h-12 w-32">HSN<br/>Code</TableHead>
                  <TableHead className="font-bold text-slate-800 text-center h-12 w-32">Rate</TableHead>
                  <TableHead className="font-bold text-slate-800 text-center h-12 w-24">Unit</TableHead>
                  <TableHead className="font-bold text-slate-800 text-center h-12 w-20">Action</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredProducts.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center h-24 text-muted-foreground">
                      No products found. Add one to get started.
                    </TableCell>
                  </TableRow>
                ) : (
                  filteredProducts.map((product) => (
                    <TableRow key={product.id} className="border-b hover:bg-slate-50/50">
                      <TableCell className="font-bold text-xs uppercase text-slate-700 py-4">
                        {product.name}
                      </TableCell>
                      <TableCell className="text-center font-medium text-xs text-slate-600 py-4">
                        {product.item_code || "-"}
                      </TableCell>
                      <TableCell className="text-center font-medium text-xs text-slate-600 py-4">
                        {product.hsn_code || "-"}
                      </TableCell>
                      <TableCell className="text-center font-medium text-xs text-slate-600 py-4">
                        {product.price ? `₹ ${Number(product.price).toFixed(2)}` : "-"}
                      </TableCell>
                      <TableCell className="text-center font-medium text-xs text-slate-600 py-4">
                        {product.unit}
                      </TableCell>
                      <TableCell className="text-center py-4">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-8 w-8 text-slate-500">
                              <MoreVertical className="h-5 w-5" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => handleEdit(product)}>
                              <Edit className="mr-2 h-4 w-4" /> Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem className="text-destructive" onClick={() => handleDelete(product.id)}>
                              <Trash className="mr-2 h-4 w-4" /> Delete
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      <ProductModal 
        isOpen={modalOpen} 
        onClose={() => setModalOpen(false)} 
        product={selectedProduct} 
      />
    </div>
  )
}
