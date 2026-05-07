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
            --code-bg: #fafafa;
            --hover: #fdfdff;
        }
        
        .dark-mode {
            --bg: #0f172a;
            --text: #f8fafc;
            --muted: #94a3b8;
            --border: #1e293b;
            --accent: #818cf8;
            --code-bg: #1e293b;
            --hover: #1e293b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg); color: var(--text); font-family: 'Outfit', sans-serif; line-height: 1.6; padding: 80px 120px;
            opacity: 0; animation: fadeIn 0.6s ease forwards; transition: background 0.3s, color 0.3s;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        header { margin-bottom: 80px; }
        .nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .logo { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 14px; color: var(--muted); letter-spacing: 0.1em; }
        .logo span { color: var(--accent); }
        .links { display: flex; gap: 20px; }
        .links a { color: var(--muted); text-decoration: none; font-size: 11px; font-weight: 700; }
        .links a:hover { color: var(--accent); }
        
        .exception { font-size: 12px; font-weight: 700; color: var(--accent); text-transform: uppercase; margin-bottom: 8px; }
        h1 { font-size: 56px; font-weight: 800; letter-spacing: -0.05em; line-height: 1.1; margin-bottom: 24px; color: var(--text); }
        .location { font-family: 'JetBrains Mono', monospace; color: var(--muted); font-size: 14px; border-left: 2px solid var(--border); padding-left: 16px; display: flex; align-items: center; gap: 12px; }
        .editor-link { cursor: pointer; color: var(--accent); opacity: 0.6; transition: opacity 0.2s; }
        .editor-link:hover { opacity: 1; }

        .insights { display: flex; gap: 32px; margin-top: 32px; padding: 20px 0; border-top: 1px solid var(--border); }
        .insight-item { display: flex; flex-direction: column; gap: 4px; }
        .insight-label { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--muted); letter-spacing: 0.05em; }
        .insight-value { font-size: 14px; font-weight: 700; }

        section { margin-bottom: 100px; }
        h2 { font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 32px; display: flex; align-items: center; gap: 12px; }
        h2::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        .code-box { background: var(--code-bg); border-radius: 24px; padding: 48px; font-family: 'JetBrains Mono', monospace; font-size: 15px; border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow-x: auto; }
        .line { display: grid; grid-template-columns: 50px 1fr; gap: 24px; opacity: 0.3; transition: all 0.2s; }
        .line.active { opacity: 1; color: var(--accent); font-weight: 600; border-left: 3px solid var(--accent); padding-left: 10px; margin-left: -13px; }
        .ln { text-align: right; color: var(--muted); user-select: none; }

        .search-box { margin-bottom: 24px; position: relative; }
        .search-box input { width: 100%; padding: 12px 20px; border-radius: 12px; border: 1px solid var(--border); background: var(--code-bg); color: var(--text); outline: none; font-family: 'Outfit', sans-serif; transition: all 0.2s; }
        .search-box input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.05); }

        .trace-list { display: grid; gap: 8px; }
        .trace-item { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-radius: 16px; border: 1px solid var(--border); cursor: pointer; transition: all 0.2s; }
        .trace-item:hover { background: var(--hover); border-color: var(--accent); transform: translateX(4px); }
        .trace-item.hidden { display: none; }
        .trace-call { font-weight: 700; font-size: 15px; }
        .trace-file { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 8px; }

        .tabs { display: flex; gap: 32px; margin-bottom: 40px; }
        .tab { padding-bottom: 12px; cursor: pointer; font-weight: 800; font-size: 13px; color: var(--muted); border-bottom: 2px solid transparent; transition: all 0.2s; }
        .tab.active { color: var(--text); border-bottom-color: var(--accent); }
        .pane { display: none; }
        .pane.active { display: block; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table tr:hover { background: var(--hover); }
        .data-table th { text-align: left; padding: 16px; font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid var(--border); width: 250px; }
        .data-table td { padding: 16px; font-size: 14px; border-bottom: 1px solid var(--border); word-break: break-all; }

        pre { background: var(--code-bg); padding: 32px; border-radius: 16px; border: 1px solid var(--border); font-family: 'JetBrains Mono', monospace; font-size: 13px; overflow-x: auto; color: var(--text); }

        .floating-tools { position: absolute; bottom: 40px; right: 40px; display: flex; flex-direction: column; gap: 12px; }
        .fab { background: var(--text); color: var(--bg); width: 56px; height: 56px; border-radius: 18px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); transition: all 0.2s; }
        .fab:hover { transform: scale(1.1) translateY(-4px); background: var(--accent); color: #fff; }

        .tag { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; background: var(--border); color: var(--muted); font-weight: 700; }
    </style>
</head>
<body>
    <header>
        <div class="nav">
            <div class="logo">FLY <span>FRAMEWORK</span></div>
            <div class="links">
                <a href="https://github.com/imcanugur/fly-framework" target="_blank">GITHUB</a>
                <a href="https://github.com/imcanugur/fly-framework" target="_blank">DOCS</a>
            </div>
        </div>
        <div class="exception"><?= $exceptionName ?></div>
        <h1><?= $message ?></h1>
        <div class="location">
            <i data-lucide="map-pin" size="14"></i>
            <?= $file ?> : <?= $line ?>
            <i data-lucide="external-link" size="14" class="editor-link" onclick="openInEditor('<?= addslashes($file) ?>', <?= $line ?>)" title="Open in VSCode"></i>
        </div>
        <div class="insights">
            <div class="insight-item"><span class="insight-label">Time</span><span class="insight-value"><?= $execution_time ?>ms</span></div>
            <div class="insight-item"><span class="insight-label">Memory</span><span class="insight-value"><?= $memory_usage ?>MB</span></div>
            <div class="insight-item"><span class="insight-label">PHP</span><span class="insight-value"><?= PHP_VERSION ?></span></div>
            <div class="insight-item"><span class="insight-label">Env</span><span class="insight-value"><?= strtoupper($_ENV['APP_ENV'] ?? 'local') ?></span></div>
        </div>
    </header>

    <section id="source-section">
        <h2>Source Code</h2>
        <div class="code-box" id="code-view">
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
        <div class="search-box">
            <input type="text" placeholder="Search trace (e.g. controller, middleware)..." onkeyup="filterTrace(this.value)">
        </div>
        <div class="trace-list" id="trace-list">
            <?php foreach ($trace as $i => $step): ?>
                <?php if (isset($step['file'])): ?>
                <div class="trace-item" data-search="<?= strtolower(($step['class'] ?? '') . ($step['function'] ?? '') . basename($step['file'])) ?>" onclick="updateCode(<?= htmlspecialchars(json_encode($step)) ?>)">
                    <div class="trace-call">
                        <?= ($step['class'] ?? '') . ($step['type'] ?? '') . $step['function'] ?>()
                    </div>
                    <div class="trace-file">
                        <?= basename($step['file']) ?>:<?= $step['line'] ?>
                        <i data-lucide="external-link" size="12" class="editor-link" onclick="event.stopPropagation(); openInEditor('<?= addslashes($step['file']) ?>', <?= $step['line'] ?>)"></i>
                    </div>
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
            <div class="tab" onclick="switchTab('config', this)">Config</div>
            <div class="tab" onclick="switchTab('system', this)">System</div>
        </div>

        <div id="req" class="pane active">
            <table class="data-table">
                <tr><th>URL</th><td><?= $request->url() ?></td></tr>
                <tr><th>Method</th><td><span class="tag"><?= $request->method() ?></span></td></tr>
                <tr><th>IP</th><td><?= $request->ip() ?></td></tr>
                <tr><th>User Agent</th><td><?= $_SERVER['HTTP_USER_AGENT'] ?? 'N/A' ?></td></tr>
            </table>
        </div>

        <div id="headers" class="pane">
            <table class="data-table">
                <?php foreach ($headers as $k => $v): ?>
                    <tr><th><?= $k ?></th><td><?= $v ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div id="env" class="pane">
            <table class="data-table">
                <?php foreach ($env as $k => $v): ?>
                    <?php if (str_contains(strtolower($k), 'pass') || str_contains(strtolower($k), 'key') || str_contains(strtolower($k), 'secret')) $v = '********'; ?>
                    <tr><th><?= $k ?></th><td><?= $v ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div id="config" class="pane">
            <pre><?= json_encode($config, JSON_PRETTY_PRINT) ?></pre>
        </div>

        <div id="system" class="pane">
            <h4 style="margin: 20px 0 10px 0; font-size: 12px; color: var(--muted);">PHP INI SETTINGS</h4>
            <table class="data-table">
                <?php foreach ($php_ini as $k => $v): ?>
                    <tr><th><?= $k ?></th><td><?= $v ?: 'off' ?></td></tr>
                <?php endforeach; ?>
            </table>
            <h4 style="margin: 30px 0 10px 0; font-size: 12px; color: var(--muted);">LOADED EXTENSIONS</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <?php foreach ($extensions as $ext): ?>
                    <span class="tag"><?= $ext ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="floating-tools">
        <div class="fab" onclick="copyError()" title="Copy Details"><i data-lucide="copy" size="20"></i></div>
        <div class="fab" onclick="searchError()" title="Search Google"><i data-lucide="search" size="20"></i></div>
        <div class="fab" onclick="toggleTheme()" title="Toggle Theme"><i data-lucide="sun-moon" size="20"></i></div>
    </div>

    <script>
        lucide.createIcons();
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
        }
        function switchTab(id, el) {
            document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            el.classList.add('active');
        }
        function updateCode(step) {
            const container = document.getElementById('code-view');
            let html = '';
            for (const [ln, txt] of Object.entries(step.snippet)) {
                html += `<div class="line ${ln == step.line ? 'active' : ''}">
                    <div class="ln">${ln}</div>
                    <div class="txt">${escapeHtml(txt)}</div>
                </div>`;
            }
            container.innerHTML = html;
            window.scrollTo({ top: document.getElementById('source-section').offsetTop - 60, behavior: 'smooth' });
        }
        function filterTrace(val) {
            const items = document.querySelectorAll('.trace-item');
            val = val.toLowerCase();
            items.forEach(item => {
                const search = item.getAttribute('data-search');
                item.classList.toggle('hidden', !search.includes(val));
            });
        }
        function openInEditor(file, line) {
            window.location.href = `vscode://file/${file}:${line}`;
        }
        function copyError() {
            const text = `Error: <?= addslashes($message) ?>\nFile: <?= addslashes($file) ?>:${<?= $line ?>}\nTime: <?= $execution_time ?>ms\nMemory: <?= $memory_usage ?>MB`;
            navigator.clipboard.writeText(text).then(() => alert('Details copied!'));
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