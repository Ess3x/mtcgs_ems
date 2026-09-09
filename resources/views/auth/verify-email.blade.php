<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 text-center">
                        <h3 class="mb-3">Verify Your Email</h3>
                        <p class="text-muted">Please confirm that this is your email address before proceeding.</p>
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">Continue</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
