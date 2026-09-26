import * as z from "zod";

export const salesBillItemSchema = z.object({
  id: z.number().optional(),
  product_id: z.coerce.number().min(1, "Product is required"),
  product_name: z.string().min(1, "Product name is required"),
  item_code: z.string().optional().nullable(),
  hsn_code: z.string().optional().nullable(),
  quantity: z.coerce.number().min(0.01, "Quantity must be greater than 0"),
  unit: z.string().min(1, "Unit is required"),
  rate: z.coerce.number().min(0, "Rate must be positive"),
  amount: z.coerce.number().min(0, "Amount must be positive"),
});

export const salesBillSchema = z.object({
  id: z.number().optional(),
  customer_id: z.coerce.number().min(1, "Customer is required"),
  bill_number: z.string().min(1, "Bill number is required"),
  bill_date: z.date({
    error: "Bill date is required",
  }),
  due_days: z.coerce.number().min(0),
  due_date: z.date({
    error: "Due date is required",
  }),
  challan_no: z.string().optional().nullable(),
  challan_date: z.date().optional().nullable(),
  apply_gst: z.boolean().default(true),
  discount_percent: z.coerce.number().min(0).default(0),
  discount_amount: z.coerce.number().min(0).default(0),
  gst_percent: z.coerce.number().min(0).default(0),
  gst_amount: z.coerce.number().min(0).default(0),
  taxable_amount: z.coerce.number().min(0).default(0),
  grand_total: z.coerce.number().min(0).default(0),
  remarks: z.string().optional().nullable(),
  tds_tcs_type: z.enum(["NONE", "TDS", "TCS"]).default("NONE"),
  tds_tcs_percent: z.coerce.number().min(0).default(0),
  tds_tcs_amount: z.coerce.number().min(0).default(0),
  items: z.array(salesBillItemSchema).min(1, "At least one item is required"),
});

export type SalesBillItemFormValues = z.infer<typeof salesBillItemSchema>;
export type SalesBillFormValues = z.infer<typeof salesBillSchema>;

export const paymentSchema = z.object({
  bill_id: z.coerce.number(),
  payment_date: z.date({
    error: "Payment date is required",
  }),
  transaction_amount: z.coerce.number().min(0.01, "Amount must be greater than 0"),
  payment_mode: z.enum(["Cash", "Bank Transfer", "UPI", "Cheque"]),
  reference_number: z.string().optional().nullable(),
  settlement_amount: z.coerce.number().min(0).default(0),
  tds_amount: z.coerce.number().min(0).default(0),
  notes: z.string().optional().nullable(),
});

export type PaymentFormValues = z.infer<typeof paymentSchema>;
