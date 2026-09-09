<?php $__env->startSection('title', 'Calendar View'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-0 px-2" style="margin-top: -15px;">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-12">
            <!-- Calendar Card - Raised Higher -->
            <div class="card border-0 shadow-sm" style="margin-top: 0; border-radius: 8px;">
                <div class="card-header bg-white border-0 pt-1 pb-0 px-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h5 class="fw-bold mb-0" style="font-size: 1rem;">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>School Calendar
                            </h5>
                            <p class="text-muted mb-0" style="font-size: 0.65rem;">View all scheduled activities and holidays</p>
                        </div>
                        <div class="d-flex gap-2 mt-1 mt-sm-0">
                            <a href="<?php echo e(route('calendar.index')); ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.65rem;">
                                <i class="fas fa-list me-1"></i>List View
                            </a>
                            <?php if(Auth::user()->role === 'admin'): ?>
                                <a href="<?php echo e(route('admin.calendar.create')); ?>" class="btn btn-sm btn-primary py-0 px-2" style="font-size: 0.65rem;">
                                    <i class="fas fa-plus me-1"></i>Add Event
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- Calendar Legend -->
                <div class="bg-light border-top px-3 py-2" style="font-size: 0.75rem;">
                    <div class="d-flex flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background-color: #0d6efd; width: 20px; height: 20px;"></span>
                            <span>Your Leave</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background-color: #198754; width: 20px; height: 20px;"></span>
                            <span>Approved Leave</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background-color: #ffc107; width: 20px; height: 20px;"></span>
                            <span>Pending Leave</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary" style="width: 20px; height: 20px;"></span>
                            <span>Activity</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning" style="width: 20px; height: 20px;"></span>
                            <span>Holiday</span>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0 pb-1 px-2">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold" id="eventModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="eventModalBody" style="min-height: 200px; max-height: 500px; overflow-y: auto;">
                <!-- Content will be inserted here -->
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <?php if(Auth::user()->role === 'admin'): ?>
                    <a href="#" id="editEventBtn" class="btn btn-primary d-none">Edit Event</a>
                    <button type="button" id="approveLeaveBtn" class="btn btn-success d-none">Approve Leave</button>
                    <button type="button" id="rejectLeaveBtn" class="btn btn-danger d-none">Reject Leave</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('styles'); ?>
<style>
    body {
        margin-top: 0 !important;
        padding-top: 0 !important;
    }

    body.dark-mode #eventModal .modal-content,
    body.dark-mode #eventModal .modal-header,
    body.dark-mode #eventModal .modal-footer {
        background-color: #4647AE;
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.25);
    }

    body.dark-mode #eventModal .modal-body {
        background-color: #A290B7;
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.25);
    }

    body.dark-mode #eventModal .modal-header.bg-light {
        background-color: #4647AE !important;
    }

    body.dark-mode #eventModal .modal-body .text-muted,
    body.dark-mode #eventModal .modal-body strong {
        color: #ffffff !important;
    }

    body.dark-mode #eventModal .modal-body .text-dark {
        color: #ffffff !important;
    }

    body.dark-mode #eventModal .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
    
    .container-fluid {
        max-width: 100%;
        margin: 0 auto;
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    /* Calendar Styling - Compact para makita ang nasa baba */
    .fc {
        width: 100%;
    }
    
    .fc .fc-toolbar-title {
        font-size: 0.85rem !important;
        font-weight: bold !important;
    }
    
    .fc .fc-button {
        padding: 0.15rem 0.4rem !important;
        font-size: 0.65rem !important;
        border-radius: 4px !important;
    }
    
    .fc .fc-toolbar.fc-header-toolbar {
        margin-bottom: 0.3rem !important;
    }
    
    .fc .fc-col-header-cell-cushion {
        font-size: 0.65rem !important;
        font-weight: 600 !important;
        padding: 0.3rem 0 !important;
        text-decoration: none !important;
    }
    
    .fc .fc-daygrid-day-number {
        font-size: 0.7rem !important;
        padding: 0.2rem !important;
        font-weight: 500 !important;
    }
    
    .fc .fc-daygrid-day-frame {
        min-height: 55px !important;
        height: auto !important;
    }
    
    .fc .fc-daygrid-day-events {
        min-height: 18px !important;
    }
    
    .fc .fc-event-title {
        font-size: 0.55rem !important;
        font-weight: 500 !important;
    }
    
    .fc .fc-event {
        padding: 0.05rem 0.2rem !important;
        margin: 1px 1px !important;
        border-radius: 3px !important;
        cursor: pointer !important;
        pointer-events: auto !important;
        position: relative;
        z-index: 2;
    }
    
    .fc .fc-day-today {
        background-color: rgba(13, 110, 253, 0.05) !important;
    }
    
    .card {
        border-radius: 8px !important;
        overflow: hidden;
    }
    
    @media (max-width: 768px) {
        .fc .fc-toolbar {
            flex-direction: column;
            gap: 0.3rem;
        }
        .fc .fc-daygrid-day-frame {
            min-height: 50px !important;
        }
        .fc .fc-col-header-cell-cushion {
            font-size: 0.55rem !important;
            padding: 0.2rem 0 !important;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: {
            today: 'Today',
            month: 'Month',
            week: 'Week',
            list: 'List'
        },
        events: [
            <?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            {
                id: <?php echo json_encode((string) $event->id, 15, 512) ?>,
                title: <?php echo json_encode($event->title, 15, 512) ?>,
                start: <?php echo json_encode($event->event_date->format('Y-m-d'), 15, 512) ?>,
                backgroundColor: <?php echo json_encode($event->event_type === 'activity' ? '#0d6efd' : '#fd7e14', 15, 512) ?>,
                borderColor: <?php echo json_encode($event->event_type === 'activity' ? '#0d6efd' : '#fd7e14', 15, 512) ?>,
                textColor: '#ffffff',
                extendedProps: {
                    type: <?php echo json_encode($event->event_type, 15, 512) ?>,
                    description: <?php echo json_encode($event->description, 15, 512) ?>,
                    branch: <?php echo json_encode($event->branch ? $event->branch->branch_name : 'All Branches', 15, 512) ?>,
                    creator: <?php echo json_encode(optional($event->creator)->name ?? 'Philippine Holiday Calendar', 15, 512) ?>
                }
            },
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php if(isset($leaveRequests)): ?>
                <?php $__currentLoopData = $leaveRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $leave): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                {
                    id: <?php echo json_encode('leave-' . $leave->id, 15, 512) ?>,
                    title: <?php echo json_encode('Leave: ' . (optional($leave->employeeProfile)->full_name ?? optional($leave->employee)->name ?? 'Employee'), 15, 512) ?>,
                    start: <?php echo json_encode($leave->start_date->format('Y-m-d'), 15, 512) ?>,
                    end: <?php echo json_encode($leave->end_date->copy()->addDay()->format('Y-m-d'), 15, 512) ?>,
                    backgroundColor: <?php echo json_encode(($currentUserProfileId && $leave->employee_profile_id === $currentUserProfileId) ? '#0d6efd' : ($leave->status === 'approved' ? '#198754' : '#ffc107'), 15, 512) ?>,
                    borderColor: <?php echo json_encode(($currentUserProfileId && $leave->employee_profile_id === $currentUserProfileId) ? '#0d6efd' : ($leave->status === 'approved' ? '#198754' : '#ffc107'), 15, 512) ?>,
                    textColor: '#ffffff',
                    extendedProps: {
                        type: 'leave',
                        leaveId: <?php echo json_encode((string) $leave->id, 15, 512) ?>,
                        status: <?php echo json_encode($leave->status, 15, 512) ?>,
                        employee: <?php echo json_encode(optional($leave->employeeProfile)->full_name ?? optional($leave->employee)->name ?? 'Employee', 15, 512) ?>,
                        leaveType: <?php echo json_encode(ucfirst($leave->leave_type), 15, 512) ?>,
                        reason: <?php echo json_encode($leave->reason, 15, 512) ?>,
                        branch: <?php echo json_encode(optional($leave->employeeProfile->branch)->branch_name ?? 'Unknown Branch', 15, 512) ?>,
                        isOwnLeave: <?php echo e(($currentUserProfileId && $leave->employee_profile_id === $currentUserProfileId) ? 'true' : 'false'); ?>

                    }
                },
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        ],
        eventClick: function(info) {
            const event = info.event;
            const props = event.extendedProps;
            
            // Set modal title
            const modalTitle = document.getElementById('eventModalLabel');
            if (modalTitle) {
                modalTitle.textContent = event.title;
            }

            let detailHtml = '';

            if (props.type === 'leave') {
                const endDate = event.end ? new Date(event.end) : null;
                const displayEndDate = endDate ? new Date(endDate.getTime() - 86400000).toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }) : 'N/A';
                
                detailHtml = `
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="mb-3">
                                <strong class="d-block text-muted" style="font-size: 0.85rem;">Employee</strong>
                                <span class="badge ${props.isOwnLeave ? 'bg-primary' : 'bg-info'} p-2">
                                    ${props.employee}${props.isOwnLeave ? ' (Your Leave)' : ''}
                                </span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <strong class="d-block text-muted" style="font-size: 0.85rem;">Leave Type</strong>
                                <span class="badge bg-secondary p-2">${props.leaveType}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="mb-3">
                                <strong class="d-block text-muted" style="font-size: 0.85rem;">Status</strong>
                                <span class="badge ${props.status === 'approved' ? 'bg-success' : props.status === 'pending' ? 'bg-warning' : 'bg-danger'} p-2">${props.status.charAt(0).toUpperCase() + props.status.slice(1)}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <strong class="d-block text-muted" style="font-size: 0.85rem;">Branch</strong>
                                <span class="text-dark">${props.branch}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="mb-3">
                                <strong class="d-block text-muted" style="font-size: 0.85rem;">Start Date</strong>
                                <span class="text-dark">${new Date(event.start).toLocaleDateString('en-US', {
                                    weekday: 'short',
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric'
                                })}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <strong class="d-block text-muted" style="font-size: 0.85rem;">End Date</strong>
                                <span class="text-dark">${displayEndDate}</span>
                            </div>
                        </div>
                    </div>

                    ${props.reason ? `
                        <div class="mb-3">
                            <strong class="d-block text-muted mb-2" style="font-size: 0.85rem;">Reason</strong>
                            <p class="text-dark border-start ps-3 mb-0">${props.reason}</p>
                        </div>
                    ` : `
                        <div class="mb-3">
                            <strong class="d-block text-muted mb-2" style="font-size: 0.85rem;">Reason</strong>
                            <p class="text-muted mb-0">No reason provided</p>
                        </div>
                    `}
                `;
            } else {
                detailHtml = `
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="mb-3">
                                <strong class="d-block text-muted" style="font-size: 0.85rem;">Type</strong>
                                <span class="badge bg-${props.type === 'activity' ? 'primary' : 'warning'} p-2">${props.type.charAt(0).toUpperCase() + props.type.slice(1)}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <strong class="d-block text-muted" style="font-size: 0.85rem;">Branch</strong>
                                <span class="text-dark">${props.branch}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="mb-3">
                                <strong class="d-block text-muted" style="font-size: 0.85rem;">Date</strong>
                                <span class="text-dark">${new Date(event.start).toLocaleDateString('en-US', {
                                    weekday: 'long',
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                })}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="mb-3">
                                <strong class="d-block text-muted mb-2" style="font-size: 0.85rem;">Created by</strong>
                                <span class="text-dark">${props.creator}</span>
                            </div>
                        </div>
                    </div>

                    ${props.description ? `
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="mb-3">
                                    <strong class="d-block text-muted mb-2" style="font-size: 0.85rem;">Description</strong>
                                    <p class="text-dark border-start ps-3 mb-0">${props.description}</p>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                `;
            }

            const bodyElement = document.getElementById('eventModalBody');
            if (bodyElement) {
                bodyElement.innerHTML = detailHtml;
            }

            const editBtn = document.getElementById('editEventBtn');
            const approveBtn = document.getElementById('approveLeaveBtn');
            const rejectBtn = document.getElementById('rejectLeaveBtn');

            if (props.type === 'leave') {
                if (editBtn) editBtn.classList.add('d-none');
                if (approveBtn) approveBtn.classList.remove('d-none');
                if (rejectBtn) rejectBtn.classList.remove('d-none');

                if (props.status === 'approved') {
                    if (approveBtn) approveBtn.classList.add('d-none');
                    if (rejectBtn) rejectBtn.classList.add('d-none');
                }

                if (approveBtn) {
                    approveBtn.onclick = function() {
                        processLeaveAction('approve', props.leaveId, event);
                    };
                }

                if (rejectBtn) {
                    rejectBtn.onclick = function() {
                        processLeaveAction('reject', props.leaveId, event);
                    };
                }
            } else {
                if (approveBtn) approveBtn.classList.add('d-none');
                if (rejectBtn) rejectBtn.classList.add('d-none');
                if (editBtn) editBtn.classList.remove('d-none');

                if (editBtn) {
                    <?php if(Auth::user()->role === 'admin'): ?>
                    editBtn.href = '<?php echo e(url("/admin/calendar")); ?>/' + event.id + '/edit';
                    <?php endif; ?>
                }
            }

            const modal = new bootstrap.Modal(document.getElementById('eventModal'));
            modal.show();
        },
        height: 'auto',
        contentHeight: 'auto',
        dayMaxEventRows: true,
        weekends: true
    });

    function processLeaveAction(action, leaveId, event) {
        const url = '<?php echo e(url('leave')); ?>' + '/' + action + '/' + leaveId;
        fetch(url, { method: 'GET' })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Action failed');
                }
                if (action === 'reject') {
                    event.remove();
                } else {
                    event.setProp('backgroundColor', '#198754');
                    event.setProp('borderColor', '#198754');
                }
                const modalEl = document.getElementById('eventModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                modalInstance.hide();
                window.location.reload();
            })
            .catch(() => {
                alert('Unable to process the leave action. Please refresh and try again.');
            });
    }

    calendar.render();
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\calendar\calendar.blade.php ENDPATH**/ ?>