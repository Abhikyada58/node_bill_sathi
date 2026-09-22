/**
 * Finance ERP - Purchase Bills Module JavaScript Controller
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- Global State ---
    let activeView = 'list'; // 'list' or 'form'
    let currentPage = 1;
    let productsCache = [];
    let suppliersList = [];
    let activeGstTab = 'All'; // 'All', 'With Tax', 'Without Tax'
    let purchaseBillsList = [];

    // --- Switch Screen Panels ---
    window.toggleView = function(view) {
        activeView = view;
        const listPanel = document.getElementById('purchase-bill-list-panel');
        const formPanel = document.getElementById('purchase-bill-form-panel');
        const titleRow = document.getElementById('view-title-row');

        if (view === 'list') {
            listPanel.classList.add('active');
            formPanel.classList.remove('active');
            if (titleRow) titleRow.style.display = 'flex';
            loadPurchaseBills();
        } else {
            listPanel.classList.remove('active');
            formPanel.classList.add('active');
            if (titleRow) titleRow.style.display = 'none';
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
    const tabWithTax = document.getElementById('tab-with-tax');
    const tabWithoutTax = document.getElementById('tab-without-tax');

    function selectGstTab(tabName, element) {
        activeGstTab = tabName;
        [tabAll, tabWithTax, tabWithoutTax].forEach(btn => {
            if (btn) btn.classList.remove('active');
        });
        if (element) element.classList.add('active');
        currentPage = 1;
        loadPurchaseBills();
    }

    if (tabAll) tabAll.addEventListener('click', () => selectGstTab('All', tabAll));
    if (tabWithTax) tabWithTax.addEventListener('click', () => selectGstTab('With Tax', tabWithTax));
    if (tabWithoutTax) tabWithoutTax.addEventListener('click', () => selectGstTab('Without Tax', tabWithoutTax));

    // --- Collapsible Filter Panel Toggle ---
    window.toggleFilterPanel = function() {
        const drawer = document.getElementById('filterDrawer');
        const badge = document.getElementById('filterBadge');
        drawer.classList.toggle('active');
        
        // Show indicator if filter panel is active
        if (drawer.classList.contains('active')) {
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    };

    window.resetFilters = function() {
        document.getElementById('filterForm').reset();
        currentPage = 1;
        loadPurchaseBills();
    };

    // --- Load Suppliers for filter & forms ---
    async function loadSuppliersList(selectedSupplierId = null) {
        try {
            const res = await fetch('auth/parties_crud.php?action=read&limit=100');
            const data = await res.json();
            if (data.success) {
                suppliersList = data.data;
                const filterSelect = document.getElementById('filterSupplier');
                const formSelect = document.getElementById('formSupplierSelect');
                
                if (filterSelect) filterSelect.innerHTML = '<option value="">-- All Suppliers --</option>';
                if (formSelect) formSelect.innerHTML = '<option value="">Select Party</option>';

                suppliersList.forEach(sup => {
                    const selectedAttr = (selectedSupplierId && sup.id == selectedSupplierId) ? 'selected' : '';
                    const opt = `<option value="${sup.id}" ${selectedAttr}>${sup.name.toUpperCase()} (${sup.gst_number || 'No GST'})</option>`;
                    if (filterSelect) filterSelect.innerHTML += opt;
                    if (formSelect) formSelect.innerHTML += opt;
                });
            }
        } catch (err) {
            console.error('Failed to load suppliers: ', err);
        }
    }

    // --- Load Products Cache ---
    async function loadProductsCache() {
        try {
            const res = await fetch('auth/products_crud.php?action=read&limit=200');
            const data = await res.json();
            if (data.success) {
                productsCache = data.data;
            }
        } catch (err) {
            console.error('Failed to load products cache: ', err);
        }
    }

    // --- Load Purchase Bills List ---
    window.loadPurchaseBills = async function() {
        const searchInput = document.getElementById('filterSearch');
        const search = searchInput ? searchInput.value : '';
        const supplierSelect = document.getElementById('filterSupplier');
        const supplierId = supplierSelect ? supplierSelect.value : '';
        const statusSelect = document.getElementById('filterStatus');
        const status = statusSelect ? statusSelect.value : '';
        const dateFromInput = document.getElementById('filterDateFrom');
        const dateFrom = dateFromInput ? dateFromInput.value : '';
        const dateToInput = document.getElementById('filterDateTo');
        const dateTo = dateToInput ? dateToInput.value : '';
        const limitSelect = document.getElementById('paginationLimit');
        const limit = limitSelect ? parseInt(limitSelect.value) : 100;
        const page = currentPage;

        let applyGstParam = '';
        if (activeGstTab === 'With Tax') {
            applyGstParam = '1';
        } else if (activeGstTab === 'Without Tax') {
            applyGstParam = '0';
        }

        try {
            const url = `auth/purchase_bills_crud.php?action=read&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}&supplier_id=${supplierId}&status=${status}&date_from=${dateFrom}&date_to=${dateTo}&apply_gst=${applyGstParam}`;
            const res = await fetch(url);
            const data = await res.json();

            if (data.success) {
                purchaseBillsList = data.data;
                const tbody = document.querySelector('#purchaseBillsTable tbody');
                tbody.innerHTML = '';

                if (purchaseBillsList.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">No records found.</td></tr>`;
                    updateTotalsFooter(0, 0, 0);
                    renderPaginationUI(0, page, limit);
                    return;
                }

                let sumTotalAmount = 0.00;
                let sumPendingAmount = 0.00;

                purchaseBillsList.forEach(bill => {
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

                    const pendingAmount = parseFloat(bill.amount) - parseFloat(bill.paid_amount);

                    sumTotalAmount += parseFloat(bill.amount);
                    sumPendingAmount += pendingAmount;

                    // Dates formatting (DD/MM/YYYY)
                    let formattedDate = bill.bill_date;
                    if (bill.bill_date) {
                        const parts = bill.bill_date.split('-');
                        if (parts.length === 3) {
                            formattedDate = `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                    }

                    const billTypeDisplay = (parseInt(bill.apply_gst) === 1) ? 'With Tax' : 'Without Tax';

                    // Action buttons
                    tr.innerHTML = `
                        <td class="ps-4">${formattedDate}</td>
                        <td><strong>${bill.bill_number}</strong></td>
                        <td>${bill.supplier_name.toUpperCase()}</td>
                        <td class="small" style="color: #64748b; font-weight: 500;">${billTypeDisplay}</td>
                        <td style="font-weight:600;">${parseFloat(bill.amount).toFixed(2)}</td>
                        <td style="font-weight:600;">${pendingAmount.toFixed(2)}</td>
                        <td>${statusBadge}</td>
                        <td>${dueDaysDisplay}</td>
                        <td class="pe-4 text-end">
                            <div class="action-icon-container">
                                <button class="action-btn-circle pay-btn" onclick="recordPaymentPrompt(${bill.id}, ${pendingAmount})" title="Record Payment" ${bill.status === 'PAID' ? 'disabled' : ''}><i class="fa-solid fa-credit-card"></i></button>
                                <button class="action-btn-circle" onclick="printPurchaseBill(${bill.id})" title="Print Invoice"><i class="fa-solid fa-print"></i></button>
                                <button class="action-btn-circle" onclick="triggerPurchaseBillDownload(${bill.id})" title="View / Download PDF"><i class="fa-solid fa-download"></i></button>
                                <button class="action-btn-circle" onclick="editPurchaseBill(${bill.id})" title="Edit Invoice"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="action-btn-circle" onclick="viewPurchaseBillDetails(${bill.id})" title="View Details"><i class="fa-solid fa-eye"></i></button>
                                
                                <div class="dropdown d-inline-block">
                                    <button class="action-btn-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size: 13px;">
                                        <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="deletePurchaseBill(${bill.id})"><i class="fa-solid fa-trash-can me-2"></i> Delete Bill</a></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                // Update dynamic sum totals row
                updateTotalsFooter(purchaseBillsList.length, sumTotalAmount, sumPendingAmount);
                renderPaginationUI(data.total, page, limit);
            }
        } catch (err) {
            console.error('Failed to load purchase bills: ', err);
        }
    };

    function updateTotalsFooter(count, total, pending) {
        const footCount = document.getElementById('tbl-total-count');
        const footTotal = document.getElementById('tbl-total-amount');
        const footPending = document.getElementById('tbl-total-pending');

        if (footCount) footCount.textContent = `Total ${count}`;
        if (footTotal) footTotal.textContent = total.toFixed(2);
        if (footPending) footPending.textContent = pending.toFixed(2);
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
        loadPurchaseBills();
    };

    // --- Add Purchase Bill Row Elements ---
    let rowIdCounter = 0;
    window.addProductRow = function(itemData = null) {
        const tbody = document.querySelector('#formProductsTable tbody');
        if (!tbody) return;

        const rowId = `product-row-${rowIdCounter++}`;
        const tr = document.createElement('tr');
        tr.id = rowId;

        // Generate product options
        let optionsHtml = '<option value="">Select Product</option>';
        productsCache.forEach(prod => {
            const isSelected = itemData && itemData.product_id == prod.id ? 'selected' : '';
            optionsHtml += `<option value="${prod.id}" ${isSelected}>${prod.name.toUpperCase()}</option>`;
        });

        tr.innerHTML = `
            <td>
                <select class="product-select" onchange="onProductSelectChange('${rowId}', this.value)" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="text" class="item-code bg-light text-muted" value="${itemData ? (itemData.item_code || '') : ''}" readonly>
            </td>
            <td>
                <input type="text" class="hsn-code bg-light text-muted" value="${itemData ? (itemData.hsn_code || '') : ''}" readonly>
            </td>
            <td>
                <input type="number" class="qty" step="0.01" placeholder="Enter Qty" value="${itemData ? itemData.quantity : ''}" oninput="calculateRowAmount('${rowId}')" required>
            </td>
            <td>
                <select class="unit-select" required>
                    <option value="Pcs" ${itemData && itemData.unit === 'Pcs' ? 'selected' : ''}>Pcs</option>
                    <option value="Meters" ${itemData && itemData.unit === 'Meters' ? 'selected' : ''}>Meters</option>
                    <option value="Spools" ${itemData && itemData.unit === 'Spools' ? 'selected' : ''}>Spools</option>
                    <option value="Sets" ${itemData && itemData.unit === 'Sets' ? 'selected' : ''}>Sets</option>
                    <option value="Kgs" ${itemData && itemData.unit === 'Kgs' ? 'selected' : ''}>Kgs</option>
                </select>
            </td>
            <td>
                <input type="number" class="rate" step="0.01" placeholder="Enter Rate" value="${itemData ? itemData.rate : ''}" oninput="calculateRowAmount('${rowId}')" required>
            </td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <input type="number" class="discount" step="0.01" placeholder="Enter Discount" value="${itemData ? itemData.discount_percent : '0'}" oninput="calculateRowAmount('${rowId}')">
                    <span class="discount-badge badge bg-light text-dark" style="font-size:10px; border:1px solid #cbd5e1; min-width:40px;">0.00 ₹</span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <select class="tax-select" onchange="calculateRowAmount('${rowId}')" required>
                        <option value="0.00" ${itemData && parseFloat(itemData.gst_percent) === 0 ? 'selected' : ''}>GST 0%</option>
                        <option value="1.50" ${itemData && parseFloat(itemData.gst_percent) === 1.5 ? 'selected' : ''}>GST 1.50%</option>
                        <option value="5.00" ${itemData && parseFloat(itemData.gst_percent) === 5 ? 'selected' : ''}>GST 5%</option>
                        <option value="12.00" ${itemData && parseFloat(itemData.gst_percent) === 12 ? 'selected' : ''}>GST 12%</option>
                        <option value="18.00" ${(itemData && parseFloat(itemData.gst_percent) === 18) || (!itemData) ? 'selected' : ''}>GST 18%</option>
                        <option value="28.00" ${itemData && parseFloat(itemData.gst_percent) === 28 ? 'selected' : ''}>GST 28%</option>
                    </select>
                    <span class="tax-badge badge bg-light text-dark" style="font-size:10px; border:1px solid #cbd5e1; min-width:40px;">0.00 ₹</span>
                </div>
            </td>
            <td>
                <input type="number" class="amount bg-light text-muted fw-bold" value="${itemData ? itemData.amount : '0.00'}" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn-remove-row" onclick="removeProductRow('${rowId}')"><i class="fa-solid fa-minus"></i></button>
            </td>
        `;

        tbody.appendChild(tr);

        // If data is provided, trigger calculation
        if (itemData) {
            calculateRowAmount(rowId);
        }
    };

    window.removeProductRow = function(rowId) {
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
            calculateGrandTotal();
        }
    };

    window.onProductSelectChange = function(rowId, productId) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const itemCodeInput = row.querySelector('.item-code');
        const hsnCodeInput = row.querySelector('.hsn-code');
        const rateInput = row.querySelector('.rate');
        const unitSelect = row.querySelector('.unit-select');
        const taxSelect = row.querySelector('.tax-select');

        if (!productId) {
            itemCodeInput.value = '';
            hsnCodeInput.value = '';
            rateInput.value = '';
            return;
        }

        const prod = productsCache.find(p => p.id == productId);
        if (prod) {
            itemCodeInput.value = prod.item_code || '';
            hsnCodeInput.value = prod.hsn_code || '';
            rateInput.value = parseFloat(prod.price) > 0 ? prod.price : '';
            unitSelect.value = prod.unit || 'Pcs';
            
            if (taxSelect && prod.gst_percent !== undefined) {
                const pct = parseFloat(prod.gst_percent).toFixed(2);
                taxSelect.value = pct;
            }
            
            calculateRowAmount(rowId);
        }
    };

    window.calculateRowAmount = function(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const qty = parseFloat(row.querySelector('.qty').value) || 0;
        const rate = parseFloat(row.querySelector('.rate').value) || 0;
        const discountPercent = parseFloat(row.querySelector('.discount').value) || 0;
        const discountBadge = row.querySelector('.discount-badge');
        
        const taxSelect = row.querySelector('.tax-select');
        const gstPercent = taxSelect ? parseFloat(taxSelect.value) : 0;
        const taxBadge = row.querySelector('.tax-badge');
        
        const amountInput = row.querySelector('.amount');

        let rawAmount = qty * rate;
        let discountVal = rawAmount * (discountPercent / 100);
        let taxableAmount = rawAmount - discountVal;

        const applyGst = document.getElementById('formApplyGst').checked;
        const effectiveGstPercent = applyGst ? gstPercent : 0.00;
        let gstVal = taxableAmount * (effectiveGstPercent / 100);
        let finalAmount = taxableAmount + gstVal;

        discountBadge.textContent = `${discountVal.toFixed(2)} ₹`;
        if (taxBadge) {
            taxBadge.textContent = `${gstVal.toFixed(2)} ₹`;
        }
        amountInput.value = finalAmount.toFixed(2);

        calculateGrandTotal();
    };

    window.calculateGrandTotal = function() {
        let totalQty = 0;
        let netAmount = 0;
        let discountAmount = 0;
        let totalGstAmount = 0;

        const applyGst = document.getElementById('formApplyGst').checked;
        const rows = document.querySelectorAll('#formProductsTable tbody tr');
        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            const rate = parseFloat(row.querySelector('.rate').value) || 0;
            const discountPercent = parseFloat(row.querySelector('.discount').value) || 0;
            
            const taxSelect = row.querySelector('.tax-select');
            const gstPercent = taxSelect ? parseFloat(taxSelect.value) : 0;

            const rawAmt = qty * rate;
            const discAmt = rawAmt * (discountPercent / 100);
            const taxableAmt = rawAmt - discAmt;

            const effectiveGstPercent = applyGst ? gstPercent : 0.00;
            const gstAmt = taxableAmt * (effectiveGstPercent / 100);

            totalQty += qty;
            netAmount += rawAmt;
            discountAmount += discAmt;
            totalGstAmount += gstAmt;
        });

        const taxableAmount = netAmount - discountAmount;
        const grandTotal = taxableAmount + totalGstAmount;

        // Update display labels
        document.getElementById('summaryTotalQty').textContent = totalQty.toFixed(2);
        document.getElementById('summaryNetAmount').textContent = `₹ ${netAmount.toFixed(2)}`;
        document.getElementById('summaryDiscountAmount').textContent = `- ₹ ${discountAmount.toFixed(2)}`;
        document.getElementById('summaryTaxableAmount').textContent = `₹ ${taxableAmount.toFixed(2)}`;
        
        const summaryGstElement = document.getElementById('summaryGstAmount');
        if (summaryGstElement) {
            summaryGstElement.textContent = `+ ₹ ${totalGstAmount.toFixed(2)}`;
        }
        
        document.getElementById('summaryGrandTotal').textContent = `₹ ${grandTotal.toFixed(2)}`;
    };

    // --- Remark character counter ---
    window.updateCharCounter = function(textarea) {
        const length = textarea.value.length;
        document.getElementById('remarksCharCounter').textContent = `${length} / 200`;
    };

    // --- Dynamic Due Date Calculations ---
    window.updateDueDate = function() {
        const dateVal = document.getElementById('formBillDate').value;
        const daysVal = parseInt(document.getElementById('formDueDays').value) || 0;
        const dueDisplay = document.getElementById('formDueDateDisplay');
        const dueHidden = document.getElementById('formDueDate');

        if (!dateVal) {
            dueDisplay.value = 'DD/MM/YYYY';
            dueHidden.value = '';
            return;
        }

        const dateObj = new Date(dateVal);
        dateObj.setDate(dateObj.getDate() + daysVal);

        const dd = String(dateObj.getDate()).padStart(2, '0');
        const mm = String(dateObj.getMonth() + 1).padStart(2, '0');
        const yyyy = dateObj.getFullYear();

        dueDisplay.value = `${dd}/${mm}/${yyyy}`;
        dueHidden.value = `${yyyy}-${mm}-${dd}`;
    };

    function resetInvoiceForm() {
        const form = document.getElementById('createBillForm');
        form.reset();
        
        document.getElementById('editBillId').value = '';
        document.getElementById('formViewTitle').textContent = 'Add Purchase Bill';
        document.getElementById('createBillForm').querySelector("[name='action']").value = 'create';

        // Set default date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('formBillDate').value = today;

        document.querySelector('#formProductsTable tbody').innerHTML = '';
        document.getElementById('remarksCharCounter').textContent = '0 / 200';
        
        // Insert one blank row default
        addProductRow();
        updateDueDate();
        calculateGrandTotal();
    }

    // --- Submit Form handler ---
    window.submitBillForm = async function(submitAction) {
        const form = document.getElementById('createBillForm');
        document.getElementById('formSubmitAction').value = submitAction;

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Collect rows
        const items = [];
        const rows = document.querySelectorAll('#formProductsTable tbody tr');
        let hasError = false;

        rows.forEach(row => {
            const productSelect = row.querySelector('.product-select');
            const productId = productSelect.value;
            const productName = productSelect.options[productSelect.selectedIndex].text;
            const itemCode = row.querySelector('.item-code').value;
            const hsnCode = row.querySelector('.hsn-code').value;
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            const unit = row.querySelector('.unit-select').value;
            const rate = parseFloat(row.querySelector('.rate').value) || 0;
            const discountPercent = parseFloat(row.querySelector('.discount').value) || 0;
            const amount = parseFloat(row.querySelector('.amount').value) || 0;

            const taxSelect = row.querySelector('.tax-select');
            const gstPercent = taxSelect ? parseFloat(taxSelect.value) : 0;
            const applyGst = document.getElementById('formApplyGst').checked;
            const effectiveGstPercent = applyGst ? gstPercent : 0.00;

            const rawAmt = qty * rate;
            const discAmt = rawAmt * (discountPercent / 100);
            const taxableAmt = rawAmt - discAmt;
            const gstAmount = taxableAmt * (effectiveGstPercent / 100);

            if (!productId || qty <= 0 || rate <= 0) {
                hasError = true;
                return;
            }

            items.push({
                product_id: productId,
                product_name: productName,
                item_code: itemCode,
                hsn_code: hsnCode,
                quantity: qty,
                unit: unit,
                rate: rate,
                discount_percent: discountPercent,
                gst_percent: gstPercent,
                gst_amount: gstAmount,
                amount: amount
            });
        });

        if (hasError || items.length === 0) {
            alert('Please select a product and enter a valid Qty / Rate for all added product lines.');
            return;
        }

        const formData = new FormData(form);
        formData.append('items', JSON.stringify(items));

        // Add calculated numerical summaries
        const netAmount = parseFloat(document.getElementById('summaryNetAmount').textContent.replace(/[^\d.]/g, '')) || 0;
        const discountAmount = parseFloat(document.getElementById('summaryDiscountAmount').textContent.replace(/[^\d.]/g, '')) || 0;
        const taxableAmount = parseFloat(document.getElementById('summaryTaxableAmount').textContent.replace(/[^\d.]/g, '')) || 0;
        const grandTotal = parseFloat(document.getElementById('summaryGrandTotal').textContent.replace(/[^\d.]/g, '')) || 0;

        const gstAmount = parseFloat(document.getElementById('summaryGstAmount').textContent.replace(/[^\d.]/g, '')) || 0;
        const gstPercent = taxableAmount > 0 ? (gstAmount / taxableAmount) * 100 : 0;

        formData.append('taxable_amount', taxableAmount);
        formData.append('discount_amount', discountAmount);
        formData.append('gst_amount', gstAmount);
        formData.append('gst_percent', gstPercent);
        formData.append('grand_total', grandTotal);
        
        // Assume fully paid if status PAID or if action is edit and it was paid
        const isEdit = document.getElementById('editBillId').value !== '';
        if (!isEdit && submitAction === 'save') {
            // Defaults to PAID for mockup simplicity when submit is clicked
            formData.append('paid_amount', grandTotal);
        } else {
            formData.append('paid_amount', 0); // UNPAID/PARTIAL
        }

        try {
            const res = await fetch('auth/purchase_bills_crud.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                alert(data.message);
                if (submitAction === 'save_and_new') {
                    resetInvoiceForm();
                } else {
                    toggleView('list');
                }
            } else {
                alert(data.message);
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred while saving.');
        }
    };

    // --- Record Payment Modal control ---
    window.recordPaymentPrompt = function(billId, pendingAmount) {
        const bill = purchaseBillsList.find(b => b.id == billId);
        if (!bill) return;

        document.getElementById('paymentBillId').value = bill.id;
        document.getElementById('pay-meta-bill-no').textContent = bill.bill_number;
        document.getElementById('pay-meta-supplier').textContent = bill.supplier_name.toUpperCase();
        document.getElementById('pay-calc-total').textContent = `₹ ${parseFloat(bill.amount).toFixed(2)}`;
        document.getElementById('pay-calc-pending').textContent = `₹ ${pendingAmount.toFixed(2)}`;

        // Set default payment date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('pay-input-date').value = today;
        document.getElementById('paymentMaxAmount').value = pendingAmount.toFixed(2);

        const modal = new bootstrap.Modal(document.getElementById('modalPayment'));
        modal.show();
    };

    // Record Payment Submission
    const recordPaymentForm = document.getElementById('recordPaymentForm');
    if (recordPaymentForm) {
        recordPaymentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(recordPaymentForm);
            
            // Record payment endpoint helper (using payments_crud.php if it exists, or purchase_bills_crud wrapper)
            // For simple mockup execution, we will update paid_amount in purchase_bills_crud
            const billId = document.getElementById('paymentBillId').value;
            const amtPaid = parseFloat(document.getElementById('paymentMaxAmount').value);
            
            const reqData = new FormData();
            reqData.append('action', 'update');
            reqData.append('id', billId);
            
            // Find current bill details
            const bill = purchaseBillsList.find(b => b.id == billId);
            if (!bill) return;
            
            reqData.append('supplier_id', bill.supplier_id);
            reqData.append('bill_number', bill.bill_number);
            reqData.append('bill_date', bill.bill_date);
            reqData.append('due_days', bill.due_days);
            reqData.append('due_date', bill.due_date);
            reqData.append('apply_gst', bill.apply_gst);
            reqData.append('discount_percent', bill.discount_percent);
            reqData.append('discount_amount', bill.discount_amount);
            reqData.append('gst_percent', bill.gst_percent);
            reqData.append('gst_amount', bill.gst_amount);
            reqData.append('taxable_amount', bill.taxable_amount);
            reqData.append('grand_total', bill.amount);
            reqData.append('paid_amount', parseFloat(bill.paid_amount) + amtPaid);
            reqData.append('remarks', bill.remarks || '');
            
            // Fetch items
            try {
                const itemRes = await fetch(`auth/purchase_bills_crud.php?action=read_items&id=${billId}`);
                const itemData = await itemRes.json();
                if (itemData.success) {
                    reqData.append('items', JSON.stringify(itemData.data));
                    
                    const res = await fetch('auth/purchase_bills_crud.php', {
                        method: 'POST',
                        body: reqData
                    });
                    const resJSON = await res.json();
                    
                    if (resJSON.success) {
                        const modalEl = document.getElementById('modalPayment');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        modal.hide();
                        
                        alert('Payment recorded successfully!');
                        loadPurchaseBills();
                    } else {
                        alert(resJSON.message);
                    }
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while recording payment.');
            }
        });
    }

    // --- Edit Purchase Bill ---
    window.editPurchaseBill = async function(billId) {
        const bill = purchaseBillsList.find(b => b.id == billId);
        if (!bill) return;

        toggleView('form');
        document.getElementById('editBillId').value = bill.id;
        document.getElementById('formViewTitle').textContent = `Edit Purchase Bill (${bill.bill_number})`;
        document.getElementById('createBillForm').querySelector("[name='action']").value = 'update';

        document.getElementById('formSupplierSelect').value = bill.supplier_id;
        document.getElementById('formBillNumber').value = bill.bill_number;
        document.getElementById('formBillDate').value = bill.bill_date;
        document.getElementById('formDueDays').value = bill.due_days;
        document.getElementById('formApplyGst').checked = parseInt(bill.apply_gst) === 1;
        document.getElementById('formRemarks').value = bill.remarks || '';
        updateCharCounter(document.getElementById('formRemarks'));

        // Load items
        try {
            const res = await fetch(`auth/purchase_bills_crud.php?action=read_items&id=${billId}`);
            const data = await res.json();
            if (data.success) {
                document.querySelector('#formProductsTable tbody').innerHTML = '';
                data.data.forEach(item => {
                    addProductRow(item);
                });
                updateDueDate();
                calculateGrandTotal();
            }
        } catch (err) {
            console.error('Failed to load purchase bill items: ', err);
        }
    };

    // --- Delete Purchase Bill ---
    window.deletePurchaseBill = async function(billId) {
        if (!confirm('Are you sure you want to delete this purchase bill? This action cannot be undone.')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', billId);

            const res = await fetch('auth/purchase_bills_crud.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                alert(data.message);
                loadPurchaseBills();
            } else {
                alert(data.message);
            }
        } catch (err) {
            console.error(err);
        }
    };

    // --- View Bill Details / Print Invoice ---

    window.viewPurchaseBillDetails = async function(billId) {
        await openPurchaseBillInvoice(billId);
    };

    window.triggerPurchaseBillDownload = async function(billId) {
        await openPurchaseBillInvoice(billId);
        // Wait briefly for modal and DOM to fully render before taking screenshot
        setTimeout(() => {
            if (typeof downloadPurchaseInvoicePDF === 'function') {
                downloadPurchaseInvoicePDF();
            }
        }, 500);
    };

    window.printPurchaseBill = async function(billId) {
        await openPurchaseBillInvoice(billId);
        setTimeout(() => { window.print(); }, 500);
    };

    async function openPurchaseBillInvoice(billId) {
        const bill = purchaseBillsList.find(b => b.id == billId);
        if (!bill) return;

        // Populate header fields
        setText('pur-inv-preview-shop-name',   document.getElementById('pur-inv-preview-shop-name')?.textContent || '');
        setText('pur-inv-preview-shop-address', document.getElementById('pur-inv-preview-shop-address')?.textContent || '');
        setText('pur-inv-preview-shop-state',   document.getElementById('pur-inv-preview-shop-state')?.textContent || '');

        // Supplier / Party info
        setText('pur-inv-preview-customer',      bill.supplier_name ? bill.supplier_name.toUpperCase() : '-');
        setText('pur-inv-preview-address',        bill.address || '-');
        setText('pur-inv-preview-gst',            bill.supplier_gst || '-');
        setText('pur-inv-preview-customer-pan',   bill.supplier_pan || '-');

        // Bill meta
        setText('pur-inv-preview-bill-no',        bill.bill_number || '-');
        const billDateFormatted = formatDate(bill.bill_date);
        setText('pur-inv-preview-date',           billDateFormatted);
        setText('pur-inv-preview-challan-no',     bill.challan_no || '-');

        // Financial totals
        const discountAmt   = parseFloat(bill.discount_amount || 0);
        const discountPct   = parseFloat(bill.discount_percent || 0);
        const taxableAmt    = parseFloat(bill.taxable_amount || 0);
        const gstAmt        = parseFloat(bill.gst_amount || 0);
        const grandTotal    = parseFloat(bill.amount || 0);
        const gstPct        = parseFloat(bill.gst_percent || 0);

        const discEl = document.getElementById('pur-inv-preview-discount-percent');
        if (discEl) discEl.textContent = discountPct.toFixed(2);
        setText('pur-inv-preview-discount',   `- ₹ ${discountAmt.toFixed(2)}`);
        setText('pur-inv-preview-taxable',    `₹ ${taxableAmt.toFixed(2)}`);
        setText('pur-inv-preview-total-tax',  `₹ ${gstAmt.toFixed(2)}`);
        setText('pur-inv-preview-grand-total',`₹ ${grandTotal.toFixed(2)}`);
        const roundAmt = Math.round(grandTotal) - grandTotal;
        setText('pur-inv-preview-round-amount', `₹ ${roundAmt.toFixed(2)}`);

        // Amount in Words
        setText('pur-inv-preview-amount-words', convertNumberToWords(grandTotal));

        // CGST / SGST or IGST tax breakdown
        const taxBreakdownEl = document.getElementById('pur-inv-preview-tax-breakdown');
        if (taxBreakdownEl) {
            const shopState    = document.getElementById('pur-inv-preview-shop-state')?.textContent?.trim() || '';
            const partyState   = bill.supplier_state || '';
            const sameState    = shopState.toLowerCase() === partyState.toLowerCase() && shopState !== '';
            const halfGst      = (gstAmt / 2).toFixed(2);
            const halfPct      = (gstPct / 2).toFixed(2);

            if (parseInt(bill.apply_gst) === 1 && gstAmt > 0) {
                if (sameState) {
                    taxBreakdownEl.innerHTML = `
                        <div style="display:flex;justify-content:space-between;padding:4px 8px;border-bottom:1px solid #000;">
                            <span>CGST (${halfPct}%)</span><span>${halfGst}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;padding:4px 8px;border-bottom:1px solid #000;">
                            <span>SGST (${halfPct}%)</span><span>${halfGst}</span>
                        </div>`;
                } else {
                    taxBreakdownEl.innerHTML = `
                        <div style="display:flex;justify-content:space-between;padding:4px 8px;border-bottom:1px solid #000;">
                            <span>IGST (${gstPct.toFixed(2)}%)</span><span>${gstAmt.toFixed(2)}</span>
                        </div>`;
                }
            } else {
                taxBreakdownEl.innerHTML = `
                    <div style="display:flex;justify-content:space-between;padding:4px 8px;border-bottom:1px solid #000;">
                        <span>Tax (GST)</span><span>0.00</span>
                    </div>`;
            }
        }

        // TDS/TCS row — purchase bills don't use TDS/TCS currently, hide it
        const tdsTcsRow = document.getElementById('pur-inv-preview-tds-tcs-row');
        if (tdsTcsRow) tdsTcsRow.style.display = 'none';

        // Load and render line items
        try {
            const res = await fetch(`auth/purchase_bills_crud.php?action=read_items&id=${billId}`);
            const data = await res.json();
            const tbody = document.getElementById('pur-inv-preview-items');
            const totalQtyEl = document.getElementById('pur-inv-preview-total-qty');
            const totalSumEl = document.getElementById('pur-inv-preview-total-amount-sum');

            if (tbody) tbody.innerHTML = '';
            let totalQty = 0, totalSum = 0;

            if (data.success && data.data.length > 0) {
                data.data.forEach((item, idx) => {
                    const qty    = parseFloat(item.quantity || 0);
                    const amount = parseFloat(item.amount || 0);
                    totalQty += qty;
                    totalSum += amount;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="text-align:center; border-right:1px solid #000; padding:4px;">${idx + 1}</td>
                        <td style="border-right:1px solid #000; padding:4px; font-weight:600;">${item.product_name || '-'}</td>
                        <td style="text-align:center; border-right:1px solid #000; padding:4px;">${item.hsn_code || '-'}</td>
                        <td style="text-align:center; border-right:1px solid #000; padding:4px;">${qty.toFixed(2)} ${item.unit || ''}</td>
                        <td style="text-align:right; border-right:1px solid #000; padding:4px;">${parseFloat(item.rate || 0).toFixed(2)}</td>
                        <td style="text-align:right; padding:4px;">${amount.toFixed(2)}</td>
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
            } else {
                if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:12px;color:#94a3b8;border-bottom:1px solid #000;">No items found</td></tr>';
            }

            if (totalQtyEl) totalQtyEl.textContent = totalQty.toFixed(2);
            if (totalSumEl) totalSumEl.textContent = `₹ ${totalSum.toFixed(2)}`;
        } catch (err) {
            console.error('Failed to load purchase bill items for invoice:', err);
        }

        // Payment history timeline
        const timelineEl = document.getElementById('pur-inv-preview-timeline');
        if (timelineEl) {
            const paidAmt    = parseFloat(bill.paid_amount || 0);
            const pendingAmt = parseFloat(bill.amount || 0) - paidAmt;
            timelineEl.innerHTML = `
                <div style="padding:4px 0; color:#64748b;">
                    <i class="fa-solid fa-circle-dot me-2" style="color:#2563eb; font-size:10px;"></i>
                    <strong>Bill Created:</strong> ${billDateFormatted} &mdash; Total: ₹ ${parseFloat(bill.amount).toFixed(2)}
                </div>
                ${paidAmt > 0 ? `
                <div style="padding:4px 0; color:#16a34a;">
                    <i class="fa-solid fa-circle-dot me-2" style="color:#16a34a; font-size:10px;"></i>
                    <strong>Paid:</strong> ₹ ${paidAmt.toFixed(2)} &mdash; Pending: ₹ ${pendingAmt.toFixed(2)}
                </div>` : `
                <div style="padding:4px 0; color:#dc2626;">
                    <i class="fa-solid fa-circle-dot me-2" style="color:#dc2626; font-size:10px;"></i>
                    <strong>Unpaid</strong> &mdash; Full amount pending: ₹ ${pendingAmt.toFixed(2)}
                </div>`}
            `;
        }

        // Open the modal
        const modal = new bootstrap.Modal(document.getElementById('modalViewBill'));
        modal.show();
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const parts = dateStr.split('-');
        if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
        return dateStr;
    }

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



    

    window.exportToExcel = function() {
        alert('Exporting purchase bills list to Excel sheet...');
    };

    // --- Initialization ---
    async function init() {
        await loadSuppliersList();
        await loadProductsCache();
        await loadPurchaseBills();
    }

    init();

});

window.downloadPurchaseInvoicePDF = function() {
    const element = document.getElementById('invoice-print-area');
    const billNo = document.getElementById('pur-inv-preview-bill-no').textContent.trim() || 'bill';
    
    // Configure html2pdf
    const opt = {
        margin:       [5, 5, 5, 5],
        filename:     `Purchase_Bill_${billNo}.pdf`,
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
