"use client"

import { useEffect } from "react"
import { useRouter } from "next/navigation"
import { useForm, useFieldArray } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { format } from "date-fns"
import { CalendarIcon, Plus, Trash, UploadCloud } from "lucide-react"
import { toast } from "sonner"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent } from "@/components/ui/card"
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Calendar } from "@/components/ui/calendar"
import { Checkbox } from "@/components/ui/checkbox"
import { cn } from "@/lib/utils"

import { purchaseBillSchema, PurchaseBillFormValues } from "@/lib/validations/purchase-bill"
import { createPurchaseBill } from "@/actions/purchase-bills"

interface PurchaseBillFormProps {
  parties: any[]
  products: any[]
}

export function PurchaseBillForm({ parties, products }: PurchaseBillFormProps) {
  const router = useRouter()

  const form = useForm<PurchaseBillFormValues>({
    resolver: zodResolver(purchaseBillSchema),
    defaultValues: {
      supplier_id: 0,
      bill_number: "",
      bill_date: new Date(),
      due_days: 0,
      due_date: new Date(),
      apply_gst: false,
      discount_percent: 0,
      discount_amount: 0,
      gst_percent: 0,
      gst_amount: 0,
      taxable_amount: 0,
      amount: 0,
      items: [
        { product_id: 0, product_name: "", quantity: 1, rate: 0, discount_percent: 0, amount: 0, unit: "Pcs" }
      ],
    }
  })

  const { fields, append, remove } = useFieldArray({
    name: "items",
    control: form.control,
  })

  const items = form.watch("items")
  const applyGst = form.watch("apply_gst")
  const billDate = form.watch("bill_date")
  const dueDays = form.watch("due_days")

  useEffect(() => {
    if (billDate) {
      const newDueDate = new Date(billDate)
      newDueDate.setDate(newDueDate.getDate() + (Number(dueDays) || 0))
      form.setValue("due_date", newDueDate)
    }
  }, [billDate, dueDays, form])

  const handleProductSelect = (index: number, productId: string) => {
    const product = products.find(p => p.id === Number(productId))
    if (product) {
      form.setValue(`items.${index}.product_id`, product.id)
      form.setValue(`items.${index}.product_name`, product.name)
      form.setValue(`items.${index}.item_code`, product.item_code || "")
      form.setValue(`items.${index}.hsn_code`, product.hsn_code || "")
      form.setValue(`items.${index}.unit`, product.unit || "Pcs")
      form.setValue(`items.${index}.rate`, Number(product.price))
      calculateItemAmount(index)
    }
  }

  const calculateItemAmount = (index: number) => {
    const qty = Number(form.getValues(`items.${index}.quantity`)) || 0
    const rate = Number(form.getValues(`items.${index}.rate`)) || 0
    const discountPercent = Number(form.getValues(`items.${index}.discount_percent`)) || 0
    
    const gross = qty * rate
    const discount = gross * (discountPercent / 100)
    const net = gross - discount
    
    form.setValue(`items.${index}.amount`, net)
  }

  useEffect(() => {
    let totalQty = 0
    let netAmount = 0
    let totalDiscount = 0

    items.forEach(item => {
      const qty = Number(item.quantity) || 0
      const rate = Number(item.rate) || 0
      const discountP = Number(item.discount_percent) || 0
      const gross = qty * rate
      const discount = gross * (discountP / 100)
      
      totalQty += qty
      netAmount += gross
      totalDiscount += discount
    })

    const taxableAmount = netAmount - totalDiscount
    form.setValue("discount_amount", totalDiscount)
    form.setValue("taxable_amount", taxableAmount)
    
    // In screenshots, Total Amount equals Taxable Amount when GST is not applied.
    // If Apply GST is checked in future, you would add GST calculation here.
    let gstAmount = 0
    if (applyGst) {
      // Assuming 18% as default for demo if applied, this can be made dynamic
      gstAmount = taxableAmount * 0.18
      form.setValue("gst_percent", 18)
    } else {
      form.setValue("gst_percent", 0)
    }
    form.setValue("gst_amount", gstAmount)
    
    form.setValue("amount", taxableAmount + gstAmount)

    // Store total qty in a virtual state or just use it in render
  }, [items, applyGst, form])

  const onSubmit = async (data: PurchaseBillFormValues) => {
    if (!data.supplier_id) {
      toast.error("Please select a party")
      return
    }

    const res = await createPurchaseBill(data)
    if (res.error) {
      toast.error(res.error)
    } else {
      toast.success("Purchase Bill created successfully")
      router.push("/purchase-bills")
      router.refresh()
    }
  }

  // Calculate totals for UI display
  const totalQty = items.reduce((acc, item) => acc + (Number(item.quantity) || 0), 0)
  const netAmount = items.reduce((acc, item) => acc + ((Number(item.quantity) || 0) * (Number(item.rate) || 0)), 0)

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
        
        <Card className="border-none shadow-sm">
          <CardContent className="pt-6">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="space-y-4">
                <FormField
                  control={form.control}
                  name="supplier_id"
                  render={({ field }) => (
                    <FormItem>
                      <div className="flex justify-between">
                        <FormLabel>Party *</FormLabel>
                        <span className="text-blue-500 text-xs cursor-pointer">+ Add Party</span>
                      </div>
                      <Select onValueChange={field.onChange} defaultValue={field.value ? String(field.value) : undefined}>
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue placeholder="Select Party" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {parties.map((p) => (
                            <SelectItem key={p.id} value={String(p.id)}>{p.name}</SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <div className="flex items-center space-x-2 pt-2">
                  <Checkbox 
                    checked={applyGst} 
                    onCheckedChange={(c) => form.setValue("apply_gst", !!c)} 
                    id="applyGst" 
                  />
                  <label htmlFor="applyGst" className="text-sm font-medium text-blue-500 cursor-pointer">
                    Apply GST
                  </label>
                </div>
              </div>

              <div className="space-y-4">
                <FormField
                  control={form.control}
                  name="bill_number"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Bill No. *</FormLabel>
                      <FormControl>
                        <Input placeholder="Bill No" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <FormField
                  control={form.control}
                  name="due_days"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Due Days</FormLabel>
                      <FormControl>
                        <Input type="number" placeholder="Enter Due Days" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              <div className="space-y-4">
                <FormField
                  control={form.control}
                  name="bill_date"
                  render={({ field }) => (
                    <FormItem className="flex flex-col">
                      <FormLabel>Bill Date</FormLabel>
                      <Popover>
                        <PopoverTrigger asChild>
                          <FormControl>
                            <Button
                              variant={"outline"}
                              className={cn("w-full pl-3 text-left font-normal", !field.value && "text-muted-foreground")}
                            >
                              {field.value ? format(field.value, "dd/MM/yyyy") : <span>Pick a date</span>}
                              <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />
                            </Button>
                          </FormControl>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0" align="start">
                          <Calendar
                            mode="single"
                            selected={field.value}
                            onSelect={field.onChange}
                            initialFocus
                          />
                        </PopoverContent>
                      </Popover>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="due_date"
                  render={({ field }) => (
                    <FormItem className="flex flex-col">
                      <FormLabel>Due Date</FormLabel>
                      <FormControl>
                        <Button
                          variant={"outline"}
                          className="w-full pl-3 text-left font-normal bg-muted text-muted-foreground cursor-not-allowed"
                        >
                          {field.value ? format(field.value, "dd/MM/yyyy") : "DD/MM/YYYY"}
                        </Button>
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-none shadow-sm">
          <CardContent className="pt-6">
            <h3 className="font-semibold mb-4 text-lg">Products</h3>
            <div className="overflow-x-auto border rounded-md mb-4 bg-slate-50/50">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-muted-foreground text-left text-xs font-semibold">
                    <th className="p-3 w-[20%]">Product *</th>
                    <th className="p-3 w-[10%]">Item Code</th>
                    <th className="p-3 w-[10%]">HSN Code</th>
                    <th className="p-3 w-[10%]">Qty *</th>
                    <th className="p-3 w-[12%]">Unit *</th>
                    <th className="p-3 w-[12%]">Rate (₹) *</th>
                    <th className="p-3 w-[12%]">Discount (%)</th>
                    <th className="p-3 w-[14%]">Amount (₹)</th>
                  </tr>
                </thead>
                <tbody className="bg-white">
                  {fields.map((field, index) => (
                    <tr key={field.id} className="border-b">
                      <td className="p-2">
                        <Select onValueChange={(val) => handleProductSelect(index, val)}>
                          <SelectTrigger className="h-9">
                            <SelectValue placeholder="Select" />
                          </SelectTrigger>
                          <SelectContent>
                            {products.map((p) => (
                              <SelectItem key={p.id} value={String(p.id)}>{p.name}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </td>
                      <td className="p-2">
                        <Input {...form.register(`items.${index}.item_code`)} readOnly className="h-9" />
                      </td>
                      <td className="p-2">
                        <Input {...form.register(`items.${index}.hsn_code`)} className="h-9" />
                      </td>
                      <td className="p-2">
                        <Input 
                          type="number" 
                          step="0.01" 
                          className="h-9"
                          {...form.register(`items.${index}.quantity`)} 
                          onChange={(e) => {
                            form.setValue(`items.${index}.quantity`, Number(e.target.value))
                            calculateItemAmount(index)
                          }}
                        />
                      </td>
                      <td className="p-2">
                        <Select 
                          onValueChange={(val) => form.setValue(`items.${index}.unit`, val)}
                          defaultValue={field.unit}
                        >
                          <SelectTrigger className="h-9">
                            <SelectValue placeholder="Unit" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="Pcs">Pcs</SelectItem>
                            <SelectItem value="Kg">Kg</SelectItem>
                            <SelectItem value="Mtr">Mtr</SelectItem>
                            <SelectItem value="Box">Box</SelectItem>
                          </SelectContent>
                        </Select>
                      </td>
                      <td className="p-2">
                        <Input 
                          type="number" 
                          step="0.01" 
                          className="h-9"
                          {...form.register(`items.${index}.rate`)}
                          onChange={(e) => {
                            form.setValue(`items.${index}.rate`, Number(e.target.value))
                            calculateItemAmount(index)
                          }}
                        />
                      </td>
                      <td className="p-2 relative">
                        <div className="flex items-center">
                          <Input 
                            type="number" 
                            step="0.01" 
                            placeholder="Enter D"
                            className="h-9 pr-6"
                            {...form.register(`items.${index}.discount_percent`)}
                            onChange={(e) => {
                              form.setValue(`items.${index}.discount_percent`, Number(e.target.value))
                              calculateItemAmount(index)
                            }}
                          />
                          <span className="absolute right-4 text-xs text-muted-foreground">₹</span>
                        </div>
                      </td>
                      <td className="p-2 flex items-center gap-2">
                        <Input 
                          type="number" 
                          readOnly 
                          className="bg-muted h-9"
                          {...form.register(`items.${index}.amount`)} 
                        />
                        <button 
                          type="button" 
                          onClick={() => remove(index)}
                          disabled={fields.length === 1}
                          className="text-gray-400 hover:text-red-500 disabled:opacity-50"
                        >
                          <div className="h-5 w-5 rounded-full bg-gray-200 flex items-center justify-center">
                            <span className="text-xl font-bold leading-none mb-1">-</span>
                          </div>
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <Button
              type="button"
              variant="outline"
              size="sm"
              className="text-blue-500 border-blue-200 bg-blue-50 hover:bg-blue-100"
              onClick={() => append({ product_id: 0, product_name: "", quantity: 1, rate: 0, discount_percent: 0, amount: 0, unit: "Pcs" })}
            >
              <Plus className="mr-2 h-4 w-4" /> ADD PRODUCT
            </Button>
          </CardContent>
        </Card>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 bg-white p-6 border-t mt-4">
          <div className="space-y-4">
            <div className="border rounded-md p-4 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-slate-50 text-muted-foreground bg-slate-50/50">
              <UploadCloud className="h-6 w-6 text-blue-500 mb-2" />
              <p className="text-sm font-medium text-black">Add Attachment</p>
              <p className="text-xs">JPG, JPEG, PNG, PDF (Max 5MB)</p>
            </div>
            
            <FormField
              control={form.control}
              name="remarks"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Remark</FormLabel>
                  <FormControl>
                    <Textarea className="resize-none h-24" {...field} value={field.value || ""} />
                  </FormControl>
                  <div className="text-right text-xs text-muted-foreground">
                    {(field.value || "").length} / 200
                  </div>
                </FormItem>
              )}
            />
          </div>

          <div className="space-y-3 pt-2">
            <div className="flex justify-between text-sm text-muted-foreground">
              <span>Total Qty.:</span>
              <span className="font-medium text-black">{totalQty.toFixed(2)}</span>
            </div>
            <div className="flex justify-between text-sm text-muted-foreground">
              <span>Net Amount:</span>
              <span className="font-medium text-black">₹ {netAmount.toFixed(2)}</span>
            </div>
            <div className="flex justify-between text-sm text-muted-foreground">
              <span>Discount Amount:</span>
              <span className="font-medium text-black">- ₹ {form.watch("discount_amount").toFixed(2)}</span>
            </div>
            <div className="flex justify-between text-sm text-muted-foreground">
              <span>Taxable Amount:</span>
              <span className="font-medium text-black">₹ {form.watch("taxable_amount").toFixed(2)}</span>
            </div>
            
            {applyGst && (
              <div className="flex justify-between text-sm text-muted-foreground">
                <span>Tax Amount (GST):</span>
                <span className="font-medium text-black">+ ₹ {form.watch("gst_amount").toFixed(2)}</span>
              </div>
            )}
            
            <div className="flex justify-between font-bold text-base pt-2">
              <span>Total Amount:</span>
              <span>₹ {form.watch("amount").toFixed(2)}</span>
            </div>

            <div className="flex justify-end gap-3 pt-6">
              <Button type="button" variant="default" className="bg-green-500 hover:bg-green-600">
                Save & Create New
              </Button>
              <Button type="button" variant="outline" className="text-red-500 border-red-200 bg-red-50 hover:bg-red-100" onClick={() => router.back()}>
                CANCEL
              </Button>
              <Button type="submit" className="bg-blue-600 hover:bg-blue-700 px-8">
                SUBMIT
              </Button>
            </div>
          </div>
        </div>
      </form>
    </Form>
  )
}
