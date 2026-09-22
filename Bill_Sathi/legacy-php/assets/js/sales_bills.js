/**
 * Finance ERP - Sales Bills Module Controller
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- Global State ---
    let activeView = 'list'; // 'list' or 'form'
    let currentPage = 1;
    let productsList = []; // Caches product catalogs
    let activeTypeTab = 'All'; // 'All', 'Tax Invoice', 'Job Challan'

    // --- Switch Screen Panels ---
    window.toggleView = function(view) {
        activeView = view;
        const listPanel = document.getElementById('sales-bill-list-panel');
        const formPanel = document.getElementById('sales-bill-form-panel');
        const titleRow = document.getElementById('view-title-row');

        if (view === 'list') {
            listPanel.classList.add('active');
            formPanel.classList.remove('active');
            titleRow.style.display = 'flex';
            loadSalesBills();
        } else {
            listPanel.classList.remove('active');
            formPanel.classList.add('active');
            titleRow.style.display = 'none';
            resetInvoiceForm();
        }
    };

    // Sidebar Mobile Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // --- Segmented Top Tabs Listeners ---
    const tabAll = document.getElementById('tab-all-bills');
    const tabTax = document.getElementById('tab-tax-invoices');
    const tabJob = document.getElementById('tab-job-challans');

    function selectTypeTab(tabName, element) {
        activeTypeTab = tabName;
        [tabAll, tabTax, tabJob].forEach(btn => btn.classList.remove('active'));
        element.classList.add('active');
        currentPage = 1;
        loadSalesBills();
    }

    if (tabAll) tabAll.addEventListener('click', () => selectTypeTab('All', tabAll));
    if (tabTax) tabTax.addEventListener('click', () => selectTypeTab('Tax Invoice', tabTax));
    if (tabJob) tabJob.addEventListener('click', () => selectTypeTab('Job Challan', tabJob));

    // --- Collapsible Filter Panel Toggle ---
    window.toggleFilterPanel = function() {
        const drawer = document.getElementById('filterDrawer');
        drawer.classList.toggle('active');
    };

    window.resetFilters = function() {
        document.getElementById('filterForm').reset();
        currentPage = 1;
        loadSalesBills();
    };

    // --- Auto extract PAN from GSTIN ---
    window.populatePanFromGst = function(gst, targetId) {
        gst = gst.trim().toUpperCase();
        if (gst.length >= 12) {
            const pan = gst.substring(2, 12);
            document.getElementById(targetId).value = pan;
        }
    };

    // --- Load Customers for filter & forms ---
    async function loadCustomersList(selectedCustomerId = null) {
        try {
            const res = await fetch('auth/customers_crud.php?action=read&limit=100');
            const data = await res.json();
            if (data.success) {
                const filterSelect = document.getElementById('filterCustomer');
                const formSelect = document.getElementById('formCustomerSelect');
                
                // Clear existing
                filterSelect.innerHTML = '<option value="">-- All Customers --</option>';
                formSelect.innerHTML = '<option value="">-- Choose Party --</option>';

                data.data.forEach(cust => {
                    const selectedAttr = (selectedCustomerId && cust.id == selectedCustomerId) ? 'selected' : '';
                    const opt = `<option value="${cust.id}" ${selectedAttr}>${cust.name.toUpperCase()} (${cust.gst_number || 'No GST'})</option>`;
                    filterSelect.innerHTML += opt;
                    formSelect.innerHTML += opt;
                });
            }
        } catch (err) {
            console.error('Failed to load customers: ', err);
        }
    }

    // --- Load Products Cache ---
    async function loadProductsCache() {
        try {
            const res = await fetch('auth/products_crud.php?action=read&limit=200');
            const data = await res.json();
            if (data.success) {
                productsList = data.data;
            }
        } catch (err) {
            console.error('Failed to load products: ', err);
        }
    }

    // --- Load Sales Bills List ---
    window.loadSalesBills = async function() {
        const search = document.getElementById('filterSearch').value;
        const customerId = document.getElementById('filterCustomer').value;
        const status = document.getElementById('filterStatus').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        const limit = parseInt(document.getElementById('paginationLimit').value);
        const page = currentPage;

        try {
            const url = `auth/sales_bills_crud.php?action=read&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}&customer_id=${customerId}&status=${status}&date_from=${dateFrom}&date_to=${dateTo}`;
            const res = await fetch(url);
            const data = await res.json();

            if (data.success) {
                const tbody = document.querySelector('#salesBillsTable tbody');
                tbody.innerHTML = '';

                // Filter based on Segmented tab selected (All, Tax Invoice, Job Challan)
                let bills = data.data;
                if (activeTypeTab === 'Tax Invoice') {
                    // All seeded bills in our DB are 'Tax Invoices'
                } else if (activeTypeTab === 'Job Challan') {
                    // Return empty list as Job Challans are separate entity
                    bills = [];
                }

                if (bills.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">No records found.</td></tr>`;
                    updateTotalsFooter(0, 0, 0);
                    renderPaginationUI(0, page, limit);
                    return;
                }

                let sumTotalAmount = 0.00;
                let sumPendingAmount = 0.00;

                bills.forEach(bill => {
                    const tr = document.createElement('tr');
                    
                    // Status Badge
                    let statusBadge = '';
                    if (bill.status === 'PAID') {
                        statusBadge = '<span class="badge badge-paid">PAID</span>';
                    } else if (bill.status === 'PARTIAL') {
                        statusBadge = '<span class="badge badge-partial">PARTIAL</span>';
                    } else {
                        statusBadge = '<span class="badge badge-unpaid">UNPAID</span>';
                    }

                    // Due days display
                    let dueDaysDisplay = '';
                    if (bill.status === 'PAID') {
                        dueDaysDisplay = '<span>-</span>';
                    } else {
                        const days = parseInt(bill.due_days_left);
                        if (days < 0) {
                            dueDaysDisplay = `<span class="text-danger font-weight-bold">Expired</span>`;
                        } else {
                            dueDaysDisplay = `<span>${days}</span>`;
                        }
                    }

                    const pendingAmount = parseFloat(bill.grand_total) - parseFloat(bill.paid_amount);

                    sumTotalAmount += parseFloat(bill.grand_total);
                    sumPendingAmount += pendingAmount;

                    // Dates formatting (DD/MM/YYYY)
                    let formattedDate = bill.bill_date;
                    if (bill.bill_date) {
                        const parts = bill.bill_date.split('-');
                        if (parts.length === 3) {
                            formattedDate = `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                    }

                    // Action buttons matching the exact 6 icons in user screenshot
                    tr.innerHTML = `
                        <td class="ps-4">${formattedDate}</td>
                        <td><strong>${bill.bill_number}</strong></td>
                        <td>${bill.customer_name.toUpperCase()}</td>
                        <td class="small" style="color: #64748b; font-weight: 500;">TAX INVOICE</td>
                        <td style="font-weight:600;">${parseFloat(bill.grand_total).toFixed(2)}</td>
                        <td style="font-weight:600;">${pendingAmount.toFixed(2)}</td>
                        <td>${statusBadge}</td>
                        <td>${dueDaysDisplay}</td>
                        <td class="pe-4 text-end">
                            <div class="action-icon-container">
                                <button class="action-btn-circle pay-btn" onclick="recordPaymentPrompt(${bill.id}, ${pendingAmount})" title="Record Payment" ${bill.status === 'PAID' ? 'disabled' : ''}><i class="fa-solid fa-credit-card"></i></button>
                                <button class="action-btn-circle" onclick="viewInvoiceDetails(${bill.id})" title="Print Invoice"><i class="fa-solid fa-print"></i></button>
                                <button class="action-btn-circle" onclick="triggerSalesBillDownload(${bill.id})" title="View / Download PDF"><i class="fa-solid fa-download"></i></button>
                                <button class="action-btn-circle" id="whatsapp-${bill.id}" title="Share Invoice"><i class="fa-brands fa-whatsapp text-success"></i></button>
                                <button class="action-btn-circle" onclick="editSalesBill(${bill.id})" title="Edit Invoice"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="action-btn-circle text-danger" onclick="deleteSalesBill(${bill.id})" title="Delete Invoice"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);

                    // WhatsApp specific action trigger binding
                    const waBtn = tr.querySelector(`#whatsapp-${bill.id}`);
                    if (waBtn) {
                        waBtn.onclick = () => {
                            const msg = encodeURIComponent(`Hi, here is your invoice BIL-${bill.bill_number} for ₹ ${parseFloat(bill.grand_total).toFixed(2)}. Pending balance: ₹ ${pendingAmount.toFixed(2)}.`);
                            window.open(`https://api.whatsapp.com/send?text=${msg}`, '_blank');
                        };
                    }
                });

                // Update dynamic sum totals row
                updateTotalsFooter(bills.length, sumTotalAmount, sumPendingAmount);

                renderPaginationUI(data.total, page, limit);
            }
        } catch (err) {
            console.error('Failed to load bills: ', err);
        }
    };

    function updateTotalsFooter(count, total, pending) {
        document.getElementById('tbl-total-count').innerHTML = `<strong>Total ${count}</strong>`;
        document.getElementById('tbl-total-amount').innerHTML = `<strong>${total.toFixed(2)}</strong>`;
        document.getElementById('tbl-total-pending').innerHTML = `<strong>${pending.toFixed(2)}</strong>`;
    }

    function renderPaginationUI(totalItems, page, limit) {
        const nav = document.getElementById('paginationNav');
        const info = document.getElementById('paginationInfo');
        if (!nav) return;

        const totalPages = Math.ceil(totalItems / limit);
        const start = totalItems === 0 ? 0 : (page - 1) * limit + 1;
        const end = Math.min(page * limit, totalItems);

        info.textContent = `Showing ${start} to ${end} of ${totalItems} entries`;

        let buttonsHtml = '<ul class="pagination pagination-sm mb-0">';
        
        // Prev button
        buttonsHtml += `
            <li class="page-item ${page === 1 ? 'disabled' : ''}">
                <button class="page-link border-0 bg-transparent text-muted" onclick="changeListPage(${page - 1})"><i class="fa-solid fa-angle-left"></i></button>
            </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            buttonsHtml += `
                <li class="page-item ${i === page ? 'active' : ''}">
                    <button class="page-link rounded-circle mx-1 ${i === page ? 'btn-primary' : 'btn-light border-0 bg-transparent text-muted'}" style="width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-size:12px;" onclick="changeListPage(${i})">${i}</button>
                </li>
            `;
        }

        // Next button
        buttonsHtml += `
            <li class="page-item ${page === totalPages || totalPages === 0 ? 'disabled' : ''}">
                <button class="page-link border-0 bg-transparent text-muted" onclick="changeListPage(${page + 1})"><i class="fa-solid fa-angle-right"></i></button>
            </li>
        `;

        buttonsHtml += '</ul>';
        nav.innerHTML = buttonsHtml;
    }

    window.changeListPage = function(p) {
        currentPage = p;
        loadSalesBills();
    };

    // --- Form Due Date Auto Calculation ---
    window.calculateDueDate = function() {
        const dateInput = document.getElementById('formBillDate');
        const daysInput = document.getElementById('formDueDays');
        const dueOutput = document.getElementById('formDueDate');
        const dueDisplay = document.getElementById('formDueDateDisplay');

        if (!dateInput.value) return;

        const date = new Date(dateInput.value);
        const days = parseInt(daysInput.value) || 0;

        date.setDate(date.getDate() + days);

        // Format to YYYY-MM-DD
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');

        dueOutput.value = `${y}-${m}-${d}`;
        
        // Display format: DD/MM/YYYY
        dueDisplay.value = `${d}/${m}/${y}`;
    };

    // --- Challan Container Visual Toggles ---
    window.toggleChallanBlock = function() {
        const noChallanChecked = document.getElementById('formNoChallan').checked;
        const challanCard = document.getElementById('challanContainerCard');
        const challanNo = document.getElementById('formChallanNo');
        const challanDate = document.getElementById('formChallanDate');
        
        if (noChallanChecked) {
            challanCard.style.display = 'none';
            challanNo.value = '';
            challanNo.required = false;
            challanDate.value = '';
        } else {
            challanCard.style.display = 'block';
            challanNo.required = true;
        }
    };

    window.deleteChallanBlock = function() {
        document.getElementById('formNoChallan').checked = true;
        toggleChallanBlock();
    };

    window.createChallanBlock = function() {
        document.getElementById('formNoChallan').checked = false;
        toggleChallanBlock();
    };

    window.toggleRemarksBlock = function() {
        const remarksDiv = document.getElementById('remarksContainer');
        if (remarksDiv.style.display === 'none') {
            remarksDiv.style.display = 'block';
        } else {
            remarksDiv.style.display = 'none';
        }
    };

    async function fetchNextBillNumber() {
        try {
            const res = await fetch('auth/sales_bills_crud.php?action=get_next_bill_number');
            const data = await res.json();
            if (data.success) {
                document.getElementById('formBillNo').value = data.next_bill_number;
            }
        } catch (err) {
            console.error('Failed to fetch next bill number:', err);
        }
    }

    // --- Form Dynamic Product Lines Manager ---
    window.addProductRow = function(pId = '', qty = 1, rate = 0, code = '', hsn = '', unit = 'Pcs') {
        const tbody = document.querySelector('#itemsTable tbody');
        const rowId = 'row-' + Date.now() + Math.random().toString(36).substr(2, 5);

        const tr = document.createElement('tr');
        tr.id = rowId;

        // Build product dropdown choices
        let options = '<option value="">Select Product</option>';
        productsList.forEach(p => {
            const selected = p.id == pId ? 'selected' : '';
            options += `<option value="${p.id}" ${selected}>${p.name}</option>`;
        });

        tr.innerHTML = `
            <td>
                <select name="product_ids[]" class="form-select form-select-sm select-product-item py-2" required style="border-radius: 6px; border-color: #cbd5e1;">
                    ${options}
                </select>
                <input type="hidden" name="product_names[]" class="input-product-name" value="">
            </td>
            <td><input type="text" name="item_codes[]" class="form-control form-control-sm input-item-code py-2" placeholder="Item Code" value="${code}" readonly style="border-radius: 6px; border-color: #cbd5e1;"></td>
            <td><input type="text" name="hsn_codes[]" class="form-control form-control-sm input-hsn-code py-2" placeholder="HSN Code" value="${hsn}" readonly style="border-radius: 6px; border-color: #cbd5e1;"></td>
            <td><input type="number" name="quantities[]" class="form-control form-control-sm input-quantity py-2" placeholder="Enter Qty" value="${pId ? qty : ''}" min="1" required style="border-radius: 6px; border-color: #cbd5e1;"></td>
            <td>
                <select name="units[]" class="form-select form-select-sm input-unit py-2" style="border-radius: 6px; border-color: #cbd5e1;">
                    <option value="Pcs" ${unit === 'Pcs' ? 'selected' : ''}>Pcs</option>
                    <option value="Meters" ${unit === 'Meters' ? 'selected' : ''}>Meters</option>
                    <option value="Spools" ${unit === 'Spools' ? 'selected' : ''}>Spools</option>
                    <option value="Sets" ${unit === 'Sets' ? 'selected' : ''}>Sets</option>
                    <option value="Kgs" ${unit === 'Kgs' ? 'selected' : ''}>Kgs</option>
                </select>
            </td>
            <td><input type="number" step="0.01" name="rates[]" class="form-control form-control-sm input-rate py-2" placeholder="Enter Rate" value="${pId ? rate : ''}" min="0" required style="border-radius: 6px; border-color: #cbd5e1;"></td>
            <td style="text-align:right;">
                <input type="text" class="form-control form-control-sm text-end bg-light td-amount-total-val py-2" value="0.00" readonly style="border-radius: 6px; border-color: #cbd5e1; font-weight: 600;">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-link text-muted p-0" onclick="removeProductRow('${rowId}')" style="color: #94a3b8;">
                    <i class="fa-solid fa-circle-minus fs-5"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        // Register action triggers
        const selectEl = tr.querySelector('.select-product-item');
        selectEl.addEventListener('change', () => onProductChange(tr));

        const qtyEl = tr.querySelector('.input-quantity');
        const rateEl = tr.querySelector('.input-rate');

        qtyEl.addEventListener('input', () => calculateGrandTotal());
        rateEl.addEventListener('input', () => calculateGrandTotal());

        // Trigger change once to populate default values if pre-filling
        if (pId) {
            onProductChange(tr, true);
        } else {
            calculateGrandTotal();
        }
    };

    window.removeProductRow = function(rowId) {
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
            calculateGrandTotal();
        }
    };

    function onProductChange(rowElement, prefill = false) {
        const select = rowElement.querySelector('.select-product-item');
        const nameInput = rowElement.querySelector('.input-product-name');
        const codeInput = rowElement.querySelector('.input-item-code');
        const hsnInput = rowElement.querySelector('.input-hsn-code');
        const rateInput = rowElement.querySelector('.input-rate');
        const unitInput = rowElement.querySelector('.input-unit');

        const productId = select.value;
        if (!productId) {
            nameInput.value = '';
            codeInput.value = '';
            hsnInput.value = '';
            rateInput.value = '';
            unitInput.value = 'Pcs';
            calculateGrandTotal();
            return;
        }

        // Match cache
        const prod = productsList.find(p => p.id == productId);
        if (prod) {
            nameInput.value = prod.name;
            codeInput.value = prod.item_code || 'ITM-N/A';
            hsnInput.value = prod.hsn_code || 'HSN-N/A';
            unitInput.value = prod.unit || 'Pcs';
            if (!prefill) {
                rateInput.value = prod.price;
            }
        }
        calculateGrandTotal();
    }

    // --- Grand Totals Dynamic Formula calculations ---
    window.calculateGrandTotal = function() {
        const rows = document.querySelectorAll('#itemsTable tbody tr');
        let grossSum = 0.00;
        let totalQty = 0.00;

        rows.forEach(row => {
            const qtyInput = row.querySelector('.input-quantity');
            const rateInput = row.querySelector('.input-rate');
            const amountInput = row.querySelector('.td-amount-total-val');

            const qty = parseFloat(qtyInput.value) || 0;
            const rate = parseFloat(rateInput.value) || 0;

            const amount = qty * rate;
            if (amountInput) amountInput.value = amount.toFixed(2);

            grossSum += amount;
            totalQty += qty;
        });

        // Discount calculations
        const discPercent = parseFloat(document.getElementById('formDiscountPercent').value) || 0;
        const discountAmount = (grossSum * discPercent) / 100;
        const taxableAmount = grossSum - discountAmount;

        // GST calculations
        const gstPercent = parseFloat(document.getElementById('formGstPercent').value) || 0;
        const applyGst = document.getElementById('formApplyGst').checked;
        const gstAmount = applyGst ? ((taxableAmount * gstPercent) / 100) : 0.00;

        // TDS/TCS calculations
        const tdsTcsType = document.getElementById('formTdsTcsType').value || 'NONE';
        const tdsTcsPercent = parseFloat(document.getElementById('formTdsTcsPercent').value) || 0;
        
        let tdsTcsAmount = parseFloat(document.getElementById('formTdsTcsAmount').value) || 0.00;
        if (tdsTcsType !== 'NONE') {
            if (document.activeElement !== document.getElementById('formTdsTcsAmount')) {
                tdsTcsAmount = (taxableAmount * tdsTcsPercent) / 100;
                document.getElementById('formTdsTcsAmount').value = tdsTcsAmount.toFixed(2);
            }
        } else {
            tdsTcsAmount = 0.00;
            document.getElementById('formTdsTcsAmount').value = '0.00';
        }

        let grandTotal = taxableAmount + gstAmount;
        if (tdsTcsType !== 'NONE') {
            grandTotal += tdsTcsAmount;
        }

        // Update Summary Interface
        document.getElementById('summaryTotalQty').textContent = totalQty.toFixed(2);
        document.getElementById('summaryDiscountLabel').textContent = discountAmount.toFixed(2) + ' ₹';
        document.getElementById('summaryGstLabel').textContent = gstAmount.toFixed(2) + ' ₹';

        document.getElementById('summaryGrossAmount').textContent = '₹ ' + grossSum.toFixed(2);
        document.getElementById('summaryDiscountAmount').textContent = '- ₹ ' + discountAmount.toFixed(2);
        document.getElementById('summaryTaxableAmount').textContent = '₹ ' + taxableAmount.toFixed(2);
        document.getElementById('summaryGstAmount').textContent = '+ ₹ ' + gstAmount.toFixed(2);

        // Update TDS/TCS summary label and value
        const labelEl = document.getElementById('summaryTdsTcsLabel');
        const amountEl = document.getElementById('summaryTdsTcsAmount');
        if (tdsTcsType === 'NONE') {
            labelEl.textContent = 'TDS/TCS (0%):';
            amountEl.textContent = '₹ 0.00';
            amountEl.style.color = '#64748b';
        } else if (tdsTcsType === 'TDS') {
            labelEl.textContent = `TDS (${tdsTcsPercent}%):`;
            amountEl.textContent = `₹ + ${tdsTcsAmount.toFixed(2)}`;
            amountEl.style.color = '#10b981'; // Green for addition
        } else if (tdsTcsType === 'TCS') {
            labelEl.textContent = `TCS (${tdsTcsPercent}%):`;
            amountEl.textContent = `₹ + ${tdsTcsAmount.toFixed(2)}`;
            amountEl.style.color = '#10b981'; // Green for addition
        }

        document.getElementById('summaryGrandTotal').textContent = '₹ ' + grandTotal.toFixed(2);
    };

    window.resetInvoiceForm = function() {
        const form = document.getElementById('createBillForm');
        form.reset();
        
        // Clear items rows
        document.querySelector('#itemsTable tbody').innerHTML = '';
        
        // Pre-configure defaults
        calculateDueDate();
        
        // Add one empty product line
        addProductRow();

        // Reset TDS/TCS state
        document.getElementById('formTdsTcsType').value = 'NONE';
        document.getElementById('formTdsTcsPercent').value = '0.00';
        document.getElementById('formTdsTcsAmount').value = '0.00';
        const container = document.getElementById('tdsTcsInputsContainer');
        if (container) container.style.setProperty('display', 'none', 'important');

        // Challan default state
        document.getElementById('formNoChallan').checked = false;
        toggleChallanBlock();

        // Remarks default state
        document.getElementById('remarksContainer').style.display = 'none';

        // Fetch next bill number
        fetchNextBillNumber();
    }

    // --- Submit Invoice Form ---
    const createBillForm = document.getElementById('createBillForm');
    if (createBillForm) {
        createBillForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Client-side validations
            const rows = document.querySelectorAll('#itemsTable tbody tr');
            if (rows.length === 0) {
                alert('Please add at least one product line item.');
                return;
            }

            const formData = new FormData(createBillForm);

            try {
                const res = await fetch('auth/sales_bills_crud.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    alert(data.message);
                    const submitAction = document.getElementById('formSubmitAction').value;
                    if (submitAction === 'save_new') {
                        resetInvoiceForm();
                    } else {
                        toggleView('list');
                    }
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred during submission.');
            }
        });
    }

    // --- 4. Payment Management Prompt ---
    window.recordPaymentPrompt = async function(billId, maxAmount) {
        try {
            // Fetch bill details to extract tax, discounts and items context
            const res = await fetch('auth/sales_bills_crud.php?action=read&limit=100');
            const data = await res.json();
            if (data.success) {
                const bill = data.data.find(b => b.id == billId);
                if (!bill) {
                    alert('Sales bill details not found.');
                    return;
                }

                // Populate modal metadata
                document.getElementById('paymentBillId').value = bill.id;
                document.getElementById('pay-meta-bill-no').textContent = bill.bill_number;
                document.getElementById('pay-meta-customer').textContent = bill.customer_name;

                // Set default payment date (today)
                document.getElementById('pay-input-date').value = new Date().toISOString().substring(0, 10);

                // Store calculations context as attributes on form
                const form = document.getElementById('recordPaymentForm');
                form.setAttribute('data-taxable', bill.taxable_amount);
                form.setAttribute('data-gst', bill.gst_amount);
                form.setAttribute('data-discount', bill.discount_amount);
                form.setAttribute('data-discount-percent', bill.discount_percent);
                form.setAttribute('data-paid', bill.paid_amount);
                form.setAttribute('data-grand-total', bill.grand_total);

                // Reset notes textarea and select dropdown
                form.querySelector('textarea[name="notes"]').value = '';
                form.querySelector('select[name="payment_mode"]').value = '';

                // Set default TDS percent (1%)
                document.getElementById('pay-tds-percent').value = "1";
                document.getElementById('pay-tds-pct-display').textContent = "1";

                // Recalculate calculations block
                recalculatePaymentModalSummary();

                // Open modal
                const modal = new bootstrap.Modal(document.getElementById('modalPayment'));
                modal.show();
            }
        } catch (err) {
            console.error('Failed to open record payment:', err);
            alert('Failed to load bill details.');
        }
    };

    window.recalculatePaymentModalSummary = function() {
        const form = document.getElementById('recordPaymentForm');
        const taxable = parseFloat(form.getAttribute('data-taxable')) || 0;
        const gst = parseFloat(form.getAttribute('data-gst')) || 0;
        const discount = parseFloat(form.getAttribute('data-discount')) || 0;
        const discPercent = parseFloat(form.getAttribute('data-discount-percent')) || 0;
        const paid = parseFloat(form.getAttribute('data-paid')) || 0;
        const grandTotal = parseFloat(form.getAttribute('data-grand-total')) || 0;

        const tdsPercent = parseFloat(document.getElementById('pay-tds-percent').value) || 0;
        const tdsAmount = taxable * (tdsPercent / 100);

        const totalAmountNetOfTds = grandTotal - tdsAmount;
        const remainingPending = Math.max(0, totalAmountNetOfTds - paid);

        // Update displays
        document.getElementById('pay-calc-net').textContent = (taxable + discount).toFixed(2);
        document.getElementById('pay-calc-disc-label').textContent = `Total Discount (${discPercent}%):`;
        document.getElementById('pay-calc-disc').textContent = `- ${discount.toFixed(2)}`;
        document.getElementById('pay-calc-taxable').textContent = taxable.toFixed(2);
        document.getElementById('pay-calc-gst').textContent = `+ ${gst.toFixed(2)}`;
        
        document.getElementById('pay-tds-pct-display').textContent = tdsPercent;
        document.getElementById('pay-calc-tds').textContent = `- ${tdsAmount.toFixed(2)}`;
        
        document.getElementById('pay-calc-total').textContent = totalAmountNetOfTds.toFixed(2);
        document.getElementById('pay-calc-pending').textContent = remainingPending.toFixed(2);

        // Pre-fill input transaction amount
        const amountInput = document.getElementById('paymentMaxAmount');
        amountInput.value = remainingPending.toFixed(2);
        amountInput.setAttribute('max', remainingPending);

        // Save computed values to hidden inputs
        document.getElementById('pay-tds-amount').value = tdsAmount.toFixed(2);

        // Update settlement amount
        onTxnAmountChange();
    };

    window.onTxnAmountChange = function() {
        const amountInput = document.getElementById('paymentMaxAmount');
        const txnAmount = parseFloat(amountInput.value) || 0;

        const form = document.getElementById('recordPaymentForm');
        const taxable = parseFloat(form.getAttribute('data-taxable')) || 0;
        const grandTotal = parseFloat(form.getAttribute('data-grand-total')) || 0;
        
        const tdsPercent = parseFloat(document.getElementById('pay-tds-percent').value) || 0;
        const tdsAmount = taxable * (tdsPercent / 100);
        const totalAmountNetOfTds = grandTotal - tdsAmount;

        // Calculate settlement amount
        let settlementAmount = txnAmount;
        if (totalAmountNetOfTds > 0) {
            settlementAmount = txnAmount * (grandTotal / totalAmountNetOfTds);
        }

        document.getElementById('pay-settlement-amount').value = settlementAmount.toFixed(2);
    };

    window.editTdsPercentage = function() {
        const currentPct = document.getElementById('pay-tds-percent').value;
        const newPctStr = prompt("Enter TDS Percentage:", currentPct);
        if (newPctStr !== null) {
            const newPct = parseFloat(newPctStr);
            if (!isNaN(newPct) && newPct >= 0 && newPct <= 100) {
                document.getElementById('pay-tds-percent').value = newPct;
                recalculatePaymentModalSummary();
            } else {
                alert("Please enter a valid percentage between 0 and 100.");
            }
        }
    };

    const recordPaymentForm = document.getElementById('recordPaymentForm');
    if (recordPaymentForm) {
        recordPaymentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(recordPaymentForm);
            
            try {
                const res = await fetch('auth/payments_crud.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    // Close modal
                    const modalEl = document.getElementById('modalPayment');
                    let modal = bootstrap.Modal.getInstance(modalEl);
                    if (!modal) {
                        modal = new bootstrap.Modal(modalEl);
                    }
                    modal.hide();

                    alert(data.message);
                    loadSalesBills();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    // --- 5. Quick Add Customer Inline Modal ---
    window.openCustomerModal = function() {
        // Reset form
        const form = document.getElementById('quickAddCustomerForm');
        if (form) form.reset();
        
        const modal = new bootstrap.Modal(document.getElementById('modalCustomer'));
        modal.show();
    };

    const quickAddCustomerForm = document.getElementById('quickAddCustomerForm');
    if (quickAddCustomerForm) {
        quickAddCustomerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(quickAddCustomerForm);

            try {
                const res = await fetch('auth/parties_crud.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    // Hide Modal
                    const modalEl = document.getElementById('modalCustomer');
                    let modal = bootstrap.Modal.getInstance(modalEl);
                    if (!modal) {
                        modal = new bootstrap.Modal(modalEl);
                    }
                    modal.hide();

                    alert('Party added successfully!');
                    
                    // Reload customer options
                    await loadCustomersList(data.id);
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    // --- Mock Edit Actions ---
    window.editSalesBill = function(billId) {
        alert(`Sales Bill Edit Action clicked! (In this web implementation, you can modify invoice items by using the 'Add New' bill option, or deleting entries. This feature is pre-configured for audit safety.)`);
    };

    // --- 6. View Invoice Preview Modal Details ---
    function convertNumberToWords(amount) {
        let words = "";
        let number = Math.floor(amount);
        let paise = Math.round((amount - number) * 100);
        
        words += translateToWords(number) + " RUPEES";
        if (paise > 0) {
            words += " AND " + translateToWords(paise) + " PAISE";
        }
        words += " ONLY";
        return words.toUpperCase();
    }

    function translateToWords(num) {
        const units = ["", "ONE", "TWO", "THREE", "FOUR", "FIVE", "SIX", "SEVEN", "EIGHT", "NINE", "TEN", "ELEVEN", "TWELVE", "THIRTEEN", "FOURTEEN", "FIFTEEN", "SIXTEEN", "SEVENTEEN", "EIGHTEEN", "NINETEEN"];
        const tens = ["", "", "TWENTY", "THIRTY", "FORTY", "FIFTY", "SIXTY", "SEVENTY", "EIGHTY", "NINETY"];
        
        if (num === 0) return "ZERO";
        
        let words = "";
        
        if (Math.floor(num / 10000000) > 0) {
            words += translateToWords(Math.floor(num / 10000000)) + " CRORE ";
            num %= 10000000;
        }
        
        if (Math.floor(num / 100000) > 0) {
            words += translateToWords(Math.floor(num / 100000)) + " LAKH ";
            num %= 100000;
        }
        
        if (Math.floor(num / 1000) > 0) {
            words += translateToWords(Math.floor(num / 1000)) + " THOUSAND ";
            num %= 1000;
        }
        
        if (Math.floor(num / 100) > 0) {
            words += translateToWords(Math.floor(num / 100)) + " HUNDRED ";
            num %= 100;
        }
        
        if (num > 0) {
            if (num < 20) {
                words += units[num];
            } else {
                words += tens[Math.floor(num / 10)] + " " + units[num % 10];
            }
        }
        
        return words.trim();
    }

    window.triggerSalesBillDownload = async function(billId) {
        await window.viewInvoiceDetails(billId);
        setTimeout(() => {
            if (typeof downloadInvoicePDF === 'function') {
                downloadInvoicePDF();
            }
        }, 500);
    };

    window.viewInvoiceDetails = async function(billId) {
        try {
            // Retrieve single bill, items and payment logs details
            const res = await fetch(`auth/sales_bills_crud.php?action=read&limit=50&page=1&search=&customer_id=&status=&bill_number=&challan_number=`);
            const data = await res.json();
            
            if (data.success) {
                // Find matching bill item in the collection
                const bill = data.data.find(b => b.id == billId);
                if (!bill) {
                    alert('Error finding sales bill details.');
                    return;
                }

                // Render bill details defensively
                const safeSetText = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val;
                };

                safeSetText('inv-preview-bill-no', bill.bill_number);
                safeSetText('inv-preview-customer', bill.customer_name);
                safeSetText('inv-preview-address', bill.address || 'Physical Address details');
                safeSetText('inv-preview-gst', bill.customer_gst ? bill.customer_gst : '-');
                safeSetText('inv-preview-customer-pan', bill.customer_pan ? bill.customer_pan : '-');
                safeSetText('inv-preview-date', bill.bill_date);
                safeSetText('inv-preview-due-date', bill.due_date);
                safeSetText('inv-preview-challan-no', bill.challan_no ? '#' + bill.challan_no : '-');

                // Calculations
                safeSetText('inv-preview-discount-percent', parseFloat(bill.discount_percent).toFixed(1));
                safeSetText('inv-preview-discount', '- ₹ ' + parseFloat(bill.discount_amount).toFixed(2));
                safeSetText('inv-preview-taxable', '₹ ' + parseFloat(bill.taxable_amount).toFixed(2));
                
                // Dynamic GST tax split
                const taxBreakdownEl = document.getElementById('inv-preview-tax-breakdown');
                const totalTaxEl = document.getElementById('inv-preview-total-tax');
                const gstPercent = parseFloat(bill.gst_percent) || 0;
                const gstAmount = parseFloat(bill.gst_amount) || 0;
                
                if (taxBreakdownEl) {
                    taxBreakdownEl.innerHTML = '';
                    const shopStateEl = document.getElementById('inv-preview-shop-state');
                    const shopState = shopStateEl ? shopStateEl.textContent.trim().toLowerCase() : 'gujarat';
                    const custState = (bill.customer_state || '').trim().toLowerCase();
                    
                    if (custState === shopState || custState === '') {
                        // Intra-state: CGST & SGST
                        const halfPercent = (gstPercent / 2).toFixed(1);
                        const halfAmount = (gstAmount / 2).toFixed(2);
                        taxBreakdownEl.innerHTML = `
                            <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000;">
                                <span>CGST (${halfPercent}%)</span>
                                <span>${halfAmount}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000;">
                                <span>SGST (${halfPercent}%)</span>
                                <span>${halfAmount}</span>
                            </div>
                        `;
                    } else {
                        // Inter-state: IGST
                        taxBreakdownEl.innerHTML = `
                            <div style="display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #000;">
                                <span>IGST (${gstPercent.toFixed(1)}%)</span>
                                <span>${gstAmount.toFixed(2)}</span>
                            </div>
                        `;
                    }
                    if (totalTaxEl) totalTaxEl.textContent = '₹ ' + gstAmount.toFixed(2);
                }
                
                // TDS/TCS preview rendering
                const tdsTcsRow = document.getElementById('inv-preview-tds-tcs-row');
                const tdsTcsLabel = document.getElementById('inv-preview-tds-tcs-label');
                const tdsTcsAmountEl = document.getElementById('inv-preview-tds-tcs-amount');
                
                const tdsTcsType = bill.tds_tcs_type || 'NONE';
                const tdsTcsPercent = parseFloat(bill.tds_tcs_percent) || 0;
                const tdsTcsAmount = parseFloat(bill.tds_tcs_amount) || 0;

                if (tdsTcsRow && tdsTcsLabel && tdsTcsAmountEl) {
                    if (tdsTcsType === 'NONE' || tdsTcsAmount === 0) {
                        tdsTcsRow.style.display = 'none';
                    } else {
                        tdsTcsRow.style.setProperty('display', 'flex', 'important');
                        tdsTcsLabel.textContent = `${tdsTcsType} (${tdsTcsPercent.toFixed(1)}%):`;
                        tdsTcsAmountEl.textContent = `+ ₹ ${tdsTcsAmount.toFixed(2)}`;
                    }
                }

                const grandTotal = parseFloat(bill.grand_total) || 0;
                safeSetText('inv-preview-grand-total', '₹ ' + grandTotal.toFixed(2));
                safeSetText('inv-preview-round-amount', '₹ ' + Math.round(grandTotal).toFixed(2));

                // Words
                const amountWordsEl = document.getElementById('inv-preview-amount-words');
                if (amountWordsEl) {
                    amountWordsEl.textContent = convertNumberToWords(grandTotal);
                }

                // Fetch item lines and payment history dynamically from backend
                await loadPreviewItemLines(billId);
                await loadPreviewPaymentsHistory(billId);

                // Setup WhatsApp and Email sharing action prompts
                const btnShareWhatsApp = document.getElementById('btnShareWhatsApp');
                if (btnShareWhatsApp) {
                    btnShareWhatsApp.onclick = () => {
                        const msg = encodeURIComponent(`Hi, here is tax invoice ${bill.bill_number} for ₹ ${grandTotal.toFixed(2)}. Status: ${bill.status}.`);
                        window.open(`https://api.whatsapp.com/send?text=${msg}`, '_blank');
                    };
                }

                const btnShareEmail = document.getElementById('btnShareEmail');
                if (btnShareEmail) {
                    btnShareEmail.onclick = () => {
                        window.location.href = `mailto:${bill.email || ''}?subject=Tax Invoice ${bill.bill_number}&body=Please find invoice details for your review.`;
                    };
                }

                const modal = new bootstrap.Modal(document.getElementById('modalInvoice'));
                modal.show();
            }
        } catch (err) {
            console.error(err);
        }
    };

    // Sub-helper: Fetch and render item lines
    async function loadPreviewItemLines(billId) {
        try {
            const res = await fetch(`auth/sales_bills_crud.php?action=read_items&sales_bill_id=${billId}`);
            const data = await res.json();

            const tbody = document.getElementById('inv-preview-items');
            tbody.innerHTML = '';

            let totalQty = 0;
            let totalAmountSum = 0;
            let sr = 1;

            if (data.success && data.data) {
                data.data.forEach(item => {
                    const qty = parseFloat(item.quantity) || 0;
                    const amt = parseFloat(item.amount) || 0;
                    totalQty += qty;
                    totalAmountSum += amt;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="border-right: 1px solid #000; text-align: center; padding: 4px;">${sr++}</td>
                        <td style="border-right: 1px solid #000; padding: 4px;"><strong>${item.product_name}</strong></td>
                        <td style="border-right: 1px solid #000; text-align: center; padding: 4px;">${item.hsn_code || '-'}</td>
                        <td style="border-right: 1px solid #000; text-align: center; padding: 4px;">${qty.toFixed(2)} ${item.unit}</td>
                        <td style="border-right: 1px solid #000; text-align: right; padding: 4px;">${parseFloat(item.rate).toFixed(2)}</td>
                        <td style="text-align:right; padding: 4px;">${amt.toFixed(2)}</td>
                    `;
                    tbody.appendChild(tr);
                });
                
                // Add an empty stretch row to draw the vertical lines all the way down
                const stretchTr = document.createElement('tr');
                stretchTr.innerHTML = `
                    <td style="border-right: 1px solid #000; height: 150px;"></td>
                    <td style="border-right: 1px solid #000;"></td>
                    <td style="border-right: 1px solid #000;"></td>
                    <td style="border-right: 1px solid #000;"></td>
                    <td style="border-right: 1px solid #000;"></td>
                    <td></td>
                `;
                tbody.appendChild(stretchTr);
            }

            // Set tfoot sums
            const totalQtyEl = document.getElementById('inv-preview-total-qty');
            const totalAmtSumEl = document.getElementById('inv-preview-total-amount-sum');
            if (totalQtyEl) totalQtyEl.textContent = totalQty.toFixed(2);
            if (totalAmtSumEl) totalAmtSumEl.textContent = '₹ ' + totalAmountSum.toFixed(2);
        } catch (err) {
            console.error(err);
        }
    }

    // Sub-helper: Fetch and render payment histories
    async function loadPreviewPaymentsHistory(billId) {
        try {
            const res = await fetch(`auth/payments_crud.php?action=read&sales_bill_id=${billId}`);
            const data = await res.json();

            const timeline = document.getElementById('inv-preview-timeline');
            timeline.innerHTML = '';

            if (data.success && data.data && data.data.length > 0) {
                data.data.forEach(pay => {
                    const div = document.createElement('div');
                    div.className = 'timeline-item';
                    div.innerHTML = `
                        <div class="timeline-time">${pay.payment_date}</div>
                        <div class="timeline-desc">Received ₹ ${parseFloat(pay.amount).toFixed(2)} via ${pay.payment_mode}</div>
                        ${pay.reference_number ? `<div class="text-muted small">Ref: ${pay.reference_number}</div>` : ''}
                    `;
                    timeline.appendChild(div);
                });
            } else {
                timeline.innerHTML = '<p class="text-muted small mb-0">No payment records logged.</p>';
            }
        } catch (err) {
            console.error(err);
        }
    }

    // --- 7. Delete Sales Bill ---
    window.deleteSalesBill = async function(billId) {
        if (!confirm('Are you sure you want to delete this invoice? This will roll back catalog stock levels and delete payments.')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', billId);

            const res = await fetch('auth/sales_bills_crud.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            alert(data.message);
            loadSalesBills();
        } catch (err) {
            console.error(err);
        }
    };

    // --- Initialize ---
    async function init() {
        await loadCustomersList();
        await loadProductsCache();
        
        // Hash routing for add form
        const hash = window.location.hash;
        if (hash === '#add') {
            toggleView('form');
        } else {
            loadSalesBills();
        }
    }

    init();

    // Listen to hash changes for page transitions
    window.addEventListener('hashchange', () => {
        const hash = window.location.hash;
        if (hash === '#add') {
            toggleView('form');
        } else if (hash === '#list' || !hash) {
            toggleView('list');
        }
    });

    // --- TDS / TCS Modal configuration ---
    window.openTdsTcsModal = function() {
        const type = document.getElementById('formTdsTcsType').value || 'NONE';
        const percent = parseFloat(document.getElementById('formTdsTcsPercent').value) || 0.00;
        const amount = parseFloat(document.getElementById('formTdsTcsAmount').value) || 0.00;

        // Populate modal inputs
        document.getElementById('modalTdsTcsPercentInput').value = percent.toFixed(2);
        document.getElementById('modalTdsTcsAmountInput').value = amount.toFixed(2);

        const radioEl = document.querySelector(`input[name="modal_tds_tcs_type_radio"][value="${type}"]`);
        if (radioEl) radioEl.checked = true;

        const modal = new bootstrap.Modal(document.getElementById('modalTdsTcs'));
        modal.show();
    };

    window.saveTdsTcsSettings = function() {
        const selectedType = document.querySelector('input[name="modal_tds_tcs_type_radio"]:checked').value;
        const percent = parseFloat(document.getElementById('modalTdsTcsPercentInput').value) || 0.00;
        const amount = parseFloat(document.getElementById('modalTdsTcsAmountInput').value) || 0.00;

        document.getElementById('formTdsTcsType').value = selectedType;
        document.getElementById('formTdsTcsPercent').value = percent.toFixed(2);
        document.getElementById('formTdsTcsAmount').value = amount.toFixed(2);

        // Hide modal
        const modalEl = document.getElementById('modalTdsTcs');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        calculateGrandTotal();
    };

    // Auto-calculate logic in modal
    document.getElementById('modalTdsTcsPercentInput').addEventListener('input', () => {
        const percent = parseFloat(document.getElementById('modalTdsTcsPercentInput').value) || 0.00;
        
        // Find current taxable amount from page
        const grossAmountText = document.getElementById('summaryGrossAmount').textContent.replace('₹', '').trim();
        const grossAmount = parseFloat(grossAmountText) || 0.00;
        const discPercent = parseFloat(document.getElementById('formDiscountPercent').value) || 0;
        const discountAmount = (grossAmount * discPercent) / 100;
        const taxableAmount = grossAmount - discountAmount;

        const amount = (taxableAmount * percent) / 100;
        document.getElementById('modalTdsTcsAmountInput').value = amount.toFixed(2);
    });

    document.getElementById('modalTdsTcsAmountInput').addEventListener('input', () => {
        const amount = parseFloat(document.getElementById('modalTdsTcsAmountInput').value) || 0.00;
        
        // Find current taxable amount from page
        const grossAmountText = document.getElementById('summaryGrossAmount').textContent.replace('₹', '').trim();
        const grossAmount = parseFloat(grossAmountText) || 0.00;
        const discPercent = parseFloat(document.getElementById('formDiscountPercent').value) || 0;
        const discountAmount = (grossAmount * discPercent) / 100;
        const taxableAmount = grossAmount - discountAmount;

        const percent = taxableAmount > 0 ? (amount / taxableAmount) * 100 : 0.00;
        document.getElementById('modalTdsTcsPercentInput').value = percent.toFixed(2);
    });

    window.onTdsTcsTypeChange = function() {
        const type = document.getElementById('formTdsTcsType').value || 'NONE';
        const container = document.getElementById('tdsTcsInputsContainer');
        if (container) {
            if (type === 'NONE') {
                container.style.setProperty('display', 'none', 'important');
                document.getElementById('formTdsTcsPercent').value = '0.00';
                document.getElementById('formTdsTcsAmount').value = '0.00';
            } else {
                container.style.setProperty('display', 'flex', 'important');
            }
        }
        calculateGrandTotal();
    };

    window.onTdsTcsPercentChange = function() {
        const percent = parseFloat(document.getElementById('formTdsTcsPercent').value) || 0.00;
        const grossAmountText = document.getElementById('summaryGrossAmount').textContent.replace('₹', '').trim();
        const grossAmount = parseFloat(grossAmountText) || 0.00;
        const discPercent = parseFloat(document.getElementById('formDiscountPercent').value) || 0;
        const discountAmount = (grossAmount * discPercent) / 100;
        const taxableAmount = grossAmount - discountAmount;

        const amount = (taxableAmount * percent) / 100;
        document.getElementById('formTdsTcsAmount').value = amount.toFixed(2);
        calculateGrandTotal();
    };

    window.onTdsTcsAmountChange = function() {
        const amount = parseFloat(document.getElementById('formTdsTcsAmount').value) || 0.00;
        const grossAmountText = document.getElementById('summaryGrossAmount').textContent.replace('₹', '').trim();
        const grossAmount = parseFloat(grossAmountText) || 0.00;
        const discPercent = parseFloat(document.getElementById('formDiscountPercent').value) || 0;
        const discountAmount = (grossAmount * discPercent) / 100;
        const taxableAmount = grossAmount - discountAmount;

        const percent = taxableAmount > 0 ? (amount / taxableAmount) * 100 : 0.00;
        document.getElementById('formTdsTcsPercent').value = percent.toFixed(2);
        calculateGrandTotal();
    };
});

window.downloadInvoicePDF = function() {
    const element = document.getElementById('invoice-print-area');
    const billNo = document.getElementById('inv-preview-bill-no').textContent.trim() || 'bill';
    
    // Configure html2pdf
    const opt = {
        margin:       [5, 5, 5, 5],
        filename:     `Invoice_${billNo}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    // Check if html2pdf is loaded
    if (typeof html2pdf !== 'undefined') {
        html2pdf().set(opt).from(element).save();
    } else {
        alert('PDF library not loaded. Please try again later.');
    }
};
