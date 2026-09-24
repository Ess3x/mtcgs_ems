<?php $__env->startSection('title', 'Calendar Events'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0 fw-bold">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>Calendar Events
                    </h2>
                    <p class="text-muted mb-0">Manage school activities and holidays</p>
                </div>
                <?php if(Auth::user()->role === 'admin'): ?>
                    <div class="d-flex gap-2">
                        <a href="<?php echo e(route('calendar.calendar')); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-calendar me-2"></i>Calendar View
                        </a>
                        <a href="<?php echo e(route('admin.calendar.create')); ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Event
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo e(route('calendar.calendar')); ?>" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-2"></i>Calendar View
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Events List -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <?php if($events->count() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Branch</th>
                                        <th>Created By</th>
                                        <?php if(Auth::user()->role === 'admin'): ?>
                                            <th class="text-center">Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-<?php echo e($event->event_type === 'activity' ? 'primary' : 'warning'); ?> text-white me-3">
                                                    <i class="fas fa-<?php echo e($event->event_type === 'activity' ? 'graduation-cap' : 'umbrella-beach'); ?>"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold"><?php echo e($event->title); ?></h6>
                                                    <?php if($event->description): ?>
                                                        <small class="text-muted"><?php echo e(Str::limit($event->description, 50)); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo e($event->event_type === 'activity' ? 'primary' : 'warning'); ?>">
                                                <?php echo e(ucfirst($event->event_type)); ?>

                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo e($event->event_date->format('M d, Y')); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo e($event->event_date->format('l')); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($event->branch): ?>
                                                <span class="badge bg-info"><?php echo e($event->branch->branch_name); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">All Branches</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e(optional($event->creator)->name ?? 'Philippine Holiday Calendar'); ?></td>
                                        <?php if(Auth::user()->role === 'admin'): ?>
                                            <td class="text-center">
                                                <?php if($event->exists && $event->getKey()): ?>
                                                    <div class="btn-group" role="group">
                                                        <a href="<?php echo e(route('admin.calendar.edit', $event)); ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="POST" action="<?php echo e(route('admin.calendar.destroy', $event)); ?>" class="d-inline"
                                                              onsubmit="return confirm('Are you sure you want to delete this event?')">
                                                            <?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?>
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">System holiday</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-day fa-3x text-muted mb-3 d-block"></i>
                            <h5 class="text-muted">No calendar events found</h5>
                            <p class="text-muted mb-3">There are no scheduled activities or holidays at this time.</p>
                            <?php if(Auth::user()->role === 'admin'): ?>
                                <a href="<?php echo e(route('admin.calendar.create')); ?>" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add First Event
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\calendar\index.blade.php ENDPATH**/ ?>