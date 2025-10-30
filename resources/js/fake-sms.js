document.addEventListener('DOMContentLoaded', function(){
    const modeState = { mode: 'recv' };

    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(t => t.addEventListener('click', (e)=>{
        tabs.forEach(x=>x.classList.remove('active'));
        e.currentTarget.classList.add('active');
        modeState.mode = e.currentTarget.dataset.mode;
        applyMode();
    }));

    function applyMode(){
        const main = document.getElementById('mainArea');
        const col1 = document.querySelector('.column:nth-child(1)');
        const col2 = document.querySelector('.column:nth-child(2)');
        if(modeState.mode === 'sent'){
            main.style.gridTemplateColumns = '1fr';
            col1.style.display = '';
            col2.style.display = 'none';
        } else if(modeState.mode === 'recv'){
            main.style.gridTemplateColumns = '1fr';
            col1.style.display = 'none';
            col2.style.display = '';
        }
    }

    // render messages
    async function refresh(){
        try{
            const res = await fetch('/api/cache-watch');
            const json = await res.json();
            const messages = json.messages || [];
            document.getElementById('cacheSize').textContent = messages.length;

            const sentList = document.getElementById('sentList');
            const recvList = document.getElementById('recvList');
            sentList.innerHTML = '';
            recvList.innerHTML = '';

            messages.forEach(m => {
                const el = document.createElement('div');
                el.className = 'message';
                const left = document.createElement('div');
                left.className = 'm-left';
                left.innerHTML = `<div class="to">To: ${escapeHtml(m.number)}</div>
                    <div class="from">From: ${escapeHtml(m.sender === 0 ? 'TestApp' : 'TestApp')}</div>
                    <div class="util">${escapeHtml(m.util || '')}</div>
                    <div class="content">${escapeHtml(m.content)}</div>
                    <div class="status-line"><div class="status-small">Status: ${escapeHtml(m.status || '')}</div>
                    <div style="flex:1"></div>
                    ${m.delivered !== false ? '<div class="badge">Status: Delivered</div>' : ''}
                    </div>`;

                const right = document.createElement('div');
                right.className = 'm-right';
                right.innerHTML = `<div>${formatTime(m.timestamp)}</div>`;

                el.appendChild(left);
                el.appendChild(right);

                if(m.sender === 0){
                    sentList.appendChild(el);
                } else {
                    recvList.appendChild(el);
                }
            });

        }catch(e){
            console.error(e);
        }
    }

    function escapeHtml(s){ if(!s) return ''; return String(s).replace(/[&<>\"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":"&#39;"}[c]; }); }

    function formatTime(ts){
        if(!ts) return '';
        try{ const d = new Date(ts); return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}); }catch(e){ return ts; }
    }

    document.getElementById('triggerBtn').addEventListener('click', async ()=>{
        const num = '+123456' + Math.floor(Math.random()*900 + 100);
        const text = 'Received! ' + ['We\'re here to help.','Hello!','Thanks, got it.'][Math.floor(Math.random()*3)];
        try{
            await fetch('/api/get-message', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({number:num,text:text})});
            refresh();
        }catch(e){console.error(e)}
    });

    // initial
    applyMode();
    refresh();
    setInterval(refresh, 2500);
});
