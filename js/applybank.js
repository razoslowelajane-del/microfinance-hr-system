/* ============================================================
   applybank.js — ESS Apply Bank Account JS
   Handles: drag-drop, submission upload AJAX
============================================================ */

const AB = {
    actionUrl: 'applybank_action.php',

    form: null,
    dropZone: null,
    fileInput: null,
    dropContent: null,
    filePreview: null,
    submitBtn: null,

    init() {
        this.form = document.getElementById('submitForm');
        this.dropZone = document.getElementById('abDropZone');
        this.fileInput = document.getElementById('filledPdf');
        this.dropContent = document.getElementById('abDropContent');
        this.filePreview = document.getElementById('abFilePreview');
        this.submitBtn = document.getElementById('submitBtn');

        if (!this.form) return;

        // Drag & drop
        if (this.dropZone) {
            this.dropZone.addEventListener('click', () => this.fileInput.click());
            this.dropZone.addEventListener('dragover', e => { e.preventDefault(); this.dropZone.classList.add('ab-dz-hover'); });
            this.dropZone.addEventListener('dragleave', () => this.dropZone.classList.remove('ab-dz-hover'));
            this.dropZone.addEventListener('drop', e => {
                e.preventDefault();
                this.dropZone.classList.remove('ab-dz-hover');
                const file = e.dataTransfer.files[0];
                if (file) this.handleFile(file);
            });
            this.fileInput.addEventListener('change', () => {
                if (this.fileInput.files[0]) this.handleFile(this.fileInput.files[0]);
            });
        }

        this.form.addEventListener('submit', e => {
            e.preventDefault();
            this.submit();
        });
    },

    handleFile(file) {
        if (file.type !== 'application/pdf') {
            Swal.fire({ icon: 'error', title: 'Invalid File', text: 'Only PDF files are accepted.' });
            return;
        }
        if (file.size > 15 * 1024 * 1024) {
            Swal.fire({ icon: 'error', title: 'Too Large', text: 'Maximum file size is 15 MB.' });
            return;
        }
        const dt = new DataTransfer();
        dt.items.add(file);
        this.fileInput.files = dt.files;

        const sizeKB = (file.size / 1024).toFixed(1);
        const sizeLabel = sizeKB >= 1024 ? (sizeKB / 1024).toFixed(1) + ' MB' : sizeKB + ' KB';
        this.dropContent.style.display = 'none';
        this.filePreview.style.display = 'flex';
        this.filePreview.innerHTML = `
      <i data-lucide="file-check" style="color:var(--brand-green)"></i>
      <span class="ab-fp-name">${this.escHtml(file.name)}</span>
      <span class="ab-fp-size">${sizeLabel}</span>
      <button type="button" class="ab-fp-clear">&#x2715;</button>
    `;
        lucide.createIcons();
        this.filePreview.querySelector('.ab-fp-clear').addEventListener('click', () => this.resetDrop());
    },

    resetDrop() {
        if (this.fileInput) this.fileInput.value = '';
        if (this.dropContent) this.dropContent.style.display = '';
        if (this.filePreview) { this.filePreview.style.display = 'none'; this.filePreview.innerHTML = ''; }
    },

    async submit() {
        if (!this.fileInput.files.length) {
            Swal.fire({ icon: 'warning', title: 'No file selected', text: 'Please select the completed PDF before submitting.' });
            return;
        }

        const result = await Swal.fire({
            title: 'Submit your form?',
            text: 'Your completed PDF will be sent to HR for processing.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2ca078',
            confirmButtonText: 'Yes, submit',
        });
        if (!result.isConfirmed) return;

        const fd = new FormData(this.form);
        fd.append('action', 'submit_application');

        const orig = this.submitBtn.innerHTML;
        this.submitBtn.disabled = true;
        this.submitBtn.innerHTML = '<i data-lucide="loader"></i> Submitting…';
        lucide.createIcons();

        try {
            const res = await fetch(this.actionUrl, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                await Swal.fire({ icon: 'success', title: 'Submitted!', text: data.message, timer: 2500, showConfirmButton: false });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Submission Failed', text: data.message });
            }
        } catch {
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server.' });
        } finally {
            this.submitBtn.disabled = false;
            this.submitBtn.innerHTML = orig;
            lucide.createIcons();
        }
    },

    escHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },
};

document.addEventListener('DOMContentLoaded', () => AB.init());