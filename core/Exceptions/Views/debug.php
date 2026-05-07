<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $exceptionName ?>: <?= $message ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #f1f5f9;
            --accent: #4f46e5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Outfit', sans-serif;
            line-height: 1.6;
            padding: 80px 120px;
        }
        
        header { margin-bottom: 80px; }
        .logo { 
            display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 14px; color: var(--muted); margin-bottom: 40px; 
            letter-spacing: 0.1em;
        }
        .logo span { color: var(--accent); }
        .logo-links { margin-left: auto; display: flex; gap: 16px; }
        .logo-links a { color: var(--muted); text-decoration: none; font-size: 11px; font-weight: 700; }
        .logo-links a:hover { color: var(--accent); }
        
        .exception { font-size: 12px; font-weight: 700; color: var(--accent); text-transform: uppercase; margin-bottom: 8px; }
        h1 { font-size: 56px; font-weight: 800; letter-spacing: -0.05em; line-height: 1; margin-bottom: 24px; }
        .location { font-family: 'JetBrains Mono', monospace; color: var(--muted); font-size: 14px; border-left: 2px solid var(--border); padding-left: 16px; display: flex; align-items: center; gap: 12px; }
        .insights { display: flex; gap: 24px; margin-top: 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em; }
        .insights span { color: var(--text); }

        section { margin-bottom: 80px; }
        h2 { font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 24px; }

        .code-snippet {
            background: #fafafa; border-radius: 20px; padding: 40px;
            font-family: 'JetBrains Mono', monospace; font-size: 15px;
            overflow-x: auto; border: 1px solid var(--border);
        }
        .line { display: grid; grid-template-columns: 50px 1fr; gap: 20px; opacity: 0.4; }
        .line.active { opacity: 1; color: var(--accent); font-weight: 600; }
        .ln { text-align: right; user-select: none; color: var(--muted); }

        .trace-list { display: grid; gap: 12px; }
        .trace-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 16px 0; border-bottom: 1px solid var(--border);
            cursor: pointer; transition: all 0.2s;
        }
        .trace-item:hover { color: var(--accent); border-bottom-color: var(--accent); }
        .trace-item .file { font-family: 'JetBrains Mono', monospace; font-size: 13px; }
        .trace-item .call { font-weight: 600; }

        .tabs { display: flex; gap: 32px; margin-bottom: 32px; border-bottom: 1px solid var(--border); }
        .tab { padding-bottom: 16px; cursor: pointer; font-weight: 700; font-size: 14px; color: var(--muted); border-bottom: 2px solid transparent; }
        .tab.active { color: var(--text); border-bottom-color: var(--text); }
        .pane { display: none; }
        .pane.active { display: block; }
        
        .data-grid { display: grid; gap: 8px; }
        .data-row { display: grid; grid-template-columns: 200px 1fr; padding: 12px 0; border-bottom: 1px solid #f8fafc; }
        .key { color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .val { word-break: break-all; font-weight: 500; }

        pre { background: #fafafa; padding: 24px; border-radius: 12px; border: 1px solid var(--border); font-family: 'JetBrains Mono', monospace; font-size: 13px; overflow-x: auto; }

        .floating-actions { position: fixed; bottom: 40px; right: 40px; display: flex; gap: 12px; }
        .fab {
            background: var(--text); color: white; width: 48px; height: 48px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); transition: transform 0.2s;
        }
        .fab:hover { transform: scale(1.1); background: var(--accent); }

        @media (max-width: 1024px) { body { padding: 40px; } h1 { font-size: 32px; } }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            FLY <span>FRAMEWORK</span>
            <div class="logo-links">
                <a href="https://github.com/imcanugur/fly-framework" target="_blank">GITHUB</a>
                <a href="https://github.com/imcanugur/fly-framework" target="_blank">DOCS</a>
            </div>
        </div>
        <div class="exception"><?= $exceptionName ?></div>
        <h1><?= $message ?></h1>
        <div class="location"><?= $file ?> : <?= $line ?></div>
        <div class="insights">
            <div>Execution Time: <span><?= $execution_time ?>ms</span></div>
            <div>Memory Peak: <span><?= $memory_usage ?>MB</span></div>
            <div>PHP Version: <span><?= PHP_VERSION ?></span></div>
        </div>
    </header>

    <section id="source-code">
        <h2>Source Code</h2>
        <div class="code-snippet" id="main-code">
            <?php foreach ($codeSnippet as $ln => $content): ?>
                <div class="line <?= $ln == $line ? 'active' : '' ?>">
                    <div class="ln"><?= $ln ?></div>
                    <div class="txt"><?= htmlspecialchars($content) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section>
        <h2>Stack Trace</h2>
        <div class="trace-list">
            <?php foreach ($trace as $i => $step): ?>
                <?php if (isset($step['file'])): ?>
                <div class="trace-item" onclick="updateCode(<?= htmlspecialchars(json_encode($step)) ?>)">
                    <div class="call"><?= ($step['class'] ?? '') . ($step['type'] ?? '') . $step['function'] ?>()</div>
                    <div class="file"><?= basename($step['file']) ?>:<?= $step['line'] ?></div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section>
        <div class="tabs">
            <div class="tab active" onclick="switchTab('req', this)">Request</div>
            <div class="tab" onclick="switchTab('headers', this)">Headers</div>
            <div class="tab" onclick="switchTab('env', this)">Env</div>
            <div class="tab" onclick="switchTab('cfg', this)">Config</div>
        </div>
        
        <div id="req" class="pane active">
            <div class="data-grid">
                <div class="data-row"><div class="key">URL</div><div class="val"><?= $request->url() ?></div></div>
                <div class="data-row"><div class="key">Method</div><div class="val"><?= $request->method() ?></div></div>
                <div class="data-row"><div class="key">IP</div><div class="val"><?= $request->ip() ?></div></div>
            </div>
        </div>

        <div id="headers" class="pane">
            <div class="data-grid">
                <?php foreach ($headers as $k => $v): ?>
                    <div class="data-row"><div class="key"><?= $k ?></div><div class="val"><?= $v ?></div></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="env" class="pane">
            <div class="data-grid">
                <?php foreach ($_ENV as $k => $v): ?>
                    <?php if (str_contains(strtolower($k), 'pass') || str_contains(strtolower($k), 'key') || str_contains(strtolower($k), 'secret')) $v = '****'; ?>
                    <div class="data-row"><div class="key"><?= $k ?></div><div class="val"><?= $v ?></div></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="cfg" class="pane">
            <pre><?= json_encode(config()->all(), JSON_PRETTY_PRINT) ?></pre>
        </div>
    </section>

    <div class="floating-actions">
        <div class="fab" onclick="copyError()" title="Copy Error"><i data-lucide="copy" size="20"></i></div>
        <div class="fab" onclick="searchError()" title="Search Google"><i data-lucide="search" size="20"></i></div>
        <div class="fab" onclick="document.body.style.filter = document.body.style.filter ? '' : 'invert(1) hue-rotate(180deg)'" title="Toggle Theme"><i data-lucide="sun-moon" size="20"></i></div>
    </div>

    <script>
        lucide.createIcons();
        function switchTab(id, el) {
            document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            el.classList.add('active');
        }
        function updateCode(step) {
            const container = document.getElementById('main-code');
            let html = '';
            for (const [ln, txt] of Object.entries(step.snippet)) {
                html += `<div class="line ${ln == step.line ? 'active' : ''}">
                    <div class="ln">${ln}</div>
                    <div class="txt">${escapeHtml(txt)}</div>
                </div>`;
            }
            container.innerHTML = html;
            window.scrollTo({ top: document.getElementById('source-code').offsetTop - 60, behavior: 'smooth' });
        }
        function copyError() {
            const text = "<?= addslashes($message) ?>\nAt: <?= addslashes($file) ?>:<?= $line ?>";
            navigator.clipboard.writeText(text).then(() => alert('Copied!'));
        }
        function searchError() {
            window.open('https://www.google.com/search?q=php+' + encodeURIComponent('<?= addslashes($message) ?>'), '_blank');
        }
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
