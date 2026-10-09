

<?php $__env->startSection('title', 'My Schedule'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .employee-schedule-grid {
        min-width: 920px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .employee-schedule-grid th,
    .employee-schedule-grid td {
        border: 1px solid #1f2937;
        height: 46px;
        padding: 4px;
        text-align: center;
        vertical-align: middle;
    }

    .employee-schedule-grid thead th {
        background: #111827;
        color: #fff;
        font-size: .85rem;
    }

    .employee-schedule-grid .time-column {
        width: 105px;
        background: #f8fafc;
        color: #111827;
        font-size: .72rem;
        white-space: nowrap;
    }

    .employee-schedule-grid .schedule-cell {
        background: #dbeafe;
        color: #1e3a5f;
        font-size: .75rem;
        font-weight: 600;
    }

    .employee-schedule-grid .break-cell {
        background: #9a4f0b;
        color: #fff;
    }

    .employee-schedule-grid .rest-cell {
        background: #f8fafc;
    }
</style>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">My Schedule</h2>
            <p class="text-muted mb-0"><?php echo e($profile->first_name); ?> <?php echo e($profile->last_name); ?></p>
        </div>
        <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    <?php if($shifts->isNotEmpty()): ?>
        <?php
            $days = [
                'Mon' => 'Monday',
                'Tue' => 'Tuesday',
                'Wed' => 'Wednesday',
                'Thu' => 'Thursday',
                'Fri' => 'Friday',
                'Sat' => 'Saturday',
                'Sun' => 'Sunday',
            ];
            $timeSlots = collect(range(7, 19));
        ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-calendar-week text-primary me-2"></i><strong>Weekly Schedule</strong></span>
                <small class="text-muted"><?php echo e($shifts->count()); ?> assigned schedule<?php echo e($shifts->count() === 1 ? '' : 's'); ?></small>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="employee-schedule-grid w-100">
                        <thead>
                            <tr>
                                <th class="time-column">Time</th>
                                <?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <th><?php echo e($day); ?></th>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $timeSlots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $slotStart = \Carbon\Carbon::today()->setTime($hour, 0);
                                    $slotEnd = $slotStart->copy()->addHour();
                                    $labelStart = $slotStart->format('g:i');
                                    $labelEnd = $slotEnd->format('g:i');
                                    $period = $slotStart->format('A');
                                ?>
                                <tr>
                                    <th class="time-column"><?php echo e($labelStart); ?> - <?php echo e($labelEnd); ?> <?php echo e($period); ?></th>
                                    <?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shortDay => $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                                $cellShifts = $shifts->filter(function ($assignedShift) use ($shortDay, $slotStart, $slotEnd) {
                                                    $workingDays = collect($assignedShift->working_days_list)
                                                        ->map(fn ($day) => ucfirst(substr(trim($day), 0, 3)))
                                                        ->all();
                                                    $shiftStart = \Carbon\Carbon::parse($assignedShift->start_time);
                                                    $shiftEnd = \Carbon\Carbon::parse($assignedShift->end_time);
                                                    return in_array($shortDay, $workingDays, true)
                                                        && $slotStart->lt($shiftEnd)
                                                        && $slotEnd->gt($shiftStart);
                                                });
                                        ?>
                                            <td class="<?php echo e($cellShifts->isNotEmpty() ? 'schedule-cell' : 'rest-cell'); ?>">
                                                <?php $__currentLoopData = $cellShifts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignedShift): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php
                                                        $breakStart = $assignedShift->break_start ? \Carbon\Carbon::parse($assignedShift->break_start) : null;
                                                        $breakEnd = $assignedShift->break_end ? \Carbon\Carbon::parse($assignedShift->break_end) : null;
                                                        $isBreak = $breakStart && $breakEnd && $slotStart->lt($breakEnd) && $slotEnd->gt($breakStart);
                                                    ?>
                                                    <div class="<?php echo e($isBreak ? 'break-cell' : ''); ?> rounded p-1 mb-1">
                                                        <?php if($isBreak): ?>
                                                            Break
                                                        <?php else: ?>
                                                            <strong><?php echo e($assignedShift->class_code ?: $assignedShift->name); ?></strong><br>
                                                            <small><?php echo e($assignedShift->room ?: 'Room not set'); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </td>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="small text-muted mb-4">
            <span class="badge" style="background:#dbeafe;color:#1e3a5f;">Working Hours</span>
            <span class="badge ms-2" style="background:#9a4f0b;color:#fff;">Break</span>
            <span class="badge bg-light text-dark border ms-2">Rest Day / No Schedule</span>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h4>No schedule assigned</h4>
                <p class="text-muted mb-0">Please contact your administrator to have a schedule assigned.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\employee\schedule.blade.php ENDPATH**/ ?>