<?php $__env->startSection('title', 'File Leave'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid px-4">
    <div class="row justify-content-center align-items-center" style="min-height: calc(100vh - 300px);">
        <div class="col-12">
            <div class="card shadow-lg">
                <div class="card-header bg-white text-center">
                    <h4 class="mb-0"><i class="fas fa-calendar-alt"></i> File Leave Request</h4>
                </div>
                <div class="card-body">
                    <?php if($errors->any()): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong>Please correct the following:</strong>
                            <ul class="mb-0 mt-2">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <!-- Leave Balance Summary -->
                    <div class="alert alert-info mb-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h6 class="mb-0"><i class="fas fa-chart-line"></i> Your Leave Balances (<?php echo e(date('Y')); ?>)</h6>
                            <span class="badge bg-primary fs-6">Status: <?php echo e($profile->status ?? 'New Hire'); ?></span>
                        </div>
                        <?php if(in_array($profile->status ?? 'New Hire', ['New Hire'], true)): ?>
                            <p class="small mb-3">You may submit a leave request, but you currently have no leave credits.</p>
                        <?php elseif(in_array($profile->status ?? '', ['3+ Years of Service', '3+ Year of Service', 'Branch Head'], true)): ?>
                            <p class="small mb-3">You may avail of sick, vacation, emergency, maternity, and paternity leave.</p>
                        <?php else: ?>
                            <p class="small mb-3">You may avail of sick, vacation, and emergency leave based on your available credits.</p>
                        <?php endif; ?>
                        <div class="row text-center">
                            <div class="col-md-4 mb-2">
                                <small class="d-block text-muted">Sick Leave</small>
                                <strong class="d-block fs-5"><?php echo e($leaveBalance->getAvailableSickLeave()); ?> / <?php echo e($leaveBalance->sick_leave_total); ?> days</strong>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="d-block text-muted">Vacation Leave</small>
                                <strong class="d-block fs-5"><?php echo e($leaveBalance->getAvailableVacationLeave()); ?> / <?php echo e($leaveBalance->vacation_leave_total); ?> days</strong>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="d-block text-muted">Emergency Leave</small>
                                <strong class="d-block fs-5"><?php echo e($leaveBalance->getAvailableEmergencyLeave()); ?> / <?php echo e($leaveBalance->emergency_leave_total); ?> days</strong>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="d-block text-muted">Maternity Leave</small>
                                <strong class="d-block fs-5"><?php echo e($leaveBalance->getAvailableMaternityLeave()); ?> / <?php echo e($leaveBalance->maternity_leave_total); ?> days</strong>
                            </div>
                            <div class="col-md-4 mb-2">
                                <small class="d-block text-muted">Paternity Leave</small>
                                <strong class="d-block fs-5"><?php echo e($leaveBalance->getAvailablePaternityLeave()); ?> / <?php echo e($leaveBalance->paternity_leave_total); ?> days</strong>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <form id="leaveRequestForm" method="POST" action="<?php echo e(route('leave.store')); ?>">
                                <?php echo csrf_field(); ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Leave Type</label>
                                        <select name="leave_type" class="form-control" required>
                                            <option value="sick" <?php echo e(old('leave_type') === 'sick' ? 'selected' : ''); ?>>Sick Leave</option>
                                            <option value="vacation" <?php echo e(old('leave_type') === 'vacation' ? 'selected' : ''); ?>>Vacation Leave</option>
                                            <option value="emergency" <?php echo e(old('leave_type') === 'emergency' ? 'selected' : ''); ?>>Emergency Leave</option>
                                            <option value="maternity" <?php echo e(old('leave_type') === 'maternity' ? 'selected' : ''); ?> <?php echo e(in_array($profile->status ?? '', ['3+ Years of Service', '3+ Year of Service', 'Branch Head'], true) ? '' : 'disabled'); ?>>Maternity Leave</option>
                                            <option value="paternity" <?php echo e(old('leave_type') === 'paternity' ? 'selected' : ''); ?> <?php echo e(in_array($profile->status ?? '', ['3+ Years of Service', '3+ Year of Service', 'Branch Head'], true) ? '' : 'disabled'); ?>>Paternity Leave</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Start Date</label>
                                        <input id="start_date" type="date" name="start_date" class="form-control" min="<?php echo e(now()->toDateString()); ?>" value="<?php echo e(old('start_date')); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">End Date</label>
                                        <input id="end_date" type="date" name="end_date" class="form-control" min="<?php echo e(now()->toDateString()); ?>" value="<?php echo e(old('end_date')); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason</label>
                                    <textarea name="reason" class="form-control" rows="4" required><?php echo e(old('reason')); ?></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-primary w-100">Submit Request</button>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="<?php echo e(route('leave.index')); ?>" class="btn btn-secondary w-100">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-lg-6 mt-4 mt-lg-0">
                            <div class="card border shadow-sm">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0">School Activity Calendar</h6>
                                    <small class="text-muted d-block">Choose leave dates while checking activities or holidays.</small>
                                </div>
                                <div class="card-body py-2">
                                    <div id="leaveCalendar"></div>
                                    <div class="mt-3">
                                        <div class="d-flex gap-2 flex-wrap">
                                            <span class="badge bg-primary">Activity</span>
                                            <span class="badge bg-warning text-dark">Holiday</span>
                                            <span class="badge bg-success">Leave</span>
                                        </div>
                                        <p class="small text-muted mt-2 mb-0">Click a date in the calendar to quickly fill Start/End date fields.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('styles'); ?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<style>
    #leaveCalendar {
        min-height: 340px;
    }
    #leaveCalendar .fc {
        font-weight: 700;
    }
    #leaveCalendar .fc-bg-event {
        opacity: 1;
        align-items: flex-end;
        box-sizing: border-box;
        display: flex;
        padding: 0.25rem;
        background-color: #3E3E75 !important;
        border-color: #3E3E75 !important;
    }
    body.dark-mode #leaveCalendar .fc-bg-event {
        background-color: #F62477 !important;
        border-color: #F62477 !important;
    }
    #leaveCalendar .fc-day-today {
        background-color: #007DCC !important;
    }
    #leaveCalendar .fc-day-today .fc-daygrid-day-number {
        color: #ffffff !important;
    }
    body.dark-mode #leaveCalendar .fc-day-today {
        background-color: #FFB900 !important;
    }
    body.dark-mode #leaveCalendar .fc-day-today .fc-daygrid-day-number {
        color: #111827 !important;
    }
    #leaveCalendar .fc-bg-event .fc-event-title {
        color: #ffffff;
        font-weight: 700;
        line-height: 1.1;
        overflow: hidden;
        position: static;
        text-overflow: ellipsis;
        white-space: nowrap;
        width: 100%;
    }
    #leaveCalendar .fc-header-toolbar {
        align-items: center;
        gap: 0.5rem;
    }
    #leaveCalendar .fc-toolbar-chunk {
        align-items: center;
        display: flex;
        gap: 0.25rem;
    }
    #leaveCalendar .fc-toolbar-title {
        font-size: 1.35rem;
        white-space: nowrap;
    }
    #leaveCalendar .fc-button {
        align-items: center;
        display: inline-flex;
        justify-content: center;
        white-space: nowrap;
    }
    .fc .fc-daygrid-day-number {
        font-size: 0.75rem;
        font-weight: 700;
    }
    .fc .fc-col-header-cell-cushion,
    .fc .fc-toolbar-title,
    .fc .fc-button,
    .fc .fc-list-day-text,
    .fc .fc-list-day-side-text {
        font-weight: 700;
    }
    .fc .fc-event {
        font-size: 0.65rem;
        padding: 0.15rem 0.3rem;
        border-radius: 4px;
    }
    #leaveCalendar .fc-list-event-title,
    #leaveCalendar .fc-list-event-time,
    #leaveCalendar .fc-list-day-text,
    #leaveCalendar .fc-list-day-side-text {
        font-size: 0.95rem;
        font-weight: 700;
    }
    #leaveCalendar .fc-list-event-dot {
        border-width: 7px;
    }
    #leaveCalendar .fc-list-table td {
        padding: 0.7rem 0.5rem;
    }
    body.dark-mode #leaveCalendar .fc-list-table,
    body.dark-mode #leaveCalendar .fc-list-table td {
        background-color: #1e293b;
        border-color: #475569;
        color: #ffffff;
    }
    body.dark-mode #leaveCalendar .fc-list-event-title,
    body.dark-mode #leaveCalendar .fc-list-event-title a,
    body.dark-mode #leaveCalendar .fc-list-event-time,
    body.dark-mode #leaveCalendar .fc-list-day-text,
    body.dark-mode #leaveCalendar .fc-list-day-side-text {
        color: #ffffff !important;
        font-weight: 700;
    }
    body.dark-mode #leaveCalendar .fc-list-day-cushion {
        background-color: #334155 !important;
        border-color: #475569 !important;
    }
    @media (max-width: 576px) {
        #leaveCalendar .fc-header-toolbar {
            display: grid;
            grid-template-columns: 1fr auto;
            row-gap: 0.5rem;
        }
        #leaveCalendar .fc-toolbar-chunk:nth-child(1) {
            grid-column: 1;
            grid-row: 1;
        }
        #leaveCalendar .fc-toolbar-chunk:nth-child(2) {
            grid-column: 2;
            grid-row: 1;
        }
        #leaveCalendar .fc-toolbar-chunk:nth-child(3) {
            grid-column: 1 / -1;
            grid-row: 2;
            justify-content: flex-end;
        }
        #leaveCalendar .fc-toolbar-title {
            font-size: 1.15rem;
        }
        #leaveCalendar .fc-button {
            padding: 0.35rem 0.5rem;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('leaveCalendar');
        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');
        const leaveForm = document.getElementById('leaveRequestForm');
        const blockedLeaves = [
            <?php $__currentLoopData = $activeLeaves; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leave): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            { start: <?php echo json_encode($leave->start_date->format('Y-m-d'), 15, 512) ?>, end: <?php echo json_encode($leave->end_date->format('Y-m-d'), 15, 512) ?> },
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        ];
        const leaveRangeEventId = 'selected-leave-range';

        function overlapsBlockedLeave(start, end) {
            if (!start || !end || end < start) {
                return false;
            }

            return blockedLeaves.some(range => range.start <= end && range.end >= start);
        }

        function clearBlockedDates() {
            if (overlapsBlockedLeave(startInput.value, endInput.value)) {
                startInput.value = '';
                endInput.value = '';
                window.alert('You already have a pending or approved leave on the selected date(s). Please choose another date.');
                return true;
            }

            return false;
        }

        function updateLeaveRange() {
            const currentRange = calendar.getEventById(leaveRangeEventId);
            if (currentRange) {
                currentRange.remove();
            }

            if (!startInput.value || !endInput.value || endInput.value < startInput.value) {
                return;
            }

            const endDate = new Date(`${endInput.value}T00:00:00`);
            endDate.setDate(endDate.getDate() + 1);
            const nextDay = [
                endDate.getFullYear(),
                String(endDate.getMonth() + 1).padStart(2, '0'),
                String(endDate.getDate()).padStart(2, '0')
            ].join('-');

            calendar.addEvent({
                id: leaveRangeEventId,
                title: 'Selected Leave',
                start: startInput.value,
                end: nextDay,
                display: 'background',
                backgroundColor: '#3E3E75',
                borderColor: '#3E3E75',
                textColor: '#ffffff'
            });
        }

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            buttonText: {
                today: 'Today',
                month: 'Month',
                week: 'Week',
                list: 'List'
            },
            dateClick: function(info) {
                const blockedLeave = blockedLeaves.some(range => info.dateStr >= range.start && info.dateStr <= range.end);
                if (blockedLeave) {
                    return;
                }
                if (info.dateStr < startInput.min) {
                    return;
                }
                if (!startInput.value || (startInput.value && endInput.value)) {
                    startInput.value = info.dateStr;
                    endInput.value = info.dateStr;
                } else {
                    endInput.value = info.dateStr;
                }
                updateLeaveRange();
            },
            events: [
                <?php if(isset($calendarEvents) && $calendarEvents->count()): ?>
                    <?php $__currentLoopData = $calendarEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    {
                        title: '<?php echo e(addslashes($event->title)); ?>',
                        extendedProps: {
                            description: <?php echo json_encode($event->description, 15, 512) ?>,
                            type: <?php echo json_encode($event->event_type, 15, 512) ?>
                        },
                        start: '<?php echo e($event->event_date->format('Y-m-d')); ?>',
                        backgroundColor: '<?php echo e($event->event_type === 'activity' ? '#0d6efd' : '#fd7e14'); ?>',
                        borderColor: '<?php echo e($event->event_type === 'activity' ? '#0d6efd' : '#fd7e14'); ?>',
                        textColor: '#ffffff'
                    },
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
                <?php if(isset($approvedLeaves)): ?>
                    <?php $__currentLoopData = $approvedLeaves; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leave): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    {
                        id: <?php echo json_encode('approved-leave-' . $leave->id, 15, 512) ?>,
                        title: <?php echo json_encode('Approved ' . ucfirst($leave->leave_type) . ' Leave', 15, 512) ?>,
                        start: <?php echo json_encode($leave->start_date->format('Y-m-d'), 15, 512) ?>,
                        end: <?php echo json_encode($leave->end_date->copy()->addDay()->format('Y-m-d'), 15, 512) ?>,
                        backgroundColor: '#198754',
                        borderColor: '#198754',
                        textColor: '#ffffff',
                        extendedProps: {
                            type: 'leave',
                            status: 'approved',
                            description: <?php echo json_encode($leave->reason, 15, 512) ?>
                        }
                    },
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php endif; ?>
            ],
            height: 'auto',
            dayMaxEventRows: true,
            contentHeight: 'auto'
        });
        calendar.render();
        startInput.addEventListener('change', function() {
            clearBlockedDates();
            updateLeaveRange();
        });
        endInput.addEventListener('change', function() {
            clearBlockedDates();
            updateLeaveRange();
        });
        leaveForm.addEventListener('submit', function(event) {
            if (clearBlockedDates()) {
                event.preventDefault();
            }
        });
        updateLeaveRange();
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\leave\create.blade.php ENDPATH**/ ?>