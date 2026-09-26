"use client"

import { useEffect } from "react"
import { useRouter } from "next/navigation"
import { useForm, useFieldArray, type Resolver } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { format } from "date-fns"
import { CalendarIcon, Plus, Trash } from "lucide-react"
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

import { salesBillSchema, SalesBillFormValues } from "@/lib/validations/sales-bill"
import { createSalesBill } from "@/actions/sales-bills"

interface SalesBillFormProps {
  parties: any[]
  products: any[]
}

export function SalesBillForm({ parties, products }: SalesBillFormProps) {
  const router = useRouter()

  const form = useForm<SalesBillFormValues>({
    resolver: zodResolver(salesBillSchema) as Resolver<SalesBillFormValues>,
    defaultValues: {
      customer_id: 0,
      bill_number: "",
      bill_date: new Date(),
      due_days: 45,
      due_date: new Date(Date.now() + 45 * 24 * 60 * 60 * 1000),
      apply_gst: true,
      discount_percent: 0,
      discount_amount: 0,
      gst_percent: 5, // Default GST example
      gst_amount: 0,
      taxable_amount: 0,
      grand_total: 0,
      tds_tcs_type: "NONE",
      tds_tcs_percent: 0,
      tds_tcs_amount: 0,
      items: [
        { product_id: 0, product_name: "", quantity: 1, rate: 0, amount: 0, unit: "Pcs" }
      ],
    }
  })

  const { fields, append, remove } = useFieldArray({
    name: "items",
    control: form.control,
  })

  // Watch values for calculation
  const items = form.watch("items")
  const discountPercent = form.watch("discount_percent")
  const applyGst = form.watch("apply_gst")
  const gstPercent = form.watch("gst_percent")
  const tdsTcsType = form.watch("tds_tcs_type")
  const tdsTcsPercent = form.watch("tds_tcs_percent")
  const billDate = form.watch("bill_date")
  const dueDays = form.watch("due_days")

  // Handle due date calculation
  useEffect(() => {
    if (billDate && dueDays >= 0) {
      const newDueDate = new Date(billDate)
      newDueDate.setDate(newDueDate.getDate() + dueDays)
      form.setValue("due_date", newDueDate)
    }
  }, [billDate, dueDays, form])

  // Handle product selection
  const handleProductSelect = (index: number, productId: string) => {
    const product = products.find(p => p.id === Number(productId))
    if (product) {
      form.setValue(`items.${index}.product_id`, product.id)
      form.setValue(`items.${index}.product_name`, product.name)
      form.setValue(`items.${index}.item_code`, product.item_code || "")
      form.setValue(`items.${index}.hsn_code`, product.hsn_code || "")
      form.setValue(`items.${index}.unit`, product.unit || "Pcs")
      form.setValue(`items.${index}.rate`, Number(product.price))
      
      const qty = form.getValues(`items.${index}.quantity`)
      form.setValue(`items.${index}.amount`, qty * Number(product.price))
    }
  }

  // Handle item quantity/rate change
  const calculateItemAmount = (index: number) => {
    const qty = form.getValues(`items.${index}.quantity`) || 0
    const rate = form.getValues(`items.${index}.rate`) || 0
    form.setValue(`items.${index}.amount`, qty * rate)
  }

  // Main calculation effect
  useEffect(() => {
    // 1. Calculate Gross Amount (sum of all items)
    const grossAmount = items.reduce((sum, item) => sum + (Number(item.amount) || 0), 0)

    // 2. Calculate Discount
    const discountAmount = grossAmount * ((Number(discountPercent) || 0) / 100)
    form.setValue("discount_amount", discountAmount)

    // 3. Calculate Taxable Amount
    const taxableAmount = grossAmount - discountAmount
    form.setValue("taxable_amount", taxableAmount)

    // 4. Calculate GST
    let gstAmount = 0
    if (applyGst) {
      gstAmount = taxableAmount * ((Number(gstPercent) || 0) / 100)
    }
    form.setValue("gst_amount", gstAmount)

    // 5. Calculate TDS/TCS
    let tdsAmount = 0
    if (tdsTcsType !== "NONE") {
      // Typically TDS is on taxable amount, TCS on taxable+GST. Assuming Taxable Amount here.
      tdsAmount = taxableAmount * ((Number(tdsTcsPercent) || 0) / 100)
    }
    form.setValue("tds_tcs_amount", tdsAmount)

    // 6. Calculate Grand Total
    let grandTotal = taxableAmount + gstAmount
    if (tdsTcsType === "TDS") {
      grandTotal -= tdsAmount // TDS is deducted
    } else if (tdsTcsType === "TCS") {
      grandTotal += tdsAmount // TCS is collected/added
    }
    form.setValue("grand_total", grandTotal)

  }, [items, discountPercent, applyGst, gstPercent, tdsTcsType, tdsTcsPercent, form])

  const onSubmit = async (data: SalesBillFormValues) => {
    // Make sure customer is selected
    if (!data.customer_id) {
      toast.error("Please select a customer")
      return
    }

    const res = await createSalesBill(data)
    if (res.error) {
      toast.error(res.error)
    } else {
      toast.success("Sales Bill created successfully")
      router.push("/sales-bills")
      router.refresh()
    }
  }

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
        
        {/* Header Section */}
        <Card>
          <CardContent className="pt-6">
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <FormField
                control={form.control}
                name="customer_id"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Customer *</FormLabel>
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

              <FormField
                control={form.control}
                name="bill_number"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Bill No. *</FormLabel>
                    <FormControl>
                      <Input placeholder="e.g. INV-001" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="bill_date"
                render={({ field }) => (
                  <FormItem className="flex flex-col">
                    <FormLabel>Bill Date *</FormLabel>
                    <Popover>
                      <PopoverTrigger asChild>
                        <FormControl>
                          <Button
                            variant={"outline"}
                            className={cn(
                              "w-full pl-3 text-left font-normal",
                              !field.value && "text-muted-foreground"
                            )}
                          >
                            {field.value ? format(field.value, "dd-MM-yyyy") : <span>Pick a date</span>}
                            <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />
                          </Button>
                        </FormControl>
                      </PopoverTrigger>
                      <PopoverContent className="w-auto p-0" align="start">
                        <Calendar
                          mode="single"
                          selected={field.value}
                          onSelect={field.onChange}
                          autoFocus
                        />
                      </PopoverContent>
                    </Popover>
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
                      <Input type="number" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
          </CardContent>
        </Card>

        {/* Line Items Section */}
        <Card>
          <CardContent className="pt-6 space-y-4">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b bg-muted/50 text-muted-foreground text-left">
                    <th className="p-2 w-[25%]">Product *</th>
                    <th className="p-2 w-[10%]">Item Code</th>
                    <th className="p-2 w-[10%]">HSN Code</th>
                    <th className="p-2 w-[10%]">Qty *</th>
                    <th className="p-2 w-[10%]">Unit *</th>
                    <th className="p-2 w-[15%]">Rate (₹) *</th>
                    <th className="p-2 w-[15%]">Amount (₹)</th>
                    <th className="p-2 w-[5%]"></th>
                  </tr>
                </thead>
                <tbody>
                  {fields.map((field, index) => (
                    <tr key={field.id} className="border-b">
                      <td className="p-2">
                        <Select onValueChange={(val) => handleProductSelect(index, val)}>
                          <SelectTrigger>
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
                        <Input {...form.register(`items.${index}.item_code`)} readOnly className="bg-muted" />
                      </td>
                      <td className="p-2">
                        <Input {...form.register(`items.${index}.hsn_code`)} />
                      </td>
                      <td className="p-2">
                        <Input 
                          type="number" 
                          step="0.01" 
                          {...form.register(`items.${index}.quantity`)} 
                          onChange={(e) => {
                            form.setValue(`items.${index}.quantity`, Number(e.target.value))
                            calculateItemAmount(index)
                          }}
                        />
                      </td>
                      <td className="p-2">
                        <Input {...form.register(`items.${index}.unit`)} />
                      </td>
                      <td className="p-2">
                        <Input 
                          type="number" 
                          step="0.01" 
                          {...form.register(`items.${index}.rate`)}
                          onChange={(e) => {
                            form.setValue(`items.${index}.rate`, Number(e.target.value))
                            calculateItemAmount(index)
                          }}
                        />
                      </td>
                      <td className="p-2">
                        <Input 
                          type="number" 
                          readOnly 
                          className="bg-muted"
                          {...form.register(`items.${index}.amount`)} 
                        />
                      </td>
                      <td className="p-2 text-center">
                        <Button 
                          type="button" 
                          variant="ghost" 
                          size="icon" 
                          className="text-destructive"
                          onClick={() => remove(index)}
                          disabled={fields.length === 1}
                        >
                          <Trash className="h-4 w-4" />
                        </Button>
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
              onClick={() => append({ product_id: 0, product_name: "", quantity: 1, rate: 0, amount: 0, unit: "Pcs" })}
            >
              <Plus className="mr-2 h-4 w-4" /> ADD PRODUCT
            </Button>
          </CardContent>
        </Card>

        {/* Footer / Calculations Section */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <Card>
            <CardContent className="pt-6 space-y-4">
              <Button type="button" variant="secondary" className="w-full justify-start text-blue-600 bg-blue-50">
                <Plus className="mr-2 h-4 w-4" /> Add Remarks
              </Button>
              <FormField
                control={form.control}
                name="remarks"
                render={({ field }) => (
                  <FormItem>
                    <FormControl>
                      <Textarea placeholder="Bill remarks or notes..." {...field} value={field.value || ""} />
                    </FormControl>
                  </FormItem>
                )}
              />
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6 space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="discount_percent"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Discount (%)</FormLabel>
                      <div className="flex gap-2">
                        <FormControl>
                          <Input type="number" step="0.01" {...field} />
                        </FormControl>
                        <Input 
                          readOnly 
                          value={form.watch("discount_amount").toFixed(2)} 
                          className="bg-muted w-24" 
                        />
                      </div>
                    </FormItem>
                  )}
                />

                <div className="space-y-3">
                  <div className="flex items-center space-x-2">
                    <Checkbox 
                      checked={applyGst} 
                      onCheckedChange={(c) => form.setValue("apply_gst", !!c)} 
                      id="applyGst" 
                    />
                    <label htmlFor="applyGst" className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                      Apply GST
                    </label>
                  </div>
                  {applyGst && (
                    <FormField
                      control={form.control}
                      name="gst_percent"
                      render={({ field }) => (
                        <FormItem>
                          <div className="flex gap-2">
                            <FormControl>
                              <Input type="number" step="0.01" {...field} />
                            </FormControl>
                            <Input 
                              readOnly 
                              value={form.watch("gst_amount").toFixed(2)} 
                              className="bg-muted w-24" 
                            />
                          </div>
                        </FormItem>
                      )}
                    />
                  )}
                </div>
              </div>

              <div className="space-y-2 text-sm border-t pt-4">
                <div className="flex justify-between text-muted-foreground">
                  <span>Net Amount:</span>
                  <span>₹ {form.watch("taxable_amount").toFixed(2)}</span>
                </div>
                <div className="flex justify-between text-muted-foreground">
                  <span>Discount Amount:</span>
                  <span>- ₹ {form.watch("discount_amount").toFixed(2)}</span>
                </div>
                <div className="flex justify-between font-medium">
                  <span>Taxable Amount:</span>
                  <span>₹ {form.watch("taxable_amount").toFixed(2)}</span>
                </div>
                {applyGst && (
                  <div className="flex justify-between text-muted-foreground">
                    <span>Tax Amount:</span>
                    <span>+ ₹ {form.watch("gst_amount").toFixed(2)}</span>
                  </div>
                )}
                
                <div className="flex items-center justify-between pt-2 border-t">
                  <div className="flex items-center gap-2">
                    <FormField
                      control={form.control}
                      name="tds_tcs_type"
                      render={({ field }) => (
                        <Select onValueChange={field.onChange} defaultValue={field.value}>
                          <SelectTrigger className="w-[100px] h-8 text-xs">
                            <SelectValue placeholder="TDS/TCS" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="NONE">None</SelectItem>
                            <SelectItem value="TDS">TDS (-)</SelectItem>
                            <SelectItem value="TCS">TCS (+)</SelectItem>
                          </SelectContent>
                        </Select>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name="tds_tcs_percent"
                      render={({ field }) => (
                        <Input type="number" step="0.01" className="w-16 h-8 text-xs" {...field} />
                      )}
                    />
                    <span>%:</span>
                  </div>
                  <span>
                    {tdsTcsType === "TDS" ? "-" : tdsTcsType === "TCS" ? "+" : ""} ₹ {form.watch("tds_tcs_amount").toFixed(2)}
                  </span>
                </div>

                <div className="flex justify-between font-bold text-lg pt-4 border-t">
                  <span>Total Amount:</span>
                  <span>₹ {form.watch("grand_total").toFixed(2)}</span>
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-4">
                <Button type="button" variant="outline" onClick={() => router.back()}>
                  CANCEL
                </Button>
                <Button type="submit" className="bg-blue-600 hover:bg-blue-700">
                  SUBMIT
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </form>
    </Form>
  )
}
