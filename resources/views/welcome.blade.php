<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTCGS-EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #8A2BE2 0%, #4B0082 100%);
            min-height: 100vh;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .welcome-card {
            max-width: 540px;
            margin: auto;
        }
        .hero-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .hero-header img {
            max-width: 140px;
            height: auto;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 2rem;
        }
    </style>
</head>
<body>
    <div class="container min-vh-100 d-flex align-items-center justify-content-center">
        <div class="row justify-content-center w-100">
            <div class="col-12 col-md-10 col-lg-8 welcome-card">
                <div class="card text-center p-4">
                    <div class="card-body">
                        <div class="hero-header mb-3">
                            <img src="{{ asset('images/logo.jpg') }}" alt="MTCGS" class="mb-3" style="max-width: 140px; width: 100%; height: auto;" onerror="this.style.display='none'">
                            <h1 class="display-5 fw-bold mt-3">MTCGS-EMS</h1>
                            <p class="lead text-muted mb-1">Employee Management System</p>
                            <p class="text-muted mb-0">Mother Theresa Colegio Group of Schools</p>
                        </div>
                        <hr>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a href="{{ route('login') }}" class="btn btn-login text-white px-4">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-muted">
                    <small>&copy; {{ date('Y') }} MTCGS - All rights reserved</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
