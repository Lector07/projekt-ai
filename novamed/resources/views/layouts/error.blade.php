// resources/views/layouts/error.blade.php
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Nazwa Aplikacji</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .error-container {
            text-align: center;
            padding: 50px 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .error-code {
            font-size: 120px;
            color: #f44336;
            margin-bottom: 0;
        }
        .error-title {
            font-size: 32px;
            margin-top: 0;
        }
        .error-message {
            font-size: 18px;
            color: #555;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<div class="container">
    @yield('content')
</div>
</body>
</html>
