/**
 * transactions.js
 * Client Controller for Payments History & Allocations Modal
 */

let currentPage = 1;
let limit = 10;
let totalPayments = 0;
let modalPayment = null;

document.addEventListener('DOMContentLoaded', () => {
    // Initialize Bootstrap Modal
    const modalEl = document.getElementById('modalAddPayment');
    if (modalEl) {
        modalPayment = new bootstrap.Modal(modalEl);
    }
    
    // Check if the URL has a hash for adding payment
    if (window.location.hash === '#add') {
        openAddPaymentModal();
    }

    // Load initial payments list
    loadPayments();
    
    // Form submit listener
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', handlePaymentSubmit);
    }
    
    // Sidebar Mobile Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
});

// Toggle filter panel
function toggleFilterPanel() {
    const drawer = document.getElementById('filterDrawer');
    if (drawer) {
        drawer.classList.toggle('active');
    }
}

// Reset filters
function resetFilters() {
    document.getElementById('filterForm').reset();
    currentPage = 1;
    updateFilterBadge();
    loadPayments();
}

// Update filter badge if filters are active
function updateFilterBadge() {
    const type = document.getElementById('filterType').value;
    const mode = document.getElementById('filterMode').value;
    const from = document.getElementById('filterDateFrom').value;
    const to = document.getElementById('filterDateTo').value;
    
    const badge = document.getElementById('filterBadge');
    if (badge) {
        if (type || mode || from || to) {
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Load payments
function loadPayments() {
    updateFilterBadge();
    
    const limitSelect = document.getElementById('paginationLimit');
    limit = limitSelect ? parseInt(limitSelect.value) : 10;
    
    const search = document.getElementById('paymentsSearch').value;
    const type = document.getElementById('filterType').value;
    const mode = document.getElementById('filterMode').value;
    const from = document.getElementById('filterDateFrom').value;
    const to = document.getElementById('filterDateTo').value;
    
    const params = new URLSearchParams({
        action: 'read',
        page: currentPage,
        limit: limit,
        search: search,
        type: type,
        mode: mode,
        date_from: from,
        date_to: to
    });
    
    fetch(`auth/transactions_crud.php?${params.toString()}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                renderPaymentsTable(res.data);
                renderPagination(res.total, res.page, res.limit);
            } else {
                alert(res.message || 'Error loading payments.');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Failed to connect to server.');
        });
}

// Render payments table
function renderPaymentsTable(data) {
    const tbody = document.querySelector('#paymentsTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">No payment records found.</td></tr>`;
        return;
    }
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        
        // Format date
        const payDate = new Date(row.payment_date).toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        
        // Amount formatted
        const amt = parseFloat(row.amount).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        // Badges
        let badgeClass = 'badge-credit';
        if (row.tx_type === 'DEBIT') {
            badgeClass = 'badge-debit';
        }
        
        // Link to Invoice
        let txnForText = '-';
        if (row.sales_bill_id) {
            txnForText = `<a href="sales_bills.php?search=${row.sales_bill_number}" class="text-decoration-none fw-bold text-primary">Sale/Bill#${row.sales_bill_number}</a>`;
        } else if (row.purchase_bill_id) {
            txnForText = `<a href="purchase_bills.php?search=${row.purchase_bill_number}" class="text-decoration-none fw-bold text-primary">Purchase#${row.purchase_bill_number}</a>`;
        }
        
        const note = row.notes ? row.notes : '-';
        
        tr.innerHTML = `
            <td>${payDate}</td>
            <td><strong class="text-secondary">${row.reference_number || '-'}</strong></td>
            <td><strong>${row.party_name || '-'}</strong></td>
            <td><strong>₹ ${amt}</strong></td>
            <td><span class="badge ${badgeClass}">${row.tx_type}</span></td>
            <td>${txnForText}</td>
            <td><span class="text-muted text-capitalize">${row.payment_mode}</span></td>
            <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${note}">${note}</td>
            <td>
                <div class="action-icon-container">
                    <button class="action-btn-circle" onclick="window.print()" title="Print Receipt">
                        <i class="fa-solid fa-print"></i>
                    </button>
                    <button class="action-btn-circle delete-btn" onclick="deletePayment(${row.id})" title="Delete Record">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Render pagination
function renderPagination(total, page, limitValue) {
    totalPayments = total;
    const info = document.getElementById('paginationInfo');
    const nav = document.getElementById('paginationNav');
    
    const start = total === 0 ? 0 : (page - 1) * limitValue + 1;
    const end = Math.min(page * limitValue, total);
    
    if (info) {
        info.innerText = `Showing ${start} to ${end} of ${total} entries`;
    }
    
    if (!nav) return;
    
    const totalPages = Math.ceil(total / limitValue);
    nav.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    const ul = document.createElement('ul');
    ul.className = 'pagination pagination-sm mb-0';
    
    // Prev Button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${page === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="changePage(${page - 1})">Previous</a>`;
    ul.appendChild(prevLi);
    
    // Page Numbers
    for (let i = 1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${page === i ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="changePage(${i})">${i}</a>`;
        ul.appendChild(li);
    }
    
    // Next Button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${page === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="javascript:void(0)" onclick="changePage(${page + 1})">Next</a>`;
    ul.appendChild(nextLi);
    
    nav.appendChild(ul);
}

function changePage(p) {
    currentPage = p;
    loadPayments();
}

// Delete payment
function deletePayment(id) {
    if (!confirm('Are you sure you want to delete this payment log? This will reverse the payment allocation on the target invoice(s).')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    fetch('auth/transactions_crud.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert(res.message || 'Payment deleted successfully.');
            loadPayments();
        } else {
            alert(res.message || 'Failed to delete payment.');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Failed to connect to server.');
    });
}

// Add Payment Modal Handling
function openAddPaymentModal() {
    const form = document.getElementById('paymentForm');
    if (form) {
        form.reset();
        document.getElementById('notesCharCounter').innerText = '0 / 250';
        document.getElementById('formPaymentDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('totalPaymentAmountSummary').innerText = '₹ 0.00';
        
        // Clear nested table
        const tbody = document.querySelector('#nestedBillsTable tbody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Please select a party first.</td></tr>`;
        }
    }
    
    // Load parties list
    fetch('auth/transactions_crud.php?action=get_parties')
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const select = document.getElementById('formPartySelect');
                select.innerHTML = '<option value="">-- Choose Party --</option>';
                res.data.forEach(p => {
                    select.innerHTML += `<option value="${p.id}">${p.name}</option>`;
                });
                
                if (modalPayment) {
                    modalPayment.show();
                }
            } else {
                alert('Failed to load parties list.');
            }
        });
}

function onTypeChange() {
    const select = document.getElementById('formPartySelect');
    select.value = '';
    
    const tbody = document.querySelector('#nestedBillsTable tbody');
    if (tbody) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Please select a party first.</td></tr>`;
    }
    document.getElementById('totalPaymentAmountSummary').innerText = '₹ 0.00';
}

function onPartyChange() {
    const partyId = document.getElementById('formPartySelect').value;
    const type = document.querySelector('input[name="type"]:checked').value;
    
    const tbody = document.querySelector('#nestedBillsTable tbody');
    if (!tbody) return;
    
    if (!partyId) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted">Please select a party first.</td></tr>`;
        document.getElementById('totalPaymentAmountSummary').innerText = '₹ 0.00';
        return;
    }
    
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3"><i class="fa-solid fa-spinner fa-spin text-primary"></i> Loading pending bills...</td></tr>`;
    
    fetch(`auth/transactions_crud.php?action=get_unpaid_bills&party_id=${partyId}&type=${type}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                renderNestedBillsTable(res.data);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Failed to load pending bills.</td></tr>`;
            }
        })
        .catch(err => {
            console.error('Error:', err);
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Server connection error.</td></tr>`;
        });
}

function renderNestedBillsTable(bills) {
    const tbody = document.querySelector('#nestedBillsTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (bills.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">No pending or unpaid bills found for this party.</td></tr>`;
        return;
    }
    
    bills.forEach(bill => {
        const tr = document.createElement('tr');
        tr.setAttribute('data-bill-id', bill.id);
        
        const billDate = new Date(bill.bill_date).toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        
        tr.innerHTML = `
            <td><input type="checkbox" class="bill-checkbox form-check-input" onchange="calculateTotalPayment()"></td>
            <td><strong>${bill.bill_number}</strong></td>
            <td>${billDate}</td>
            <td class="pending-balance" data-pending="${bill.pending_amount}">₹ ${parseFloat(bill.pending_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
            <td><input type="number" step="0.01" class="pay-amount-input form-control form-control-sm" value="0.00" min="0" oninput="calculateRowBalance(this)"></td>
            <td><input type="number" step="0.01" class="settle-amount-input form-control form-control-sm" value="0.00" min="0" oninput="calculateRowBalance(this)"></td>
            <td class="remaining-balance">₹ ${parseFloat(bill.pending_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
        `;
        tbody.appendChild(tr);
    });
}

function calculateRowBalance(input) {
    const tr = input.closest('tr');
    if (!tr) return;
    
    // Auto-fill settlement amount if user types pay amount and settlement is still 0
    const payInput = tr.querySelector('.pay-amount-input');
    const settleInput = tr.querySelector('.settle-amount-input');
    
    if (input === payInput && parseFloat(settleInput.value) === 0) {
        settleInput.value = payInput.value;
    }
    
    const pendingVal = parseFloat(tr.querySelector('.pending-balance').getAttribute('data-pending')) || 0;
    const settleVal = parseFloat(settleInput.value) || 0;
    
    const remainingVal = Math.max(0, pendingVal - settleVal);
    tr.querySelector('.remaining-balance').innerText = `₹ ${remainingVal.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
    
    // Check the box if pay amount is greater than 0
    const payVal = parseFloat(payInput.value) || 0;
    const checkbox = tr.querySelector('.bill-checkbox');
    if (payVal > 0) {
        checkbox.checked = true;
    } else {
        checkbox.checked = false;
    }
    
    calculateTotalPayment();
}

function calculateTotalPayment() {
    let total = 0;
    const rows = document.querySelectorAll('#nestedBillsTable tbody tr');
    
    rows.forEach(tr => {
        const checkbox = tr.querySelector('.bill-checkbox');
        if (checkbox && checkbox.checked) {
            const payVal = parseFloat(tr.querySelector('.pay-amount-input').value) || 0;
            total += payVal;
        }
    });
    
    document.getElementById('totalPaymentAmountSummary').innerText = `₹ ${total.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
}

// Form Submit
function handlePaymentSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    const type = document.querySelector('input[name="type"]:checked').value;
    const partyId = document.getElementById('formPartySelect').value;
    
    // Gather allocations
    const allocations = [];
    const rows = document.querySelectorAll('#nestedBillsTable tbody tr');
    
    rows.forEach(tr => {
        const checkbox = tr.querySelector('.bill-checkbox');
        if (checkbox && checkbox.checked) {
            const billId = tr.getAttribute('data-bill-id');
            const payVal = parseFloat(tr.querySelector('.pay-amount-input').value) || 0;
            const settleVal = parseFloat(tr.querySelector('.settle-amount-input').value) || 0;
            
            if (billId && payVal > 0) {
                allocations.push({
                    bill_id: billId,
                    pay_amount: payVal,
                    settlement_amount: settleVal
                });
            }
        }
    });
    
    if (allocations.length === 0) {
        alert('Please check at least one bill and enter a payment amount greater than ₹ 0.');
        return;
    }
    
    formData.append('allocations', JSON.stringify(allocations));
    
    fetch('auth/transactions_crud.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert(res.message || 'Payment allocations saved successfully.');
            if (modalPayment) {
                modalPayment.hide();
            }
            loadPayments();
        } else {
            alert(res.message || 'Failed to record payment.');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Failed to connect to server.');
    });
}

function updateNotesCharCounter(elem) {
    const len = elem.value.length;
    document.getElementById('notesCharCounter').innerText = `${len} / 250`;
}

// Excel Export
function exportToExcel() {
    const table = document.getElementById('paymentsTable');
    if (!table) return;
    
    // We clone the table to strip action column before exporting
    const cloneTable = table.cloneNode(true);
    cloneTable.querySelectorAll('tr').forEach(tr => {
        const cells = tr.querySelectorAll('th, td');
        if (cells.length > 0) {
            // Remove the last column (Action)
            cells[cells.length - 1].remove();
        }
    });
    
    const wb = XLSX.utils.table_to_book(cloneTable, {sheet: "Payments History"});
    XLSX.writeFile(wb, "Payments_History.xlsx");
}
