

<?php $__env->startSection('title', 'Leave Credits Management'); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        .leave-credits-page .card-body {
            overflow-x: auto;
        }

        .leave-credits-table-shell {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .leave-credits-table {
            min-width: 980px;
            margin-bottom: 0;
        }

        .leave-credits-table th,
        .leave-credits-table td {
            white-space: nowrap;
        }

        .leave-credits-table .form-control {
            min-width: 54px;
            width: 100%;
            max-width: 72px;
        }

        .leave-credits-table .employee-cell {
            min-width: 220px;
        }

        .leave-credits-table .branch-cell {
            min-width: 180px;
        }

        @media (max-width: 767.98px) {
            .leave-credits-page .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .leave-credits-table {
                min-width: 860px;
            }

            .leave-credits-table th,
            .leave-credits-table td {
                padding-left: 0.55rem;
                padding-right: 0.55rem;
            }

            .leave-credits-table .form-control {
                max-width: 64px;
            }
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="container-fluid py-4 leave-credits-page">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h2 class="mb-1">Leave Credits Management</h2>
                <p class="text-muted mb-0">Set and update the annual leave credits of active users, including branch admins and finance officers.</p>
            </div>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo e(session('success')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo e(session('error')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong>Active Users</strong>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary branch-segregation-toggle" data-bs-toggle="button" aria-pressed="true">
                        <i class="bi bi-diagram-3-fill me-1"></i> Segregate Branches
                    </button>
                    <span class="badge bg-primary-subtle text-primary"><?php echo e($employees->count()); ?> users</span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php $__empty_1 = true; $__currentLoopData = $groupedEmployees ?? collect(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branchName => $roleGroups): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $branchSectionId = 'branch-' . str_replace([' ', '/', '&'], '-', strtolower($branchName));
                    ?>
                    <div class="border-bottom branch-segregation-section" id="<?php echo e($branchSectionId); ?>">
                        <div class="px-4 py-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo e($branchName); ?></h5>
                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 branch-collapse-toggle" data-bs-toggle="collapse" data-bs-target="#<?php echo e($branchSectionId); ?>-content" aria-expanded="true">
                                Hide
                            </button>
                        </div>

                        <div class="collapse show" id="<?php echo e($branchSectionId); ?>-content">

                        <?php $__currentLoopData = $roleGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roleName => $roleEmployees): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="px-3 py-3">
                                <div class="mb-2 px-2">
                                    <span class="badge bg-secondary-subtle text-dark"><?php echo e($roleName); ?></span>
                                </div>

                                <div class="table-responsive leave-credits-table-shell">
                                    <table class="table table-hover align-middle mb-0 leave-credits-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="employee-cell">Employee</th>
                                                <th class="branch-cell">Branch</th>
                                                <th>Status</th>
                                                <th>Sick</th>
                                                <th>Vacation</th>
                                                <th>Emergency</th>
                                                <th>Birthday</th>
                                                <th>Maternity</th>
                                                <th>Paternity</th>
                                                <th>Cash Charges</th>
                                                <th class="text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $roleEmployees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $balance = $employee->leaveBalance ?? \App\Models\LeaveBalance::firstOrCreate([
                                                        'employee_profile_id' => $employee->id,
                                                        'year' => now()->year,
                                                    ], [
                                                        'sick_leave_used' => 0,
                                                        'vacation_leave_used' => 0,
                                                        'emergency_leave_used' => 0,
                                                        'birthday_leave_used' => 0,
                                                        'maternity_leave_used' => 0,
                                                        'paternity_leave_used' => 0,
                                                    ]);
                                                ?>
                                                <tr>
                                                    <td class="employee-cell">
                                                        <div class="fw-semibold"><?php echo e($employee->first_name); ?> <?php echo e($employee->last_name); ?></div>
                                                        <small class="text-muted"><?php echo e($employee->employee_number ?: 'No employee number'); ?></small>
                                                    </td>
                                                    <td class="branch-cell"><?php echo e($employee->branch?->branch_name ?? 'No branch assigned'); ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary-subtle text-secondary">
                                                            <?php echo e($employee->status ?? 'New Hire'); ?>

                                                        </span>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.5" min="0" max="365" class="form-control form-control-sm" form="leave-form-<?php echo e($employee->id); ?>" name="sick_leave_total" value="<?php echo e((int) ((float) ($balance->sick_leave_total ?? 0))); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.5" min="0" max="365" class="form-control form-control-sm" form="leave-form-<?php echo e($employee->id); ?>" name="vacation_leave_total" value="<?php echo e((int) ((float) ($balance->vacation_leave_total ?? 0))); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.5" min="0" max="365" class="form-control form-control-sm" form="leave-form-<?php echo e($employee->id); ?>" name="emergency_leave_total" value="<?php echo e((int) ((float) ($balance->emergency_leave_total ?? 0))); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.5" min="0" max="365" class="form-control form-control-sm" form="leave-form-<?php echo e($employee->id); ?>" name="birthday_leave_total" value="<?php echo e((int) ((float) ($balance->birthday_leave_total ?? 0))); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.5" min="0" max="365" class="form-control form-control-sm" form="leave-form-<?php echo e($employee->id); ?>" name="maternity_leave_total" value="<?php echo e((int) ((float) ($balance->maternity_leave_total ?? 0))); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.5" min="0" max="365" class="form-control form-control-sm" form="leave-form-<?php echo e($employee->id); ?>" name="paternity_leave_total" value="<?php echo e((int) ((float) ($balance->paternity_leave_total ?? 0))); ?>">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.5" min="0" max="365" class="form-control form-control-sm" form="leave-form-<?php echo e($employee->id); ?>" name="cash_charge_total" value="<?php echo e((int) ((float) ($balance->cash_charge_total ?? 0))); ?>">
                                                    </td>
                                                    <td class="text-end">
                                                        <form id="leave-form-<?php echo e($employee->id); ?>" action="<?php echo e(route('admin.leave-credits.update', $employee)); ?>" method="POST" class="d-inline">
                                                            <?php echo csrf_field(); ?>
                                                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-center text-muted py-4">No active users found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleButton = document.querySelector('.branch-segregation-toggle');
            const branchSections = document.querySelectorAll('.branch-segregation-section');

            if (toggleButton && branchSections.length) {
                toggleButton.addEventListener('click', function () {
                    const isPressed = this.getAttribute('aria-pressed') === 'true';
                    this.setAttribute('aria-pressed', String(!isPressed));

                    branchSections.forEach(function (section) {
                        const content = section.querySelector('.collapse');
                        const collapseButton = section.querySelector('.branch-collapse-toggle');

                        if (content) {
                            if (isPressed) {
                                bootstrap.Collapse.getOrCreateInstance(content).hide();
                                if (collapseButton) {
                                    collapseButton.textContent = 'Show';
                                    collapseButton.setAttribute('aria-expanded', 'false');
                                }
                            } else {
                                bootstrap.Collapse.getOrCreateInstance(content).show();
                                if (collapseButton) {
                                    collapseButton.textContent = 'Hide';
                                    collapseButton.setAttribute('aria-expanded', 'true');
                                }
                            }
                        }
                    });
                });
            }

            document.querySelectorAll('.branch-collapse-toggle').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    const target = document.getElementById(this.getAttribute('data-bs-target').replace('#', ''));
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    this.textContent = isExpanded ? 'Show' : 'Hide';
                    this.setAttribute('aria-expanded', String(!isExpanded));
                    if (target && target.classList.contains('show')) {
                        bootstrap.Collapse.getOrCreateInstance(target).hide();
                    } else if (target) {
                        bootstrap.Collapse.getOrCreateInstance(target).show();
                    }
                });
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\leave-credits.blade.php ENDPATH**/ ?>