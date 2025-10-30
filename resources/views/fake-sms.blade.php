<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMS Simulator & Test Interface</title>
    <style>
        :root{
            --bg:#0f1720; /* dark */
            --panel:#eef2f6; /* card */
            --accent:#14b8a6; /* teal */
            --muted:#94a3b8;
            --card-shadow: 0 8px 20px rgba(2,6,23,0.6);
            --radius:10px;
            --max-width:1180px;
        }
        html,body{height:100%;margin:0}
        body{font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background:linear-gradient(180deg,#0b1116 0%, #0f1720 100%); color:#fff}
        .app{max-width:var(--max-width);margin:28px auto;padding:20px}

        header.top{display:flex;align-items:center;justify-content:space-between;padding:18px 12px}
        .title{font-size:28px;font-weight:700}
        .icons{display:flex;gap:10px;align-items:center}
        .icon{width:34px;height:34px;border-radius:6px;background:rgba(255,255,255,0.04);display:inline-flex;align-items:center;justify-content:center;color:#cbd5e1}

        .controls{display:flex;align-items:center;justify-content:space-between;padding:12px}
        .tabs{display:flex;gap:8px}
        .tab{padding:10px 14px;border-radius:8px;background:rgba(255,255,255,0.02);color:#cbd5e1;cursor:pointer}
        .tab.active{background:var(--accent);color:#fff}
        .trigger{background:var(--accent);color:#042a2a;padding:10px 14px;border-radius:8px;border:none;cursor:pointer;font-weight:600}

        main{display:grid;grid-template-columns:1fr 1fr;gap:20px;padding:12px}
        .column{min-height:360px}
        .panel{background:var(--panel);color:#0b1220;border-radius:var(--radius);padding:14px;box-shadow:var(--card-shadow)}
        h4{margin:0 0 12px 0;color:#fff;font-weight:600}
        .subtitle{color:#e6eef6;font-size:13px;margin-bottom:6px}

        .message-list{display:flex;flex-direction:column;gap:12px}
        .message{background:#fff;border-radius:8px;padding:12px;box-shadow:0 4px 12px rgba(2,6,23,0.12);display:flex;justify-content:space-between;align-items:flex-start}
        .m-left{max-width:78%}
        .m-right{min-width:64px;text-align:right;color:#667085;font-size:13px}
        .to{font-weight:700;color:#0b1220}
        .from{color:#334155;font-size:13px;margin-top:6px}
        .util{color:#64748b;font-size:12px;margin-top:6px}
        .content{margin-top:10px;color:#0b1220}

        .status-line{display:flex;gap:8px;align-items:center;margin-top:10px}
        .badge{background:var(--accent);color:#042a2a;padding:6px 10px;border-radius:999px;font-weight:600;font-size:12px}
        .status-small{color:#475569;font-size:13px}

        .callback{background:#fff3e0;padding:10px;border-radius:8px;margin-top:8px;color:#0b1220}
        .callback a{color:#0b63ff;text-decoration:underline}

        footer.status{position:fixed;left:0;right:0;bottom:10px;margin:auto;max-width:var(--max-width);background:rgba(2,6,23,0.6);color:#cbd5e1;border-radius:12px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center}
        .dot{width:10px;height:10px;border-radius:50%;background:#10b981;margin-right:8px}

        /* responsive */
        @media (max-width:900px){main{grid-template-columns:1fr}}
    <link rel="stylesheet" href="{{ asset('css/fake-sms.css') }}">
</head>
<body>
<div id="app">
  <fake-sms></fake-sms>
</div>

<footer class="status">
    <div style="display:flex;align-items:center"><div class="dot"></div>System Status: <span style="margin-left:8px">Running</span></div>
    <div>Cache Size: <span id="cacheSize">0</span> entries</div>
</footer>

    <script src="{{ mix('js/app.js') }}"></script>
</body>
</html>