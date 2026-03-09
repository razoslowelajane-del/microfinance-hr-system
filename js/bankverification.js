/* ============================================================
   bankverification.js — Manager Bank Verification JS
   Handles: filter tabs, live search, send-to-bank, confirm modal + AJAX
============================================================ */

const BV = {
    actionUrl: 'bankverification_action.php',

    modal: null,
    confirmForm: null,
    appIdInput: null,
    empIdInput: null,
    empBadge: null,

    init() {
        this.modal = document.getElementById('confirmModal');
        this.confirmForm = document.getElementById('confirmForm');
        this.appIdInput = document.getElementById('confirmAppId');
        this.empIdInput = document.getElementById('confirmEmpId');
        this.empBadge = document.getElementById('confirmEmpBadge');

        // ── Modal close
        document.getElementById('closeConfirmModal')?.addEventListener('click', () => this.closeModal());
        document.getElementById('cancelConfirm')?.addEventListener('click', () => this.closeModal());
        this.modal?.addEventListener('click', e => { if (e.target === this.modal) this.closeModal(); });

        // ── "Sent to Bank" buttons
        document.querySelectorAll('.bv-btn-send').forEach(btn => {
            btn.addEventListener('click', () => this.sendToBank(btn.dataset.appId, btn.dataset.empName));
        });

        // ── "Mark Confirmed" buttons
        document.querySelectorAll('.bv-btn-confirm').forEach(btn => {
            btn.addEventListener('click', () => this.openConfirmModal(btn.dataset.appId, btn.dataset.empId, btn.dataset.empName));
        });

        // ── Confirm form submit
        this.confirmForm?.addEventListener('submit', e => {
            e.preventDefault();
            this.saveConfirm();
        });

        // ── Filter tabs
        document.querySelectorAll('.bv-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.bv-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                this.filterTable(tab.dataset.filter);
            });
        });

        // ── Live search
        document.getElementById('tableSearch')?.addEventListener('input', e => {
            this.searchTable(e.target.value.toLowerCase().trim());
        });
    },

    // ── Filter by status
    filterTable(filter) {
        document.querySelectorAll('#submissionsTable tbody tr').forEach(row => {
            const status = row.dataset.status || '';
            row.style.display = (filter === 'all' || status === filter) ? '' : 'none';
        });
    },

    // ── Live search
    searchTable(q) {
        document.querySelectorAll('#submissionsTable tbody tr').forEach(row => {
            const haystack = row.dataset.search || '';
            row.style.display = (!q || haystack.includes(q)) ? '' : 'none';
        });
    },

    // ── Mark Sent to Bank
    async sendToBank(appId, empName) {
        const result = await Swal.fire({
            title: 'Mark as Sent to Bank?',
            html: `Confirm that you have physically sent <b>${this.escHtml(empName)}'s</b> completed form to the bank.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Yes, mark Sent',
        });
        if (!result.isConfirmed) return;

        const fd = new FormData();
        fd.append('action', 'send_to_bank');
        fd.append('app_id', appId);

        try {
            const res = await fetch(this.actionUrl, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                await Swal.fire({ icon: 'success', title: 'Updated!', text: data.message, timer: 2000, showConfirmButton: false });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        } catch {
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server.' });
        }
    },

    // ── Open Confirm Modal
    openConfirmModal(appId, empId, empName) {
        if (!this.modal) return;
        this.appIdInput.value = appId;
        this.empIdInput.value = empId;
        this.empBadge.innerHTML = `<i data-lucide="user"></i> Recording for: <strong>${this.escHtml(empName)}</strong>`;
        lucide.createIcons();
        // Clear account number from previous entry
        const accNumEl = document.getElementById('accountNumberInput');
        if (accNumEl) accNumEl.value = '';
        this.modal.classList.add('bv-modal-show');
        document.body.style.overflow = 'hidden';
    },

    closeModal() {
        if (!this.modal) return;
        this.modal.classList.remove('bv-modal-show');
        document.body.style.overflow = '';
    },

    // ── Submit confirmation with bank details
    async saveConfirm() {
        const fd = new FormData(this.confirmForm);
        fd.append('action', 'confirm_bank');

        const btn = this.confirmForm.querySelector('[type="submit"]');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader"></i> Saving…';
        lucide.createIcons();

        try {
            const res = await fetch(this.actionUrl, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                this.closeModal();
                await Swal.fire({ icon: 'success', title: 'Bank Account Recorded!', text: data.message, timer: 2500, showConfirmButton: false });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Save Failed', text: data.message });
            }
        } catch {
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server.' });
        } finally {
            btn.disabled = false;
            btn.innerHTML = orig;
            lucide.createIcons();
        }
    },

    escHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },
};

document.addEventListener('DOMContentLoaded', () => BV.init());