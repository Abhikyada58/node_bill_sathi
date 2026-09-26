"use client"

import { useState } from "react"
import { useForm } from "react-hook-form"
import { zodResolver } from "@hookform/resolvers/zod"
import { toast } from "sonner"
import { Users } from "lucide-react"

import { Button } from "@/components/ui/button"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"

import { settingsSchema, SettingsFormValues } from "@/lib/validations/settings"
import { updateProfileSettings } from "@/actions/settings"

interface SettingsFormProps {
  initialData: any
}

export function SettingsForm({ initialData }: SettingsFormProps) {
  const [isSubmitting, setIsSubmitting] = useState(false)

  const form = useForm<SettingsFormValues>({
    resolver: zodResolver(settingsSchema),
    defaultValues: {
      full_name: initialData.full_name || "",
      email: initialData.email || "",
      shop_name: initialData.shop_name || "",
      shop_mobile: initialData.shop_mobile || "",
      shop_address: initialData.shop_address || "",
      shop_state: initialData.shop_state || "",
      shop_gstin: initialData.shop_gstin || "",
      shop_pan: initialData.shop_pan || "",
      shop_msme_no: initialData.shop_msme_no || "",
      bank_name: initialData.bank_name || "",
      bank_account_no: initialData.bank_account_no || "",
      bank_account_type: initialData.bank_account_type || "",
      bank_ifsc: initialData.bank_ifsc || "",
    },
  })

  async function onSubmit(data: SettingsFormValues) {
    setIsSubmitting(true)
    const res = await updateProfileSettings(data)
    setIsSubmitting(false)
    
    if (res.error) {
      toast.error(res.error)
    } else {
      toast.success("Profile updated successfully")
    }
  }

  return (
    <div className="max-w-3xl bg-slate-50 min-h-screen">
      <div className="flex items-center space-x-2 mb-8">
        <Users className="h-6 w-6 text-slate-700" />
        <h2 className="text-2xl font-semibold text-slate-800">System Settings</h2>
      </div>

      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-8 pb-10">
          
          {/* Administrator Settings */}
          <div className="space-y-4">
            <FormField
              control={form.control}
              name="full_name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold">Administrator Account Name</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            
            <FormField
              control={form.control}
              name="email"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold">Email Address</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl text-slate-500" readOnly {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>

          {/* Shop Details */}
          <div className="space-y-4 pt-4">
            <h3 className="text-lg font-bold text-slate-800">Shop Details</h3>
            
            <FormField
              control={form.control}
              name="shop_name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold text-sm">Shop Name</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl" {...field} value={field.value || ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="shop_mobile"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold text-sm">Shop Mobile</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl" {...field} value={field.value || ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="shop_address"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold text-sm">Shop Address</FormLabel>
                  <FormControl>
                    <Textarea className="bg-white max-w-xl resize-none h-20" {...field} value={field.value || ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="shop_state"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold text-sm">Shop State</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl" {...field} value={field.value || ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="shop_gstin"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold text-sm">Shop GSTIN</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl" {...field} value={field.value || ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="shop_pan"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold text-sm">Shop PAN</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl" {...field} value={field.value || ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="shop_msme_no"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold text-sm">Shop MSME No</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl" {...field} value={field.value || ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>

          {/* Bank Details */}
          <div className="space-y-4 pt-4">
            <h3 className="text-lg font-bold text-slate-800">Bank Details</h3>
            
            <FormField
              control={form.control}
              name="bank_name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold text-sm">Bank Name</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl" {...field} value={field.value || ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="bank_account_no"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold text-sm">Bank A/c No</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl" {...field} value={field.value || ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="bank_account_type"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold text-sm">Account Type</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl" {...field} value={field.value || ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="bank_ifsc"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-600 font-semibold text-sm">Bank IFSC</FormLabel>
                  <FormControl>
                    <Input className="bg-white max-w-xl" {...field} value={field.value || ""} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>

          <div className="pt-6">
            <Button type="submit" disabled={isSubmitting} className="bg-blue-600 hover:bg-blue-700 px-8 py-2">
              {isSubmitting ? "Saving..." : "Save Profile"}
            </Button>
          </div>
        </form>
      </Form>
    </div>
  )
}
