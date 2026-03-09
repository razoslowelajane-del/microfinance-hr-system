document.addEventListener('DOMContentLoaded', () => {
    fetchEmployees();
    lucide.createIcons();
});

async function fetchEmployees() {
    try {
        const response = await fetch('be_employeemaster.php?action=fetch_employees');
        const result = await response.json();

        if (result.success) {
            renderTable(result.data);
            // Update stat cards
            const emps = result.data;
            const el = id => document.getElementById(id);
            if (el('statTotal')) el('statTotal').textContent = emps.length;
            if (el('statRegular')) el('statRegular').textContent = emps.filter(e => e.EmploymentStatus === 'Regular').length;
            if (el('statProbationary')) el('statProbationary').textContent = emps.filter(e => e.EmploymentStatus === 'Probationary').length;

        } else {
            console.error('Failed to fetch employees:', result.message);
        }
    } catch (error) {
        console.error('Error fetching employees:', error);
    }
}

function renderTable(employees) {
    const tbody = document.querySelector('#employeeTable tbody');
    tbody.innerHTML = '';

    if (employees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No employees found</td></tr>';
        return;
    }

    employees.forEach(emp => {
        const initials = emp.FirstName.charAt(0) + emp.LastName.charAt(0);
        const tr = document.createElement('tr');
        tr.className = 'emp-row';
        tr.innerHTML = `
            <td>
                <div class="emp-cell">
                    <div class="emp-avatar">${initials.toUpperCase()}</div>
                    <div>
                        <div class="emp-name">${emp.FirstName} ${emp.LastName}</div>
                        <div class="emp-dept">${emp.EmployeeCode || emp.EmployeeID}</div>
                    </div>
                </div>
            </td>
            <td style="font-size:13px;color:var(--text-secondary)">${emp.PositionName || '—'}</td>
            <td style="font-size:13px;color:var(--text-secondary)">${emp.DepartmentName || '—'}</td>
            <td><span class="badge badge-${getStatusClass(emp.EmploymentStatus)}">${emp.EmploymentStatus || 'Unknown'}</span></td>
            <td style="font-size:13px;color:var(--text-secondary)">${emp.GradeLevel || '—'}</td>
            <td>
                <button class="btn-review" onclick="viewProfile(${emp.EmployeeID})">
                    <i data-lucide="file-user"></i> View File
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    lucide.createIcons();
}

async function viewProfile(id) {
    try {
        const response = await fetch(`be_employeemaster.php?action=get_employee_details&id=${id}`);
        const result = await response.json();

        if (result.success) {
            renderResumeModal(result.data);
            const modal = document.getElementById('employeeModal');
            // Restore full-width for the view modal
            const dlg = modal.querySelector('.modal-dialog');
            if (dlg) dlg.classList.remove('ep-edit-dialog');
            modal.style.display = 'flex';
            modal.classList.add('show');
        } else {
            alert('Failed to load profile: ' + result.message);
        }
    } catch (error) {
        console.error('Error fetching profile:', error);
        alert('An error occurred while loading the profile.');
    }
}

function renderResumeModal(data) {
    const modalBody = document.getElementById('modalBody');
    const modalTitle = document.getElementById('modalTitle');

    // Clear header title content if we want a cleaner look, or keep it
    modalTitle.textContent = ""; // Clearing it because we'll have a close button and header inside

    const initials = data.FirstName.charAt(0) + data.LastName.charAt(0);
    const statusClass = getStatusClass(data.EmploymentStatus);
    const statusColors = { active: '#059669', unverified: '#d97706', inactive: '#dc2626' };
    const statusColor = statusColors[statusClass] || '#6b7280';

    modalBody.style.padding = '0';

    modalBody.innerHTML = `
    <div class="ep-container">

        <!-- Hero Banner -->
        <div class="ep-hero">
            <button class="ep-close" onclick="closeModal()" title="Close">&times;</button>
            <div class="ep-hero-content">
                <div class="ep-avatar-wrap">
                    <div class="ep-avatar">${initials.toUpperCase()}</div>
                    <button class="ep-avatar-edit" onclick="document.getElementById('profileUpload').click()" title="Change photo">
                        <i data-lucide="camera"></i>
                    </button>
                    <input type="file" id="profileUpload" style="display:none" accept="image/*">
                </div>
                <div class="ep-hero-info">
                    <h2 class="ep-name">${data.FirstName} ${data.MiddleName ? data.MiddleName + ' ' : ''}${data.LastName}</h2>
                    <p class="ep-position">${data.PositionName || 'No Position'}</p>
                    <div class="ep-meta">
                        <span class="ep-meta-chip"><i data-lucide="building-2"></i>${data.DepartmentName || 'No Department'}</span>
                        <span class="ep-meta-chip"><i data-lucide="hash"></i>${data.EmployeeCode || data.EmployeeID}</span>
                        <span class="ep-status-badge" style="background:${statusColor}20;color:${statusColor};border:1px solid ${statusColor}40">
                          <span class="ep-status-dot" style="background:${statusColor}"></span>${data.EmploymentStatus || '—'}
                        </span>
                    </div>
                </div>
                <button class="ep-edit-btn" onclick="editEmployee(${data.EmployeeID})">
                    <i data-lucide="pencil"></i> Edit Profile
                </button>
            </div>
        </div>

        <!-- Quick Stats Bar -->
        <div class="ep-stats-bar">
            <div class="ep-stat">
                <i data-lucide="calendar"></i>
                <div><span class="ep-stat-val">${data.HiringDate ? new Date(data.HiringDate).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'}</span><span class="ep-stat-lbl">Date Hired</span></div>
            </div>
            <div class="ep-stat-divider"></div>
            <div class="ep-stat">
                <i data-lucide="layers"></i>
                <div><span class="ep-stat-val">${data.GradeLevel || '—'}</span><span class="ep-stat-lbl">Salary Grade</span></div>
            </div>
            <div class="ep-stat-divider"></div>
            <div class="ep-stat">
                <i data-lucide="mail"></i>
                <div><span class="ep-stat-val" style="font-size:12px">${data.WorkEmail || '—'}</span><span class="ep-stat-lbl">Work Email</span></div>
            </div>
            <div class="ep-stat-divider"></div>
            <div class="ep-stat">
                <i data-lucide="phone"></i>
                <div><span class="ep-stat-val">${data.PhoneNumber || '—'}</span><span class="ep-stat-lbl">Phone</span></div>
            </div>
        </div>

        <!-- Section Grid -->
        <div class="ep-body">

            <!-- Personal Information -->
            <div class="ep-section">
                <div class="ep-section-hdr ep-hdr-blue"><i data-lucide="user"></i> Personal Information</div>
                <div class="ep-fields">
                    <div class="ep-field"><label>Date of Birth</label><span>${data.DateOfBirth || '—'}</span></div>
                    <div class="ep-field"><label>Gender</label><span>${data.Gender || '—'}</span></div>
                    <div class="ep-field"><label>Personal Email</label><span>${data.PersonalEmail || '—'}</span></div>
                    <div class="ep-field full"><label>Permanent Address</label><span>${data.PermanentAddress || '—'}</span></div>
                </div>
            </div>

            <!-- Government Numbers -->
            <div class="ep-section">
                <div class="ep-section-hdr ep-hdr-purple"><i data-lucide="landmark"></i> Government Numbers</div>
                <div class="ep-fields">
                    <div class="ep-field"><label>TIN</label><span>${data.TINNumber || '—'}</span></div>
                    <div class="ep-field"><label>SSS</label><span>${data.SSSNumber || '—'}</span></div>
                    <div class="ep-field"><label>PhilHealth</label><span>${data.PhilHealthNumber || '—'}</span></div>
                    <div class="ep-field"><label>Pag-IBIG</label><span>${data.PagIBIGNumber || '—'}</span></div>
                </div>
            </div>

            <!-- Bank & Compensation -->
            <div class="ep-section">
                <div class="ep-section-hdr ep-hdr-green"><i data-lucide="landmark"></i> Bank & Compensation</div>
                <div class="ep-fields">
                    <div class="ep-field"><label>Bank Name</label><span>${data.BankName || '—'}</span></div>
                    <div class="ep-field"><label>Account Number</label><span>${data.BankAccountNumber || '—'}</span></div>
                    <div class="ep-field"><label>Account Type</label><span>${data.AccountType || '—'}</span></div>
                    <div class="ep-field"><label>Salary Range</label><span>${data.MinSalary ? formatCurrency(data.MinSalary) + ' – ' + formatCurrency(data.MaxSalary) : '—'}</span></div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="ep-section">
                <div class="ep-section-hdr ep-hdr-red"><i data-lucide="heart-pulse"></i> Emergency Contact</div>
                <div class="ep-fields">
                    <div class="ep-field"><label>Contact Name</label><span>${data.ContactName || '—'}</span></div>
                    <div class="ep-field"><label>Relationship</label><span>${data.Relationship || '—'}</span></div>
                    <div class="ep-field full"><label>Phone</label><span>${data.EmergencyPhone || '—'}</span></div>
                </div>
            </div>

        </div>
    </div>
    `;
    lucide.createIcons();
}

function getStatusClass(status) {
    if (!status) return 'inactive'; // Default
    switch (status.toLowerCase()) {
        case 'regular': return 'active';
        case 'probationary': return 'unverified';
        case 'resigned': return 'inactive';
        case 'terminated': return 'inactive';
        default: return 'active';
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

// Store current employee data for edit reference if needed
let currentEmployeeData = null;

async function editEmployee(id) {
    try {
        // Switch modal to compact edit width
        const dlg = document.querySelector('#employeeModal .modal-dialog');
        if (dlg) dlg.classList.add('ep-edit-dialog');

        // Reuse get_employee_details to fetch fresh data
        const response = await fetch(`be_employeemaster.php?action=get_employee_details&id=${id}`);
        const result = await response.json();

        if (result.success) {
            currentEmployeeData = result.data;
            renderEditForm(result.data);
        } else {
            alert('Failed to load employee data for editing.');
        }
    } catch (error) {
        console.error('Error fetching data for edit:', error);
        alert('An error occurred.');
    }
}

function renderEditForm(data) {
    const modalBody = document.getElementById('modalBody');
    const initials = data.FirstName.charAt(0) + data.LastName.charAt(0);
    const statusClass = getStatusClass(data.EmploymentStatus);
    const statusColors = { active: '#059669', unverified: '#d97706', inactive: '#dc2626' };
    const statusColor = statusColors[statusClass] || '#6b7280';

    modalBody.style.padding = '0';

    modalBody.innerHTML = `
    <div class="ep-container ep-edit">

        <!-- Hero Banner — edit variant -->
        <div class="ep-hero">
            <button class="ep-close" onclick="closeModal()" title="Close">&times;</button>
            <div class="ep-hero-content">
                <div class="ep-avatar-wrap">
                    <div class="ep-avatar">${initials.toUpperCase()}</div>
                </div>
                <div class="ep-hero-info">
                    <h2 class="ep-name">Edit Profile</h2>
                    <p class="ep-position">${data.FirstName} ${data.LastName}</p>
                    <div class="ep-meta">
                        <span class="ep-meta-chip"><i data-lucide="building-2"></i>${data.DepartmentName || 'No Department'}</span>
                        <span class="ep-meta-chip"><i data-lucide="hash"></i>${data.EmployeeCode || data.EmployeeID}</span>
                        <span class="ep-status-badge" style="background:${statusColor}20;color:${statusColor};border:1px solid ${statusColor}40">
                          <span class="ep-status-dot" style="background:${statusColor}"></span>${data.EmploymentStatus || '—'}
                        </span>
                    </div>
                </div>
                <button class="ep-edit-btn" onclick="viewProfile(${data.EmployeeID})">
                    <i data-lucide="arrow-left"></i> Back to Profile
                </button>
            </div>
        </div>

        <!-- Edit Form Body -->
        <div class="ep-body">
            <form id="editEmployeeForm" onsubmit="submitEditForm(event)" style="display:contents">
                <input type="hidden" name="EmployeeID"   value="${data.EmployeeID}">
                <input type="hidden" name="EmploymentID" value="${data.EmploymentID || ''}">

                <!-- Personal Information -->
                <div class="ep-section">
                    <div class="ep-section-hdr ep-hdr-blue"><i data-lucide="user"></i> Personal Information</div>
                    <div class="ep-fields">
                        <div class="ep-field"><label>First Name</label><input type="text"  name="FirstName"        class="ep-input" value="${data.FirstName || ''}" required></div>
                        <div class="ep-field"><label>Last Name</label> <input type="text"  name="LastName"         class="ep-input" value="${data.LastName || ''}" required></div>
                        <div class="ep-field"><label>Middle Name</label><input type="text" name="MiddleName"       class="ep-input" value="${data.MiddleName || ''}"></div>
                        <div class="ep-field"><label>Date of Birth</label><input type="date" name="DateOfBirth"   class="ep-input" value="${data.DateOfBirth || ''}"></div>
                        <div class="ep-field"><label>Gender</label>
                            <select name="Gender" class="ep-input">
                                <option value="Male"   ${data.Gender === 'Male' ? 'selected' : ''}>Male</option>
                                <option value="Female" ${data.Gender === 'Female' ? 'selected' : ''}>Female</option>
                            </select>
                        </div>
                        <div class="ep-field"><label>Personal Email</label><input type="email" name="PersonalEmail" class="ep-input" value="${data.PersonalEmail || ''}"></div>
                        <div class="ep-field full"><label>Permanent Address</label><input type="text" name="PermanentAddress" class="ep-input" value="${data.PermanentAddress || ''}"></div>
                    </div>
                </div>

                <!-- Government Numbers -->
                <div class="ep-section">
                    <div class="ep-section-hdr ep-hdr-purple"><i data-lucide="landmark"></i> Government Numbers</div>
                    <div class="ep-fields">
                        <div class="ep-field"><label>TIN</label>      <input type="text" name="TINNumber"       class="ep-input" value="${data.TINNumber || ''}"></div>
                        <div class="ep-field"><label>SSS</label>      <input type="text" name="SSSNumber"       class="ep-input" value="${data.SSSNumber || ''}"></div>
                        <div class="ep-field"><label>PhilHealth</label><input type="text" name="PhilHealthNumber" class="ep-input" value="${data.PhilHealthNumber || ''}"></div>
                        <div class="ep-field"><label>Pag-IBIG</label> <input type="text" name="PagIBIGNumber"   class="ep-input" value="${data.PagIBIGNumber || ''}"></div>
                    </div>
                </div>

                <!-- Bank & Compensation -->
                <div class="ep-section">
                    <div class="ep-section-hdr ep-hdr-green"><i data-lucide="credit-card"></i> Bank & Compensation</div>
                    <div class="ep-fields">
                        <div class="ep-field"><label>Bank Name</label>      <input type="text" name="BankName"      class="ep-input" value="${data.BankName || ''}"></div>
                        <div class="ep-field"><label>Account Number</label> <input type="text" name="BankAccountNumber" class="ep-input" value="${data.BankAccountNumber || ''}"></div>
                        <div class="ep-field"><label>Account Type</label>
                            <select name="AccountType" class="ep-input">
                                <option value="">— Select —</option>
                                <option value="Savings"  ${data.AccountType === 'Savings' ? 'selected' : ''}>Savings</option>
                                <option value="Checking" ${data.AccountType === 'Checking' ? 'selected' : ''}>Checking</option>
                                <option value="Payroll"  ${data.AccountType === 'Payroll' ? 'selected' : ''}>Payroll</option>
                            </select>
                        </div>
                        <div class="ep-field">
                            <label>Employment Status</label>
                            <select name="EmploymentStatus" class="ep-input">
                                <option value="Regular"      ${data.EmploymentStatus === 'Regular' ? 'selected' : ''}>Regular</option>
                                <option value="Probationary" ${data.EmploymentStatus === 'Probationary' ? 'selected' : ''}>Probationary</option>
                                <option value="Resigned"     ${data.EmploymentStatus === 'Resigned' ? 'selected' : ''}>Resigned</option>
                                <option value="Terminated"   ${data.EmploymentStatus === 'Terminated' ? 'selected' : ''}>Terminated</option>
                            </select>
                        </div>
                        <div class="ep-field"><label>Date Hired</label>  <input type="date"  name="HiringDate" class="ep-input" value="${data.HiringDate || ''}"></div>
                        <div class="ep-field"><label>Work Email</label>  <input type="email" name="WorkEmail"  class="ep-input" value="${data.WorkEmail || ''}"></div>
                        <div class="ep-field"><label>Phone</label>       <input type="text"  name="PhoneNumber" class="ep-input" value="${data.PhoneNumber || ''}"></div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="ep-section">
                    <div class="ep-section-hdr ep-hdr-red"><i data-lucide="heart-pulse"></i> Emergency Contact</div>
                    <div class="ep-fields">
                        <div class="ep-field"><label>Contact Name</label>  <input type="text" name="ContactName"    class="ep-input" value="${data.ContactName || ''}"></div>
                        <div class="ep-field"><label>Relationship</label>  <input type="text" name="Relationship"   class="ep-input" value="${data.Relationship || ''}"></div>
                        <div class="ep-field full"><label>Phone</label>    <input type="text" name="EmergencyPhone" class="ep-input" value="${data.EmergencyPhone || ''}"></div>
                    </div>
                </div>

            </form>
        </div>

        <!-- Sticky Save Bar -->
        <div class="ep-save-bar">
            <span class="ep-save-hint"><i data-lucide="info"></i> All changes are saved immediately to the employee record.</span>
            <button type="submit" form="editEmployeeForm" class="ep-save-btn">
                <i data-lucide="save"></i> Save Changes
            </button>
        </div>

    </div>
    `;
    lucide.createIcons();
}


async function submitEditForm(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    // Append action
    formData.append('action', 'update_employee');

    try {
        const response = await fetch('be_employeemaster.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            closeModal(); // Hide modal first
            Swal.fire({
                title: 'Success!',
                text: 'Employee updated successfully!',
                icon: 'success',
                confirmButtonColor: '#2ca078'
            }).then(() => {
                fetchEmployees(); // Refresh table
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: result.message || 'Error updating employee',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        }
    } catch (error) {
        console.error('Error updating employee:', error);
        Swal.fire({
            title: 'Error!',
            text: 'An error occurred while saving.',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
    }
}

function openAddEmployeeModal() {
    alert('Add Employee Modal - To Be Implemented');
}

function closeModal() {
    const modal = document.getElementById('employeeModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
}
