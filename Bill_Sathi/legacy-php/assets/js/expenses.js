/**
 * Finance ERP - Expense Tracker Module JavaScript Controller
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- Global State ---
    let activeView = 'list'; // 'list' or 'form'
    let currentPage = 1;
    let suppliersList = [];
    let expensesList = [];
    let activeFormTab = 'amount'; // 'amount' or 'item'
    let itemRowCounter = 0;

    // --- Switch Screen Panels ---
    window.toggleView = function(view) {
        activeView = view;
        const listPanel = document.getElementById('expense-list-panel');
        const formPanel = document.getElementById('expense-form-panel');

        if (view === 'list') {
            listPanel.style.display = 'block';
            formPanel.style.display = 'none';
            loadExpenses();
        } else {
            listPanel.style.display = 'none';
            formPanel.style.display = 'block';
            resetExpenseForm();
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

    // --- Collapsible Filter Panel Toggle ---
    window.toggleFilterPanel = function() {
        const drawer = document.getElementById('filterDrawer');
        const badge = document.getElementById('filterBadge');
        drawer.classList.toggle('active');
        badge.style.display = drawer.classList.contains('active') ? 'block' : 'none';
    };

    window.resetFilters = function() {
        document.getElementById('filterForm').reset();
        currentPage = 1;
        loadExpenses();
    };

    // --- Load Suppliers for dropdowns ---
    async function loadSuppliersList() {
        try {
            const res = await fetch('auth/parties_crud.php?action=read&limit=100');
            const data = await res.json();
            if (data.success) {
                suppliersList = data.data;
                const filterSelect = document.getElementById('filterSupplier');
                const formSelect = document.getElementById('formSupplierSelect');
                
                if (filterSelect) filterSelect.innerHTML = '<option value="">-- All Suppliers --</option>';
                if (formSelect) formSelect.innerHTML = '<option value="">Supplier</option>';

                suppliersList.forEach(sup => {
                    const opt = `<option value="${sup.id}">${sup.name.toUpperCase()}</option>`;
                    if (filterSelect) filterSelect.innerHTML += opt;
                    if (formSelect) formSelect.innerHTML += opt;
                });
            }
        } catch (err) {
            console.error('Failed to load suppliers: ', err);
        }
    }

    // --- Load Expenses List ---
    window.loadExpenses = async function() {
        const searchInput = document.getElementById('filterSearch');
        const search = searchInput ? searchInput.value : '';
        const supplierSelect = document.getElementById('filterSupplier');
        const supplierId = supplierSelect ? supplierSelect.value : '';
        const categorySelect = document.getElementById('filterCategory');
        const category = categorySelect ? categorySelect.value : '';
        const statusSelect = document.getElementById('filterStatus');
        const status = statusSelect ? statusSelect.value : '';
        const dateFromInput = document.getElementById('filterDateFrom');
        const dateFrom = dateFromInput ? dateFromInput.value : '';
        const dateToInput = document.getElementById('filterDateTo');
        const dateTo = dateToInput ? dateToInput.value : '';
        
        const limitSelect = document.getElementById('paginationLimit');
        const limit = limitSelect ? parseInt(limitSelect.value) : 100;
        const page = currentPage;

        try {
            const url = `auth/expenses_crud.php?action=read&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}&supplier_id=${supplierId}&category=${encodeURIComponent(category)}&status=${status}&date_from=${dateFrom}&date_to=${dateTo}`;
            const res = await fetch(url);
            const data = await res.json();

            if (data.success) {
                expensesList = data.data;
                const tbody = document.querySelector('#expensesTable tbody');
                tbody.innerHTML = '';

                if (expensesList.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr>`;
                    updateTotalsFooter(0, 0, 0);
                    renderPaginationUI(0, page, limit);
                    return;
                }

                let sumTotalAmount = 0.00;
                let sumPendingAmount = 0.00;

                expensesList.forEach(exp => {
                    const tr = document.createElement('tr');
                    
                    // Status Badge
                    let statusBadge = '';
                    if (exp.status === 'PAID') {
                        statusBadge = '<span class="badge badge-paid">PAID</span>';
                    } else if (exp.status === 'PARTIAL') {
                        statusBadge = '<span class="badge badge-partial">PARTIAL</span>';
                    } else {
                        statusBadge = '<span class="badge badge-unpaid">UNPAID</span>';
                    }

                    const pendingAmount = parseFloat(exp.amount) - parseFloat(exp.paid_amount);

                    sumTotalAmount += parseFloat(exp.amount);
                    sumPendingAmount += pendingAmount;

                    // Dates formatting (DD/MM/YYYY)
                    let formattedDate = exp.expense_date;
                    if (exp.expense_date) {
                        const parts = exp.expense_date.split('-');
                        if (parts.length === 3) {
                            formattedDate = `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                    }

                    const supplierNameDisplay = exp.supplier_name ? exp.supplier_name.toLowerCase() : '-';

                    tr.innerHTML = `
                        <td class="ps-4">${formattedDate}</td>
                        <td class="fw-semibold text-dark">${exp.category}</td>
                        <td>${supplierNameDisplay}</td>
                        <td style="font-weight:600;">${parseFloat(exp.amount).toFixed(2)}</td>
                        <td style="font-weight:600;">${pendingAmount.toFixed(2)}</td>
                        <td>${statusBadge}</td>
                        <td class="pe-4 text-end">
                            <div class="action-icon-container">
                                <button class="action-btn-circle pay-btn" onclick="recordPaymentPrompt(${exp.id}, ${pendingAmount})" title="Record Payment" ${exp.status === 'PAID' ? 'disabled' : ''}><i class="fa-solid fa-credit-card"></i></button>
                                <button class="action-btn-circle" onclick="viewExpenseDetails(${exp.id})" title="View Details"><i class="fa-solid fa-eye"></i></button>
                                <button class="action-btn-circle" onclick="alert('Viewing receipt details...')" title="Print Receipt"><i class="fa-solid fa-file-circle-plus"></i></button>
                                
                                <div class="dropdown d-inline-block">
                                    <button class="action-btn-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size: 13px;">
                                        <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="editExpense(${exp.id})"><i class="fa-solid fa-pencil me-2"></i> Edit Expense</a></li>
                                        <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="deleteExpense(${exp.id})"><i class="fa-solid fa-trash-can me-2"></i> Delete Expense</a></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                updateTotalsFooter(expensesList.length, sumTotalAmount, sumPendingAmount);
                renderPaginationUI(data.total, page, limit);
            }
        } catch (err) {
            console.error('Failed to load expenses: ', err);
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
        loadExpenses();
    };

    // --- Tab Switch inside Form ---
    window.switchFormTab = function(tabName) {
        activeFormTab = tabName;
        const btnAmount = document.getElementById('btn-tab-amount');
        const btnItem = document.getElementById('btn-tab-item');
        const containerAmount = document.getElementById('formAmountInputContainer');
        const containerItems = document.getElementById('formItemsTableContainer');
        const amountField = document.getElementById('formAmount');

        if (tabName === 'amount') {
            btnAmount.classList.add('active');
            btnItem.classList.remove('active');
            containerAmount.style.display = 'block';
            containerItems.style.display = 'none';
            amountField.readOnly = false;
            amountField.classList.remove('bg-light', 'text-muted');
        } else {
            btnAmount.classList.remove('active');
            btnItem.classList.add('active');
            containerAmount.style.display = 'block'; // Keep it visible but read-only
            containerItems.style.display = 'block';
            amountField.readOnly = true;
            amountField.classList.add('bg-light', 'text-muted');
            
            // Add a default row if empty
            const rows = document.querySelectorAll('#formItemsTable tbody tr');
            if (rows.length === 0) {
                addExpenseItemRow();
            }
            calculateItemsTotal();
        }
    };

    // --- Dynamic Items Row Creation ---
    window.addExpenseItemRow = function(itemData = null) {
        const tbody = document.querySelector('#formItemsTable tbody');
        if (!tbody) return;

        const rowId = `item-row-${itemRowCounter++}`;
        const tr = document.createElement('tr');
        tr.id = rowId;

        tr.innerHTML = `
            <td>
                <input type="text" class="item-desc" placeholder="Enter Description" value="${itemData ? (itemData.description || '') : ''}" required>
            </td>
            <td>
                <input type="number" class="item-qty" step="0.01" placeholder="Qty" value="${itemData ? itemData.quantity : '1'}" oninput="calculateItemRowAmount('${rowId}')" required>
            </td>
            <td>
                <input type="number" class="item-rate" step="0.01" placeholder="Rate" value="${itemData ? itemData.rate : ''}" oninput="calculateItemRowAmount('${rowId}')" required>
            </td>
            <td>
                <input type="number" class="item-amount bg-light text-muted fw-bold" value="${itemData ? itemData.amount : '0.00'}" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn-remove-row" onclick="removeExpenseItemRow('${rowId}')"><i class="fa-solid fa-minus"></i></button>
            </td>
        `;

        tbody.appendChild(tr);
        if (itemData) {
            calculateItemRowAmount(rowId);
        }
    };

    window.removeExpenseItemRow = function(rowId) {
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
            calculateItemsTotal();
        }
    };

    window.calculateItemRowAmount = function(rowId) {
        const row = document.getElementById(rowId);
        if (!row) return;

        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const rate = parseFloat(row.querySelector('.item-rate').value) || 0;
        const amountInput = row.querySelector('.item-amount');

        const amt = qty * rate;
        amountInput.value = amt.toFixed(2);

        calculateItemsTotal();
    };

    window.calculateItemsTotal = function() {
        if (activeFormTab !== 'item') return;

        let total = 0.00;
        const rows = document.querySelectorAll('#formItemsTable tbody tr');
        rows.forEach(row => {
            const amt = parseFloat(row.querySelector('.item-amount').value) || 0;
            total += amt;
        });

        document.getElementById('formAmount').value = total.toFixed(2);
    };

    // --- Inline Payment Log Toggler ---
    window.toggleInlinePaymentBlock = function() {
        const block = document.getElementById('inlinePaymentBlock');
        const text = document.getElementById('paymentLinkText');
        const pDate = document.getElementById('inlinePaymentDate');
        const pAmt = document.getElementById('inlinePaymentAmount');

        if (block.style.display === 'block') {
            block.style.display = 'none';
            text.innerHTML = '<i class="fa-solid fa-hand-holding-dollar"></i> Add Payment';
            pDate.required = false;
            pAmt.required = false;
        } else {
            block.style.display = 'block';
            text.innerHTML = '<i class="fa-solid fa-hand-holding-dollar"></i> Remove Payment';
            pDate.value = document.getElementById('formExpenseDate').value;
            pAmt.value = document.getElementById('formAmount').value;
            pDate.required = true;
            pAmt.required = true;
        }
    };

    // --- Remark character counter ---
    window.updateCharCounter = function(textarea) {
        const length = textarea.value.length;
        document.getElementById('notesCharCounter').textContent = `${length} / 200`;
    };

    // --- Submit Expense Form ---
    window.submitExpenseForm = async function(submitAction) {
        const form = document.getElementById('expenseForm');
        document.getElementById('formSubmitAction').value = submitAction;

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);

        // Capture payment state
        const block = document.getElementById('inlinePaymentBlock');
        const inlinePaid = block.style.display === 'block' ? parseFloat(document.getElementById('inlinePaymentAmount').value) : 0;
        formData.append('paid_amount', inlinePaid);

        // Serialize items if in Item tab
        const items = [];
        if (activeFormTab === 'item') {
            const rows = document.querySelectorAll('#formItemsTable tbody tr');
            let hasError = false;

            rows.forEach(row => {
                const desc = row.querySelector('.item-desc').value;
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const rate = parseFloat(row.querySelector('.item-rate').value) || 0;
                const amt = parseFloat(row.querySelector('.item-amount').value) || 0;

                if (!desc || qty <= 0 || rate <= 0) {
                    hasError = true;
                    return;
                }

                items.push({
                    description: desc,
                    quantity: qty,
                    rate: rate,
                    amount: amt
                });
            });

            if (hasError || items.length === 0) {
                alert('Please provide valid descriptions, qty, and rates for all items.');
                return;
            }
            formData.append('items', JSON.stringify(items));
        }

        try {
            const res = await fetch('auth/expenses_crud.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                alert(data.message);
                if (submitAction === 'save_and_new') {
                    resetExpenseForm();
                } else {
                    toggleView('list');
                }
            } else {
                alert(data.message);
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred while logging the expense.');
        }
    };

    // --- Edit Expense ---
    window.editExpense = async function(expenseId) {
        const exp = expensesList.find(e => e.id == expenseId);
        if (!exp) return;

        toggleView('form');
        document.getElementById('editExpenseId').value = exp.id;
        document.getElementById('formViewTitle').textContent = `Edit Expense`;
        document.getElementById('expenseForm').querySelector("[name='action']").value = 'update';

        document.getElementById('formExpenseDate').value = exp.expense_date;
        document.getElementById('formCategorySelect').value = exp.category;
        document.getElementById('formSupplierSelect').value = exp.supplier_id || '';
        document.getElementById('formAmount').value = exp.amount;
        document.getElementById('formNotes').value = exp.notes || '';
        updateCharCounter(document.getElementById('formNotes'));

        // Hide inline payment for editing to keep it clean (they can use Record Payment modal)
        document.getElementById('inlinePaymentBlock').style.display = 'none';
        document.getElementById('paymentLinkText').innerHTML = '<i class="fa-solid fa-hand-holding-dollar"></i> Add Payment';

        // Load items details
        try {
            const res = await fetch(`auth/expenses_crud.php?action=read_items&id=${expenseId}`);
            const data = await res.json();
            if (data.success && data.data.length > 0) {
                // If it has multiple rows or is not a single row identical to category, set as Itemized
                const isItemized = data.data.length > 1 || data.data[0].description !== exp.category;
                
                document.querySelector('#formItemsTable tbody').innerHTML = '';
                data.data.forEach(item => {
                    addExpenseItemRow(item);
                });

                if (isItemized) {
                    switchFormTab('item');
                } else {
                    switchFormTab('amount');
                }
            }
        } catch (err) {
            console.error('Failed to load items details: ', err);
        }
    };

    // --- Record Payment modal control ---
    window.recordPaymentPrompt = function(expenseId, pendingAmount) {
        const exp = expensesList.find(e => e.id == expenseId);
        if (!exp) return;

        document.getElementById('paymentExpenseId').value = exp.id;
        document.getElementById('pay-meta-category').textContent = exp.category;
        document.getElementById('pay-meta-supplier').textContent = exp.supplier_name ? exp.supplier_name.toUpperCase() : 'Generic';
        document.getElementById('pay-calc-total').textContent = `₹ ${parseFloat(exp.amount).toFixed(2)}`;
        document.getElementById('pay-calc-pending').textContent = `₹ ${pendingAmount.toFixed(2)}`;

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('pay-input-date').value = today;
        document.getElementById('paymentMaxAmount').value = pendingAmount.toFixed(2);

        const modal = new bootstrap.Modal(document.getElementById('modalPayment'));
        modal.show();
    };

    // Payment submission
    const recordPaymentForm = document.getElementById('recordPaymentForm');
    if (recordPaymentForm) {
        recordPaymentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const expId = document.getElementById('paymentExpenseId').value;
            const amtPaid = parseFloat(document.getElementById('paymentMaxAmount').value);

            const exp = expensesList.find(e => e.id == expId);
            if (!exp) return;

            const reqData = new FormData();
            reqData.append('action', 'update');
            reqData.append('id', expId);
            reqData.append('expense_date', exp.expense_date);
            reqData.append('category', exp.category);
            reqData.append('supplier_id', exp.supplier_id || '');
            reqData.append('amount', exp.amount);
            reqData.append('paid_amount', parseFloat(exp.paid_amount) + amtPaid);
            reqData.append('notes', exp.notes || '');

            try {
                const itemRes = await fetch(`auth/expenses_crud.php?action=read_items&id=${expId}`);
                const itemData = await itemRes.json();
                if (itemData.success) {
                    reqData.append('items', JSON.stringify(itemData.data));
                    
                    const res = await fetch('auth/expenses_crud.php', {
                        method: 'POST',
                        body: reqData
                    });
                    const resJSON = await res.json();
                    
                    if (resJSON.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalPayment')).hide();
                        alert('Payment recorded successfully!');
                        loadExpenses();
                    } else {
                        alert(resJSON.message);
                    }
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while saving.');
            }
        });
    }

    // --- Delete Expense ---
    window.deleteExpense = async function(expenseId) {
        if (!confirm('Are you sure you want to delete this expense? This action cannot be undone.')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', expenseId);

            const res = await fetch('auth/expenses_crud.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                alert(data.message);
                loadExpenses();
            } else {
                alert(data.message);
            }
        } catch (err) {
            console.error(err);
        }
    };

    // --- View Details ---
    window.viewExpenseDetails = async function(expenseId) {
        const exp = expensesList.find(e => e.id == expenseId);
        if (!exp) return;

        try {
            const res = await fetch(`auth/expenses_crud.php?action=read_items&id=${expenseId}`);
            const data = await res.json();
            if (data.success) {
                const container = document.getElementById('viewExpenseContent');
                
                let itemsRows = '';
                data.data.forEach((item, idx) => {
                    itemsRows += `
                        <tr>
                            <td>${idx + 1}</td>
                            <td>${item.description}</td>
                            <td class="text-end">${parseFloat(item.quantity).toFixed(2)}</td>
                            <td class="text-end">₹ ${parseFloat(item.rate).toFixed(2)}</td>
                            <td class="text-end">₹ ${parseFloat(item.amount).toFixed(2)}</td>
                        </tr>
                    `;
                });

                container.innerHTML = `
                    <div class="row g-3 small mb-4">
                        <div class="col-6">
                            <span class="text-muted d-block">Supplier:</span>
                            <strong>${exp.supplier_name ? exp.supplier_name.toUpperCase() : 'Generic'}</strong><br>
                            ${exp.address || ''}
                        </div>
                        <div class="col-6 text-end">
                            <span class="text-muted d-block">Details:</span>
                            <strong>Category: ${exp.category}</strong><br>
                            Date: ${exp.expense_date}
                        </div>
                    </div>
                    
                    <table class="table table-bordered table-sm align-middle small mb-4">
                        <thead class="bg-light">
                            <tr>
                                <th style="width:5%;">#</th>
                                <th>Item Description</th>
                                <th class="text-end" style="width:12%;">Qty</th>
                                <th class="text-end" style="width:15%;">Rate</th>
                                <th class="text-end" style="width:15%;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsRows}
                        </tbody>
                    </table>

                    <div class="row g-3 small">
                        <div class="col-7">
                            <span class="text-muted d-block">Notes:</span>
                            <p class="fst-italic">${exp.notes || 'No notes added.'}</p>
                        </div>
                        <div class="col-5">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Total Amount:</span>
                                <span class="fw-semibold">₹ ${parseFloat(exp.amount).toFixed(2)}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 text-success">
                                <span class="text-muted">Paid Amount:</span>
                                <span>₹ ${parseFloat(exp.paid_amount).toFixed(2)}</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold text-primary fs-6 border-top pt-2">
                                <span>Pending Amount:</span>
                                <span>₹ ${(parseFloat(exp.amount) - parseFloat(exp.paid_amount)).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                `;

                new bootstrap.Modal(document.getElementById('modalViewExpense')).show();
            }
        } catch (err) {
            console.error(err);
        }
    };

    function resetExpenseForm() {
        const form = document.getElementById('expenseForm');
        form.reset();
        
        document.getElementById('editExpenseId').value = '';
        document.getElementById('formViewTitle').textContent = 'Add Expense';
        document.getElementById('expenseForm').querySelector("[name='action']").value = 'create';

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('formExpenseDate').value = today;

        document.querySelector('#formItemsTable tbody').innerHTML = '';
        document.getElementById('notesCharCounter').textContent = '0 / 200';
        
        document.getElementById('inlinePaymentBlock').style.display = 'none';
        document.getElementById('paymentLinkText').innerHTML = '<i class="fa-solid fa-hand-holding-dollar"></i> Add Payment';

        switchFormTab('amount');
    }

    // --- Initialization ---
    async function init() {
        await loadSuppliersList();
        await loadExpenses();
    }

    init();

});
