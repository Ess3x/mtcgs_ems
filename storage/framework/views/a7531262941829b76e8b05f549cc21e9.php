<?php
    $resetEmail = request('email') ?: old('email') ?: '';
    $resetUser = $resetEmail ? \App\Models\User::whereRaw('LOWER(email) = ?', [strtolower(trim($resetEmail))])->first() : null;
    $profile = $resetUser?->profile ?? $resetUser?->getEmployeeProfile();
    $photoPath = $profile?->profile_photo ?? null;
    $photoUrl = null;

    if ($photoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($photoPath)) {
        $photoUrl = asset('storage/' . ltrim($photoPath, '/')) . '?v=' . ($profile->updated_at?->timestamp ?? time());
    }

    $firstName = $profile?->first_name ?? ($resetUser?->name ? explode(' ', trim((string) $resetUser->name))[0] : '');
    $lastName = $profile?->last_name ?? (count(explode(' ', trim((string) ($resetUser?->name ?? '')))) > 1 ? implode(' ', array_slice(explode(' ', trim((string) $resetUser->name)), 1)) : '');
    $branchName = $profile?->branch?->branch_name ?? $resetUser?->branch?->branch_name ?? 'N/A';
    $employeeId = $profile?->employee_number ?? 'N/A';
    $displayName = trim($firstName . ' ' . $lastName) ?: ($resetUser?->name ?: 'User');
    $initials = strtoupper(substr((string) ($firstName ?: 'U'), 0, 1) . substr((string) ($lastName ?: 'S'), 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            font-family: 'Segoe UI', sans-serif;
            padding: 2rem 1rem;
        }
        .reset-card {
            width: min(100%, 520px);
            border: 0;
            border-radius: 18px;
            box-shadow: 0 14px 30px rgba(17, 24, 39, 0.08);
            background: #fff;
        }
        .brand-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            margin-bottom: 1.2rem;
        }
        .brand-header img {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            object-fit: cover;
            background: #fff;
            padding: 4px;
            box-shadow: 0 6px 18px rgba(123, 76, 214, 0.2);
        }
        .brand-header span {
            font-size: 1.7rem;
            font-weight: 700;
            color: #5b2bb6;
            letter-spacing: 0.03em;
        }
        .profile-summary {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1rem;
            margin-bottom: 1.2rem;
        }
        .profile-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            overflow: hidden;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-detail {
            font-size: 0.82rem;
            color: #4b5563;
            margin-bottom: 0.18rem;
        }
        .profile-detail strong {
            color: #111827;
        }
        .form-control {
            background: #eef5ff;
            border: 1px solid #dfeafc;
            border-radius: 10px;
            padding: 0.85rem 0.9rem;
            color: #111827;
        }
        .form-control:focus {
            border-color: rgba(59, 130, 246, 0.7);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.12);
            background: #f4f9ff;
        }
        .input-group-text {
            background: #eef5ff;
            border: 1px solid #dfeafc;
            border-left: none;
            color: #4b5563;
            cursor: pointer;
        }
        .btn-primary {
            border-radius: 10px;
            padding: 0.8rem;
            font-weight: 600;
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            border: none;
            box-shadow: 0 8px 18px rgba(124, 58, 237, 0.25);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #6d28d9, #9333ea);
        }
        .toggle-password {
            min-width: 44px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card reset-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="brand-header">
                            <img src="<?php echo e(asset('images/logo.jpg')); ?>" alt="MTCGS Logo" onerror="this.style.display='none'">
                            <span>MTCGS</span>
                        </div>

                        <h3 class="mb-4 text-center fw-bold">Reset Password</h3>

                        <div class="profile-summary d-flex align-items-center gap-3 mb-3">
                            <div class="profile-avatar">
                                <?php if($photoUrl): ?>
                                    <img src="<?php echo e($photoUrl); ?>" alt="Profile picture">
                                <?php else: ?>
                                    <span><?php echo e($initials); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark"><?php echo e($displayName); ?></div>
                                <div class="profile-detail"><strong>Email:</strong> <?php echo e($resetEmail ?: 'N/A'); ?></div>
                                <div class="profile-detail"><strong>ID:</strong> <?php echo e($employeeId); ?></div>
                                <div class="profile-detail"><strong>Branch:</strong> <?php echo e($branchName); ?></div>
                            </div>
                        </div>

                        <?php if($errors->any()): ?>
                            <div class="alert alert-danger mb-3"><?php echo e($errors->first()); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo e(route('password.update.reset')); ?>" autocomplete="off">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="token" value="<?php echo e($token); ?>">

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input id="email" type="email" name="email" class="form-control" value="<?php echo e($resetEmail); ?>" readonly aria-readonly="true">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input id="password" type="password" name="password" class="form-control" placeholder="Enter new password" autocomplete="new-password" required>
                                    <span class="input-group-text toggle-password" data-target="password" title="Show / hide password">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" placeholder="Confirm new password" autocomplete="new-password" required>
                                    <span class="input-group-text toggle-password" data-target="password_confirmation" title="Show / hide password">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (!input) return;

                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            });
        });
    </script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\auth\reset-password.blade.php ENDPATH**/ ?>