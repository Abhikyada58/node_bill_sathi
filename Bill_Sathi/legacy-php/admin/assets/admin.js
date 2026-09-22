/**
 * Admin Panel — JavaScript
 * Handles: stats, user table, search/filter, modals, CRUD actions, toasts
 */

'use strict';

/* ── STATE ─────────────────────────────────────────────────── */
const state = {
    page:           1,
    filter:         'all',
    search:         '',
    deleteTarget:   null,   // { id, name }
    validityTarget: null,   // { id, name }
    selectedDays:   null,
};

/* ── DOM SHORTHAND ─────────────────────────────────────────── */
const $  = (id)  => document.getElementById(id);
const $$ = (sel) => document.querySelectorAll(sel);

/* ── AVATAR COLOUR CYCLE ─────────────────────────────────── */
const AVATAR_CLASSES = ['ua-green', 'ua-purple', 'ua-blue', 'ua-orange', 'ua-pink', 'ua-teal'];

/* ── INIT ──────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadUsers();
    bindEvents();
});

/* ── EVENT BINDINGS ───────────────────────────────────────── */
function bindEvents() {
    // Search (debounced)
    const searchEl = $('searchInput');
    if (searchEl) {
        searchEl.addEventListener('input', debounce(() => {
            state.search = searchEl.value.trim();
            state.page   = 1;
            loadUsers();
        }, 350));
    }

    // Filter tabs
    $$('.filter-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            $$('.filter-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            state.filter = tab.dataset.filter;
            state.page   = 1;
            loadUsers();
        });
    });

    // Delete confirm button
    const delBtn = $('confirmDeleteBtn');
    if (delBtn) delBtn.addEventListener('click', executeDelete);

    // Validity confirm button
    const valBtn = $('confirmValidityBtn');
    if (valBtn) valBtn.addEventListener('click', executeSetValidity);

    // Validity preset buttons
    $$('.validity-opt').forEach(opt => {
        opt.addEventListener('click', () => {
            $$('.validity-opt').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            state.selectedDays = parseInt(opt.dataset.days, 10);
            const cd = $('customDate');
            if (cd) cd.value = '';
        });
    });

    // Custom date clears preset selection
    const customDateEl = $('customDate');
    if (customDateEl) {
        customDateEl.addEventListener('input', () => {
            if (customDateEl.value) {
                $$('.validity-opt').forEach(o => o.classList.remove('selected'));
                state.selectedDays = null;
            }
        });
    }

    // Close modals on backdrop click
    $$('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) closeAllModals();
        });
    });

    // Close buttons
    $$('.modal-close').forEach(btn => {
        btn.addEventListener('click', closeAllModals);
    });

    // ESC key closes modals
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeAllModals();
    });
}

/* ── LOAD STATS ───────────────────────────────────────────── */
async function loadStats() {
    try {
        const res  = await fetch('api/get_stats.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        countUp('statTotal',     data.total);
        countUp('statActive',    data.active);
        countUp('statExpired',   data.expired);
        countUp('statSuspended', data.suspended);
    } catch (err) {
        console.error('Stats load failed:', err);
    }
}

function countUp(id, target) {
    const el = $(id);
    if (!el) return;
    const from   = parseInt(el.textContent, 10) || 0;
    const dur    = 650;
    const start  = performance.now();

    function step(now) {
        const t = Math.min((now - start) / dur, 1);
        const v = 1 - Math.pow(1 - t, 3); // ease-out-cubic
        el.textContent = Math.round(from + (target - from) * v);
        if (t < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

/* ── LOAD USERS TABLE ─────────────────────────────────────── */
async function loadUsers() {
    const tbody = $('usersTableBody');
    if (!tbody) return;

    tbody.innerHTML = buildSkeletonRows(6);

    const qs = new URLSearchParams({
        search: state.search,
        filter: state.filter,
        page:   state.page,
    });

    try {
        const res  = await fetch('api/get_users.php?' + qs);
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        setCountBadge(data.total);
        renderRows(data.users, tbody);
        renderPagination(data.page, data.pages, data.total);

    } catch (err) {
        tbody.innerHTML = `
            <tr><td colspan="7">
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <h3>Failed to load users</h3>
                    <p>${escHtml(err.message)}</p>
                </div>
            </td></tr>`;
    }
}

function setCountBadge(total) {
    const el = $('userCountBadge');
    if (el) el.textContent = total + ' user' + (total !== 1 ? 's' : '');
}

/* ── RENDER USER ROWS ─────────────────────────────────────── */
function renderRows(users, tbody) {
    if (!users || users.length === 0) {
        tbody.innerHTML = `
            <tr><td colspan="7">
                <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <h3>No users found</h3>
                    <p>Try a different search term or filter.</p>
                </div>
            </td></tr>`;
        return;
    }

    tbody.innerHTML = users.map((u, i) => {
        const avatarCls  = AVATAR_CLASSES[i % AVATAR_CLASSES.length];
        const initials   = getInitials(u.full_name);
        const statusBadge = `<span class="status-badge ${esc(u.status)}">${cap(u.status)}</span>`;
        const roleBadge   = `<span class="role-badge ${esc(u.role)}">${cap(u.role)}</span>`;
        const created     = fmtDate(u.created_at);
        const validity    = renderValidUntil(u.valid_until);
        const safeName    = escHtml(u.full_name);
        const safeEmail   = escHtml(u.email);

        return `
        <tr data-uid="${u.id}">
            <td>
                <div class="user-info">
                    <div class="user-avatar ${avatarCls}">${initials}</div>
                    <div>
                        <div class="user-name">${safeName}</div>
                        <div class="user-email">${safeEmail}</div>
                    </div>
                </div>
            </td>
            <td>${roleBadge}</td>
            <td><span class="status-cell" id="badge-${u.id}">${statusBadge}</span></td>
            <td><span class="date-text">${created}</span></td>
            <td id="validity-cell-${u.id}">${validity}</td>
            <td>
                <select
                    class="status-select"
                    data-original="${esc(u.status)}"
                    onchange="changeStatus(${u.id}, this.value, this)"
                    title="Change status"
                >
                    <option value="active"    ${u.status === 'active'    ? 'selected' : ''}>✅ Active</option>
                    <option value="expired"   ${u.status === 'expired'   ? 'selected' : ''}>⏱️ Expired</option>
                    <option value="suspended" ${u.status === 'suspended' ? 'selected' : ''}>🚫 Suspended</option>
                </select>
            </td>
            <td>
                <div class="actions-cell">
                    <button
                        class="btn btn-ghost"
                        style="font-size:12px;padding:5px 10px"
                        onclick="openValidityModal(${u.id}, '${safeName.replace(/'/g, "\\'")}')">
                        📅 Validity
                    </button>
                    <button
                        class="btn btn-danger"
                        style="font-size:12px;padding:5px 9px"
                        onclick="openDeleteModal(${u.id}, '${safeName.replace(/'/g, "\\'")}')">
                        🗑️
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

/* renders the "Valid Until" cell */
function renderValidUntil(dateStr) {
    if (!dateStr) return '<span class="date-text none">No expiry</span>';

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const exp  = new Date(dateStr);
    const diff = Math.ceil((exp - today) / 86400000);

    const label = fmtDate(dateStr);
    if (diff < 0)  return `<span class="date-text expired">⛔ ${label}</span>`;
    if (diff <= 7) return `<span class="date-text expiring">⚠️ ${label} (${diff}d)</span>`;
    return `<span class="date-text">${label}</span>`;
}

/* Skeleton rows while loading */
function buildSkeletonRows(n) {
    const widths = [160, 50, 70, 90, 90, 100, 110];
    return Array.from({ length: n }).map(() =>
        `<tr>${widths.map(w =>
            `<td><div class="skeleton" style="height:20px;width:${w}px;border-radius:4px"></div></td>`
        ).join('')}</tr>`
    ).join('');
}

/* ── PAGINATION ──────────────────────────────────────────── */
function renderPagination(page, pages, total) {
    const infoEl = $('paginationInfo');
    const wrapEl = $('paginationWrap');

    const perPage = 20;
    const start   = total === 0 ? 0 : (page - 1) * perPage + 1;
    const end     = Math.min(page * perPage, total);

    if (infoEl) infoEl.textContent = total > 0 ? `Showing ${start}–${end} of ${total}` : 'No results';

    if (!wrapEl) return;
    if (pages <= 1) { wrapEl.innerHTML = ''; return; }

    let html = `<button class="page-btn" onclick="goPage(${page - 1})" ${page === 1 ? 'disabled' : ''}>‹</button>`;

    for (let i = 1; i <= pages; i++) {
        const near = Math.abs(i - page) <= 1 || i === 1 || i === pages;
        if (!near) {
            if (i === 2 || i === pages - 1) html += `<span class="page-btn" style="cursor:default;pointer-events:none">…</span>`;
            continue;
        }
        html += `<button class="page-btn ${i === page ? 'active' : ''}" onclick="goPage(${i})">${i}</button>`;
    }

    html += `<button class="page-btn" onclick="goPage(${page + 1})" ${page === pages ? 'disabled' : ''}>›</button>`;
    wrapEl.innerHTML = html;
}

function goPage(page) {
    state.page = page;
    loadUsers();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ── CHANGE STATUS ───────────────────────────────────────── */
async function changeStatus(userId, newStatus, selectEl) {
    const original = selectEl.dataset.original;
    selectEl.disabled = true;

    try {
        const fd = new FormData();
        fd.append('user_id', userId);
        fd.append('status',  newStatus);

        const res  = await fetch('api/update_status.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        // Update the status badge inline
        const badgeCell = $(`badge-${userId}`);
        if (badgeCell) {
            badgeCell.innerHTML = `<span class="status-badge ${newStatus}">${cap(newStatus)}</span>`;
        }

        selectEl.dataset.original = newStatus;
        toast('success', `Status updated to ${cap(newStatus)}.`);
        loadStats(); // refresh counters

    } catch (err) {
        toast('error', err.message || 'Failed to update status.');
        selectEl.value = original;
    } finally {
        selectEl.disabled = false;
    }
}

/* ── DELETE MODAL ────────────────────────────────────────── */
function openDeleteModal(userId, userName) {
    state.deleteTarget = { id: userId, name: userName };
    const nameEl = $('deleteUserName');
    if (nameEl) nameEl.textContent = userName;
    openModal('deleteModalOverlay');
}

async function executeDelete() {
    if (!state.deleteTarget) return;

    const btn  = $('confirmDeleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="display:inline-block;border-color:rgba(255,255,255,.3);border-top-color:#fff"></span> Deleting…';

    try {
        const fd = new FormData();
        fd.append('user_id', state.deleteTarget.id);

        const res  = await fetch('api/delete_user.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        toast('success', data.message);
        closeAllModals();
        state.page = 1;
        loadUsers();
        loadStats();

    } catch (err) {
        toast('error', err.message || 'Failed to delete user.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '🗑️ Delete Permanently';
    }
}

/* ── VALIDITY MODAL ──────────────────────────────────────── */
function openValidityModal(userId, userName) {
    state.validityTarget = { id: userId, name: userName };
    state.selectedDays   = null;

    const nameEl = $('validityUserName');
    if (nameEl) nameEl.textContent = userName;

    $$('.validity-opt').forEach(o => o.classList.remove('selected'));
    const cd = $('customDate');
    if (cd) cd.value = '';

    openModal('validityModalOverlay');
}

async function executeSetValidity() {
    if (!state.validityTarget) return;

    const customDate = $('customDate')?.value?.trim();

    if (!state.selectedDays && !customDate) {
        toast('warning', 'Please select a duration or enter a custom expiry date.');
        return;
    }

    const btn = $('confirmValidityBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="display:inline-block;border-color:rgba(255,255,255,.3);border-top-color:#fff"></span> Saving…';

    try {
        const fd = new FormData();
        fd.append('user_id', state.validityTarget.id);

        if (customDate) {
            fd.append('custom_date', customDate);
        } else {
            fd.append('days', state.selectedDays);
        }

        const res  = await fetch('api/set_validity.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        toast('success', data.message);

        // Update validity cell inline
        if (data.valid_until) {
            const cell = $(`validity-cell-${state.validityTarget.id}`);
            if (cell) cell.innerHTML = renderValidUntil(data.valid_until);
        }

        closeAllModals();
        loadUsers();
        loadStats();

    } catch (err) {
        toast('error', err.message || 'Failed to set validity.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '✅ Set Validity';
    }
}

/* ── MODAL HELPERS ────────────────────────────────────────── */
function openModal(overlayId) {
    const el = $(overlayId);
    if (el) {
        el.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeAllModals() {
    $$('.modal-overlay').forEach(o => o.classList.remove('show'));
    document.body.style.overflow = '';
    state.deleteTarget   = null;
    state.validityTarget = null;
    state.selectedDays   = null;
}

/* ── TOAST ────────────────────────────────────────────────── */
const TOAST_ICONS = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };

function toast(type, message, duration = 4000) {
    let container = $('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `
        <span class="toast-icon">${TOAST_ICONS[type] || 'ℹ️'}</span>
        <span class="toast-text">${escHtml(message)}</span>`;

    container.appendChild(el);
    el.addEventListener('click', () => dismissToast(el));
    setTimeout(() => dismissToast(el), duration);
}

function dismissToast(el) {
    if (el.classList.contains('out')) return;
    el.classList.add('out');
    el.addEventListener('animationend', () => el.remove(), { once: true });
}

/* ── UTILITIES ────────────────────────────────────────────── */
function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function esc(str) { return escHtml(str); }

function getInitials(name) {
    const parts = String(name ?? '').trim().split(/\s+/);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function cap(str) {
    return String(str ?? '').charAt(0).toUpperCase() + String(str ?? '').slice(1);
}

function fmtDate(dateStr) {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch { return dateStr; }
}
