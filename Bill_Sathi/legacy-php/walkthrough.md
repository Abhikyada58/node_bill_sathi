# Sales Bill TDS/TCS Calculations — Walkthrough

This document outlines the implementation, database changes, and dynamic interface updates completed to support **TDS / TCS Calculations** on the **Add Sales Bill** page.

---

## 🛠️ Features Implemented

### 1. Database Layer Updates (`database/schema.sql` & `setup.php`)
- Added three new columns to the `sales_bills` table structure to persist tax configuration:
  - `tds_tcs_type VARCHAR(10) DEFAULT 'NONE'` (can store 'NONE', 'TDS', or 'TCS')
  - `tds_tcs_percent DECIMAL(5, 2) DEFAULT 0.00`
  - `tds_tcs_amount DECIMAL(15, 2) DEFAULT 0.00`
- Integrated automated migration statements in `setup.php` to execute `ALTER TABLE` operations on database initialization, ensuring zero data loss on existing records.

### 2. Backend API Processing (`auth/sales_bills_crud.php`)
- Updated the `create` action to extract the new fields (`tds_tcs_type`, `tds_tcs_percent`, and `tds_tcs_amount`) from form POST parameters.
- Updated the main `INSERT INTO sales_bills` query to persist the configured tax type, percentage, and amount.
- Updated the `read` action query (`sb.*`) to automatically deliver these values to the frontend list/views.

### 3. Inline Calculation Summary UI (`sales_bills.php`)
- Placed the inline TDS/TCS select dropdown (NONE, TDS, TCS) and inputs (Percentage, Amount) directly in the Calculations panel under GST.
- Added a summary line item right above "Total Amount" to display the applied TDS/TCS:
  - Label: `TDS/TCS (0%):`
  - Value: `₹ 0.00`

### 4. Interactive Inline Calculation Engine (`assets/js/sales_bills.js`)
- **Inline Selection**: Changing the select dropdown to TDS or TCS shows the percentage and amount input boxes in real-time. Selecting NONE resets and hides them.
- **Bidirectional Input Synchronization**:
  - Typing in the *Tax Percentage* input automatically calculates and populates the *Tax Amount* input as `Taxable Amount * (Percentage / 100)`.
  - Typing in the *Tax Amount* input dynamically updates the *Tax Percentage* as `(Amount / Taxable Amount) * 100`.
- **Active typing protection**: The engine detects if the user is actively typing in the Amount input box, preventing the percentage recalculation from overwriting what they are typing.
- **Addition Logic**:
  - If **TDS** or **TCS** is selected, the tax amount is added to the grand total (and displays as green `₹ + amount` in the summary card and invoice preview).

---

## 🧪 Verification & Testing

### 1. Automated Database Verification Script
An automated verification script [`verify_tds_tcs.php`](file:///C:/Users/ABHI/.gemini/antigravity/brain/878f0a5d-84d2-4f89-af83-471ba375d64f/scratch/verify_tds_tcs.php) was run via CLI and succeeded:
```text
1. Checking columns exist in database...
✓ tds_tcs_type exists.
✓ tds_tcs_percent exists.
✓ tds_tcs_amount exists.

2. Testing mockup insertion with TDS enabled...
✓ Test bill inserted successfully.

3. Testing read values from database...
✓ Read successful:
  Type:    TDS (Expected: TDS)
  Percent: 1.00% (Expected: 1.00)
  Amount:  ₹10.00 (Expected: 10.00)
  Total:   ₹1170.00 (Expected: 1170.00)

✓ Cleanup successful. TDS/TCS Database logic verified!
```

### 2. Manual Verification Steps
1. Go to the Sales Bill page.
2. Select **ADD BILL**.
3. Add a product (e.g., Rate: 90000.00, Qty: 1, GST: 0% = ₹ 0.00).
4. In the calculations panel, find the **TDS/TCS** dropdown select box and choose **TCS**.
5. The inline input boxes for percentage and amount will slide in. Type `20` in the percentage field; the amount field will automatically calculate to `18000.00`.
6. Verify the summary card displays:
   - `TCS (20%): ₹ + 18000.00` (highlighted in green)
   - `Total Amount: ₹ 108000.00`
7. Click **SUBMIT** to save the invoice.
8. In the bills list, click the **Print Invoice** icon (second icon in actions list) on the newly created bill.
9. Verify the invoice preview modal displays **TCS (20.0%): + ₹ 18000.00** right above the grand total.
