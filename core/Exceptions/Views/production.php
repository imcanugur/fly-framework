<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Something went wrong | Fly Framework</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #f1f5f9;
            --accent: #4f46e5;
        }
        
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f172a;
                --text: #f8fafc;
                --muted: #94a3b8;
                --border: #1e293b;
                --accent: #818cf8;
            }
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
            padding: 40px;
        }

        .container { max-width: 600px; animation: fadeIn 0.8s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .logo { 
            display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 800; font-size: 14px; color: var(--muted); margin-bottom: 40px; 
            letter-spacing: 0.1em;
        }
        .logo span { color: var(--accent); }

        h1 { font-size: 48px; font-weight: 800; letter-spacing: -0.04em; margin-bottom: 24px; line-height: 1.1; }
        p { color: var(--muted); font-size: 18px; margin-bottom: 48px; line-height: 1.6; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: var(--text);
            color: var(--bg);
            padding: 16px 32px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.2s;
        }
        .btn:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); background: var(--accent); color: #fff; }

        .footer-links { margin-top: 80px; display: flex; justify-content: center; gap: 24px; }
        .footer-links a { color: var(--muted); text-decoration: none; font-size: 13px; font-weight: 700; transition: color 0.2s; }
        .footer-links a:hover { color: var(--accent); }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">FLY <span>FRAMEWORK</span></div>
        <h1>Something went wrong.</h1>
        <p>Our systems are having a moment. We've been notified and are looking into it. Please try again in a few minutes.</p>
        
        <a href="/" class="btn">
            <i data-lucide="home" size="20"></i>
            Take Me Home
        </a>

        <div class="footer-links">
            <a href="https://github.com/imcanugur/fly-framework" target="_blank">GITHUB</a>
            <a href="https://github.com/imcanugur/fly-framework" target="_blank">DOCUMENTATION</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
