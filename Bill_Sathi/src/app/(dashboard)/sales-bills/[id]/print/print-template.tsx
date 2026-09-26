"use client"

import { useEffect } from "react"
import { format } from "date-fns"
import { numberToWords } from "@/lib/number-to-words"

interface PrintTemplateProps {
  bill: any
  profile: any
}

export function PrintTemplate({ bill, profile }: PrintTemplateProps) {
  
  // Auto print on load (optional but helpful)
  useEffect(() => {
    // We delay slightly to allow rendering and styles to apply
    const timer = setTimeout(() => {
      window.print()
    }, 500)
    return () => clearTimeout(timer)
  }, [])

  const party = bill.parties
  const items = bill.sales_bill_items || []
  
  // Calculations
  const totalQty = items.reduce((acc: number, i: any) => acc + Number(i.quantity), 0)
  
  const discountAmount = Number(bill.discount_amount) || 0
  const taxableAmount = Number(bill.taxable_amount) || 0
  
  // Assume local split for CGST/SGST if GST is applied
  const gstPercent = Number(bill.gst_percent) || 0
  const totalGstAmount = Number(bill.gst_amount) || 0
  
  const cgstAmount = totalGstAmount / 2
  const sgstAmount = totalGstAmount / 2
  const cgstPercent = gstPercent / 2
  const sgstPercent = gstPercent / 2

  const netAmount = Number(bill.amount) || 0
  const roundAmount = Math.round(netAmount)
  const amountInWords = numberToWords(roundAmount)

  return (
    <div className="bg-white text-black text-sm p-8 print:p-0 w-full max-w-4xl mx-auto font-sans">
      
      {/* Container with main outer border */}
      <div className="border border-black">
        
        {/* HEADER SECTION */}
        <div className="text-center py-6 bg-gray-100 border-b border-black">
          <h1 className="text-2xl font-bold uppercase tracking-wider">
            {profile.shop_name || "YOUR SHOP NAME"}
          </h1>
        </div>

        {/* SHOP DETAILS */}
        <div className="flex border-b border-black">
          <div className="w-2/3 p-2 border-r border-black">
            <p className="text-xs">{profile.shop_address || "Shop Address"}</p>
            <p className="text-xs font-semibold mt-1">State :- {profile.shop_state || "STATE"}</p>
          </div>
          <div className="w-1/3 p-2 text-xs flex flex-col justify-center space-y-1">
            <div className="flex justify-between">
              <span className="font-semibold">Mobile No :-</span>
              <span>{profile.shop_mobile || "-"}</span>
            </div>
            <div className="flex justify-between">
              <span className="font-semibold">GSTIN :-</span>
              <span>{profile.shop_gstin || "-"}</span>
            </div>
            <div className="flex justify-between">
              <span className="font-semibold">PAN :-</span>
              <span>{profile.shop_pan || "-"}</span>
            </div>
            <div className="flex justify-between">
              <span className="font-semibold">MSME No :-</span>
              <span>{profile.shop_msme_no || "-"}</span>
            </div>
          </div>
        </div>

        {/* TAX INVOICE TITLE */}
        <div className="text-center font-bold text-sm py-1 border-b border-black uppercase tracking-widest">
          TAX INVOICE
        </div>

        {/* CUSTOMER & BILL DETAILS */}
        <div className="flex border-b border-black">
          <div className="w-2/3 p-2 border-r border-black">
            <h2 className="font-bold text-sm mb-2 uppercase">BILLED TO : {party?.name}</h2>
            <p className="text-xs mb-2">
              <span className="font-semibold">Address : </span>
              {party?.address}, {party?.city}, {party?.state}
            </p>
            <div className="flex justify-between text-xs font-semibold pr-8">
              <p>GSTIN : {party?.gst_number || "-"}</p>
              <p>PAN :- {party?.pan_number || "-"}</p>
            </div>
          </div>
          
          <div className="w-1/3 p-2 flex flex-col justify-center space-y-2 text-xs font-semibold">
            <div className="flex">
              <span className="w-20">Bill No.:</span>
              <span>{bill.bill_number}</span>
            </div>
            <div className="flex">
              <span className="w-20">Bill Date:</span>
              <span>{format(new Date(bill.bill_date), "dd/MM/yyyy")}</span>
            </div>
            <div className="flex">
              <span className="w-20">Challan:</span>
              <span>-</span>
            </div>
          </div>
        </div>

        {/* TABLE SECTION */}
        <div className="w-full">
          <table className="w-full text-xs text-left border-collapse">
            <thead>
              <tr className="border-b border-black font-semibold">
                <th className="p-1 border-r border-black w-8 text-center">Sr.</th>
                <th className="p-1 border-r border-black">Item Name</th>
                <th className="p-1 border-r border-black w-16 text-center">HSN</th>
                <th className="p-1 border-r border-black w-20 text-center">Qty</th>
                <th className="p-1 border-r border-black w-20 text-center">Rate</th>
                <th className="p-1 w-24 text-center">Amount</th>
              </tr>
            </thead>
            <tbody>
              {/* Force a minimum height for the table body to match the layout */}
              <tr className="h-64 align-top">
                <td className="p-1 border-r border-black text-center">
                  {items.map((_: any, i: number) => <div key={i} className="mb-1">{i + 1}</div>)}
                </td>
                <td className="p-1 border-r border-black">
                  {items.map((item: any, i: number) => <div key={i} className="mb-1">{item.product_name}</div>)}
                </td>
                <td className="p-1 border-r border-black text-center">
                  {items.map((item: any, i: number) => <div key={i} className="mb-1">{item.hsn_code || "-"}</div>)}
                </td>
                <td className="p-1 border-r border-black text-right pr-2">
                  {items.map((item: any, i: number) => <div key={i} className="mb-1">{Number(item.quantity).toFixed(2)} {item.unit || "Pcs"}</div>)}
                </td>
                <td className="p-1 border-r border-black text-right pr-2">
                  {items.map((item: any, i: number) => <div key={i} className="mb-1">{Number(item.rate).toFixed(2)}</div>)}
                </td>
                <td className="p-1 text-right pr-2">
                  {items.map((item: any, i: number) => <div key={i} className="mb-1">{Number(item.amount).toFixed(2)}</div>)}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr className="border-t border-b border-black font-bold">
                <td colSpan={3} className="p-1 text-right border-r border-black">Total</td>
                <td className="p-1 text-right border-r border-black pr-2">{totalQty.toFixed(2)}</td>
                <td className="p-1 border-r border-black"></td>
                <td className="p-1 text-right pr-2">{(taxableAmount + discountAmount).toFixed(2)}</td>
              </tr>
            </tfoot>
          </table>
        </div>

        {/* BOTTOM SECTION */}
        <div className="flex border-b border-black text-xs">
          {/* Bottom Left: Words, Terms */}
          <div className="w-2/3 border-r border-black flex flex-col">
            <div className="p-2 border-b border-black h-12">
              <span className="font-bold">Amount in Words : </span>
              <i className="uppercase">{amountInWords}</i>
            </div>
            <div className="p-2 border-b border-black flex-1 text-[10px] text-gray-700">
              <p className="font-bold text-black mb-1">Terms :</p>
              <p>1. Subject to '{profile.shop_state || "Local"}' Jurisdiction only. 2. (PAYMENT DUE TO {bill.due_days || 45} DAYS)</p>
              <p>(MSME-{profile.shop_msme_no || "-"})</p>
            </div>
            
            {/* Bank Details embedded at bottom left */}
            <div>
              <div className="text-center font-bold border-b border-black p-1 bg-gray-50">
                Bank Details
              </div>
              <div className="flex">
                <div className="w-1/2 border-r border-black">
                  <div className="p-1 border-b border-black flex"><span className="font-bold w-12 text-[10px]">Bank:</span> <span className="text-[10px]">{profile.bank_name || "-"}</span></div>
                  <div className="p-1 flex"><span className="font-bold w-12 text-[10px]">Type:</span> <span className="text-[10px]">{profile.bank_account_type || "-"}</span></div>
                </div>
                <div className="w-1/2">
                  <div className="p-1 border-b border-black flex"><span className="font-bold w-14 text-[10px]">A/c No:</span> <span className="text-[10px]">{profile.bank_account_no || "-"}</span></div>
                  <div className="p-1 flex"><span className="font-bold w-14 text-[10px]">IFSC:</span> <span className="text-[10px]">{profile.bank_ifsc || "-"}</span></div>
                </div>
              </div>
            </div>
          </div>

          {/* Bottom Right: Tax Calcs */}
          <div className="w-1/3 flex flex-col font-semibold">
            <div className="flex border-b border-black">
              <div className="w-1/2 p-1 text-right border-r border-black font-normal">Discount ({bill.discount_percent || 0}%)</div>
              <div className="w-1/2 p-1 text-right">{discountAmount.toFixed(2)}</div>
            </div>
            <div className="flex border-b border-black">
              <div className="w-1/2 p-1 text-right border-r border-black font-normal">Taxable Amount</div>
              <div className="w-1/2 p-1 text-right">{taxableAmount.toFixed(2)}</div>
            </div>
            <div className="flex border-b border-black">
              <div className="w-1/2 p-1 text-right border-r border-black font-normal">CGST ({cgstPercent}%)</div>
              <div className="w-1/2 p-1 text-right">{cgstAmount.toFixed(2)}</div>
            </div>
            <div className="flex border-b border-black">
              <div className="w-1/2 p-1 text-right border-r border-black font-normal">SGST ({sgstPercent}%)</div>
              <div className="w-1/2 p-1 text-right">{sgstAmount.toFixed(2)}</div>
            </div>
            <div className="flex border-b border-black">
              <div className="w-1/2 p-1 text-right border-r border-black font-normal">Total Tax</div>
              <div className="w-1/2 p-1 text-right">{totalGstAmount.toFixed(2)}</div>
            </div>
            {/* Placeholder for TDS if any */}
            <div className="flex border-b border-black">
              <div className="w-1/2 p-1 text-right border-r border-black font-normal">TDS (0%)</div>
              <div className="w-1/2 p-1 text-right">0.00</div>
            </div>
            <div className="flex border-b border-black font-bold">
              <div className="w-1/2 p-1 text-right border-r border-black">Net Amount</div>
              <div className="w-1/2 p-1 text-right">{netAmount.toFixed(2)}</div>
            </div>
            <div className="flex border-b border-black font-bold">
              <div className="w-1/2 p-1 text-right border-r border-black">Round Amount</div>
              <div className="w-1/2 p-1 text-right">{roundAmount.toFixed(2)}</div>
            </div>
            
            {/* Signature Box */}
            <div className="flex-1 p-2 flex flex-col justify-end min-h-[60px]">
              <div className="flex justify-between items-end">
                <span className="font-bold">Signature :</span>
                <span className="border-b border-dotted border-gray-400 w-24"></span>
              </div>
            </div>
          </div>
        </div>

      </div>
      
      {/* Hide print buttons when actually printing */}
      <div className="mt-8 flex justify-center gap-4 print:hidden">
        <button 
          onClick={() => window.print()}
          className="bg-blue-600 text-white px-6 py-2 rounded font-medium hover:bg-blue-700 transition"
        >
          Print Invoice
        </button>
        <button 
          onClick={() => window.history.back()}
          className="border border-gray-300 px-6 py-2 rounded font-medium hover:bg-gray-50 transition"
        >
          Go Back
        </button>
      </div>

    </div>
  )
}
