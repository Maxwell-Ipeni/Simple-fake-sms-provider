<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fake SMS Provider</title>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background:#111213; color:#e6eef6; margin:0; padding:20px }
        .container{max-width:1100px;margin:0 auto}
        header{display:flex;align-items:center;justify-content:space-between}
        .cards{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:20px}
        .card{background:#0f1720;padding:16px;border-radius:8px;box-shadow:0 6px 14px rgba(0,0,0,0.6)}
        .message{padding:10px;background:#0b1220;border-radius:6px;margin-bottom:8px}
        .badge{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px}
        .user{background:#065f46;color:#d1fae5}
        .provider{background:#0ea5e9;color:#042a3a}
        form{display:flex;gap:8px;margin-top:12px}
        input, button{padding:8px;border-radius:6px;border:1px solid #24303a;background:#0b1220;color:#e6eef6}
        button{cursor:pointer}
        .meta{font-size:12px;color:#94a3b8;margin-top:6px}
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>SMS Simulator & Test Interface</h1>
        <div>
            <button id="refreshBtn">Refresh</button>
        </div>
    </header>

    <div style="margin-top:14px">
        <form id="sendForm">
            <input id="sendNumber" placeholder="Number (optional)" />
            <input id="sendText" placeholder="Message to send from provider" required />
            <button type="submit">Send (provider)</button>
        </form>

        <form id="receiveForm">
            <input id="recvNumber" placeholder="Number" required />
            <input id="recvText" placeholder="Message from user" required />
            <button type="submit">Receive (user)</button>
        </form>
    </div>

    <div class="cards" id="cards">
        <div class="card" id="sentColumn">
            <h3>Sent Messages To /send-message</h3>
            <div id="sentList"></div>
        </div>
        <div class="card" id="recvColumn">
            <h3>Simulated Incoming Responses From /get-message</h3>
            <div id="recvList"></div>
        </div>
    </div>

    <div style="margin-top:20px;color:#94a3b8;font-size:13px">System Status: <span id="status">Unknown</span></div>
</div>

<script>
    const refresh = async () => {
        try {
            const res = await fetch('/api/cache-watch');
            const json = await res.json();
            const messages = json.messages || [];

            const sentList = document.getElementById('sentList');
            const recvList = document.getElementById('recvList');
            sentList.innerHTML = '';
            recvList.innerHTML = '';

            messages.forEach(m => {
                const el = document.createElement('div');
                el.className = 'message';
                const badge = document.createElement('span');
                badge.className = 'badge ' + (m.sender === 1 ? 'user' : 'provider');
                badge.textContent = m.sender === 1 ? 'User' : 'Provider';

                el.innerHTML = `<div><strong>${m.number}</strong></div><div style=\"margin-top:6px\">${m.content}</div><div class=\"meta\">${m.timestamp}</div>`;
                el.prepend(badge);

                if (m.sender === 0) {
                    sentList.appendChild(el);
                } else {
                    recvList.appendChild(el);
                }
            });

            document.getElementById('status').textContent = 'Running';
        } catch (e) {
            document.getElementById('status').textContent = 'Error';
        }
    }

    document.getElementById('refreshBtn').addEventListener('click', (e)=>{e.preventDefault();refresh()});

    document.getElementById('sendForm').addEventListener('submit', async (e)=>{
        e.preventDefault();
        const number = document.getElementById('sendNumber').value;
        const text = document.getElementById('sendText').value;
        const body = { text };
        if (number) body.number = number;
        const res = await fetch('/api/send-message', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
        const j = await res.json();
        console.log(j);
        refresh();
    });

    document.getElementById('receiveForm').addEventListener('submit', async (e)=>{
        e.preventDefault();
        const number = document.getElementById('recvNumber').value;
        const text = document.getElementById('recvText').value;
        const body = { number, text };
        const res = await fetch('/api/get-message', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
        const j = await res.json();
        console.log(j);
        refresh();
    });

    // poll every 2.5 seconds
    setInterval(refresh, 2500);
    refresh();
</script>
</body>
</html>