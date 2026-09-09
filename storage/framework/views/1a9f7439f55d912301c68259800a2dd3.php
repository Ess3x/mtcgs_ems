<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Login - MTCGS-EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --accent-start: #7c3aed;
            --accent-end: #6d28d9;
            --accent-pink: #FF62BB;
            --font-body: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            --font-head: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--font-body);
            background: #f4f1fb;
        }

        h1, h2, h3, .brand-top span, .btn-login { font-family: var(--font-head); }


        /* Split-screen shell */
        .auth-shell {
            display: flex;
            min-height: 100vh;
        }

        /* Left branding panel */
        .auth-brand {
            flex: 1 1 50%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem;
            color: #fff;
            background: url('<?php echo e(asset('images/Login-BG.jpg')); ?>') no-repeat center center;
            background-size: cover;
            overflow: hidden;
        }

        .auth-brand::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(76, 29, 149, 0.85) 0%, rgba(109, 40, 217, 0.8) 45%, rgba(255, 98, 187, 0.6) 100%);
            z-index: 0;
        }

        .auth-brand > * { position: relative; z-index: 1; }

        .brand-top {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 700;
            font-size: 1.35rem;
            letter-spacing: 0.5px;
        }

        .brand-top img {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            background: #fff;
            padding: 6px;
            object-fit: contain;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
        }


        .brand-hero h1 {
            font-weight: 700;
            font-size: 2.4rem;
            line-height: 1.2;
            margin-bottom: 1rem;
        }

        .brand-hero p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 1.05rem;
            max-width: 460px;
        }

        .brand-points {
            list-style: none;
            padding: 0;
            margin: 1.75rem 0 0;
        }

        .brand-points li {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 0.85rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .brand-points li i {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.18);
        }

        .brand-foot {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Right form panel */
        .auth-form-panel {
            flex: 1 1 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem;
        }

        .auth-form-inner {
            width: 100%;
            max-width: 400px;
        }

        .auth-form-inner .form-head {
            margin-bottom: 2rem;
        }

        .auth-form-inner .form-head h2 {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.35rem;
        }

        .auth-form-inner .form-head p {
            color: #6b7280;
            margin: 0;
            font-size: 0.95rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #374151;
        }

        .input-group-text {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-right: none;
            color: var(--accent-start);
        }

        .form-control {
            border: 1px solid #e5e7eb;
            padding: 0.7rem 0.9rem;
            color: #333;
        }

        .input-group .form-control { border-left: none; }

        .form-control:focus {
            border-color: rgba(124, 58, 237, 0.6);
            box-shadow: 0 0 0 0.2rem rgba(124, 58, 237, 0.15);
        }

        .toggle-password {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-left: none;
            color: #9ca3af;
            cursor: pointer;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--accent-start) 0%, var(--accent-pink) 100%);
            border: none;
            padding: 0.75rem;
            border-radius: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: transform 0.15s ease, filter 0.15s ease, box-shadow 0.15s ease;
        }

        .btn-login:hover {
            filter: brightness(1.08);
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.35);
            color: #fff;
        }

        .btn-login:active { transform: translateY(1px); }

        .back-link {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.88rem;
            transition: color 0.15s ease;
        }

        .back-link:hover { color: var(--accent-start); }

        /* Stack on smaller screens */
        @media (max-width: 991.98px) {
            .auth-shell { flex-direction: column; }
            .auth-brand {
                flex: none;
                padding: 2.5rem 2rem;
            }
            .brand-hero h1 { font-size: 1.8rem; }
            .brand-points { display: none; }
            .auth-form-panel { padding: 2.5rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <!-- Left branding panel -->
        <aside class="auth-brand">
            <div class="brand-top">
                <img src="<?php echo e(asset('images/logo.jpg')); ?>" alt="MTCGS" onerror="this.style.display='none'">
                <span>MTCGS-EMS</span>
            </div>

            <div class="brand-hero">
                <h1>Welcome back to the Employee Management System</h1>
                <p>Mother Theresa Colegio Group of Schools — one portal for attendance, leave, payroll, and calendars.</p>
                <ul class="brand-points">
                    <li><i class="fas fa-shield-halved"></i> Secure branch-based access</li>
                    <li><i class="fas fa-calendar-check"></i> Leave &amp; attendance in one place</li>
                    <li><i class="fas fa-file-invoice-dollar"></i> Payroll made simple</li>
                </ul>
            </div>

            <div class="brand-foot">
                &copy; <?php echo e(date('Y')); ?> MTCGS - All rights reserved
            </div>
        </aside>

        <!-- Right form panel -->
        <main class="auth-form-panel">
            <div class="auth-form-inner">
                <div class="form-head">
                    <h2>Sign in</h2>
                    <p>Enter your credentials to access your account.</p>
                </div>

                <?php if(session('error')): ?>
                    <div class="alert alert-danger py-2"><?php echo e(session('error')); ?></div>
                <?php endif; ?>

                <?php if(session('success')): ?>
                    <div class="alert alert-success py-2"><?php echo e(session('success')); ?></div>
                <?php endif; ?>

                <?php if($errors->any()): ?>
                    <div class="alert alert-danger py-2">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div><?php echo e($error); ?></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(route('login')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" autocomplete="username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="passwordInput" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" autocomplete="current-password" required>
                            <span class="input-group-text toggle-password" id="togglePassword" title="Show / hide password">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login text-white w-100">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="<?php echo e(url('/')); ?>" class="back-link">
                        <i class="fas fa-arrow-left me-1"></i> Back to Home
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Show / hide password toggle
        (function () {
            const toggle = document.getElementById('togglePassword');
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('toggleIcon');
            if (toggle && input) {
                toggle.addEventListener('click', function () {
                    const isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }
        })();
    </script>
</body>
</html>
<?php /**PATH C:\Users\STUDENT\Desktop\mtcgs_ems\resources\views\auth\login.blade.php ENDPATH**/ ?>