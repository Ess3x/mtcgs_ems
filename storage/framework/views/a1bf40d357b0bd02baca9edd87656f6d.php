<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - MTCGS EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --font-body: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            --font-head: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

            /* Light mode: White + Purple */
            --bg: #ffffff;
            --text: #0f172a;
            --muted: #6b7280;
            --card-bg: #ffffff;
            --nav-bg: rgba(255, 255, 255, 0.85);
            --accent-start: #7c3aed;
            --accent-end: #6d28d9;
            --accent-soft: rgba(124, 58, 237, 0.08);
            --border-soft: rgba(15, 23, 42, 0.08);
            --section-bg: #faf8ff;
        }

        .dark{
            /* Dark mode: Black + Violet */
            --bg: #05040a;
            --text: #e6eef8;
            --muted: #9aa6b2;
            --card-bg: #0c0a14;
            --nav-bg: rgba(6, 6, 10, 0.85);
            --accent-start: #9f7aea;
            --accent-end: #7c3aed;
            --accent-soft: rgba(159, 122, 234, 0.12);
            --border-soft: rgba(255, 255, 255, 0.08);
            --section-bg: #08070f;
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: var(--font-body);
            color: var(--text);
            background:
                linear-gradient(rgba(255, 255, 255, 0.78), rgba(250, 248, 255, 0.86)),
                url('<?php echo e(asset('images/homepage-bg.jpg')); ?>') center center / cover no-repeat fixed;
            transition: color .25s ease;
        }

        .dark body {
            background:
                linear-gradient(rgba(5, 4, 10, 0.82), rgba(8, 7, 15, 0.9)),
                url('<?php echo e(asset('images/homepage-bg.jpg')); ?>') center center / cover no-repeat fixed;
        }


        h1, h2, h3, h4, h5, .navbar-brand, .section-title, .btn { font-family: var(--font-head); }


        /* Navbar */
        .navbar {
            background: var(--nav-bg) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-soft);
            transition: background-color .25s ease, color .25s ease;
        }

        .navbar .navbar-brand, .navbar .nav-link, .navbar .btn { color: var(--text) !important; }

        /* ===== Nav link hover ===== */
        .navbar .nav-link {
            font-weight: 500;
            position: relative;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            transition: color .2s ease;
        }

        .navbar .nav-link::after {
            content: "";
            position: absolute;
            left: 0.75rem;
            right: 0.75rem;
            bottom: 4px;
            height: 2px;
            border-radius: 2px;
            background: linear-gradient(135deg, var(--accent-start), var(--accent-end));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .25s ease;
        }

        .navbar .nav-link:hover {
            color: var(--accent-start) !important;
        }

        .navbar .nav-link:hover::after {
            transform: scaleX(1);
        }


        /* ===== Header button hover ===== */
        .navbar .btn-cta:hover {
            transform: translateY(-2px);
            filter: brightness(1.08);
            box-shadow: 0 10px 22px rgba(124, 58, 237, 0.45);
        }

        #theme-toggle {
            transition: transform .15s ease, background-color .15s ease, color .15s ease, border-color .15s ease;
        }

        #theme-toggle:hover {
            transform: translateY(-2px) rotate(-15deg);
            background: var(--accent-start);
            border-color: var(--accent-start);
            color: #fff !important;
        }

        /* ===== Centered full-width hero (background comes from body image) ===== */
        .hero-section {
            position: relative;
            text-align: center;
            padding: 6rem 0 5rem;
            overflow: hidden;
            background: transparent;
        }



        .hero-title {
            font-weight: 800;
            font-size: clamp(2rem, 5vw, 3.25rem);
            line-height: 1.15;
            max-width: 900px;
            margin: 1.25rem auto 1.25rem;
        }

        .hero-title .grad {
            background: linear-gradient(135deg, var(--accent-start) 0%, var(--accent-end) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-lead {
            max-width: 640px;
            margin: 0 auto;
            font-size: 1.1rem;
        }

        .hero-banner {
            margin-top: 3rem;
            border-radius: 1.5rem;
            box-shadow: 0 30px 70px rgba(124, 58, 237, 0.22);
            max-width: 960px;
            width: 100%;
            max-height: 460px;
            object-fit: cover;
        }

        /* ===== Hero carousel ===== */
        .hero-carousel {
            margin: 3rem auto 0;
            max-width: 960px;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 30px 70px rgba(124, 58, 237, 0.22);
        }

        .hero-carousel .carousel-item {
            position: relative;
        }

        .hero-carousel .carousel-item img {
            width: 100%;
            height: 460px;
            object-fit: cover;
        }

        .hero-carousel .carousel-item::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.65) 0%, rgba(15, 23, 42, 0.1) 55%, transparent 100%);
        }

        .hero-carousel .carousel-caption {
            z-index: 2;
            text-align: left;
            left: 8%;
            right: 8%;
            bottom: 2.5rem;
        }

        .hero-carousel .carousel-caption h3 {
            font-weight: 700;
            font-size: 1.6rem;
            color: #fff;
        }

        .hero-carousel .carousel-caption p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0;
        }

        .hero-carousel .carousel-indicators [data-bs-target] {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: none;
        }

        @media (max-width: 575.98px) {
            .hero-carousel .carousel-item img { height: 300px; }
            .hero-carousel .carousel-caption h3 { font-size: 1.2rem; }
        }


        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1.1rem;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent-start);
            font-weight: 600;
            font-size: 0.85rem;
        }

        .btn-cta {
            background: linear-gradient(135deg, var(--accent-start) 0%, var(--accent-end) 100%);
            border: none;
            color: #fff;
            border-radius: 0.65rem;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(124, 58, 237, 0.3);
            transition: transform .15s ease, filter .15s ease, box-shadow .15s ease;
        }

        .btn-cta:hover {
            filter: brightness(1.06);
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(124, 58, 237, 0.4);
            color: #fff;
        }

        .btn-outline-accent {
            border: 2px solid var(--accent-start);
            color: var(--accent-start);
            border-radius: 0.65rem;
            font-weight: 600;
            background: transparent;
            transition: all .15s ease;
        }

        .btn-outline-accent:hover { background: var(--accent-start); color: #fff; }

        .text-muted { color: var(--muted) !important; }

        .section-title { color: var(--text); font-weight: 700; }

        .accent-line {
            width: 64px;
            height: 4px;
            border-radius: 2px;
            background: linear-gradient(135deg, var(--accent-start), var(--accent-end));
            margin: 0.75rem auto 0;
        }

        /* ===== Branches as horizontal list rows ===== */
        .branches-section { background: transparent; }


        .branch-row {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            background: var(--card-bg);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
            padding: 1.5rem 1.75rem;
            box-shadow: 0 8px 22px rgba(2, 6, 23, 0.06);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .branch-row:hover {
            transform: translateX(6px);
            box-shadow: 0 14px 32px rgba(124, 58, 237, 0.16);
        }

        .branch-avatar {
            flex: 0 0 auto;
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--accent-start) 0%, var(--accent-end) 100%);
            color: #fff;
            font-size: 1.5rem;
        }

        .branch-meta { flex: 1 1 auto; }
        .branch-meta h5 { margin: 0 0 0.35rem; font-weight: 600; }
        .branch-meta p { margin: 0; }

        .branch-id {
            flex: 0 0 auto;
            font-weight: 700;
            color: var(--accent-start);
            background: var(--accent-soft);
            padding: 0.5rem 1rem;
            border-radius: 999px;
            font-size: 0.9rem;
        }

        /* ===== About with stat strip ===== */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            height: 100%;
        }

        .stat-card .num {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--accent-start);
        }

        .about-check i { color: var(--accent-start); }

        /* ===== Contact ===== */
        .contact-section {
            background: transparent;
            padding-top: 3.5rem;
            padding-bottom: 3.5rem;
        }


        .contact-card {
            background: var(--card-bg) !important;
            color: var(--text) !important;
            border-radius: 1rem;
            border: 1px solid var(--border-soft);
            box-shadow: 0 8px 24px rgba(2, 6, 23, 0.08);
        }

        footer.page-footer {
            background: var(--nav-bg);
            color: var(--muted);
            border-top: 1px solid var(--border-soft);
            padding: 2rem 0;
            font-size: 0.9rem;
        }

        #theme-toggle {
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 575.98px) {
            .branch-row { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo e(url('/')); ?>">
                <img src="<?php echo e(asset('images/logo.jpg')); ?>" alt="MTCGS" style="max-height: 64px; width: auto;" onerror="this.style.display='none'">
                <span class="ms-2" style="font-size: 1.3rem;">MTCGS EMS</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbar" aria-controls="landingNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="landingNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Branches</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
                <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
                    <button id="theme-toggle" class="btn btn-sm btn-outline-secondary" aria-label="Toggle dark mode" title="Toggle dark mode">
                        <i id="theme-icon" class="fas fa-moon"></i>
                    </button>
                    <a href="<?php echo e(route('login')); ?>" class="btn btn-cta btn-sm px-3">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Centered hero -->
    <main id="home" class="hero-section">
        <div class="container">
            <span class="brand-badge">
                <i class="fas fa-school"></i>
                School Employee System
            </span>
            <h1 class="hero-title">
                <span class="grad">Mother Theresa Colegio</span> Group of Schools Employee Management System
            </h1>
            <p class="hero-lead text-muted">Track attendance, manage leave requests, review payroll, and view branch calendars all from one system designed for school staff and administrators.</p>
            <div class="mt-4 d-flex flex-wrap gap-3 justify-content-center">
                <a href="<?php echo e(route('login')); ?>" class="btn btn-cta btn-lg px-4">
                    <i class="fas fa-sign-in-alt me-2"></i>Login to EMS
                </a>
                <a href="#features" class="btn btn-outline-accent btn-lg px-4">
                    <i class="fas fa-circle-info me-2"></i>Learn More
                </a>
            </div>
            <!-- Hero carousel -->
            <div id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="4000">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                </div>
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=1400&q=80" alt="Employee Management">
                        <div class="carousel-caption d-none d-md-block">
                            <h3>One System for Your Whole Staff</h3>
                            <p>Attendance, leave, and payroll unified in a single portal.</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1400&q=80" alt="Manage Leave Requests">
                        <div class="carousel-caption d-none d-md-block">
                            <h3>Manage Leave with Ease</h3>
                            <p>Submit, approve, and track leave requests in real time.</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?auto=format&fit=crop&w=1400&q=80" alt="Payroll and Reports">
                        <div class="carousel-caption d-none d-md-block">
                            <h3>Payroll Made Simple</h3>
                            <p>Accurate payroll and reports built on your attendance data.</p>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </main>


    <!-- Branches as list rows -->
    <section id="features" class="py-5 branches-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Branches Associated with MTCGS</h2>
                <div class="accent-line"></div>
                <p class="text-muted mt-3">Serving school staff and administrators across our campuses.</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-8">
                    <div class="branch-row mb-4">
                        <span class="branch-avatar"><i class="fas fa-building"></i></span>
                        <div class="branch-meta">
                            <h5>Mother Theresa Colegio de Iriga</h5>
                            <p class="text-muted"><i class="fas fa-location-dot me-2"></i>Iriga City, Camarines Sur</p>
                        </div>
                        <span class="branch-id">ID: 11111</span>
                    </div>
                    <div class="branch-row">
                        <span class="branch-avatar"><i class="fas fa-building"></i></span>
                        <div class="branch-meta">
                            <h5>Mother Theresa Colegio de Buhi</h5>
                            <p class="text-muted"><i class="fas fa-location-dot me-2"></i>Buhi, Camarines Sur</p>
                        </div>
                        <span class="branch-id">ID: 22222</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About with stat strip -->
    <section id="about" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">About MTCGS EMS</h2>
                <div class="accent-line"></div>
            </div>
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <p class="text-muted">MTCGS EMS is built to support school branches with employee attendance, leave management, payroll, and verification workflows. The system is designed for ease of use by branch admins, finance officers, and employees.</p>
                    <ul class="list-unstyled text-muted mt-4">
                        <li class="mb-3 about-check"><i class="fas fa-check-circle me-2"></i>Secure login and branch-based access</li>
                        <li class="mb-3 about-check"><i class="fas fa-check-circle me-2"></i>Automated leave request notifications</li>
                        <li class="mb-3 about-check"><i class="fas fa-check-circle me-2"></i>Attendance and payroll integration</li>
                    </ul>
                </div>
                <div class="col-lg-5">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="num">2</div>
                                <div class="text-muted small">Campuses</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="num"><i class="fas fa-shield-halved"></i></div>
                                <div class="text-muted small">Secure Access</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="num"><i class="fas fa-calendar-check"></i></div>
                                <div class="text-muted small">Leave &amp; Attendance</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="num"><i class="fas fa-file-invoice-dollar"></i></div>
                                <div class="text-muted small">Payroll</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact -->
    <section id="contact" class="py-5 contact-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Get Started</h2>
                <div class="accent-line"></div>
                <p class="text-muted mt-3">Ready to join MTCGS EMS? Register now and wait for a quick admin approval.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="p-4 contact-card">
                        <div class="row align-items-center gy-3">
                            <div class="col-md-8">
                                <strong><i class="fas fa-headset me-2 text-primary"></i>Need help with your account?</strong>
                                <p class="mb-0 text-muted mt-2">Contact your branch administrator or support at mepoopalaretnam@mycspc.edu.ph.</p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <a href="<?php echo e(route('login')); ?>" class="btn btn-cta px-4">
                                    <i class="fas fa-sign-in-alt me-1"></i>Login
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="page-footer">
        <div class="container text-center">
            <p class="mb-1 fw-semibold">MTCGS Employee Management System</p>
            <p class="text-muted small mb-0">Secure portal for employees, branch admins, and finance officers.</p>
            <p class="text-muted small mb-0 mt-2">&copy; <?php echo e(date('Y')); ?> MTCGS - All rights reserved</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function(){
            const toggle = document.getElementById('theme-toggle');
            const icon = document.getElementById('theme-icon');

            function applyTheme(name){
                if(name === 'dark'){
                    document.documentElement.classList.add('dark');
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    document.documentElement.classList.remove('dark');
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            }

            // Initialize from localStorage or system preference
            const saved = localStorage.getItem('mtcgs_theme');
            if(saved){
                applyTheme(saved);
            } else {
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                applyTheme(prefersDark ? 'dark' : 'light');
            }

            toggle.addEventListener('click', function(){
                const isDark = document.documentElement.classList.contains('dark');
                const next = isDark ? 'light' : 'dark';
                applyTheme(next);
                localStorage.setItem('mtcgs_theme', next);
            });
        })();
    </script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\landing.blade.php ENDPATH**/ ?>