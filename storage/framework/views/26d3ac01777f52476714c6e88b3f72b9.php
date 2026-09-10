<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>MTCGS-EMS - <?php echo $__env->yieldContent('title'); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        /* ===== Base ===== */
        :root {
            --sidebar-width: 248px;
            --navbar-height: 68px;
            --content-bg: #f3f4ff;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
            --text-color: #1f2937;
            --muted-color: #6b7280;
            --primary: #800080;
            --primary-hover: #6a006a;
            --sidebar-gradient: linear-gradient(180deg, #9c5ae2 0%, #7b2d9a 45%, #631b73 100%);
            --transition: all 0.25s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--content-bg);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
            line-height: 1.5;
        }

        /* ===== Sidebar ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--sidebar-gradient);
            transition: transform 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            box-shadow: 12px 0 32px rgba(88, 28, 135, 0.12);
        }
        .sidebar.is-collapsed { transform: translateX(-100%); }

        .sidebar-header {
            padding: 1.1rem 0.9rem 0.9rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.14);
        }
        .sidebar-header img {
            display: block;
            width: 58px;
            height: 58px;
            margin: 0 auto;
            object-fit: contain;
            border-radius: 14px;
            background: rgba(255,255,255,0.12);
            padding: 6px;
            box-shadow: 0 10px 20px rgba(58, 16, 77, 0.2);
        }
        .sidebar-header h5 {
            color: #fff;
            margin: 0.7rem 0 0;
            font-weight: 700;
            letter-spacing: 0.3px;
            font-size: 1.05rem;
        }
        .sidebar-header small { color: rgba(255,255,255,0.72); font-size: 0.72rem; }

        .sidebar-nav { padding: 0.7rem 0.7rem; flex: 1; }
        .nav-section { margin: 0.35rem 0 0.55rem; }
        .nav-section-label {
            color: rgba(255,255,255,0.62);
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 0.55rem 0.75rem 0.4rem;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.82);
            padding: 0.66rem 0.8rem;
            margin: 2px 0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-size: 0.9rem;
            font-weight: 500;
            line-height: 1.2;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid transparent;
            position: relative;
        }
        .sidebar .nav-link i { width: 18px; font-size: 0.95rem; text-align: center; }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
            transform: translateX(1px);
            border-color: rgba(255,255,255,0.08);
        }
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.18);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.1), 0 8px 18px rgba(35, 4, 49, 0.1);
        }
        .sidebar .badge {
            font-size: 0.68rem;
            padding: 0.28rem 0.45rem;
            line-height: 1.2;
        }
        .sidebar hr { border-color: rgba(255,255,255,0.12); margin: 0.75rem 0.5rem; }

        /* ===== Top Navbar ===== */
        .top-navbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--navbar-height);
            z-index: 999;
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
            padding: 0 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            transition: left 0.3s ease;
        }
        .navbar-brand-text {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #111827;
            min-width: 0;
            flex: 1 1 auto;
        }
        .navbar-brand-text i { color: var(--primary); flex-shrink: 0; }
        .brand-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }
        .brand-name-short { display: none; }
        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: nowrap;
            flex-shrink: 0;
        }
        .navbar-actions .dropdown { position: relative; z-index: 1101; }
        .navbar-actions .dropdown-menu { z-index: 1102; }
        .notification-menu { min-width: 320px; max-width: 360px; }
        .notification-item { white-space: normal; }
        .notification-item.unread { background: rgba(13, 110, 253, 0.08); }
        .notification-item small { color: var(--muted-color); }


        .theme-toggle {
            border-radius: 10px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary { background-color: #800080; border-color: #800080; }
        .btn-primary:hover, .btn-primary:focus { background-color: #6a006a; border-color: #6a006a; }
        .btn-info { background-color: #00bcd4; border-color: #00bcd4; color: #fff; }
        .btn-success { background-color: #32cd32; border-color: #32cd32; color: #102010; }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; }
        .progress-bar { background-color: #32cd32; }

        /* ===== Main Content ===== */
        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            padding: calc(var(--navbar-height) + 1.2rem) 1.2rem 1.2rem;
            transition: margin-left 0.3s ease, width 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .main-content main { flex: 1; }
        .main-content > footer {
            margin-top: 2rem;
            padding: 1rem 0;
            border-top: 1px solid var(--border-color);
            text-align: center;
            color: var(--muted-color);
            background: transparent;
        }

        /* ===== Cards & Components ===== */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 14px;
            background: var(--card-bg);
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            transition: box-shadow 0.2s ease, transform 0.2s ease, background-color 0.3s ease;
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        .card-body { padding: 1.25rem; }

        .dashboard-hero {
            border: 0;
            border-radius: 18px;
            color: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            overflow: hidden;
            position: relative;
        }
        .dashboard-hero::after {
            content: '';
            position: absolute;
            right: -50px;
            top: -50px;
            width: 220px;
            height: 220px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .dashboard-hero .card-body { padding: 1.5rem 1.75rem; position: relative; z-index: 1; }

        .dashboard-stat-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .dashboard-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon i { font-size: 1.25rem; }

        .table-responsive {
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            -webkit-overflow-scrolling: touch;
        }
        .table-responsive table { margin-bottom: 0; }
        .table thead th {
            background: #f8fafc;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .table tbody td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
        }

        .employee-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            flex-shrink: 0;
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* ===== Menu Toggle ===== */
        .menu-toggle {
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1100;
            background: var(--primary);
            border: none;
            color: white;
            width: 44px;
            height: 44px;
            border-radius: 10px;
            font-size: 1.1rem;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
            display: none;
        }

        /* ===== Dark Mode ===== */
        body.dark-mode {
            --content-bg: #0f172a;
            --card-bg: #1e293b;
            --border-color: #334155;
            --text-color: #e2e8f0;
            --muted-color: #94a3b8;
            background-color: var(--content-bg);
            color: var(--text-color);
        }
        body.dark-mode .top-navbar { background: #1e293b; border-bottom-color: var(--border-color); }
        body.dark-mode .navbar-brand-text { color: var(--text-color); }
        body.dark-mode .top-navbar small { color: var(--muted-color); }
        body.dark-mode .card { background-color: var(--card-bg); border-color: var(--border-color); }
        body.dark-mode .dashboard-stat-card { color: var(--text-color); }
        body.dark-mode .profile-page .fw-semibold,
        body.dark-mode .profile-page .text-dark { color: var(--text-color) !important; }
        .dashboard-days-late-card { background: linear-gradient(135deg, #f59e0b, #f97316); border: 0; }
        body.dark-mode .dashboard-days-late-card { background: #DC95FF; }
        body.dark-mode .card-header,
        body.dark-mode .card-header h1,
        body.dark-mode .card-header h2,
        body.dark-mode .card-header h3,
        body.dark-mode .card-header h4,
        body.dark-mode .card-header h5,
        body.dark-mode .card-header h6,
        body.dark-mode main h1,
        body.dark-mode main h2,
        body.dark-mode main h3,
        body.dark-mode main h4,
        body.dark-mode main h5,
        body.dark-mode main h6,
        body.dark-mode main label { color: var(--text-color); }
        body.dark-mode .card-header { background-color: var(--card-bg); border-bottom-color: var(--border-color); }
        body.dark-mode .table { color: var(--text-color); }
        body.dark-mode .table thead th { background: #0f172a; color: var(--text-color); border-color: var(--border-color); }
        body.dark-mode .table tbody tr,
        body.dark-mode .table tbody td { background-color: #4647AE; border-color: rgba(255,255,255,0.18); color: #ffffff; }
        body.dark-mode .table-hover tbody tr:hover,
        body.dark-mode .table-hover tbody tr:hover td { background-color: #5556bc; color: #ffffff; }
        body.dark-mode .dropdown-menu { background: var(--card-bg); border-color: var(--border-color); }
        body.dark-mode .dropdown-item { color: var(--text-color); }
        body.dark-mode .dropdown-item:hover { background: rgba(255,255,255,0.06); }
        body.dark-mode .notification-item.unread { background: rgba(220, 149, 255, 0.12); }
        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background: #0f172a;
            color: var(--text-color);
            border-color: var(--border-color);
        }
        body.dark-mode .form-control.text-dark,
        body.dark-mode .form-select.text-dark { color: var(--text-color) !important; }
        body.dark-mode .alert {
            background-color: #1e293b;
            border-color: var(--border-color);
            color: var(--text-color);
        }
        body.dark-mode .btn-light {
            background: #1e293b;
            color: var(--text-color);
            border-color: var(--border-color);
        }
        body.dark-mode main > footer { color: var(--muted-color); border-top-color: var(--border-color); }
        body.dark-mode .text-muted { color: var(--muted-color) !important; }
        body.dark-mode .bg-white,
        body.dark-mode .card-header.bg-white { background-color: var(--card-bg) !important; }
        body.dark-mode .bg-light { background-color: #0f172a !important; color: var(--text-color); }
        body.dark-mode .border-top,
        body.dark-mode .border-bottom { border-color: var(--border-color) !important; }

        /* ===== Payroll dark-mode overrides ===== */
        body.dark-mode .payroll-summary-card,
        body.dark-mode .payroll-summary-card .card-body,
        body.dark-mode .payroll-section {
            background: #182233 !important;
            border-color: rgba(148, 163, 184, 0.45) !important;
            color: #f8fafc !important;
        }
        body.dark-mode .payroll-summary-label,
        body.dark-mode .payroll-section label,
        body.dark-mode .payroll-section .form-label,
        body.dark-mode .payroll-section-header h5,
        body.dark-mode .payroll-summary-value,
        body.dark-mode .payroll-readonly,
        body.dark-mode .payroll-field,
        body.dark-mode .payroll-field.form-control,
        body.dark-mode .payroll-field.form-select,
        body.dark-mode .payroll-amount,
        body.dark-mode .payroll-section p,
        body.dark-mode .text-muted,
        body.dark-mode p.text-muted {
            color: #f8fafc !important;
        }
        body.dark-mode .payroll-summary-label {
            opacity: 1 !important;
            color: #cbd5e1 !important;
        }
        body.dark-mode .payroll-readonly,
        body.dark-mode .payroll-field,
        body.dark-mode .payroll-field.form-control,
        body.dark-mode .payroll-field.form-select {
            background: #0f172a !important;
            border-color: rgba(148, 163, 184, 0.45) !important;
            -webkit-text-fill-color: #f8fafc !important;
        }
        body.dark-mode .payroll-section-header .badge {
            background: rgba(99, 102, 241, 0.18) !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .payroll-field:focus {
            box-shadow: 0 0 0 0.2rem rgba(167, 139, 250, 0.25) !important;
            border-color: #a78bfa !important;
        }

        /* ===== Responsive ===== */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.is-open { transform: translateX(0); }
            /* leave room for the fixed hamburger toggle on the left */
            .top-navbar { left: 0; width: 100%; padding-left: 72px; }
            .main-content { margin-left: 0; width: 100%; padding: calc(var(--navbar-height) + 1rem) 1rem 1rem; }
            .menu-toggle { display: flex; align-items: center; justify-content: center; }
            /* keep the theme toggle compact, icon-only */
            .theme-toggle .theme-label { display: none; }
            .theme-toggle { padding: 0.375rem 0.6rem; }
        }
        @media (max-width: 767.98px) {
            /* swap long school name for the short abbreviation */
            .brand-name-full { display: none; }
            .brand-name-short { display: inline; }
        }
        @media (max-width: 576px) {
            .top-navbar { padding: 0 0.75rem 0 60px; height: 64px; gap: 0.5rem; }
            .menu-toggle { top: 12px; left: 12px; width: 40px; height: 40px; }
            .main-content { padding-top: calc(64px + 1rem); }
            .navbar-brand-text { font-size: 0.9rem; gap: 0.35rem; }
            .navbar-actions { gap: 0.4rem; }
            .stat-value { font-size: 1.5rem !important; }
            .welcome-title { font-size: 1.25rem !important; }
        }
        @media (max-width: 768px) {
            .table-hide-mobile { display: none; }
            .table-responsive { font-size: 0.85rem; }
        }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo e(asset('images/logo.jpg')); ?>" alt="MTCGS" onerror="this.style.display='none'">
            <h5>MTCGS-EMS</h5>
            <small>v2.0</small>
        </div>
        <nav class="sidebar-nav nav flex-column">
            <div class="nav-section">
                <div class="nav-section-label">Overview</div>
                <?php if(Auth::user()->role === 'employee'): ?>
                    <a class="nav-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                <?php else: ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo e(request()->routeIs(['dashboard', 'analytics.*']) ? 'active' : ''); ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>" href="<?php echo e(route('dashboard')); ?>">
                                Dashboard
                            </a>
                            <a class="dropdown-item <?php echo e(request()->routeIs('analytics.*') ? 'active' : ''); ?>" href="<?php echo e(route('analytics.index')); ?>">
                                Analytics
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if(Auth::user()->role === 'admin'): ?>
                <div class="nav-section">
                    <div class="nav-section-label">Administration</div>
                    <?php if(Auth::user()->admin_type === 'super_admin'): ?>
                        <a class="nav-link <?php echo e(request()->routeIs('admin.branches*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.branches.index')); ?>">
                            <i class="fas fa-building"></i> Branches
                        </a>
                    <?php endif; ?>
                    <?php if(Auth::user()->admin_type === 'super_admin'): ?>
                        <a class="nav-link <?php echo e(request()->routeIs('admin.user-management*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.user-management')); ?>">
                            <i class="fas fa-users-cog"></i> Users & Admin
                        </a>
                    <?php endif; ?>
                    <a class="nav-link <?php echo e(request()->routeIs('admin.employees*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.employees')); ?>">
                        <i class="fas fa-users"></i> Employee
                    </a>
                    <?php if(Auth::user()->admin_type === 'branch_admin' || Auth::user()->role === 'branch_head'): ?>
                        <a class="nav-link <?php echo e(request()->routeIs('employee.dtr.*') ? 'active' : ''); ?>" href="<?php echo e(route('employee.dtr.index')); ?>">
                            <i class="fas fa-clock"></i> My DTR
                        </a>
                    <?php endif; ?>
                    <a class="nav-link <?php echo e(request()->routeIs('admin.dtr.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.dtr.index')); ?>">
                        <i class="fas fa-clock"></i> DTR Management
                        <?php $pendingDTRs = \App\Models\DTR::where('status', 'submitted')->count(); ?>
                        <?php if($pendingDTRs > 0): ?>
                            <span class="badge bg-warning ms-auto"><?php echo e($pendingDTRs); ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link <?php echo e(request()->routeIs('admin.payroll*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.payroll.periods')); ?>">
                        <i class="fas fa-calculator"></i> Payroll
                    </a>
                    <a class="nav-link <?php echo e(request()->routeIs('admin.shifts.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.shifts.index')); ?>">
                        <i class="fas fa-calendar-days"></i> Shifts & Schedules
                    </a>
                    <a class="nav-link <?php echo e(request()->routeIs('employee.payslips') ? 'active' : ''); ?>" href="<?php echo e(route('employee.payslips')); ?>">
                        <i class="fas fa-file-invoice-dollar"></i> Payslip
                    </a>
                    <a class="nav-link <?php echo e(request()->routeIs('cash-advances.*') ? 'active' : ''); ?>" href="<?php echo e(route('cash-advances.index')); ?>">
                        <i class="fas fa-hand-holding-usd"></i> Cash Advance
                    </a>
                    <?php if(Auth::user()->admin_type === 'super_admin'): ?>
                        <a class="nav-link <?php echo e(request()->routeIs('admin.permissions') ? 'active' : ''); ?>" href="<?php echo e(route('admin.permissions')); ?>">
                            <i class="fas fa-lock-open"></i> Admin Permissions
                        </a>
                        <a class="nav-link <?php echo e(request()->routeIs('admin.authority') ? 'active' : ''); ?>" href="<?php echo e(route('admin.authority')); ?>">
                            <i class="fas fa-user-shield"></i> Employee Creation Authority
                        </a>
                        <a class="nav-link <?php echo e(request()->routeIs('admin.audit-logs') ? 'active' : ''); ?>" href="<?php echo e(route('admin.audit-logs')); ?>">
                            <i class="fas fa-history"></i> Audit Trail
                        </a>
                    <?php endif; ?>
                    <a class="nav-link <?php echo e(request()->routeIs('admin.biometric') ? 'active' : ''); ?>" href="<?php echo e(route('admin.biometric')); ?>">
                        <i class="fas fa-fingerprint"></i> Biometric Setup
                    </a>
                    <?php if(Auth::user()->admin_type === 'super_admin'): ?>
                        <a class="nav-link <?php echo e(request()->routeIs('admin.devices.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.devices.index')); ?>">
                            <i class="fas fa-desktop"></i> Device Access
                        </a>
                        <a class="nav-link <?php echo e(request()->routeIs('admin.branch-heads.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.branch-heads.index')); ?>">
                            <i class="fas fa-user-tie"></i> Branch Admins
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if(in_array(Auth::user()->role, ['finance_officer', 'finance_head'], true)): ?>
                <div class="nav-section">
                    <div class="nav-section-label">Finance</div>
                    <a class="nav-link <?php echo e(request()->routeIs('finance.employees') ? 'active' : ''); ?>" href="<?php echo e(route('finance.employees')); ?>">
                        <i class="fas fa-users"></i> Branch Employees
                    </a>
                    <a class="nav-link <?php echo e(request()->routeIs('employee.dtr.*') ? 'active' : ''); ?>" href="<?php echo e(route('employee.dtr.index')); ?>">
                        <i class="fas fa-clock"></i> My DTR
                    </a>
                    <a class="nav-link <?php echo e(request()->routeIs('admin.payroll*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.payroll.periods')); ?>">
                        <i class="fas fa-calculator"></i> Payroll
                    </a>
                    <a class="nav-link <?php echo e(request()->routeIs('employee.payslips') ? 'active' : ''); ?>" href="<?php echo e(route('employee.payslips')); ?>">
                        <i class="fas fa-file-invoice-dollar"></i> Payslip
                    </a>
                    <a class="nav-link <?php echo e(request()->routeIs('cash-advances.*') ? 'active' : ''); ?>" href="<?php echo e(route('cash-advances.index')); ?>">
                        <i class="fas fa-hand-holding-usd"></i> Cash Advance
                    </a>
                    <?php if(Auth::user()->isFinanceHead()): ?>
                        <a class="nav-link <?php echo e(request()->routeIs('admin.dtr.*') ? 'active' : ''); ?>" href="<?php echo e(route('admin.dtr.index')); ?>">
                            <i class="fas fa-clock"></i> DTR Management
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="nav-section">
                <div class="nav-section-label">Workspace</div>
                <a class="nav-link <?php echo e(request()->routeIs('leave.*') ? 'active' : ''); ?>" href="<?php echo e(route('leave.index')); ?>">
                    <i class="fas fa-calendar-alt"></i> Leave
                </a>

                <a class="nav-link <?php echo e(request()->routeIs('calendar.*') ? 'active' : ''); ?>" href="<?php echo e(route('calendar.index')); ?>">
                    <i class="fas fa-calendar-check"></i> Calendar
                </a>

                <?php if(in_array(Auth::user()->role, ['employee', 'branch_head'], true)): ?>
                    <a class="nav-link <?php echo e(request()->routeIs('employee.dtr.*') ? 'active' : ''); ?>" href="<?php echo e(route('employee.dtr.index')); ?>">
                        <i class="fas fa-clock"></i> DTR
                    </a>
                <?php endif; ?>

                <?php if(Auth::user()->role === 'employee'): ?>
                    <a class="nav-link <?php echo e(request()->routeIs('employee.payslips') ? 'active' : ''); ?>" href="<?php echo e(route('employee.payslips')); ?>">
                        <i class="fas fa-file-invoice-dollar"></i> My Payslips
                    </a>
                    <a class="nav-link <?php echo e(request()->routeIs('cash-advances.*') ? 'active' : ''); ?>" href="<?php echo e(route('cash-advances.index')); ?>">
                        <i class="fas fa-hand-holding-usd"></i> Cash Advance
                    </a>
                <?php endif; ?>
            </div>

            <hr>
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="nav-link w-100 text-start" style="background: none; border: none; cursor: pointer;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </nav>
    </div>

    <div class="top-navbar">
        <div class="navbar-brand-text">
            <i class="fas fa-building"></i>
            <span class="brand-name brand-name-full">Mother Theresa Colegio Group of Schools</span>
            <span class="brand-name brand-name-short">MTCGS</span>
            <?php if(session('user_branch')): ?>
                <span class="badge bg-info ms-2 d-none d-md-inline-flex align-items-center gap-1">
                    <i class="fas fa-location-dot"></i> <?php echo e(strtoupper(session('user_branch'))); ?> Branch
                </span>
            <?php endif; ?>
        </div>
        <div class="navbar-actions">
            <?php
                $headerNotifications = Auth::user()->notifications()->latest()->limit(5)->get();
                $unreadNotificationCount = Auth::user()->unreadNotifications()->count();
            ?>
            <div class="dropdown">
                <button class="btn btn-light btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if($unreadNotificationCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo e($unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount); ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0 notification-menu">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <strong>Notifications</strong>
                        <?php if($unreadNotificationCount > 0): ?>
                            <form method="POST" action="<?php echo e(route('notifications.read-all')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-link btn-sm p-0">Mark all read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php $__empty_1 = true; $__currentLoopData = $headerNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <a href="<?php echo e(route('notifications.read', $notification->id)); ?>" class="dropdown-item notification-item <?php echo e($notification->read_at ? '' : 'unread'); ?> py-2">
                            <strong class="d-block"><?php echo e($notification->data['title'] ?? 'Notification'); ?></strong>
                            <span class="d-block"><?php echo e($notification->data['message'] ?? ''); ?></span>
                            <small><?php echo e($notification->created_at->diffForHumans()); ?></small>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="px-3 py-4 text-center text-muted">No notifications yet.</div>
                    <?php endif; ?>
                </div>
            </div>
            <button id="themeToggle" class="btn btn-outline-secondary btn-sm theme-toggle">
                <i id="themeIcon" class="fas fa-moon"></i>
                <span id="themeLabel" class="theme-label">Dark Mode</span>
            </button>
            <?php
                $headerProfile = Auth::user()->profile;
                if (!$headerProfile && Auth::user()->role === 'admin') {
                    $headerProfile = Auth::user()->getAdminProfile();
                }
                $headerPhotoUrl = null;
                if ($headerProfile?->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($headerProfile->profile_photo)) {
                    $headerPhotoUrl = route('profile.photo', [strtolower(class_basename($headerProfile)), $headerProfile->id]) . '?v=' . $headerProfile->updated_at?->timestamp;
                }
            ?>
            <div class="dropdown">
                <button id="profileMenuToggle" class="btn btn-light btn-sm d-inline-flex align-items-center gap-2 text-decoration-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Open account menu">
                    <?php if($headerPhotoUrl): ?>
                        <img src="<?php echo e($headerPhotoUrl); ?>" alt="Profile photo" class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                    <span class="d-none d-sm-inline"><?php echo e(Auth::user()->name); ?></span>
                    <span class="badge bg-info ms-1"><?php echo e(ucfirst(Auth::user()->role)); ?></span>
                    <i class="fas fa-chevron-down ms-2 small"></i>
                </button>
                <ul id="profileMenu" class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo e(route('profile')); ?>"><i class="fas fa-user me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-content" id="mainContent">
        <main>
            <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show"><?php echo e(session('success')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if(session('error') && !(in_array(Auth::user()->role, ['finance_officer', 'finance_head'], true) && session('error') === 'Employee profile not found')): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?php echo e(session('error')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php echo $__env->yieldContent('content'); ?>
        </main>

        <footer>
            <small>&copy; <?php echo e(date('Y')); ?> MTCGS-EMS &nbsp;·&nbsp; Employee Management System with Biometric Attendance and Payroll Integration</small>
        </footer>
    </div>

    <script>
        (function () {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');

            function closeSidebar() {
                if (window.innerWidth <= 992 && sidebar) {
                    sidebar.classList.remove('is-open');
                }
            }

            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    sidebar.classList.toggle('is-open');
                });

                document.addEventListener('click', function (event) {
                    if (window.innerWidth <= 992 && sidebar.classList.contains('is-open')) {
                        if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                            closeSidebar();
                        }
                    }
                });
            }

            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            const themeLabel = document.getElementById('themeLabel');

            function applyTheme(theme) {
                if (theme === 'dark') {
                    document.body.classList.add('dark-mode');
                    themeIcon.classList.replace('fa-moon', 'fa-sun');
                    themeLabel.textContent = 'Light Mode';
                    themeToggle.classList.remove('btn-outline-secondary');
                    themeToggle.classList.add('btn-outline-light');
                } else {
                    document.body.classList.remove('dark-mode');
                    themeIcon.classList.replace('fa-sun', 'fa-moon');
                    themeLabel.textContent = 'Dark Mode';
                    themeToggle.classList.remove('btn-outline-light');
                    themeToggle.classList.add('btn-outline-secondary');
                }
                localStorage.setItem('mtcgsTheme', theme);
            }

            if (themeToggle) {
                const saved = localStorage.getItem('mtcgsTheme') || 'light';
                applyTheme(saved);
                themeToggle.addEventListener('click', function () {
                    applyTheme(document.body.classList.contains('dark-mode') ? 'light' : 'dark');
                });
            }
        })();
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views/layouts/app.blade.php ENDPATH**/ ?>