

<?php $__env->startSection('title', 'Shifts and Schedules'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="mb-4"><h2 class="fw-bold mb-1"><i class="fas fa-calendar-days text-primary me-2"></i>Shifts & Schedules</h2><p class="text-muted mb-0">Create work schedules and assign them to active employees.</p></div>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card" id="create-shift"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Create Shift</h5><span class="badge bg-primary">Add more options</span></div><div class="card-body">
                <form method="POST" action="<?php echo e(route('admin.shifts.store')); ?>"><?php echo csrf_field(); ?>
                    <div class="mb-3"><label for="shift-name" class="form-label">Shift Name</label><input id="shift-name" name="name" class="form-control" placeholder="Morning Shift" required></div>
                    <div class="row g-3 mb-3"><div class="col-6"><label for="class-code" class="form-label">Class Code</label><input id="class-code" name="class_code" class="form-control" placeholder="IT 1211"></div><div class="col-6"><label for="room" class="form-label">Room</label><input id="room" name="room" class="form-control" placeholder="Room 4"></div></div>
                    <div class="row g-3 mb-3"><div class="col-6"><label for="shift-start" class="form-label">Start</label><input id="shift-start" name="start_time" type="time" class="form-control" value="07:00" required></div><div class="col-6"><label for="shift-end" class="form-label">End</label><input id="shift-end" name="end_time" type="time" class="form-control" value="17:00" required></div></div>
                    <div class="row g-3 mb-3"><div class="col-6"><label for="break-start" class="form-label">Break Start</label><input id="break-start" name="break_start" type="time" class="form-control" value="12:00"></div><div class="col-6"><label for="break-end" class="form-label">Break End</label><input id="break-end" name="break_end" type="time" class="form-control" value="13:00"></div></div>
                    <fieldset class="mb-3"><legend class="form-label fs-6">Working Days</legend><div class="d-flex flex-wrap gap-3"><?php $__currentLoopData = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label class="form-check"><input class="form-check-input" type="checkbox" name="working_days[]" value="<?php echo e($day); ?>" <?php if($day !== 'Sat' && $day !== 'Sun'): echo 'checked'; endif; ?>><?php echo e($day); ?></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div></fieldset>
                    <button class="btn btn-primary w-100"><i class="fas fa-plus me-2"></i>Create Shift</button>
                </form>
            </div></div>
        </div>
        <div class="col-lg-7"><div class="card"><div class="card-header"><h5 class="mb-0">Available Shifts</h5></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Name</th><th>Class Code</th><th>Room</th><th>Schedule</th><th>Employees</th><th>Action</th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $shifts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shift): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><strong><?php echo e($shift->name); ?></strong></td><td><?php echo e($shift->class_code ?: '--'); ?></td><td><?php echo e($shift->room ?: '--'); ?></td><td><?php echo e(substr($shift->start_time, 0, 5)); ?> - <?php echo e(substr($shift->end_time, 0, 5)); ?><small class="d-block text-muted"><?php echo e($shift->working_days); ?></small></td><td><?php echo e($shift->assigned_employees_count); ?></td><td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editShift<?php echo e($shift->id); ?>" title="Edit shift"><i class="fas fa-pen"></i></button> <form class="d-inline" method="POST" action="<?php echo e(route('admin.shifts.destroy', $shift)); ?>" onsubmit="return confirm('Delete this shift?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn btn-sm btn-outline-danger" title="Delete shift"><i class="fas fa-trash"></i></button></form></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="6" class="text-center text-muted py-4">No shifts created.</td></tr><?php endif; ?></tbody></table></div></div></div>
    </div>
    <div class="card mt-4"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Employee Assignments</h5><a href="#create-shift" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i>Add New Shift</a></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Employee</th><th>Branch</th><th>Assigned Shifts</th><th>Save</th></tr></thead><tbody><?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $employee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td><?php echo e($employee->full_name); ?><small class="d-block text-muted"><?php echo e($employee->employee_number); ?></small></td><td><?php echo e($employee->branch->branch_name ?? 'N/A'); ?></td><td><form id="assignment-<?php echo e($employee->id); ?>" method="POST" action="<?php echo e(route('admin.shifts.assign', $employee)); ?>"><?php echo csrf_field(); ?><div class="d-flex flex-column gap-2"><?php $__currentLoopData = $shifts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shift): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label class="form-check"><input class="form-check-input" type="checkbox" name="shift_ids[]" value="<?php echo e($shift->id); ?>" <?php if($employee->shifts->contains('id', $shift->id)): echo 'checked'; endif; ?>><span class="form-check-label"><?php echo e($shift->name); ?><?php echo e($shift->class_code ? ' | ' . $shift->class_code : ''); ?><?php echo e($shift->room ? ' | ' . $shift->room : ''); ?> <small class="text-muted">(<?php echo e(substr($shift->start_time, 0, 5)); ?>-<?php echo e(substr($shift->end_time, 0, 5)); ?>)</small></span></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div></form></td><td><button form="assignment-<?php echo e($employee->id); ?>" class="btn btn-sm btn-primary">Save</button></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody></table></div></div>
    <?php $__currentLoopData = $shifts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shift): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="modal fade" id="editShift<?php echo e($shift->id); ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-pen me-2"></i>Edit Shift</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="<?php echo e(route('admin.shifts.update', $shift)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Shift Name</label><input name="name" class="form-control" value="<?php echo e($shift->name); ?>" required></div>
                                <div class="col-md-3"><label class="form-label">Class Code</label><input name="class_code" class="form-control" value="<?php echo e($shift->class_code); ?>"></div>
                                <div class="col-md-3"><label class="form-label">Room</label><input name="room" class="form-control" value="<?php echo e($shift->room); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Start</label><input name="start_time" type="time" class="form-control" value="<?php echo e(substr($shift->start_time, 0, 5)); ?>" required></div>
                                <div class="col-md-6"><label class="form-label">End</label><input name="end_time" type="time" class="form-control" value="<?php echo e(substr($shift->end_time, 0, 5)); ?>" required></div>
                                <div class="col-md-6"><label class="form-label">Break Start</label><input name="break_start" type="time" class="form-control" value="<?php echo e($shift->break_start ? substr($shift->break_start, 0, 5) : ''); ?>"></div>
                                <div class="col-md-6"><label class="form-label">Break End</label><input name="break_end" type="time" class="form-control" value="<?php echo e($shift->break_end ? substr($shift->break_end, 0, 5) : ''); ?>"></div>
                                <div class="col-12"><label class="form-label">Working Days</label><div class="d-flex flex-wrap gap-3"><?php $__currentLoopData = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label class="form-check"><input class="form-check-input" type="checkbox" name="working_days[]" value="<?php echo e($day); ?>" <?php if(in_array($day, $shift->working_days_list, true)): echo 'checked'; endif; ?>><?php echo e($day); ?></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div></div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button></div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\admin\shifts\index.blade.php ENDPATH**/ ?>