import * as z from "zod";

export const productSchema = z.object({
  id: z.number().optional(),
  name: z.string().min(1, "Product name is required"),
  price: z.coerce.number().min(0).default(0), // Maps to Rate
  unit: z.string().min(1, "Unit is required").default("Pcs"),
  gst_percent: z.coerce.number().min(0).default(0),
  item_code: z.string().optional().nullable(),
  hsn_code: z.string().optional().nullable(),
  description: z.string().max(250).optional().nullable(),
});

export type ProductFormValues = z.infer<typeof productSchema>;
