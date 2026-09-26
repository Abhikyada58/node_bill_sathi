"use client"

import { useState, useEffect } from "react"
import { useForm, type Resolver } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { toast } from "sonner"
import { X, ExternalLink } from "lucide-react"
import { useRouter } from "next/navigation"

import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Textarea } from "@/components/ui/textarea"

import { productSchema, ProductFormValues } from "@/lib/validations/product"
import { createProduct, updateProduct } from "@/actions/products"

interface ProductModalProps {
  isOpen: boolean
  onClose: () => void
  product?: any | null
}

export function ProductModal({ isOpen, onClose, product }: ProductModalProps) {
  const [isSubmitting, setIsSubmitting] = useState(false)
  const router = useRouter()

  const form = useForm<ProductFormValues>({
    resolver: zodResolver(productSchema) as Resolver<ProductFormValues>,
    defaultValues: {
      name: "",
      price: 0,
      unit: "Pcs",
      gst_percent: 0,
      item_code: "",
      hsn_code: "",
      description: "",
    },
  })

  useEffect(() => {
    if (product) {
      form.reset({
        name: product.name || "",
        price: product.price || 0,
        unit: product.unit || "Pcs",
        gst_percent: product.gst_percent || 0,
        item_code: product.item_code || "",
        hsn_code: product.hsn_code || "",
        description: product.description || "",
      })
    } else {
      form.reset({
        name: "",
        price: 0,
        unit: "Pcs",
        gst_percent: 0,
        item_code: "",
        hsn_code: "",
        description: "",
      })
    }
  }, [product, form, isOpen])

  async function onSubmit(data: ProductFormValues) {
    setIsSubmitting(true)
    let res;
    if (product?.id) {
      res = await updateProduct(product.id, data)
    } else {
      res = await createProduct(data)
    }
    
    setIsSubmitting(false)
    
    if (res.error) {
      toast.error(res.error)
    } else {
      toast.success(product ? "Product updated successfully" : "Product created successfully")
      onClose()
      router.refresh()
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-2xl p-0 overflow-hidden rounded-xl">
        <DialogHeader className="px-6 py-4 border-b relative">
          <DialogTitle className="text-xl font-medium">
            {product ? "Edit Product" : "Add Product"}
          </DialogTitle>
          <button 
            onClick={onClose}
            className="absolute right-4 top-4 rounded-full p-1 hover:bg-slate-100"
          >
            <X className="h-4 w-4 text-muted-foreground" />
          </button>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="px-6 py-4 space-y-4">
            
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Product Name <span className="text-red-500">*</span></FormLabel>
                  <FormControl>
                    <Input placeholder="Enter Product Name" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <div className="grid grid-cols-3 gap-6">
              <FormField
                control={form.control}
                name="price"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Rate</FormLabel>
                    <FormControl>
                      <Input type="number" step="0.01" placeholder="Enter Rate" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="unit"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Unit <span className="text-red-500">*</span></FormLabel>
                    <Select onValueChange={field.onChange} defaultValue={field.value || "Pcs"}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select Unit" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="Pcs">Pcs</SelectItem>
                        <SelectItem value="Kg">Kg</SelectItem>
                        <SelectItem value="Mtr">Mtr</SelectItem>
                        <SelectItem value="Box">Box</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="gst_percent"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>GST</FormLabel>
                    <Select onValueChange={(v) => field.onChange(Number(v))} defaultValue={String(field.value || "0")}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select GST" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="0">0%</SelectItem>
                        <SelectItem value="5">5%</SelectItem>
                        <SelectItem value="12">12%</SelectItem>
                        <SelectItem value="18">18%</SelectItem>
                        <SelectItem value="28">28%</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <div className="grid grid-cols-2 gap-6">
              <FormField
                control={form.control}
                name="item_code"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Item Code</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter Item Code" {...field} value={field.value || ""} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="hsn_code"
                render={({ field }) => (
                  <FormItem>
                    <div className="flex justify-between items-center">
                      <FormLabel>HSN Code</FormLabel>
                      <a href="#" className="text-blue-500 text-xs hover:underline flex items-center">
                        Find Code <ExternalLink className="ml-1 h-3 w-3" />
                      </a>
                    </div>
                    <FormControl>
                      <Input placeholder="Enter HSN Code" {...field} value={field.value || ""} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <FormField
              control={form.control}
              name="description"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Description</FormLabel>
                  <FormControl>
                    <Textarea 
                      placeholder="Enter Description" 
                      className="resize-none h-24" 
                      {...field} 
                      value={field.value || ""} 
                    />
                  </FormControl>
                  <div className="text-right text-xs text-muted-foreground">
                    {(field.value || "").length} / 250
                  </div>
                  <FormMessage />
                </FormItem>
              )}
            />

            <div className="pt-2">
              <Button type="submit" disabled={isSubmitting} className="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium text-base py-6">
                {isSubmitting ? "SAVING..." : "SAVE"}
              </Button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}
