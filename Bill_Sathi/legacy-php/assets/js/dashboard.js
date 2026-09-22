/**
 * Finance ERP Dashboard - Main JS Controller
 */


document.addEventListener('DOMContentLoaded', () => {
    
    // --- Global State ---
    let currentTab = 'dashboard';
    let productsList = []; // Caches product catalog for sales bill rows
    let activeSalesTypeTab = 'All'; // 'All', 'Tax Invoice', 'Job Challan'
    
    // Pagination helpers
    const limits = {
        transactions: 10,
        products: 10,
        customers: 10,
        suppliers: 10,
        sales: 100
    };
    const pages = {
        transactions: 1,
        products: 1,
        customers: 1,
        suppliers: 1,
        sales: 1
    };



    // Chart tracker
    let spendingChartInstance = null;

    // --- Tab Switcher Logic ---
    const menuItems = document.querySelectorAll('.menu-item');
    const tabContents = document.querySelectorAll('.tab-content');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    function switchTab(tabId) {
        currentTab = tabId;

        // Toggle active menu-item
        menuItems.forEach(item => {
            if (item.getAttribute('data-target') === tabId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Toggle active content section
        tabContents.forEach(content => {
            if (content.id === `tab-${tabId}`) {
                content.classList.add('active');
            } else {
                content.classList.remove('active');
            }
        });

        // Trigger dynamic data fetch for active tab
        loadTabData(tabId);
    }

    menuItems.forEach(item => {
        item.addEventListener('click', (e) => {
            const target = item.getAttribute('data-target');
            if (!target) return; // Allow normal navigation for links with href and no data-target
            
            e.preventDefault();
            switchTab(target);
            
            // Close mobile sidebar after click
            if (sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    });

    // Sidebar Mobile Toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // Connect Dribbble Module Cards buttons to tab switches
    document.querySelectorAll('.btn-manage-module, .btn-open-module').forEach(btn => {
        btn.addEventListener('click', () => {
            const moduleName = btn.getAttribute('data-module');
            if (moduleName === 'sales-bill') {
                window.location.href = 'sales_bills.php';
            } else if (moduleName === 'purchase-bill') {
                window.location.href = 'purchase_bills.php';
            } else if (moduleName === 'transactions') {
                window.location.href = 'transactions.php';
            } else if (moduleName === 'expenses') {
                window.location.href = 'expenses.php';
            } else {
                switchTab(moduleName);
            }
        });
    });

    document.querySelectorAll('.btn-add-module').forEach(btn => {
        btn.addEventListener('click', () => {
            const moduleName = btn.getAttribute('data-module');
            if (moduleName === 'sales-bill') {
                window.location.href = 'sales_bills.php#add';
            } else if (moduleName === 'purchase-bill') {
                window.location.href = 'purchase_bills.php#add';
            } else if (moduleName === 'expenses') {
                window.location.href = 'expenses.php#add';
            } else if (moduleName === 'transactions') {
                window.location.href = 'transactions.php#add';
            } else if (moduleName === 'reports') {
                switchTab('reports');
            }
        });
    });

    // --- Main Tab Data Load router ---
    function loadTabData(tabId) {
        switch(tabId) {
            case 'dashboard':
                loadDashboardStats();
                break;
            case 'sales-bill':
                toggleSalesFormView('list');
                break;
            case 'purchase-bill':
                loadTransactionsList('purchase-bill-table', 'Purchase');
                break;
            case 'customers':
                loadCustomers();
                break;
            case 'suppliers':
                loadSuppliers();
                break;
            case 'products':
                loadProducts();
                break;
            case 'income':
                loadTransactionsList('income-table', 'Sale');
                break;
            case 'expenses':
                loadTransactionsList('expenses-table', 'Expense');
                break;
            case 'transactions':
                loadTransactions();
                break;
            case 'reports':
                loadReportData('profit_loss');
                break;
        }
    }

    // --- 1. Dashboard Statistics & Chart.js Loader ---
    async function loadDashboardStats() {
        try {
            const res = await fetch('auth/get_statistics.php');
            const data = await res.json();

            if (data.success) {
                // Populate statistics
                animateCounter('stat-total-sales', data.stats.total_sales, true);
                animateCounter('stat-total-purchase', data.stats.total_purchase, true);
                animateCounter('stat-total-expenses', data.stats.total_expenses, true);
                animateCounter('stat-total-profit', data.stats.total_profit, true);
                animateCounter('stat-pending-payments', data.stats.pending_payments, true);
                animateCounter('stat-monthly-revenue', data.stats.monthly_revenue, true);

                // Render Chart.js
                renderSalesChart(data.chart.labels, data.chart.sales, data.chart.expenses);
            }
        } catch (err) {
            console.error('Failed to load statistics: ', err);
        }
    }

    function animateCounter(elementId, targetValue, isCurrency = false) {
        const el = document.getElementById(elementId);
        if (!el) return;

        let startValue = 0;
        const duration = 800; // 0.8s
        const startTime = performance.now();

        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Quad ease-out
            const ease = progress * (2 - progress);
            const val = startValue + (targetValue - startValue) * ease;

            if (isCurrency) {
                el.textContent = '$' + val.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } else {
                el.textContent = Math.floor(val).toLocaleString();
            }

            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        requestAnimationFrame(update);
    }

    function renderSalesChart(labels, sales, expenses) {
        const chartCanvas = document.getElementById('spendingChart');
        if (!chartCanvas) return;

        if (spendingChartInstance) {
            spendingChartInstance.destroy();
        }

        const ctx = chartCanvas.getContext('2d');
        
        spendingChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sales Revenue',
                        data: sales,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.05)',
                        borderWidth: 3,
                        pointRadius: 4,
                        fill: true,
                        tension: 0.35
                    },
                    {
                        label: 'Operating Expenses',
                        data: expenses,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.05)',
                        borderWidth: 3,
                        pointRadius: 4,
                        fill: true,
                        tension: 0.35
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: "'Inter', sans-serif", size: 12 }
                        }
                    }
                },
                scales: {
                    y: {
                        grid: { color: '#e5e7eb' },
                        ticks: {
                            callback: value => '$' + value.toLocaleString()
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // --- 2. Upgraded Sales Bills Controller Inside Dashboard ---
    window.toggleSalesFormView = function(view) {
        const listPanel = document.getElementById('sales-bill-list-panel');
        const formPanel = document.getElementById('sales-bill-form-panel');

        if (view === 'list') {
            listPanel.classList.add('active');
            listPanel.classList.remove('d-none');
            formPanel.classList.add('d-none');
            formPanel.classList.remove('active');
            loadSalesBillsList();
        } else {
            listPanel.classList.remove('active');
            listPanel.classList.add('d-none');
            formPanel.classList.remove('d-none');
            formPanel.classList.add('active');
            resetInvoiceForm();
        }
    };

    // Segmented tabs click handlers
    const tabAll = document.getElementById('tab-all-bills');
    const tabTax = document.getElementById('tab-tax-invoices');
    const tabJob = document.getElementById('tab-job-challans');

    function selectSalesTypeTab(tabName, element) {
        activeSalesTypeTab = tabName;
        [tabAll, tabTax, tabJob].forEach(btn => btn.classList.remove('active'));
        element.classList.add('active');
        pages.sales = 1;
        loadSalesBillsList();
    }

    if (tabAll) tabAll.addEventListener('click', () => selectSalesTypeTab('All', tabAll));
    if (tabTax) tabTax.addEventListener('click', () => selectSalesTypeTab('Tax Invoice', tabTax));
    if (tabJob) tabJob.addEventListener('click', () => selectSalesTypeTab('Job Challan', tabJob));

    window.toggleFilterPanel = function() {
        const drawer = document.getElementById('filterDrawer');
        drawer.classList.toggle('active');
    };

    window.resetFilters = function() {
        document.getElementById('filterForm').reset();
        pages.sales = 1;
        loadSalesBillsList();
    };

    async function loadSalesBillsList() {
        const search = document.getElementById('filterSearch').value;
        const customerId = document.getElementById('filterCustomer').value;
        const status = document.getElementById('filterStatus').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        const limit = parseInt(document.getElementById('paginationLimit').value);
        const page = pages.sales;

        try {
            const url = `auth/sales_bills_crud.php?action=read&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}&customer_id=${customerId}&status=${status}&date_from=${dateFrom}&date_to=${dateTo}`;
            const res = await fetch(url);
            const data = await res.json();

            if (data.success) {
                const tbody = document.querySelector('#salesBillsTable tbody');
                tbody.innerHTML = '';

                let bills = data.data;
                if (activeSalesTypeTab === 'Job Challan') {
                    bills = [];
                }

                if (bills.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">No records found.</td></tr>`;
                    updateTotalsFooter(0, 0, 0);
                    renderSalesPaginationUI(0, page, limit);
                    return;
                }

                let sumTotalAmount = 0.00;
                let sumPendingAmount = 0.00;

                bills.forEach(bill => {
                    const tr = document.createElement('tr');
                    
                    let statusBadge = '';
                    if (bill.status === 'PAID') {
                        statusBadge = '<span class="badge badge-paid">PAID</span>';
                    } else if (bill.status === 'PARTIAL') {
                        statusBadge = '<span class="badge badge-partial">PARTIAL</span>';
                    } else {
                        statusBadge = '<span class="badge badge-unpaid">UNPAID</span>';
                    }

                    let dueDaysDisplay = bill.status === 'PAID' ? '<span>-</span>' : `<span>${bill.due_days_left}</span>`;
                    const pendingAmount = parseFloat(bill.grand_total) - parseFloat(bill.paid_amount);

                    sumTotalAmount += parseFloat(bill.grand_total);
                    sumPendingAmount += pendingAmount;

                    let formattedDate = bill.bill_date;
                    if (bill.bill_date) {
                        const parts = bill.bill_date.split('-');
                        if (parts.length === 3) formattedDate = `${parts[2]}/${parts[1]}/${parts[0]}`;
                    }

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
                                <button class="action-btn-circle" onclick="alert('Downloading invoice PDF...')" title="Download PDF"><i class="fa-solid fa-download"></i></button>
                                <button class="action-btn-circle" id="whatsapp-${bill.id}" title="Share Invoice"><i class="fa-brands fa-whatsapp text-success"></i></button>
                                <button class="action-btn-circle" onclick="editSalesBill(${bill.id})" title="Edit Invoice"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="action-btn-circle text-danger" onclick="deleteSalesBill(${bill.id})" title="Delete Invoice"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);

                    const waBtn = tr.querySelector(`#whatsapp-${bill.id}`);
                    if (waBtn) {
                        waBtn.onclick = () => {
                            const msg = encodeURIComponent(`Hi, invoice BIL-${bill.bill_number} total is $${parseFloat(bill.grand_total).toFixed(2)}.`);
                            window.open(`https://api.whatsapp.com/send?text=${msg}`, '_blank');
                        };
                    }
                });

                updateTotalsFooter(bills.length, sumTotalAmount, sumPendingAmount);
                renderSalesPaginationUI(data.total, page, limit);
            }
        } catch (err) {
            console.error(err);
        }
    }

    function updateTotalsFooter(count, total, pending) {
        document.getElementById('tbl-total-count').innerHTML = `<strong>Total ${count}</strong>`;
        document.getElementById('tbl-total-amount').innerHTML = `<strong>${total.toFixed(2)}</strong>`;
        document.getElementById('tbl-total-pending').innerHTML = `<strong>${pending.toFixed(2)}</strong>`;
    }

    function renderSalesPaginationUI(totalItems, page, limit) {
        const nav = document.getElementById('paginationNav');
        const info = document.getElementById('paginationInfo');
        if (!nav) return;

        const totalPages = Math.ceil(totalItems / limit);
        const start = totalItems === 0 ? 0 : (page - 1) * limit + 1;
        const end = Math.min(page * limit, totalItems);

        info.textContent = `Showing ${start} to ${end} of ${totalItems} entries`;

        let buttonsHtml = '<ul class="pagination pagination-sm mb-0">';
        buttonsHtml += `
            <li class="page-item ${page === 1 ? 'disabled' : ''}">
                <button class="page-link border-0 bg-transparent text-muted" onclick="changeSalesPage(${page - 1})"><i class="fa-solid fa-angle-left"></i></button>
            </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            buttonsHtml += `
                <li class="page-item ${i === page ? 'active' : ''}">
                    <button class="page-link rounded-circle mx-1 ${i === page ? 'btn-primary' : 'btn-light border-0 bg-transparent text-muted'}" style="width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-size:12px;" onclick="changeSalesPage(${i})">${i}</button>
                </li>
            `;
        }

        buttonsHtml += `
            <li class="page-item ${page === totalPages || totalPages === 0 ? 'disabled' : ''}">
                <button class="page-link border-0 bg-transparent text-muted" onclick="changeSalesPage(${page + 1})"><i class="fa-solid fa-angle-right"></i></button>
            </li>
        `;
        buttonsHtml += '</ul>';
        nav.innerHTML = buttonsHtml;
    }

    window.changeSalesPage = function(p) {
        pages.sales = p;
        loadSalesBillsList();
    };

    // Form calculations
    window.calculateDueDate = function() {
        const dateInput = document.getElementById('formBillDate');
        const daysInput = document.getElementById('formDueDays');
        const dueOutput = document.getElementById('formDueDate');
        if (!dateInput.value) return;
        const date = new Date(dateInput.value);
        const days = parseInt(daysInput.value) || 0;
        date.setDate(date.getDate() + days);
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        dueOutput.value = `${y}-${m}-${d}`;
    };

    window.addProductRow = function(pId = '', qty = 1, rate = 0, code = '', hsn = '', unit = 'Pcs') {
        const tbody = document.querySelector('#itemsTable tbody');
        const rowId = 'row-' + Date.now() + Math.random().toString(36).substr(2, 5);
        const tr = document.createElement('tr');
        tr.id = rowId;

        let options = '<option value="">-- Select Product --</option>';
        productsList.forEach(p => {
            options += `<option value="${p.id}" ${p.id == pId ? 'selected' : ''}>${p.name}</option>`;
        });

        tr.innerHTML = `
            <td>
                <select name="product_ids[]" class="form-select form-select-sm select-product-item" required>
                    ${options}
                </select>
                <input type="hidden" name="product_names[]" class="input-product-name" value="">
            </td>
            <td><input type="text" name="item_codes[]" class="form-control form-control-sm input-item-code" value="${code}" readonly></td>
            <td><input type="text" name="hsn_codes[]" class="form-control form-control-sm input-hsn-code" value="${hsn}" readonly></td>
            <td><input type="number" step="1" name="quantities[]" class="form-control form-control-sm input-quantity" value="${qty}" min="1" required></td>
            <td><input type="text" name="units[]" class="form-control form-control-sm input-unit" value="${unit}" readonly></td>
            <td><input type="number" step="0.01" name="rates[]" class="form-control form-control-sm input-rate" value="${rate}" min="0" required></td>
            <td style="text-align:right; font-weight:600;" class="td-amount-total">$0.00</td>
            <td><button type="button" class="btn btn-link text-danger p-0" onclick="removeProductRow('${rowId}')"><i class="fa-regular fa-trash-can"></i></button></td>
        `;
        tbody.appendChild(tr);

        const selectEl = tr.querySelector('.select-product-item');
        selectEl.addEventListener('change', () => onProductChange(tr));

        const qtyEl = tr.querySelector('.input-quantity');
        const rateEl = tr.querySelector('.input-rate');
        qtyEl.addEventListener('input', () => calculateGrandTotal());
        rateEl.addEventListener('input', () => calculateGrandTotal());

        if (pId) onProductChange(tr, true);
        else calculateGrandTotal();
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
            nameInput.value = ''; codeInput.value = ''; hsnInput.value = ''; rateInput.value = ''; unitInput.value = 'Pcs';
            calculateGrandTotal();
            return;
        }

        const prod = productsList.find(p => p.id == productId);
        if (prod) {
            nameInput.value = prod.name;
            codeInput.value = prod.item_code || 'ITM-N/A';
            hsnInput.value = prod.hsn_code || 'HSN-N/A';
            unitInput.value = prod.unit || 'Pcs';
            if (!prefill) rateInput.value = prod.price;
        }
        calculateGrandTotal();
    }

    window.calculateGrandTotal = function() {
        const rows = document.querySelectorAll('#itemsTable tbody tr');
        let grossSum = 0.00;
        let totalQty = 0.00;

        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.input-quantity').value) || 0;
            const rate = parseFloat(row.querySelector('.input-rate').value) || 0;
            const amount = qty * rate;
            row.querySelector('.td-amount-total').textContent = '$' + amount.toFixed(2);
            grossSum += amount;
            totalQty += qty;
        });

        const discPercent = parseFloat(document.getElementById('formDiscountPercent').value) || 0;
        const discountAmount = (grossSum * discPercent) / 100;
        const taxableAmount = grossSum - discountAmount;
        const gstPercent = parseFloat(document.getElementById('formGstPercent').value) || 0;
        const applyGst = document.getElementById('formApplyGst').checked;
        const gstAmount = applyGst ? ((taxableAmount * gstPercent) / 100) : 0.00;
        const grandTotal = taxableAmount + gstAmount;

        document.getElementById('summaryTotalQty').textContent = totalQty;
        document.getElementById('summaryGrossAmount').textContent = '$' + grossSum.toFixed(2);
        document.getElementById('summaryDiscountAmount').textContent = '-$' + discountAmount.toFixed(2);
        document.getElementById('summaryTaxableAmount').textContent = '$' + taxableAmount.toFixed(2);
        document.getElementById('summaryGstAmount').textContent = '$' + gstAmount.toFixed(2);
        document.getElementById('summaryGrandTotal').textContent = '$' + grandTotal.toFixed(2);
    };

    function resetInvoiceForm() {
        const form = document.getElementById('createBillForm');
        form.reset();
        document.querySelector('#itemsTable tbody').innerHTML = '';
        calculateDueDate();
        addProductRow();
    }

    const createBillForm = document.getElementById('createBillForm');
    if (createBillForm) {
        createBillForm.addEventListener('submit', async (e) => {
            e.preventDefault();
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
                    toggleSalesFormView('list');
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    // Modal forms submission loaders
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalPayment'));
                    modal.hide();
                    alert(data.message);
                    loadSalesBillsList();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    const quickAddCustomerForm = document.getElementById('quickAddCustomerForm');
    if (quickAddCustomerForm) {
        quickAddCustomerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(quickAddCustomerForm);
            try {
                const res = await fetch('auth/customers_crud.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalCustomer'));
                    modal.hide();
                    alert('Customer added successfully!');
                    await loadCustomersListForSales();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    // Caches data
    async function loadCustomersListForSales() {
        try {
            const res = await fetch('auth/customers_crud.php?action=read&limit=100');
            const data = await res.json();
            if (data.success) {
                const filterSelect = document.getElementById('filterCustomer');
                const formSelect = document.getElementById('formCustomerSelect');
                filterSelect.innerHTML = '<option value="">-- All Customers --</option>';
                formSelect.innerHTML = '<option value="">-- Choose Party --</option>';

                data.data.forEach(cust => {
                    const opt = `<option value="${cust.id}">${cust.name} (${cust.gst_number || 'No GST'})</option>`;
                    filterSelect.innerHTML += opt;
                    formSelect.innerHTML += opt;
                });
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function loadProductsCacheForSales() {
        try {
            const res = await fetch('auth/products_crud.php?action=read&limit=200');
            const data = await res.json();
            if (data.success) productsList = data.data;
        } catch (err) {
            console.error(err);
        }
    }

    window.recordPaymentPrompt = function(billId, maxAmount) {
        document.getElementById('paymentBillId').value = billId;
        document.getElementById('paymentMaxAmount').value = maxAmount.toFixed(2);
        document.getElementById('paymentMaxAmount').setAttribute('max', maxAmount);
        document.getElementById('paymentMaxWarning').textContent = `Max payable amount: $${maxAmount.toFixed(2)}`;
        
        const modal = new bootstrap.Modal(document.getElementById('modalPayment'));
        modal.show();
    };

    window.editSalesBill = function(billId) {
        alert("Sales Bill Edit clicked! (You can create new or delete audit invoice bills to update entries safely.)");
    };

    window.deleteSalesBill = async function(billId) {
        if (!confirm('Are you sure you want to delete this invoice? Stock values will be rolled back.')) return;
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', billId);
            const res = await fetch('auth/sales_bills_crud.php', { method: 'POST', body: formData });
            const data = await res.json();
            alert(data.message);
            loadSalesBillsList();
        } catch (err) {
            console.error(err);
        }
    };

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

    window.viewInvoiceDetails = async function(billId) {
        try {
            const res = await fetch(`auth/sales_bills_crud.php?action=read&limit=100&page=1`);
            const data = await res.json();
            if (data.success) {
                const bill = data.data.find(b => b.id == billId);
                if (!bill) return;

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
                            <div style="display: flex; justify-content: space-between; padding: 4px 10px; border-bottom: 1px solid #eee;">
                                <span>CGST (${halfPercent}%):</span>
                                <span>₹ ${halfAmount}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 4px 10px; border-bottom: 1px solid #eee;">
                                <span>SGST (${halfPercent}%):</span>
                                <span>₹ ${halfAmount}</span>
                            </div>
                        `;
                    } else {
                        // Inter-state: IGST
                        taxBreakdownEl.innerHTML = `
                            <div style="display: flex; justify-content: space-between; padding: 4px 10px; border-bottom: 1px solid #eee;">
                                <span>IGST (${gstPercent.toFixed(1)}%):</span>
                                <span>₹ ${gstAmount.toFixed(2)}</span>
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

                await loadPreviewItemLines(billId);
                await loadPreviewPaymentsHistory(billId);

                const btnShareWhatsApp = document.getElementById('btnShareWhatsApp');
                if (btnShareWhatsApp) {
                    btnShareWhatsApp.onclick = () => {
                        const msg = encodeURIComponent(`Hi, invoice BIL-${bill.bill_number} grand total is ₹ ${grandTotal.toFixed(2)}.`);
                        window.open(`https://api.whatsapp.com/send?text=${msg}`, '_blank');
                    };
                }

                const btnShareEmail = document.getElementById('btnShareEmail');
                if (btnShareEmail) {
                    btnShareEmail.onclick = () => {
                        window.location.href = `mailto:${bill.email || ''}?subject=Tax Invoice ${bill.bill_number}`;
                    };
                }

                const modal = new bootstrap.Modal(document.getElementById('modalInvoice'));
                modal.show();
            }
        } catch (err) {
            console.error(err);
        }
    };

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
                        <td style="border-right: 1px solid #000; text-align: center; border-bottom: 1px solid #eee; padding: 8px;">${sr++}</td>
                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #eee; padding: 8px;"><strong>${item.product_name}</strong></td>
                        <td style="border-right: 1px solid #000; text-align: center; border-bottom: 1px solid #eee; padding: 8px;">${item.hsn_code || '-'}</td>
                        <td style="border-right: 1px solid #000; text-align: center; border-bottom: 1px solid #eee; padding: 8px;">${qty.toFixed(2)} ${item.unit}</td>
                        <td style="border-right: 1px solid #000; text-align: right; border-bottom: 1px solid #eee; padding: 8px;">₹ ${parseFloat(item.rate).toFixed(2)}</td>
                        <td style="text-align:right; border-bottom: 1px solid #eee; padding: 8px;">₹ ${amt.toFixed(2)}</td>
                    `;
                    tbody.appendChild(tr);
                });
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

    async function loadPreviewPaymentsHistory(billId) {
        try {
            const res = await fetch(`auth/payments_crud.php?action=read&sales_bill_id=${billId}`);
            const data = await res.json();
            const timeline = document.getElementById('inv-preview-timeline');
            timeline.innerHTML = '';
            if (data.success && data.data && data.data.length > 0) {
                data.data.forEach(pay => {
                    timeline.innerHTML += `
                        <div class="timeline-item">
                            <div class="timeline-time">${pay.payment_date}</div>
                            <div class="timeline-desc">Received $${parseFloat(pay.amount).toFixed(2)} via ${pay.payment_mode}</div>
                        </div>
                    `;
                });
            } else {
                timeline.innerHTML = '<p class="text-muted small mb-0">No payment records logged.</p>';
            }
        } catch (err) {
            console.error(err);
        }
    }

    // --- 3. Ledger Transactions Controller ---
    async function loadTransactions() {
        const search = document.getElementById('transactionSearch').value;
        const dateFrom = document.getElementById('dateFilterFrom').value;
        const dateTo = document.getElementById('dateFilterTo').value;
        const page = pages.transactions;
        const limit = limits.transactions;

        try {
            const res = await fetch(`auth/transactions_crud.php?action=read&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}&date_from=${dateFrom}&date_to=${dateTo}`);
            const data = await res.json();

            if (data.success) {
                const tbody = document.querySelector('#transactions-table tbody');
                tbody.innerHTML = '';

                data.data.forEach(row => {
                    const tr = document.createElement('tr');
                    
                    let typeBadge = '';
                    if (row.type === 'Sale') typeBadge = '<span class="badge success">Sale</span>';
                    else if (row.type === 'Purchase') typeBadge = '<span class="badge warning">Purchase</span>';
                    else if (row.type === 'Expense') typeBadge = '<span class="badge danger">Expense</span>';
                    else typeBadge = '<span class="badge success" style="background:rgba(59,130,246,0.08); color:#3b82f6;">Payment</span>';

                    tr.innerHTML = `
                        <td>${row.id}</td>
                        <td>${typeBadge}</td>
                        <td>${row.date}</td>
                        <td>${row.description || '-'}</td>
                        <td style="font-weight:700;">$${parseFloat(row.amount).toFixed(2)}</td>
                        <td>
                            <i class="fa-regular fa-pen-to-square row-action-btn edit" onclick="editTransactionItem(${row.id}, '${row.type}', ${row.amount}, '${row.date}', '${row.description}')" title="Edit"></i>
                            <i class="fa-regular fa-trash-can row-action-btn delete" onclick="deleteTransactionItem(${row.id})" title="Delete"></i>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                renderPagination('transactions-pagination', data.total, page, limit, 'transactions');
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function loadTransactionsList(tableId, filterType) {
        try {
            const res = await fetch(`auth/transactions_crud.php?action=read&page=1&limit=50`);
            const data = await res.json();

            if (data.success) {
                const tbody = document.querySelector(`#${tableId} tbody`);
                tbody.innerHTML = '';

                const filtered = data.data.filter(row => row.type === filterType);

                if (filtered.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#9ca3af; padding: 20px;">No records found.</td></tr>`;
                    return;
                }

                filtered.forEach(row => {
                    const tr = document.createElement('tr');
                    if (tableId === 'purchase-bill-table') {
                        tr.innerHTML = `
                            <td><strong>PUR-2026-${String(row.id).padStart(3, '0')}</strong></td>
                            <td>Supplier Ref #${row.id}</td>
                            <td>${row.date}</td>
                            <td style="font-weight:700;">$${parseFloat(row.amount).toFixed(2)}</td>
                            <td><span class="badge warning">Unpaid</span></td>
                            <td>
                                <i class="fa-regular fa-trash-can row-action-btn delete" onclick="deleteTransactionItem(${row.id})" title="Delete"></i>
                            </td>
                        `;
                    } else {
                        tr.innerHTML = `
                            <td>${row.id}</td>
                            <td>${row.description || 'N/A'}</td>
                            <td>${row.date}</td>
                            <td style="font-weight:700;">$${parseFloat(row.amount).toFixed(2)}</td>
                        `;
                    }
                    tbody.appendChild(tr);
                });
            }
        } catch (err) {
            console.error(err);
        }
    }

    const formTransaction = document.getElementById('form-transaction');
    if (formTransaction) {
        formTransaction.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formTransaction);

            try {
                const res = await fetch('auth/transactions_crud.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    closeModal('transaction');
                    alert(data.message);
                    loadTabData(currentTab);
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    // --- 4. Products CRUD Controller ---
    async function loadProducts() {
        const search = document.getElementById('productSearch').value;
        const page = pages.products;
        const limit = limits.products;

        try {
            const res = await fetch(`auth/products_crud.php?action=read&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`);
            const data = await res.json();

            if (data.success) {
                const tbody = document.querySelector('#products-table tbody');
                tbody.innerHTML = '';

                data.data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${row.name}</strong></td>
                        <td>${row.category}</td>
                        <td style="font-weight:700;">$${parseFloat(row.price).toFixed(2)}</td>
                        <td>${row.stock_quantity} units</td>
                        <td>
                            <i class="fa-regular fa-pen-to-square row-action-btn edit" onclick="editProductItem(${row.id}, '${row.name}', '${row.category}', ${row.price}, ${row.stock_quantity})" title="Edit"></i>
                            <i class="fa-regular fa-trash-can row-action-btn delete" onclick="deleteProductItem(${row.id})" title="Delete"></i>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                renderPagination('products-pagination', data.total, page, limit, 'products');
            }
        } catch (err) {
            console.error(err);
        }
    }

    const formProduct = document.getElementById('form-product');
    if (formProduct) {
        formProduct.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formProduct);
            try {
                const res = await fetch('auth/products_crud.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    closeModal('product'); alert(data.message); loadProducts();
                } else alert(data.message);
            } catch (err) {
                console.error(err);
            }
        });
    }

    // --- 5. Customers CRUD Controller ---
    async function loadCustomers() {
        const search = document.getElementById('customerSearch').value;
        const page = pages.customers;
        const limit = limits.customers;

        try {
            const res = await fetch(`auth/customers_crud.php?action=read&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`);
            const data = await res.json();

            if (data.success) {
                const tbody = document.querySelector('#customers-table tbody');
                tbody.innerHTML = '';

                data.data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${row.name}</strong></td>
                        <td>${row.email || '-'}</td>
                        <td>${row.phone || '-'}</td>
                        <td>${row.address || '-'}</td>
                        <td style="font-family: monospace; font-weight: 600;">${row.gst_number || '-'}</td>
                        <td>
                            <i class="fa-regular fa-pen-to-square row-action-btn edit" onclick="editCustomerItem(${row.id}, '${row.name}', '${row.email}', '${row.phone}', '${row.address}', '${row.gst_number}')" title="Edit"></i>
                            <i class="fa-regular fa-trash-can row-action-btn delete" onclick="deleteCustomerItem(${row.id})" title="Delete"></i>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                renderPagination('customers-pagination', data.total, page, limit, 'customers');
            }
        } catch (err) {
            console.error(err);
        }
    }

    const formCustomer = document.getElementById('form-customer-general');
    if (formCustomer) {
        formCustomer.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formCustomer);
            try {
                const res = await fetch('auth/customers_crud.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    closeModal('customer-general'); alert(data.message); loadCustomers();
                } else alert(data.message);
            } catch (err) {
                console.error(err);
            }
        });
    }

    // --- 6. Suppliers CRUD Controller ---
    async function loadSuppliers() {
        const search = document.getElementById('supplierSearch').value;
        const page = pages.suppliers;
        const limit = limits.suppliers;

        try {
            const res = await fetch(`auth/suppliers_crud.php?action=read&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`);
            const data = await res.json();

            if (data.success) {
                const tbody = document.querySelector('#suppliers-table tbody');
                tbody.innerHTML = '';

                data.data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${row.name}</strong></td>
                        <td>${row.email || '-'}</td>
                        <td>${row.phone || '-'}</td>
                        <td>${row.address || '-'}</td>
                        <td style="font-family: monospace; font-weight: 600;">${row.gst_number || '-'}</td>
                        <td>
                            <i class="fa-regular fa-pen-to-square row-action-btn edit" onclick="editSupplierItem(${row.id}, '${row.name}', '${row.email}', '${row.phone}', '${row.address}', '${row.gst_number}')" title="Edit"></i>
                            <i class="fa-regular fa-trash-can row-action-btn delete" onclick="deleteSupplierItem(${row.id})" title="Delete"></i>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                renderPagination('suppliers-pagination', data.total, page, limit, 'suppliers');
            }
        } catch (err) {
            console.error(err);
        }
    }

    const formSupplier = document.getElementById('form-supplier');
    if (formSupplier) {
        formSupplier.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formSupplier);
            try {
                const res = await fetch('auth/suppliers_crud.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    closeModal('supplier'); alert(data.message); loadSuppliers();
                } else alert(data.message);
            } catch (err) {
                console.error(err);
            }
        });
    }

    // --- 7. Reports View Panel ---
    const reportBtns = document.querySelectorAll('.report-btn');
    reportBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            reportBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadReportData(btn.getAttribute('data-report'));
        });
    });

    async function loadReportData(type) {
        const titleLabel = document.getElementById('report-title-label');
        const container = document.getElementById('reports-output-container');
        let displayTitle = 'Profit & Loss Statement';
        if (type === 'daily') displayTitle = 'Daily Ledger Breakdown';
        else if (type === 'monthly') displayTitle = 'Monthly Audit breakdown';
        else if (type === 'yearly') displayTitle = 'Yearly Financial comparison';
        else if (type === 'expense') displayTitle = 'Expense overhead Distribution';

        titleLabel.textContent = displayTitle;
        container.innerHTML = `<p style="text-align:center; color:#9ca3af;">Loading report...</p>`;

        try {
            const res = await fetch(`auth/generate_reports.php?type=${type}`);
            const data = await res.json();

            if (data.success) {
                if (type === 'profit_loss') {
                    const p = data.data;
                    container.innerHTML = `
                        <table class="erp-table">
                            <thead>
                                <tr><th>Particulars</th><th style="text-align:right;">Debit ($)</th><th style="text-align:right;">Credit ($)</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><strong>Gross Sales Revenue</strong></td><td></td><td style="text-align:right; font-weight:600;">$${p.revenue.sales.toFixed(2)}</td></tr>
                                <tr style="background:#f9fafb;"><td><strong>Total Operating Income</strong></td><td></td><td style="text-align:right; font-weight:700; color:var(--color-success);">$${p.revenue.total_revenue.toFixed(2)}</td></tr>
                                <tr><td>Suppliers/Inventory Purchases</td><td style="text-align:right;">$${p.operating_costs.purchases.toFixed(2)}</td><td></td></tr>
                                <tr><td>Operating Overheads & Expenses</td><td style="text-align:right;">$${p.operating_costs.expenses.toFixed(2)}</td><td></td></tr>
                                <tr style="background:#f9fafb;"><td><strong>Total Costs of Sales</strong></td><td style="text-align:right; font-weight:700; color:var(--color-danger);">$${p.operating_costs.total_costs.toFixed(2)}</td><td></td></tr>
                                <tr style="border-top:2px solid var(--border-color); font-size:16px;"><td><strong>NET EARNED PROFIT</strong></td><td></td><td style="text-align:right; font-weight:800; color:${p.net_profit >= 0 ? 'var(--color-success)' : 'var(--color-danger)'};">$${p.net_profit.toFixed(2)}</td></tr>
                            </tbody>
                        </table>
                    `;
                } else if (type === 'expense') {
                    let rowsHtml = '';
                    data.data.forEach(row => {
                        rowsHtml += `<tr><td><strong>${row.category}</strong></td><td>${row.count} payments</td><td style="font-weight:700; text-align:right; color:var(--color-danger);">$${parseFloat(row.total).toFixed(2)}</td></tr>`;
                    });
                    container.innerHTML = `<table class="erp-table"><thead><tr><th>Category</th><th>Frequency</th><th style="text-align:right;">Total ($)</th></tr></thead><tbody>${rowsHtml || '<tr><td colspan="3">No records</td></tr>'}</tbody></table>`;
                } else {
                    let rowsHtml = '';
                    data.data.forEach(row => {
                        const dateLabel = row.date || row.month || row.year;
                        const profit = parseFloat(row.sales) - parseFloat(row.purchases) - parseFloat(row.expenses);
                        rowsHtml += `<tr><td><strong>${dateLabel}</strong></td><td style="color:var(--color-success); font-weight:600;">+$${parseFloat(row.sales).toFixed(2)}</td><td style="color:var(--color-warning); font-weight:600;">-$${parseFloat(row.purchases).toFixed(2)}</td><td style="color:var(--color-danger); font-weight:600;">-$${parseFloat(row.expenses).toFixed(2)}</td><td style="font-weight:700; color:${profit >= 0 ? 'var(--color-success)' : 'var(--color-danger)'};">$${profit.toFixed(2)}</td></tr>`;
                    });
                    container.innerHTML = `<table class="erp-table"><thead><tr><th>Timeline</th><th>Sales (+)</th><th>Purchases (-)</th><th>Expenses (-)</th><th>Profit</th></tr></thead><tbody>${rowsHtml || '<tr><td colspan="5">No records</td></tr>'}</tbody></table>`;
                }
            }
        } catch (err) {
            console.error(err);
        }
    }

    // --- Helper: Render Pagination UI ---
    function renderPagination(containerId, totalItems, currentPage, limit, targetType) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const totalPages = Math.ceil(totalItems / limit);
        const start = totalItems === 0 ? 0 : (currentPage - 1) * limit + 1;
        const end = Math.min(currentPage * limit, totalItems);

        let buttonsHtml = '';
        if (currentPage > 1) {
            buttonsHtml += `<button class="pagination-btn" data-page="${currentPage - 1}">Previous</button>`;
        }
        for (let i = 1; i <= totalPages; i++) {
            buttonsHtml += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }
        if (currentPage < totalPages) {
            buttonsHtml += `<button class="pagination-btn" data-page="${currentPage + 1}">Next</button>`;
        }

        container.innerHTML = `
            <span>Showing ${start} to ${end} of ${totalItems} entries</span>
            <div class="pagination-buttons">${buttonsHtml}</div>
        `;

        container.querySelectorAll('.pagination-btn[data-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                pages[targetType] = parseInt(btn.getAttribute('data-page'));
                loadTabData(currentTab);
            });
        });
    }

    // --- Filter Listeners ---
    const transactionSearch = document.getElementById('transactionSearch');
    if (transactionSearch) transactionSearch.addEventListener('input', () => { pages.transactions = 1; loadTransactions(); });
    const btnApplyDateFilter = document.getElementById('btnApplyDateFilter');
    if (btnApplyDateFilter) btnApplyDateFilter.addEventListener('click', () => { pages.transactions = 1; loadTransactions(); });
    const productSearch = document.getElementById('productSearch');
    if (productSearch) productSearch.addEventListener('input', () => { pages.products = 1; loadProducts(); });
    const customerSearch = document.getElementById('customerSearch');
    if (customerSearch) customerSearch.addEventListener('input', () => { pages.customers = 1; loadCustomers(); });
    const supplierSearch = document.getElementById('supplierSearch');
    if (supplierSearch) supplierSearch.addEventListener('input', () => { pages.suppliers = 1; loadSuppliers(); });

    // Initialize
    async function init() {
        await loadCustomersListForSales();
        await loadProductsCacheForSales();
        
        // Hash routing support
        const hash = window.location.hash;
        if (hash) {
            const targetTab = hash.substring(1); // remove '#'
            const matchedItem = document.querySelector(`.menu-item[data-target="${targetTab}"]`);
            if (matchedItem) {
                switchTab(targetTab);
                return;
            }
        }
        
        loadTabData(currentTab);
    }
    init();

    // Listen to hash changes for page transitions
    window.addEventListener('hashchange', () => {
        const hash = window.location.hash;
        if (hash) {
            const targetTab = hash.substring(1);
            const matchedItem = document.querySelector(`.menu-item[data-target="${targetTab}"]`);
            if (matchedItem) {
                switchTab(targetTab);
            }
        }
    });

    // --- Profile Form Submit Handler ---
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(profileForm);
            try {
                const res = await fetch('auth/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while updating profile.');
            }
        });
    }
});

// --- Modal Helper Functions (Global Scope) ---
window.openCreateModal = function(type, defaultSubtype = '') {
    const form = document.getElementById(`form-${type}`);
    if (form) form.reset();
    document.getElementById(`modal-${type}-title`).textContent = `Add New ${type.charAt(0).toUpperCase() + type.slice(1)}`;
    document.getElementById(`action-${type}`).value = 'create';
    document.getElementById(`id-${type}`).value = '';
    if (type === 'transaction' && defaultSubtype) {
        document.getElementById('type-transaction').value = defaultSubtype;
    }
    document.getElementById(`modal-${type}`).classList.add('active');
};

window.closeModal = function(type) {
    document.getElementById(`modal-${type}`).classList.remove('active');
};

// Edit actions general
window.editTransactionItem = function(id, type, amount, date, description) {
    window.openCreateModal('transaction');
    document.getElementById('modal-transaction-title').textContent = 'Modify Transaction Ledger';
    document.getElementById('action-transaction').value = 'update';
    document.getElementById('id-transaction').value = id;
    document.getElementById('type-transaction').value = type;
    document.getElementById('amount-transaction').value = amount;
    document.getElementById('date-transaction').value = date;
    document.getElementById('description-transaction').value = description;
};

window.deleteTransactionItem = async function(id) {
    if (!confirm('Are you sure you want to delete this ledger entry?')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        const res = await fetch('auth/transactions_crud.php', { method: 'POST', body: formData });
        const data = await res.json();
        alert(data.message);
        document.querySelector('.menu-item.active').click();
    } catch(err) { console.error(err); }
};

window.editProductItem = function(id, name, category, price, stock) {
    window.openCreateModal('product');
    document.getElementById('action-product').value = 'update';
    document.getElementById('id-product').value = id;
    document.getElementById('name-product').value = name;
    document.getElementById('category-product').value = category;
    document.getElementById('price-product').value = price;
    document.getElementById('stock-product').value = stock;
};

window.deleteProductItem = async function(id) {
    if (!confirm('Are you sure you want to delete this catalog item?')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        const res = await fetch('auth/products_crud.php', { method: 'POST', body: formData });
        const data = await res.json();
        alert(data.message);
        document.querySelector('.menu-item.active').click();
    } catch(err) { console.error(err); }
};

window.editCustomerItem = function(id, name, email, phone, address, gst) {
    window.openCreateModal('customer-general');
    document.getElementById('action-customer-general').value = 'update';
    document.getElementById('id-customer-general').value = id;
    document.getElementById('name-customer-general').value = name;
    document.getElementById('email-customer-general').value = email;
    document.getElementById('phone-customer-general').value = phone;
    document.getElementById('address-customer-general').value = address;
    document.getElementById('gst-customer-general').value = gst;
};

window.deleteCustomerItem = async function(id) {
    if (!confirm('Are you sure you want to delete this customer?')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        const res = await fetch('auth/customers_crud.php', { method: 'POST', body: formData });
        const data = await res.json();
        alert(data.message);
        document.querySelector('.menu-item.active').click();
    } catch(err) { console.error(err); }
};

window.editSupplierItem = function(id, name, email, phone, address, gst) {
    window.openCreateModal('supplier');
    document.getElementById('action-supplier').value = 'update';
    document.getElementById('id-supplier').value = id;
    document.getElementById('name-supplier').value = name;
    document.getElementById('email-supplier').value = email;
    document.getElementById('phone-supplier').value = phone;
    document.getElementById('address-supplier').value = address;
    document.getElementById('gst-supplier').value = gst;
};

window.deleteSupplierItem = async function(id) {
    if (!confirm('Are you sure you want to delete this supplier?')) return;
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        const res = await fetch('auth/suppliers_crud.php', { method: 'POST', body: formData });
        const data = await res.json();
        alert(data.message);
        // hello
        document.querySelector('.menu-item.active').click();
    } catch(err) { console.error(err); }
};
