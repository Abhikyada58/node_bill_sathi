/**
 * Finance ERP - Product Management Module JavaScript Controller
 */

document.addEventListener('DOMContentLoaded', () => {

    // --- Global State ---
    let currentPage = 1;
    let productsList = [];

    // Sidebar Mobile Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    // --- Description Character Counter ---
    window.updateCharCounter = function(textarea, counterId) {
        const len = textarea.value.length;
        document.getElementById(counterId).textContent = `${len} / 250`;
    };

    // --- Load Products List ---
    window.loadProducts = async function() {
        const searchInput = document.getElementById('productSearch');
        const limitSelect = document.getElementById('paginationLimit');
        
        const search = searchInput ? searchInput.value : '';
        const limit = limitSelect ? parseInt(limitSelect.value) : 100;
        const page = currentPage;

        try {
            const url = `auth/products_crud.php?action=read&page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`;
            const res = await fetch(url);
            const data = await res.json();

            if (data.success) {
                productsList = data.data;
                const tbody = document.querySelector('#productsTable tbody');
                tbody.innerHTML = '';

                if (productsList.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No records found.</td></tr>`;
                    renderPaginationUI(0, page, limit);
                    return;
                }

                productsList.forEach(product => {
                    const tr = document.createElement('tr');
                    
                    const rateDisplay = (parseFloat(product.price) > 0) ? parseFloat(product.price).toFixed(2) : '-';
                    const itemCodeDisplay = product.item_code || '-';
                    const hsnCodeDisplay = product.hsn_code || '-';

                    tr.innerHTML = `
                        <td class="fw-semibold text-dark">${product.name.toUpperCase()}</td>
                        <td style="text-align: right; padding-right: 20px;">${itemCodeDisplay}</td>
                        <td style="text-align: right; padding-right: 20px;">${hsnCodeDisplay}</td>
                        <td style="text-align: right; padding-right: 20px;">${rateDisplay}</td>
                        <td class="text-center">${product.unit || 'Pcs'}</td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="action-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size: 13px;">
                                    <li><a class="dropdown-item py-2" href="javascript:void(0)" onclick="editProduct(${product.id})"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit</a></li>
                                    <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="deleteProduct(${product.id})"><i class="fa-solid fa-trash-can me-2"></i> Delete</a></li>
                                </ul>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                renderPaginationUI(data.total, page, limit);
            }
        } catch (err) {
            console.error('Failed to load products:', err);
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
        loadProducts();
    };

    // --- Modal control helpers ---
    window.openAddProductModal = function() {
        const form = document.getElementById('addProductForm');
        if (form) {
            form.reset();
            // Reset character counter
            const counter = document.getElementById('add-counter');
            if (counter) counter.textContent = '0 / 250';
        }
        const modal = new bootstrap.Modal(document.getElementById('modalAddProduct'));
        modal.show();
    };

    // Submit Add Product Form
    const addProductForm = document.getElementById('addProductForm');
    if (addProductForm) {
        addProductForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(addProductForm);

            try {
                const res = await fetch('auth/products_crud.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    const modalEl = document.getElementById('modalAddProduct');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();

                    alert('Product registered successfully!');
                    loadProducts();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while saving.');
            }
        });
    }

    // Load and Open Edit Product Modal
    window.editProduct = function(productId) {
        const product = productsList.find(p => p.id == productId);
        if (!product) {
            alert('Product details not found.');
            return;
        }

        // Populate form fields
        document.getElementById('edit-product-id').value = product.id;
        document.getElementById('edit-name').value = product.name || '';
        document.getElementById('edit-price').value = (parseFloat(product.price) > 0) ? product.price : '';
        document.getElementById('edit-unit').value = product.unit || 'Pcs';
        document.getElementById('edit-gst-percent').value = (product.gst_percent !== null && product.gst_percent !== undefined) ? parseInt(product.gst_percent) : '';
        document.getElementById('edit-item-code').value = product.item_code || '';
        document.getElementById('edit-hsn-code').value = product.hsn_code || '';
        
        const description = product.description || '';
        const descTextarea = document.getElementById('edit-desc');
        if (descTextarea) {
            descTextarea.value = description;
            updateCharCounter(descTextarea, 'edit-counter');
        }

        const modal = new bootstrap.Modal(document.getElementById('modalEditProduct'));
        modal.show();
    };

    // Submit Edit Product Form
    const editProductForm = document.getElementById('editProductForm');
    if (editProductForm) {
        editProductForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(editProductForm);

            try {
                const res = await fetch('auth/products_crud.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    const modalEl = document.getElementById('modalEditProduct');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();

                    alert('Product updated successfully!');
                    loadProducts();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred while updating.');
            }
        });
    }

    // Delete Product record
    window.deleteProduct = async function(productId) {
        if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', productId);

            const res = await fetch('auth/products_crud.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                alert('Product deleted successfully!');
                loadProducts();
            } else {
                alert(data.message);
            }
        } catch (err) {
            console.error(err);
        }
    };

    // --- Init ---
    loadProducts();

});
