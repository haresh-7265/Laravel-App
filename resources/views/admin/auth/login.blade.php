<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            font-family: 'Segoe UI', sans-serif;
        }
        .admin-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        .admin-card .form-control {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            color: #e0e0e0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }
        .admin-card .form-control:focus {
            background: rgba(255,255,255,0.12);
            border-color: #6c63ff;
            box-shadow: 0 0 0 0.2rem rgba(108,99,255,0.25);
            color: #fff;
        }
        .admin-card .form-control::placeholder { color: rgba(255,255,255,0.4); }
        .admin-card label { color: rgba(255,255,255,0.7); font-size: 0.875rem; }
        .btn-admin {
            background: linear-gradient(135deg, #6c63ff, #3b82f6);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .btn-admin:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(108,99,255,0.4);
        }
        .shield-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #6c63ff, #3b82f6);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="admin-card">
        <div class="shield-icon text-white">
            <i class="bi bi-shield-lock"></i>
        </div>

        <h4 class="text-white text-center mb-1">Admin Panel</h4>
        <p class="text-center mb-4" style="color: rgba(255,255,255,0.5); font-size: 0.9rem;">
            Sign in to access the administration area
        </p>

        @if($errors->any())
            <div class="alert alert-danger py-2 px-3" style="font-size: 0.875rem; border-radius: 10px;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" id="email"
                       class="form-control"
                       value="{{ old('email') }}"
                       placeholder="admin@example.com"
                       required autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password"
                       class="form-control"
                       placeholder="••••••••"
                       required>
            </div>

            {{-- CAPTCHA --}}
            @if(session('admin_captcha_question'))
                <div class="mb-3 p-3 rounded text-light" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1);">
                    <label for="admin_captcha_answer" class="form-label text-warning">
                        Security Verification: <strong>{{ session('admin_captcha_question') }}</strong>
                    </label>
                    <input type="text" name="admin_captcha_answer" id="admin_captcha_answer"
                           class="form-control text-light @error('admin_captcha_answer') is-invalid @enderror"
                           style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);"
                           placeholder="Enter the answer"
                           required>
                    @error('admin_captcha_answer')
                        <div class="invalid-feedback text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            <div class="mb-4 form-check">
                <input type="checkbox" name="remember" id="remember" class="form-check-input"
                       style="background-color: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2);">
                <label for="remember" class="form-check-label" style="color: rgba(255,255,255,0.6); font-size: 0.85rem;">
                    Remember me
                </label>
            </div>

            <button type="submit" class="btn btn-admin text-white w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="{{ url('/') }}" style="color: rgba(255,255,255,0.4); font-size: 0.8rem; text-decoration: none;">
                <i class="bi bi-arrow-left me-1"></i> Back to Store
            </a>
        </div>
    </div>
</body>
</html>
