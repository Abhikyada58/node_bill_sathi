"use client"

import { useEffect, useState } from "react"
import { format } from "date-fns"
import { numberToWords } from "@/lib/number-to-words"

interface PurchasePrintTemplateProps {
  bill: any
  profile: any
}

export function PurchasePrintTemplate({ bill, profile }: PurchasePrintTemplateProps) {
  const [isDownload, setIsDownload] = useState(false)

  useEffect(() => {
    const searchParams = new URLSearchParams(window.location.search)
    const downloadParam = searchParams.get("download") === "true"
    setIsDownload(downloadParam)

    if (downloadParam) {
      const timer = setTimeout(() => {
        handleDownload()
      }, 500)
      return () => clearTimeout(timer)
    } else {
      const timer = setTimeout(() => {
        window.print()
      }, 500)
      return () => clearTimeout(timer)
    }
  }, [])

  const handleDownload = async () => {
    const element = document.getElementById("invoice-capture")
    if (!element) return

    try {
      const { toPng } = await import("html-to-image")
      const { jsPDF } = await import("jspdf")

      // Generate image natively via browser (bypasses html2canvas css parsing errors)
      const dataUrl = await toPng(element, { quality: 0.98, pixelRatio: 2 })
      
      const pdf = new jsPDF({
        orientation: "portrait",
        unit: "pt",
        format: "a4"
      })

      // Calculate dimensions to fit A4 width
      const pdfWidth = pdf.internal.pageSize.getWidth()
      const pdfHeight = (element.offsetHeight * pdfWidth) / element.offsetWidth

      // Add a tiny margin (e.g., 20 points)
      const margin = 20;
      const printWidth = pdfWidth - (margin * 2);
      const printHeight = (element.offsetHeight * printWidth) / element.offsetWidth;

      pdf.addImage(dataUrl, "PNG", margin, margin, printWidth, printHeight)
      pdf.save(`Purchase_Invoice_${bill.bill_number}.pdf`)
    } catch (err) {
      console.error("PDF generation failed", err)
      alert("Failed to download PDF. Please use the Print option instead.")
    }
  }

  const party = bill.parties
  const items = bill.purchase_bill_items || []
  
  // Overall bill tax
  const gstPercent = Number(bill.gst_percent) || 0
  const cgstPercent = gstPercent / 2
  const sgstPercent = gstPercent / 2
  const totalGstAmount = Number(bill.gst_amount) || 0
  
  const discountAmount = Number(bill.discount_amount) || 0
  const taxableAmount = Number(bill.taxable_amount) || 0
  const netAmount = Number(bill.amount) || 0
  const roundAmount = Math.round(netAmount)
  const amountInWords = numberToWords(roundAmount)
  
  const totalQty = items.reduce((acc: number, i: any) => acc + Number(i.quantity), 0)

  return (
    <div className="bg-white text-black text-[11px] p-8 print:p-0 w-full max-w-4xl mx-auto font-sans">
      
      {/* Title */}
      <div className="text-center font-bold text-sm py-2">
        PURCHASE BILL
      </div>

      <div id="invoice-capture" className="border-2 border-black">
        
        {/* HEADER SECTION */}
        <div className="flex border-b-2 border-black">
          {/* Shop Info (Left) */}
          <div className="w-1/2 p-2 border-r-2 border-black leading-tight">
            <h2 className="font-bold text-sm mb-1 uppercase">{profile.shop_name || "YOUR SHOP NAME"}</h2>
            <p><span className="font-semibold">Address:</span> {profile.shop_address || "-"}</p>
            <p><span className="font-semibold">State:</span> {profile.shop_state || "-"}</p>
            <p><span className="font-semibold">Mobile:</span> {profile.shop_mobile || "-"}</p>
            <p><span className="font-semibold">GSTIN:</span> {profile.shop_gstin || "-"}</p>
            <p><span className="font-semibold">PAN:</span> {profile.shop_pan || "-"}</p>
            <p><span className="font-semibold">MSME NO:</span> {profile.shop_msme_no || "-"}</p>
          </div>
          
          {/* Bill & Supplier Info (Right) */}
          <div className="w-1/2 flex flex-col">
            <div className="flex border-b-2 border-black">
              <div className="w-1/2 p-2 border-r-2 border-black font-semibold">
                Bill No.: {bill.bill_number}
              </div>
              <div className="w-1/2 p-2 font-semibold">
                Bill Date: {format(new Date(bill.bill_date), "dd/MM/yyyy")}
              </div>
            </div>
            <div className="p-2 leading-tight">
              <h3 className="font-bold mb-1 uppercase">Bill From: {party?.name}</h3>
              <p><span className="font-semibold">GSTIN:</span> {party?.gst_number || "-"}</p>
              <p><span className="font-semibold">PAN:</span> {party?.pan_number || "-"}</p>
              <p><span className="font-semibold">Address:</span> {party?.address}, {party?.city}, {party?.state}</p>
              <p><span className="font-semibold">State:</span> {party?.state || "-"}</p>
            </div>
          </div>
        </div>

        {/* TABLE SECTION */}
        <div className="w-full">
          <table className="w-full text-center border-collapse">
            <thead>
              <tr className="border-b-2 border-black font-semibold">
                <th className="p-1 border-r-2 border-black">Sr no.</th>
                <th className="p-1 border-r-2 border-black text-left">Product/Service</th>
                <th className="p-1 border-r-2 border-black">HSN/SAC</th>
                <th className="p-1 border-r-2 border-black">Qty</th>
                <th className="p-1 border-r-2 border-black">Rate</th>
                <th className="p-1 border-r-2 border-black">Discount</th>
                <th className="p-1 border-r-2 border-black">GST%</th>
                <th className="p-1 border-r-2 border-black">CGST</th>
                <th className="p-1 border-r-2 border-black">SGST</th>
                <th className="p-1">Total (₹)</th>
              </tr>
            </thead>
            <tbody>
              {/* Ensure table is tall enough like the screenshot */}
              <tr className="h-96 align-top">
                <td className="p-1 border-r-2 border-black">
                  {items.map((_: any, i: number) => <div key={i} className="mb-1">{i + 1}</div>)}
                </td>
                <td className="p-1 border-r-2 border-black text-left font-semibold uppercase">
                  {items.map((item: any, i: number) => <div key={i} className="mb-1">{item.product_name}</div>)}
                </td>
                <td className="p-1 border-r-2 border-black">
                  {items.map((item: any, i: number) => <div key={i} className="mb-1">{item.hsn_code || "-"}</div>)}
                </td>
                <td className="p-1 border-r-2 border-black">
                  {items.map((item: any, i: number) => <div key={i} className="mb-1">{Number(item.quantity).toFixed(2)} <span className="text-[9px] font-normal">{item.unit || "Pcs"}</span></div>)}
                </td>
                <td className="p-1 border-r-2 border-black">
                  {items.map((item: any, i: number) => <div key={i} className="mb-1">{Number(item.rate).toFixed(2)}</div>)}
                </td>
                <td className="p-1 border-r-2 border-black">
                  {items.map((_: any, i: number) => <div key={i} className="mb-1">0.00 (0%)</div>)}
                </td>
                <td className="p-1 border-r-2 border-black">
                  {items.map((_: any, i: number) => <div key={i} className="mb-1">{gstPercent}</div>)}
                </td>
                <td className="p-1 border-r-2 border-black">
                  {items.map((item: any, i: number) => {
                    const itemTax = (Number(item.amount) * gstPercent) / 100;
                    return <div key={i} className="mb-1">{(itemTax / 2).toFixed(2)}</div>
                  })}
                </td>
                <td className="p-1 border-r-2 border-black">
                  {items.map((item: any, i: number) => {
                    const itemTax = (Number(item.amount) * gstPercent) / 100;
                    return <div key={i} className="mb-1">{(itemTax / 2).toFixed(2)}</div>
                  })}
                </td>
                <td className="p-1 font-semibold">
                  {items.map((item: any, i: number) => {
                    const itemTax = (Number(item.amount) * gstPercent) / 100;
                    const itemTotal = Number(item.amount) + itemTax;
                    return <div key={i} className="mb-1">{itemTotal.toFixed(2)}</div>
                  })}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr className="border-t-2 border-b-2 border-black font-bold bg-gray-50">
                <td className="p-1 border-r-2 border-black text-left pl-2" colSpan={2}>Sub Total</td>
                <td className="border-r-2 border-black"></td>
                <td className="p-1 border-r-2 border-black">{totalQty.toFixed(2)}</td>
                <td className="border-r-2 border-black"></td>
                <td className="border-r-2 border-black"></td>
                <td className="border-r-2 border-black"></td>
                <td className="border-r-2 border-black"></td>
                <td className="border-r-2 border-black"></td>
                <td className="p-1">{netAmount.toFixed(2)}</td>
              </tr>
            </tfoot>
          </table>
        </div>

        {/* FOOTER CALCULATIONS */}
        <div className="flex border-b-2 border-black">
          <div className="w-1/2 flex-1 p-2 border-r-2 border-black flex flex-col justify-end text-[10px]">
             {/* Left side empty or notes if needed */}
          </div>
          <div className="w-1/2 font-bold">
            <div className="flex border-b border-black">
              <div className="w-2/3 p-1 text-right border-r-2 border-black">Taxable Amount</div>
              <div className="w-1/3 p-1 text-right">{taxableAmount.toFixed(2)}</div>
            </div>
            <div className="flex border-b border-black">
              <div className="w-2/3 p-1 text-right border-r-2 border-black">CGST</div>
              <div className="w-1/3 p-1 text-right">{(totalGstAmount / 2).toFixed(2)}</div>
            </div>
            <div className="flex border-b border-black">
              <div className="w-2/3 p-1 text-right border-r-2 border-black">SGST</div>
              <div className="w-1/3 p-1 text-right">{(totalGstAmount / 2).toFixed(2)}</div>
            </div>
            <div className="flex border-b border-black">
              <div className="w-2/3 p-1 text-right border-r-2 border-black">Total TAX</div>
              <div className="w-1/3 p-1 text-right">{totalGstAmount.toFixed(2)}</div>
            </div>
            <div className="flex border-b border-black">
              <div className="w-2/3 p-1 text-right border-r-2 border-black">Net Amount</div>
              <div className="w-1/3 p-1 text-right">{netAmount.toFixed(2)}</div>
            </div>
            <div className="flex">
              <div className="w-2/3 p-1 text-right border-r-2 border-black">Round Amount</div>
              <div className="w-1/3 p-1 text-right">{roundAmount.toFixed(2)}</div>
            </div>
          </div>
        </div>
        
        {/* WORDS */}
        <div className="p-2">
          <p className="text-gray-600 mb-1">Amount chargeable (in words)</p>
          <p className="font-bold capitalize">{amountInWords.toLowerCase()}</p>
        </div>

      </div>
      
      {/* Hide print buttons when actually printing */}
      <div className="mt-8 flex justify-center gap-4 print:hidden">
        <button 
          onClick={handleDownload}
          className="bg-green-600 text-white px-6 py-2 rounded font-medium hover:bg-green-700 transition"
        >
          Download PDF
        </button>
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
