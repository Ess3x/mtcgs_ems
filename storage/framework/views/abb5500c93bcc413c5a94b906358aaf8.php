<!-- Payroll Deductions Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-calculator me-2"></i> Payroll Deductions & Benefits
                    </h5>
                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#deductionsCollapse">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>

            <div id="deductionsCollapse" class="collapse show">
                <div class="card-body">
                    <!-- Loading State -->
                    <div id="deductionsLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading deduction information...</p>
                    </div>

                    <!-- Deductions Content -->
                    <div id="deductionsContent" style="display: none;">
                        <!-- Mandatory Deductions Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary fw-bold mb-3">
                                    <i class="fas fa-shield-alt me-2"></i> Mandatory Deductions (Based on ₱<?php echo e($profile->basic_salary ? number_format($profile->basic_salary, 2) : 'N/A'); ?> salary)
                                </h6>
                                <div id="mandatoryDeductionsList" class="row">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Current Additional Deductions Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="text-info fw-bold mb-0">
                                        <i class="fas fa-plus-circle me-2"></i> Your Additional Deductions
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDeductionModal">
                                        <i class="fas fa-plus me-1"></i> Add Deduction
                                    </button>
                                </div>
                                <div id="additionalDeductionsList">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Summary Section -->
                        <div class="row mt-4 pt-3 border-top">
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <p class="text-muted small mb-1">Total Mandatory Deductions</p>
                                        <h5 id="totalMandatory" class="text-danger fw-bold">₱ 0.00</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <p class="text-muted small mb-1">Your Additional Deductions</p>
                                        <h5 id="totalAdditional" class="text-warning fw-bold">₱ 0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info Alert -->
                        <div class="alert alert-info mt-3 mb-0" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>
                                Mandatory deductions are calculated automatically based on your salary. You can add optional deductions for additional benefits like extra Pag-IBIG contributions, savings programs, or other approved deductions.
                            </small>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div id="deductionsError" style="display: none;">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <span id="errorMessage">Unable to load deduction information. Please try again.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Deduction Modal -->
<div class="modal fade" id="addDeductionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i> Add Optional Deduction
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Deduction Type Selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Select Deduction Type</label>
                    <div id="deductionTypesGrid" class="row g-2">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>

                <!-- Selected Deduction Details -->
                <div id="selectedDeductionDetails" style="display: none;">
                    <hr>
                    <div class="alert alert-light">
                        <p id="selectedDescription" class="mb-2"></p>
                        <small id="selectedMinMax" class="text-muted"></small>
                    </div>

                    <!-- Amount Input -->
                    <div class="mb-3">
                        <label for="deductionAmount" class="form-label fw-bold">Monthly Amount (₱)</label>
                        <input type="number" class="form-control form-control-lg" id="deductionAmount" 
                               placeholder="Enter amount" min="1" step="0.01">
                        <small class="text-muted d-block mt-1" id="amountValidation"></small>
                    </div>

                    <!-- Description/Notes -->
                    <div class="mb-3">
                        <label for="deductionDescription" class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" id="deductionDescription" rows="2" 
                                  placeholder="e.g., Additional Pag-IBIG for home savings"></textarea>
                    </div>

                    <!-- Calculation Preview -->
                    <div id="calculationPreview" style="display: none;" class="alert alert-info">
                        <small class="text-muted">
                            Based on your <strong>gross pay estimate</strong>, your additional deduction of 
                            <strong id="previewAmount">₱0.00</strong> will reduce your net pay accordingly.
                        </small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitDeductionBtn" style="display: none;">
                    <i class="fas fa-check me-1"></i> Confirm & Add
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.deduction-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.deduction-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.deduction-card.selected {
    border-color: #007bff;
    background-color: #f0f7ff;
}

.mandatory-deduction-item {
    padding: 12px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mandatory-deduction-item .name {
    display: flex;
    align-items: center;
    gap: 10px;
}

.additional-deduction-item {
    padding: 12px;
    background: #fff3cd;
    border-radius: 6px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.additional-deduction-item .amount {
    font-weight: bold;
    color: #856404;
}

.empty-state {
    text-align: center;
    padding: 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 10px;
    opacity: 0.5;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDeductionsData();

    // Event delegation for deduction type selection
    document.addEventListener('click', function(e) {
        if (e.target.closest('.deduction-card')) {
            const card = e.target.closest('.deduction-card');
            selectDeductionType(card);
        }
    });

    // Remove deduction button handlers
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-deduction-btn')) {
            const btn = e.target.closest('.remove-deduction-btn');
            const deductionId = btn.dataset.deductionId;
            removeDeduction(deductionId);
        }
    });

    // Amount input change - update validation and preview
    const amountInput = document.getElementById('deductionAmount');
    if (amountInput) {
        amountInput.addEventListener('change', function() {
            validateAmount();
            updatePreview();
        });
    }
});

function loadDeductionsData() {
    fetch('/api/deductions/preview')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMandatoryDeductions(data.mandatory_deductions);
                displayOptionalExamples(data.optional_examples);
                displayCurrentDeductions(data.current_deductions);
                updateSummary(data.total_mandatory, data.total_current_additional);
                
                document.getElementById('deductionsLoading').style.display = 'none';
                document.getElementById('deductionsContent').style.display = 'block';
            } else {
                showError(data.error || 'Failed to load deduction data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to load deduction data');
        });
}

function displayMandatoryDeductions(deductions) {
    const container = document.getElementById('mandatoryDeductionsList');
    container.innerHTML = '';

    for (const [key, deduction] of Object.entries(deductions)) {
        const col = document.createElement('div');
        col.className = 'col-md-6 mb-3';
        col.innerHTML = `
            <div class="mandatory-deduction-item">
                <div class="name">
                    <i class="fas fa-check-circle text-success"></i>
                    <div>
                        <div class="fw-bold">${deduction.name}</div>
                        <small class="text-muted">${deduction.description}</small>
                    </div>
                </div>
                <div class="amount text-danger fw-bold">₱ ${parseFloat(deduction.amount).toFixed(2)}</div>
            </div>
        `;
        container.appendChild(col);
    }
}

function displayOptionalExamples(examples) {
    const grid = document.getElementById('deductionTypesGrid');
    grid.innerHTML = '';

    examples.forEach(example => {
        const col = document.createElement('div');
        col.className = 'col-sm-6';
        col.innerHTML = `
            <div class="deduction-card card h-100 border-2" data-type="${example.type}" 
                 data-min="${example.min_amount}" data-max="${example.max_amount}" 
                 data-default="${example.default_amount}" data-desc="${example.description}">
                <div class="card-body">
                    <i class="fas ${example.icon} text-${example.color} me-2"></i>
                    <h6 class="card-title">${example.name}</h6>
                    <small class="text-muted">${example.description}</small>
                    <div class="mt-2">
                        <small class="text-muted">₱${example.min_amount} - ₱${example.max_amount}</small>
                    </div>
                </div>
            </div>
        `;
        grid.appendChild(col);
    });
}

function displayCurrentDeductions(deductions) {
    const container = document.getElementById('additionalDeductionsList');
    
    if (deductions.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No additional deductions added yet</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '';
    deductions.forEach(deduction => {
        const item = document.createElement('div');
        item.className = 'additional-deduction-item';
        item.innerHTML = `
            <div>
                <div class="fw-bold">${deduction.description}</div>
                <small class="text-muted">${deduction.type.replace(/_/g, ' ').toUpperCase()}</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="amount">₱ ${parseFloat(deduction.amount).toFixed(2)}</div>
                <button class="btn btn-sm btn-danger remove-deduction-btn" data-deduction-id="${deduction.id}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
        container.appendChild(item);
    });
}

function updateSummary(mandatory, additional) {
    document.getElementById('totalMandatory').textContent = '₱ ' + parseFloat(mandatory).toFixed(2);
    document.getElementById('totalAdditional').textContent = '₱ ' + parseFloat(additional).toFixed(2);
}

function selectDeductionType(card) {
    // Remove previous selection
    document.querySelectorAll('.deduction-card').forEach(c => c.classList.remove('selected'));
    
    // Add selection to clicked card
    card.classList.add('selected');
    
    // Show details
    const type = card.dataset.type;
    const minAmount = parseInt(card.dataset.min);
    const maxAmount = parseInt(card.dataset.max);
    const defaultAmount = parseInt(card.dataset.default);
    const description = card.dataset.desc;

    document.getElementById('selectedDescription').textContent = description;
    document.getElementById('selectedMinMax').textContent = `Recommended: ₱${defaultAmount} | Range: ₱${minAmount} - ₱${maxAmount}`;
    
    const amountInput = document.getElementById('deductionAmount');
    amountInput.min = minAmount;
    amountInput.max = maxAmount;
    amountInput.value = defaultAmount;

    document.getElementById('selectedDeductionDetails').style.display = 'block';
    document.getElementById('submitDeductionBtn').style.display = 'inline-block';
    document.getElementById('submitDeductionBtn').onclick = function() {
        submitDeduction(type);
    };

    validateAmount();
    updatePreview();
}

function validateAmount() {
    const amountInput = document.getElementById('deductionAmount');
    const amount = parseFloat(amountInput.value);
    const minAmount = parseFloat(amountInput.min);
    const maxAmount = parseFloat(amountInput.max);
    const validation = document.getElementById('amountValidation');

    if (isNaN(amount)) {
        validation.textContent = 'Please enter a valid amount';
        validation.style.color = '#dc3545';
        return false;
    } else if (amount < minAmount) {
        validation.textContent = `Minimum amount: ₱${minAmount}`;
        validation.style.color = '#dc3545';
        return false;
    } else if (amount > maxAmount) {
        validation.textContent = `Maximum amount: ₱${maxAmount}`;
        validation.style.color = '#dc3545';
        return false;
    } else {
        validation.textContent = '✓ Valid amount';
        validation.style.color = '#28a745';
        return true;
    }
}

function updatePreview() {
    const amount = parseFloat(document.getElementById('deductionAmount').value) || 0;
    document.getElementById('previewAmount').textContent = '₱' + amount.toFixed(2);
    document.getElementById('calculationPreview').style.display = amount > 0 ? 'block' : 'none';
}

function submitDeduction(type) {
    if (!validateAmount()) {
        alert('Please enter a valid amount');
        return;
    }

    const amount = parseFloat(document.getElementById('deductionAmount').value);
    const description = document.getElementById('deductionDescription').value || null;

    const submitBtn = document.getElementById('submitDeductionBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

    fetch('/api/deductions/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({
            deduction_type: type,
            amount: amount,
            description: description,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reset form
            document.getElementById('addDeductionModal').querySelector('.btn-close').click();
            document.getElementById('deductionAmount').value = '';
            document.getElementById('deductionDescription').value = '';
            
            // Reload deductions
            loadDeductionsData();
            
            // Show success message
            showSuccess('Deduction added successfully!');
        } else {
            alert('Error: ' + (data.error || 'Failed to add deduction'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding deduction');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function removeDeduction(deductionId) {
    if (!confirm('Are you sure you want to remove this deduction?')) {
        return;
    }

    fetch(`/api/deductions/${deductionId}/remove`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadDeductionsData();
            showSuccess('Deduction removed successfully!');
        } else {
            alert('Error: ' + (data.error || 'Failed to remove deduction'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error removing deduction');
    });
}

function showError(message) {
    const errorDiv = document.getElementById('deductionsError');
    document.getElementById('errorMessage').textContent = message;
    document.getElementById('deductionsLoading').style.display = 'none';
    document.getElementById('deductionsContent').style.display = 'none';
    errorDiv.style.display = 'block';
}

function showSuccess(message) {
    // Create a toast or alert notification
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle me-2"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.getElementById('dashboardNotificationContainer').appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
<?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\employee\deductions.blade.php ENDPATH**/ ?>