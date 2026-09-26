import * as z from "zod";

export const purchaseBillItemSchema = z.object({
  id: z.number().optional(),
  product_id: z.coerce.number().min(1, "Product is required"),
  product_name: z.string().min(1, "Product name is required"),
  item_code: z.string().optional().nullable(),
  hsn_code: z.string().optional().nullable(),
  quantity: z.coerce.number().min(0.01, "Quantity must be greater than 0"),
  unit: z.string().min(1, "Unit is required"),
  rate: z.coerce.number().min(0, "Rate must be positive"),
  discount_percent: z.coerce.number().min(0).max(100).default(0),
  amount: z.coerce.number().min(0, "Amount must be positive"),
  gst_percent: z.coerce.number().min(0).default(0),
  gst_amount: z.coerce.number().min(0).default(0),
});

export const purchaseBillSchema = z.object({
  id: z.number().optional(),
  supplier_id: z.coerce.number().min(1, "Supplier is required"),
  bill_number: z.string().min(1, "Bill number is required"),
  bill_date: z.date({
    error: "Bill date is required",
  }),
  due_days: z.coerce.number().min(0).default(0),
  due_date: z.date({
    error: "Due date is required",
  }),
  apply_gst: z.boolean().default(true),
  discount_percent: z.coerce.number().min(0).default(0),
  discount_amount: z.coerce.number().min(0).default(0),
  gst_percent: z.coerce.number().min(0).default(0),
  gst_amount: z.coerce.number().min(0).default(0),
  taxable_amount: z.coerce.number().min(0).default(0),
  amount: z.coerce.number().min(0).default(0),
  remarks: z.string().optional().nullable(),
  items: z.array(purchaseBillItemSchema).min(1, "At least one item is required"),
});

export type PurchaseBillItemFormValues = z.infer<typeof purchaseBillItemSchema>;
export type PurchaseBillFormValues = z.infer<typeof purchaseBillSchema>;
