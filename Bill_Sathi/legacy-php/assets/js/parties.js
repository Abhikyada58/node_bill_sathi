/**
 * Finance ERP - Manage Party Module JavaScript Controller
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- Global State ---
    let currentPage = 1;
    let partiesList = [];

    // Sidebar Mobile Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // --- Auto extract PAN from GSTIN ---
    window.populatePanFromGst = function(gst, targetId) {
        gst = gst.trim().toUpperCase();
        if (gst.length >= 12) {
            const pan = gst.substring(2, 12);
            document.getElementById(targetId).value = pan;
        }
    };

    // --- Load Parties List ---
    window.loadParties = async function() {
        const search = document.getElementById('partySearch').value;
        const limit = parseInt(document.getElementById('paginationLimit').value);
        const page = currentPage;

        try {
            const url = `auth/parties_crud.php?action=read&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`;
            const res = await fetch(url);
            const data = await res.json();

            if (data.success) {
                partiesList = data.data;
                const tbody = document.querySelector('#partiesTable tbody');
                tbody.innerHTML = '';

                if (partiesList.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No records found.</td></tr>`;
                    renderPaginationUI(0, page, limit);
                    return;
                }

                partiesList.forEach(party => {
                    const tr = document.createElement('tr');
                    
                    // Balance Dues Formatting
                    let balanceHtml = '';
                    const netBal = parseFloat(party.net_balance);
                    if (netBal > 0) {
                        balanceHtml = `<span class="balance-dues receive"><i class="fa-solid fa-arrow-up me-1"></i> ₹ ${netBal.toFixed(2)}</span>`;
                    } else if (netBal < 0) {
                        balanceHtml = `<span class="balance-dues pay"><i class="fa-solid fa-arrow-down me-1"></i> ₹ ${Math.abs(netBal).toFixed(2)}</span>`;
                    } else {
                        balanceHtml = `<span class="balance-dues nodues">No Dues</span>`;
                    }

                    tr.innerHTML = `
                        <td class="fw-semibold text-dark">${party.name.toUpperCase()}</td>
                        <td>${party.gst_number || '-'}</td>
                        <td>${party.pan_number || '-'}</td>
                        <td>${balanceHtml}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="action-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size: 13px;">
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="editParty(${party.id})"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit</a></li>
                                    <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="deleteParty(${party.id})"><i class="fa-solid fa-trash-can me-2"></i> Delete</a></li>
                                </ul>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                renderPaginationUI(data.total, page, limit);
            }
        } catch (err) {
            console.error('Failed to load parties:', err);
        }
    };

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
        loadParties();
    };

    // --- Modal control helpers ---
    window.openAddPartyModal = function() {
        document.getElementById('addPartyForm').reset();
        const modal = new bootstrap.Modal(document.getElementById('modalAddParty'));
        modal.show();
    };

    // Submit Add Party Form
    const addPartyForm = document.getElementById('addPartyForm');
    if (addPartyForm) {
        addPartyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(addPartyForm);

            try {
                const res = await fetch('auth/parties_crud.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    const modalEl = document.getElementById('modalAddParty');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();

                    alert('Party registered successfully!');
                    loadParties();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while saving.');
            }
        });
    }

    // Load and Open Edit Party Modal
    window.editParty = function(partyId) {
        const party = partiesList.find(p => p.id == partyId);
        if (!party) {
            alert('Party details not found.');
            return;
        }

        // Populate form
        document.getElementById('edit-party-id').value = party.id;
        document.getElementById('edit-gst').value = party.gst_number || '';
        document.getElementById('edit-pan').value = party.pan_number || '';
        document.getElementById('edit-name').value = party.name || '';
        document.getElementById('edit-owner-name').value = party.owner_name || '';
        document.getElementById('edit-address').value = party.address || '';
        document.getElementById('edit-state').value = party.state || '';
        document.getElementById('edit-city').value = party.city || '';
        document.getElementById('edit-pincode').value = party.pincode || '';
        document.getElementById('edit-phone').value = party.phone || '';
        document.getElementById('edit-discount').value = party.discount || '0';
        document.getElementById('edit-due-days').value = party.due_days || '45';
        document.getElementById('edit-broker-name').value = party.broker_name || '';
        document.getElementById('edit-broker-mobile').value = party.broker_mobile || '';

        const modal = new bootstrap.Modal(document.getElementById('modalEditParty'));
        modal.show();
    };

    // Submit Edit Party Form
    const editPartyForm = document.getElementById('editPartyForm');
    if (editPartyForm) {
        editPartyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(editPartyForm);

            try {
                const res = await fetch('auth/parties_crud.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    const modalEl = document.getElementById('modalEditParty');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();

                    alert('Party updated successfully!');
                    loadParties();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while updating.');
            }
        });
    }

    // Delete Party record
    window.deleteParty = async function(partyId) {
        if (!confirm('Are you sure you want to delete this party? This action cannot be undone.')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', partyId);

            const res = await fetch('auth/parties_crud.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                alert('Party deleted successfully!');
                loadParties();
            } else {
                alert(data.message);
            }
        } catch (err) {
            console.error(err);
        }
    };

    // --- Init ---
    loadParties();

});
