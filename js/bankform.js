/* ============================================================
   bankform.js — CHC Bank Form Management JS
   Handles: modal open/close, drag-drop, upload AJAX, delete, set-active, status update
============================================================ */

const BF = {
    actionUrl: 'bankform_action.php',

    modal: null,
    openBtn: null,
    closeBtn: null,
    cancelBtn: null,
    form: null,
    dropZone: null,
    fileInput: null,
    dropContent: null,
    filePreview: null,

    init() {
        this.modal = document.getElementById('uploadModal');
        this.openBtn = document.getElementById('openUploadBtn');
        this.closeBtn = document.getElementById('closeUploadModal');
        this.cancelBtn = document.getElementById('cancelUpload');
        this.form = document.getElementById('uploadForm');
        this.dropZone = document.getElementById('dropZone');
        this.fileInput = document.getElementById('pdfFile');
        this.dropContent = document.getElementById('dropContent');
        this.filePreview = document.getElementById('filePreview');

        if (!this.modal) return;

        // Open
        [this.openBtn, document.getElementById('openUploadBtn2')].forEach(btn => {
            if (btn) btn.addEventListener('click', () => this.openModal());
        });

        // Close
        [this.closeBtn, this.cancelBtn].forEach(btn => {
            if (btn) btn.addEventListener('click', () => this.closeModal());
        });
        this.modal.addEventListener('click', e => { if (e.target === this.modal) this.closeModal(); });

        // Drag & drop
        if (this.dropZone) {
            this.dropZone.addEventListener('click', () => this.fileInput.click());
            this.dropZone.addEventListener('dragover', e => { e.preventDefault(); this.dropZone.classList.add('bf-dz-hover'); });
            this.dropZone.addEventListener('dragleave', () => this.dropZone.classList.remove('bf-dz-hover'));
            this.dropZone.addEventListener('drop', e => {
                e.preventDefault();
                this.dropZone.classList.remove('bf-dz-hover');
                const file = e.dataTransfer.files[0];
                if (file) this.handleFile(file);
            });
            this.fileInput.addEventListener('change', () => {
                if (this.fileInput.files[0]) this.handleFile(this.fileInput.files[0]);
            });
        }

        // Form submit — upload master form
        if (this.form) {
            this.form.addEventListener('submit', e => {
                e.preventDefault();
                this.uploadForm();
            });
        }

        // Delete buttons
        document.querySelectorAll('.bf-btn-delete').forEach(btn => {
            btn.addEventListener('click', () => this.deleteForm(btn.dataset.id));
        });

        // Set active buttons
        document.querySelectorAll('.bf-btn-setactive').forEach(btn => {
            btn.addEventListener('click', () => this.setActive(btn.dataset.id));
        });

        // Status select dropdowns (submissions)
        document.querySelectorAll('.bf-status-select').forEach(sel => {
            sel.addEventListener('change', () => this.updateStatus(sel.dataset.appId, sel.value));
        });
    },

    openModal() {
        if (!this.modal) return;
        this.modal.classList.add('bf-modal-show');
        document.body.style.overflow = 'hidden';
    },

    closeModal() {
        if (!this.modal) return;
        this.modal.classList.remove('bf-modal-show');
        document.body.style.overflow = '';
        if (this.form) this.form.reset();
        this.resetDropZone();
    },

    handleFile(file) {
        if (file.type !== 'application/pdf') {
            Swal.fire({ icon: 'error', title: 'Invalid File', text: 'Only PDF files are allowed.' });
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            Swal.fire({ icon: 'error', title: 'File too large', text: 'Maximum file size is 10 MB.' });
            return;
        }
        // Transfer to actual input
        const dt = new DataTransfer();
        dt.items.add(file);
        this.fileInput.files = dt.files;

        // Show preview
        const sizeKB = (file.size / 1024).toFixed(1);
        const sizeLabel = sizeKB >= 1024 ? (sizeKB / 1024).toFixed(1) + ' MB' : sizeKB + ' KB';
        this.dropContent.style.display = 'none';
        this.filePreview.style.display = 'flex';
        this.filePreview.innerHTML = `
      <i data-lucide="file-check" style="color:var(--brand-green)"></i>
      <span class="bf-fp-name">${this.escHtml(file.name)}</span>
      <span class="bf-fp-size">${sizeLabel}</span>
      <button type="button" class="bf-fp-clear">&#x2715;</button>
    `;
        lucide.createIcons();
        this.filePreview.querySelector('.bf-fp-clear').addEventListener('click', () => this.resetDropZone());
    },

    resetDropZone() {
        if (this.fileInput) this.fileInput.value = '';
        if (this.dropContent) this.dropContent.style.display = '';
        if (this.filePreview) { this.filePreview.style.display = 'none'; this.filePreview.innerHTML = ''; }
    },

    async uploadForm() {
        const fd = new FormData(this.form);
        fd.append('action', 'upload_master');

        const btn = this.form.querySelector('[type="submit"]');
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader"></i> Uploading…';
        lucide.createIcons();

        try {
            const res = await fetch(this.actionUrl, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                await Swal.fire({ icon: 'success', title: 'Uploaded!', text: data.message, timer: 2000, showConfirmButton: false });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Upload Failed', text: data.message });
            }
        } catch {
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server.' });
        } finally {
            btn.disabled = false;
            btn.innerHTML = orig;
            lucide.createIcons();
        }
    },

    async deleteForm(id) {
        const result = await Swal.fire({
            title: 'Delete this form?',
            text: 'The PDF file will be permanently removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete',
        });
        if (!result.isConfirmed) return;

        const fd = new FormData();
        fd.append('action', 'delete_form');
        fd.append('form_id', id);

        const res = await fetch(this.actionUrl, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            await Swal.fire({ icon: 'success', title: 'Deleted', timer: 1500, showConfirmButton: false });
            location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    },

    async setActive(id) {
        const fd = new FormData();
        fd.append('action', 'set_active');
        fd.append('form_id', id);
        const res = await fetch(this.actionUrl, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            await Swal.fire({ icon: 'success', title: 'Active form updated!', timer: 1500, showConfirmButton: false });
            location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    },

    async updateStatus(appId, status) {
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('app_id', appId);
        fd.append('status', status);
        const res = await fetch(this.actionUrl, { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Update Failed', text: data.message || 'Could not update status.' });
        } else {
            // Quick toast
            Swal.fire({ icon: 'success', title: 'Status updated', toast: true, position: 'top-end', timer: 1800, showConfirmButton: false });
            location.reload();
        }
    },

    escHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },
};

document.addEventListener('DOMContentLoaded', () => BF.init());