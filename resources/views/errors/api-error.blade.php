<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Unavailable</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center;
               align-items: center; min-height: 100vh; margin: 0; background: #f8fafc; }
        .card { background: white; padding: 2rem 3rem; border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,.08); text-align: center; max-width: 420px; }
        h1 { color: #e53e3e; font-size: 1.5rem; }
        p  { color: #555; }
        a  { color: #4299e1; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>⚠️ Something went wrong</h1>
        <p>{{ $message ?? 'We could not complete your request. Please try again later.' }}</p>
        <a href="{{ url()->previous() }}">← Go back</a>
    </div>
</body>
</html>