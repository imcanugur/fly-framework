<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $statusCode ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #ffffff;
            color: #0f172a;
            font-family: 'Outfit', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .status { font-size: 140px; font-weight: 800; letter-spacing: -0.05em; line-height: 1; margin-bottom: 24px; opacity: 0.1; position: absolute; z-index: -1; }
        h1 { font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 12px; }
        p { color: #64748b; font-size: 16px; margin-bottom: 40px; }
        a { color: #0f172a; text-decoration: none; font-weight: 700; font-size: 14px; border-bottom: 2px solid #0f172a; padding-bottom: 4px; transition: opacity 0.2s; }
        a:hover { opacity: 0.6; }
        .fly { position: absolute; bottom: 40px; font-size: 11px; font-weight: 800; letter-spacing: 0.2em; color: #cbd5e1; }
    </style>
</head>
<body>
    <div class="status"><?= $statusCode ?></div>
    <div class="container">
        <h1>
            <?php 
                echo match($statusCode) {
                    404 => 'Not Found',
                    default => 'Error'
                };
            ?>
        </h1>
        <p>Something went wrong on our end.</p>
        <a href="/">BACK TO SAFETY</a>
    </div>
    <a href="https://github.com/imcanugur/fly-framework" target="_blank" class="fly">FLY FRAMEWORK</a>
</body>
</html>
