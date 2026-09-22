import * as z from "zod";

export const partySchema = z.object({
  id: z.number().optional(),
  gst_number: z.string().optional().nullable(),
  pan_number: z.string().optional().nullable(),
  name: z.string().min(1, "Party name is required"),
  owner_name: z.string().optional().nullable(),
  address: z.string().min(1, "Address is required"),
  state: z.string().min(1, "State is required"),
  city: z.string().optional().nullable(),
  pincode: z.string().optional().nullable(),
  phone: z.string().optional().nullable(),
  discount: z.coerce.number().min(0).max(100).default(0),
  due_days: z.coerce.number().min(0).default(45),
  broker_name: z.string().optional().nullable(),
  broker_mobile: z.string().optional().nullable(),
});

export type PartyFormValues = z.infer<typeof partySchema>;
