import * as z from "zod";

export const settingsSchema = z.object({
  full_name: z.string().min(1, "Name is required"),
  email: z.string().email("Invalid email address"),
  shop_name: z.string().optional().nullable(),
  shop_mobile: z.string().optional().nullable(),
  shop_address: z.string().optional().nullable(),
  shop_state: z.string().optional().nullable(),
  shop_gstin: z.string().optional().nullable(),
  shop_pan: z.string().optional().nullable(),
  shop_msme_no: z.string().optional().nullable(),
  bank_name: z.string().optional().nullable(),
  bank_account_no: z.string().optional().nullable(),
  bank_account_type: z.string().optional().nullable(),
  bank_ifsc: z.string().optional().nullable(),
});

export type SettingsFormValues = z.infer<typeof settingsSchema>;
