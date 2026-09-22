"use client"

import { useState, useEffect } from "react"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { toast } from "sonner"
import { X } from "lucide-react"
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

import { partySchema, PartyFormValues } from "@/lib/validations/party"
import { createParty, updateParty } from "@/actions/parties"

interface PartyModalProps {
  isOpen: boolean
  onClose: () => void
  party?: any | null
}

export function PartyModal({ isOpen, onClose, party }: PartyModalProps) {
  const [isSubmitting, setIsSubmitting] = useState(false)
  const router = useRouter()

  const form = useForm<PartyFormValues>({
    resolver: zodResolver(partySchema),
    defaultValues: {
      gst_number: "",
      pan_number: "",
      name: "",
      owner_name: "",
      address: "",
      state: "",
      city: "",
      pincode: "",
      phone: "",
      discount: 0,
      due_days: 45,
      broker_name: "",
      broker_mobile: "",
    },
  })

  useEffect(() => {
    if (party) {
      form.reset({
        gst_number: party.gst_number || "",
        pan_number: party.pan_number || "",
        name: party.name || "",
        owner_name: party.owner_name || "",
        address: party.address || "",
        state: party.state || "",
        city: party.city || "",
        pincode: party.pincode || "",
        phone: party.phone || "",
        discount: party.discount || 0,
        due_days: party.due_days || 45,
        broker_name: party.broker_name || "",
        broker_mobile: party.broker_mobile || "",
      })
    } else {
      form.reset({
        gst_number: "",
        pan_number: "",
        name: "",
        owner_name: "",
        address: "",
        state: "",
        city: "",
        pincode: "",
        phone: "",
        discount: 0,
        due_days: 45,
        broker_name: "",
        broker_mobile: "",
      })
    }
  }, [party, form, isOpen])

  async function onSubmit(data: PartyFormValues) {
    setIsSubmitting(true)
    let res;
    if (party?.id) {
      res = await updateParty(party.id, data)
    } else {
      res = await createParty(data)
    }
    
    setIsSubmitting(false)
    
    if (res.error) {
      toast.error(res.error)
    } else {
      toast.success(party ? "Party updated successfully" : "Party created successfully")
      onClose()
      router.refresh()
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-4xl p-0 overflow-hidden rounded-xl">
        <DialogHeader className="px-6 py-4 border-b relative">
          <DialogTitle className="text-xl font-medium">
            {party ? "Edit Party" : "Add Party"}
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
            
            <div className="grid grid-cols-2 gap-6">
              <FormField
                control={form.control}
                name="gst_number"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>GST No.</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter GST number" {...field} value={field.value || ""} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="pan_number"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>PAN Card</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter PAN Card Number" {...field} value={field.value || ""} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <div className="grid grid-cols-2 gap-6">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Party Name <span className="text-red-500">*</span></FormLabel>
                    <FormControl>
                      <Input placeholder="Enter Party Name" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="owner_name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Owner Name</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter party owner's name" {...field} value={field.value || ""} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <FormField
              control={form.control}
              name="address"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Address <span className="text-red-500">*</span></FormLabel>
                  <FormControl>
                    <Textarea placeholder="Enter full address" className="resize-none h-16" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <div className="grid grid-cols-3 gap-6">
              <FormField
                control={form.control}
                name="state"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>State <span className="text-red-500">*</span></FormLabel>
                    <Select onValueChange={field.onChange} defaultValue={field.value || ""}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select State" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="Gujarat">Gujarat</SelectItem>
                        <SelectItem value="Maharashtra">Maharashtra</SelectItem>
                        <SelectItem value="Rajasthan">Rajasthan</SelectItem>
                        <SelectItem value="Delhi">Delhi</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="city"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>City</FormLabel>
                    <Select onValueChange={field.onChange} defaultValue={field.value || ""}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select City" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="Surat">Surat</SelectItem>
                        <SelectItem value="Ahmedabad">Ahmedabad</SelectItem>
                        <SelectItem value="Mumbai">Mumbai</SelectItem>
                        <SelectItem value="Jaipur">Jaipur</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="pincode"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Pincode</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter Pincode" {...field} value={field.value || ""} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <div className="grid grid-cols-3 gap-6">
              <FormField
                control={form.control}
                name="phone"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Contact Number</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter mobile number" {...field} value={field.value || ""} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="discount"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Discount</FormLabel>
                    <FormControl>
                      <Input type="number" step="0.01" placeholder="Enter Discount" {...field} />
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
                      <Input type="number" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <div className="grid grid-cols-2 gap-6 pb-4">
              <FormField
                control={form.control}
                name="broker_name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Broker Name</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter Broker Name" {...field} value={field.value || ""} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="broker_mobile"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Broker Mobile No</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter mobile number" {...field} value={field.value || ""} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <Button type="submit" disabled={isSubmitting} className="w-full bg-blue-400 hover:bg-blue-500 text-white font-medium text-base py-6">
              {isSubmitting ? "SAVING..." : "SAVE"}
            </Button>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  )
}
