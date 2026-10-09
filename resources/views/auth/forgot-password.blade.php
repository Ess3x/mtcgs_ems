<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MTCGS-EMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
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
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            font-family: var(--font-body);
            padding: 2rem 1rem;
        }

        .reset-card {
            width: min(100%, 500px);
            background: #fff;
            border: 0;
            border-radius: 20px;
            box-shadow: 0 16px 32px rgba(17, 24, 39, 0.08);
            padding: 2rem 2rem 1.5rem;
        }

        .brand-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
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
            font-family: var(--font-head);
            font-size: 2rem;
            font-weight: 700;
            color: #5b2bb6;
        }

        h3 {
            font-family: var(--font-head);
            text-align: center;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #1f2937;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            border: 1px solid #dfeafc;
            background: #eef5ff;
            border-radius: 10px;
            padding: 0.8rem 0.9rem;
            font-size: 1rem;
            color: #111827;
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(124, 58, 237, 0.6);
            box-shadow: 0 0 0 0.2rem rgba(124, 58, 237, 0.15);
        }

        .btn-reset {
            display: block;
            width: 100%;
            margin-top: 1rem;
            border: none;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            background: linear-gradient(135deg, var(--accent-start) 0%, var(--accent-pink) 100%);
            color: #fff;
            font-family: var(--font-head);
            font-weight: 600;
            box-shadow: 0 8px 18px rgba(124, 58, 237, 0.25);
        }

        .btn-reset:hover {
            filter: brightness(1.05);
            color: #fff;
        }

        .back-link {
            display: block;
            margin-top: 1rem;
            text-align: center;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.95rem;
        }

        .back-link:hover {
            color: var(--accent-end);
        }

        .alert {
            margin-bottom: 1rem;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="brand-header">
            <img src="{{ asset('images/logo.jpg') }}" alt="MTCGS Logo" onerror="this.style.display='none'">
            <span>MTCGS</span>
        </div>

        <h3>Forgot Password</h3>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input id="email" type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn-reset">Send Reset Link</button>
        </form>

        <a href="{{ route('login') }}" class="back-link">Back to login</a>
    </div>
</body>
</html>
