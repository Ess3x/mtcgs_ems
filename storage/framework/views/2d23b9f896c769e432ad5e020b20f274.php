<?php $__env->startSection('title', 'Add Employee'); ?>

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus text-primary"></i> Add New Employee
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo e(route('admin.employee-store')); ?>">
                        <?php echo csrf_field(); ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" id="first-name" name="first_name" class="form-control" value="<?php echo e(old('first_name')); ?>" autocomplete="given-name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" id="last-name" name="last_name" class="form-control" value="<?php echo e(old('last_name')); ?>" autocomplete="family-name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                                <input type="text" name="employee_number" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position</label>
                                <input type="text" name="position" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3" id="branch-field-wrapper">
                                <label class="form-label">Branch</label>
                                <?php if(Auth::user()->admin_type === 'super_admin'): ?>
                                    <select name="branch_id" class="form-control" id="branch-select">
                                        <option value="">-- Select Branch --</option>
                                        <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($branch->id); ?>" data-name="<?php echo e($branch->branch_name); ?>"><?php echo e($branch->branch_name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                <?php else: ?>
                                    <?php $branch = $branches->first(); ?>
                                    <input type="hidden" name="branch_id" value="<?php echo e($branch->id ?? ''); ?>">
                                    <input type="hidden" id="branch-name" value="<?php echo e($branch->branch_name ?? ''); ?>">
                                    <div class="form-control bg-light text-dark"><strong><?php echo e($branch->branch_name ?? 'No Branch Assigned'); ?></strong></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date-of-birth" class="form-label">Date of Birth</label>
                                <input type="date" id="date-of-birth" name="date_of_birth" class="form-control" value="<?php echo e(old('date_of_birth')); ?>" max="<?php echo e(now()->toDateString()); ?>">
                                <small id="age-display" class="form-text text-muted" aria-live="polite">Enter a date of birth to calculate age.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select id="gender" name="gender" class="form-control">
                                    <option value="">-- Select Gender --</option>
                                    <option value="Male" <?php echo e(old('gender') === 'Male' ? 'selected' : ''); ?>>Male</option>
                                    <option value="Female" <?php echo e(old('gender') === 'Female' ? 'selected' : ''); ?>>Female</option>
                                    <option value="Other" <?php echo e(old('gender') === 'Other' ? 'selected' : ''); ?>>Other</option>
                                </select>
                                <small id="gender-suggestion" class="form-text text-muted" aria-live="polite">Gender suggestion is based on the given name only; please confirm.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date Hired <span class="text-danger">*</span></label>
                                <input type="date" name="date_hired" class="form-control" value="<?php echo e(old('date_hired')); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="">-- Select Status --</option>
                                    <option value="New Hire" <?php echo e(old('status') === 'New Hire' ? 'selected' : ''); ?>>New Hire</option>
                                    <option value="1 Year of Service" <?php echo e(old('status') === '1 Year of Service' ? 'selected' : ''); ?>>1 Year of Service</option>
                                    <option value="3+ Years of Service" <?php echo e(old('status') === '3+ Years of Service' ? 'selected' : ''); ?>>3+ Years of Service</option>
                                    <option value="Regular" <?php echo e(old('status') === 'Regular' ? 'selected' : ''); ?> style="display:none;">Regular</option>
                                    <option value="1-2 Years in Service" <?php echo e(old('status') === '1-2 Years in Service' ? 'selected' : ''); ?> style="display:none;">1-2 Years in Service</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-control" required id="role-select">
                                    <option value="employee">Employee</option>
                                    <option value="finance_officer">Finance Officer</option>
                                    <?php if(Auth::user()->admin_type === 'super_admin' || blank(Auth::user()->admin_type)): ?>
                                        <option value="finance_head">Finance Head</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="basic-salary-field-wrapper">
                                <label class="form-label">Basic Salary</label>
                                <input type="number" step="0.01" name="basic_salary" class="form-control" placeholder="0.00">
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Pending Approval:</strong> This employee will be submitted to the System Administrator for approval. Login credentials and account activation will only happen after approval.
                        </div>

                        <div class="mb-3 form-check d-none">
                            <input type="checkbox" name="is_active" class="form-check-input" hidden>
                            <label class="form-check-label text-muted">Hidden approval flag</label>
                        </div>

                        <hr>
                        
                    <div class="d-flex justify-content-between">
                            <a href="<?php echo e(route('admin.employees')); ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <div>
                                <a href="<?php echo e(route('admin.fingerprint-demo')); ?>" class="btn btn-info me-2" target="_blank">
                                    <i class="fas fa-play-circle"></i> View Demo
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Employee
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role-select');
        const branchFieldWrapper = document.getElementById('branch-field-wrapper');
        const basicSalaryFieldWrapper = document.getElementById('basic-salary-field-wrapper');
        const branchSelect = document.getElementById('branch-select');

        function updateFinanceHeadFields() {
            const isFinanceHead = roleSelect && roleSelect.value === 'finance_head';

            if (branchFieldWrapper) {
                branchFieldWrapper.style.display = isFinanceHead ? 'none' : '';
            }

            if (basicSalaryFieldWrapper) {
                basicSalaryFieldWrapper.style.display = isFinanceHead ? 'none' : '';
            }

            if (branchSelect) {
                branchSelect.required = !isFinanceHead;
            }
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', updateFinanceHeadFields);
        }

        updateFinanceHeadFields();
    });
</script>

<?php echo $__env->make('admin.partials.employee-demographics-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views/admin/employee-create.blade.php ENDPATH**/ ?>