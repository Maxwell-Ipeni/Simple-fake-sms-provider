import React, { useEffect, useState, useRef } from 'react';

function formatTime(ts){
  if(!ts) return '';
  try{ const d = new Date(ts); return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}); }catch(e){ return ts }
}

export default function FakeSms(){
  const [mode, setMode] = useState('recv');
  const [messages, setMessages] = useState([]);
  const [cacheCount, setCacheCount] = useState(0);
  const [autoTrigger, setAutoTrigger] = useState(true);
  const [sseActive, setSseActive] = useState(false);
  const [callbackInfo, setCallbackInfo] = useState(null);

  const sentListRef = useRef(null);
  const recvListRef = useRef(null);
  const autoRef = useRef(null);
  const esRef = useRef(null);
  const [pulse, setPulse] = useState(false);
  const prevCountRef = useRef(0);

  async function refresh(){
    try{
      const r = await fetch('/api/cache-watch');
      const j = await r.json();
      setMessages(j.messages || []);
      setCacheCount(j.count ?? (j.messages ? j.messages.length : 0));
    }catch(e){ console.error(e) }
  }

  async function trigger(){
    const num = '+123456' + Math.floor(Math.random()*900 + 100);
    const text = 'Received! ' + ['We\'re here to help.','Hello!','Thanks, got it.'][Math.floor(Math.random()*3)];
    try{
      await fetch('/api/get-message', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({number:num,text:text})});
      await refresh();
    }catch(e){ console.error(e) }
  }

  // basic refresh + polling fallback (only enabled when SSE is not active)
  useEffect(()=>{
    refresh();
    if (!sseActive){
      const t = setInterval(()=>refresh(), 2500);
      return ()=>{ clearInterval(t); };
    }
    // when SSE is active we rely on push updates
    return undefined;
  },[sseActive]);

  // SSE: try to open an EventSource to receive push updates from the server.
  useEffect(()=>{
    if (!window.EventSource) return; // not supported
    try{
      const es = new EventSource('/api/sse');
      esRef.current = es;
      es.onopen = ()=>{
        setSseActive(true);
      };
      es.onmessage = (e)=>{
        try{
          const j = JSON.parse(e.data || '{}');
          if(j.messages) setMessages(j.messages);
          setCacheCount(j.count ?? (j.messages ? j.messages.length : 0));
        }catch(err){ console.error('Invalid SSE payload', err) }
      };
      es.onerror = (err)=>{
        console.error('SSE error', err);
        // close and fallback to polling
        try{ es.close(); }catch(e){}
        esRef.current = null;
        setSseActive(false);
      };
    }catch(e){ console.warn('SSE connect failed', e); setSseActive(false) }

    return ()=>{
      if(esRef.current){ try{ esRef.current.close(); }catch(e){} esRef.current = null }
      setSseActive(false);
    };
  },[]);

  // watch autoTrigger changes to start/stop the auto-run
  useEffect(()=>{
    if(autoTrigger){
      if(!autoRef.current){
        autoRef.current = setInterval(()=>{ trigger().catch(()=>{}); }, 5000);
      }
    } else {
      if(autoRef.current){ clearInterval(autoRef.current); autoRef.current = null }
    }
    return ()=>{ if(autoRef.current){ clearInterval(autoRef.current); autoRef.current = null } }
  },[autoTrigger]);

  // pulse animation when cacheCount changes
  useEffect(()=>{
    if (prevCountRef.current !== cacheCount){
      setPulse(true);
      const id = setTimeout(()=> setPulse(false), 600);
      prevCountRef.current = cacheCount;
      return ()=> clearTimeout(id);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  },[cacheCount]);

  // show latest messages first (descending by timestamp)
  function sortDescByTimestamp(arr){
    return [...(arr||[])].sort((a,b)=>{
      const ta = a && a.timestamp ? new Date(a.timestamp).getTime() : 0;
      const tb = b && b.timestamp ? new Date(b.timestamp).getTime() : 0;
      return tb - ta;
    });
  }

  const sentMessages = sortDescByTimestamp(messages.filter(m => m.sender === 0));
  const recvMessages = sortDescByTimestamp(messages.filter(m => m.sender === 1));

  // Ensure the Received column displays at least `minRecv` messages (placeholders if needed)
  const minRecv = 2;
  function makePlaceholder(i){
    return {
      id: 'placeholder-' + i,
      timestamp: new Date().toISOString(),
      number: '+1000000' + (100 + i),
      content: 'No message yet — this is a placeholder',
      sender: 1,
      placeholder: true,
    };
  }
  const recvDisplay = (recvMessages.length >= minRecv) ? recvMessages : [...recvMessages, ...Array.from({length: Math.max(0, minRecv - recvMessages.length)}, (_,i)=>makePlaceholder(i))];

  function scrollToFirst(listRef){
    try{
      const el = listRef && listRef.current;
      if(!el) return;
      el.scrollTo({ top: 0, behavior: 'smooth' });
      const first = el.querySelector('.message');
      if(first){
        first.classList.add('highlight');
        setTimeout(()=> first.classList.remove('highlight'), 1400);
      }
    }catch(e){ /* ignore */ }
  }

  // open a detail view for the first message in the given column
  const [viewMessage, setViewMessage] = useState(null);
  function viewFirst(column){
    if(column === 'sent'){
      if(sentMessages.length > 0){
        const m = sentMessages[0];
        setViewMessage(m);
        scrollToFirst(sentListRef);
      }
    } else {
      if(recvMessages.length > 0){
        const m = recvMessages[0];
        setViewMessage(m);
        scrollToFirst(recvListRef);
      }
    }
  }

  return (
    <div className="app">
      <header className="top">
        <div className="title">SMS Simulator & Test Interface</div>
        <div className="icons">
          <div className="icon" title="settings">⚙️</div>
          <div className="icon" title="profile">👤</div>
        </div>
      </header>

      <section className="controls">
        <div className="tabs" role="tablist">
          <div className={"tab "+(mode==='sent'? 'active':'')} onClick={()=>setMode('sent')}>Sent Messages</div>
          <div className={"tab "+(mode==='recv'? 'active':'')} onClick={()=>setMode('recv')}>Received Responses</div>
        </div>

        {/* Right side: trigger button and illustrative image under it */}
        <div className="controls-right">
          <button id="triggerBtn" className="trigger" onClick={trigger}>Trigger /get-message</button>
          <label className="auto-toggle"><input type="checkbox" checked={autoTrigger} onChange={(e)=>setAutoTrigger(e.target.checked)} /> Auto-run</label>
        </div>
      </section>

      <main id="mainArea">
        {/* Left column: Sent messages */}
        <div className="column" data-column="sent">
          <div className="subtitle">Sent Messages To /send-message <button className="small-link" onClick={()=>viewFirst('sent')}>View First</button></div>
          <div className="panel">
            <div className="message-list" ref={sentListRef}>
              {sentMessages.map((m,idx)=> (
                <div className="message" key={'s-'+idx}>
                  <div className="m-left">
                    <div className="to">To: {m.number}</div>
                    <div className="from">From: TestApp</div>
                    <div className="util">{m.util || ''}</div>
                    <div className="content">{m.content}</div>
                    <div className="status-line">
                      <div className="status-small">Status: {m.status || ''}</div>
                      <div style={{flex:1}} />
                      {m.delivered !== false && <div className="badge">Status: Delivered</div>}
                    </div>
                  </div>
                  <div className="m-right">{formatTime(m.timestamp)}</div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Right column: Received responses */}
        <div className="column" data-column="recv">
          <div className="subtitle">Simulated Incoming Responses From /get-message <button className="small-link" onClick={()=>viewFirst('recv')}>View First</button></div>
          <div className="panel">
            <div className="message-list" ref={recvListRef}>
              {recvDisplay.map((m,idx)=> (
                <div className={"message" + (m.placeholder ? ' placeholder' : '')} key={m.id || ('r-'+idx)}>
                  <div className="m-left">
                    <div className="to">From: {m.number}</div>
                    <div className="from">To: TestApp</div>
                    <div className="util">{m.util || ''}</div>
                    <div className="content">{m.content}</div>
                    <div className="status-line">
                      <div className="status-small">Status: {m.status || ''}</div>
                      <div style={{flex:1}} />
                      {m.delivered !== false && <div className="badge">Status: Delivered</div>}
                    </div>
                  </div>
                  <div className="m-right">{formatTime(m.timestamp)}</div>
                </div>
              ))}

              {callbackInfo && (
                <div className="callback">
                  <div className="status-small">Status: Pushed to Callback</div>
                  <div>Callback URL: <a href={callbackInfo.url} target="_blank" rel="noreferrer">{callbackInfo.url}</a></div>
                </div>
              )}
            </div>
          </div>
        </div>
      </main>

        {/* single full-width footer with live cache counter */}
        <footer className="status full-footer" role="status">
          <div style={{display:'flex',alignItems:'center',gap:8}}><div className="dot"/>System Status: <span style={{marginLeft:8}}>Running</span></div>
          <div className={"footer-cache" + (pulse ? ' pulse' : '')} title="Current cache size">Cache: <strong id="cacheSize">{cacheCount}</strong> {cacheCount === 1 ? 'entry' : 'entries'}</div>
        </footer>

      {/* simple modal to view a message in detail */}
      {viewMessage && (
        <div className="msg-modal" role="dialog" onClick={()=>setViewMessage(null)}>
          <div className="msg-modal-inner" onClick={(e)=>e.stopPropagation()}>
            <div style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
              <strong>Message Detail</strong>
              <button className="small-link" onClick={()=>setViewMessage(null)}>Close</button>
            </div>
            <div style={{marginTop:12}}>
              <div style={{fontSize:13,color:'#64748b'}}>From: {viewMessage.sender === 0 ? 'Provider' : 'User'}</div>
              <div style={{fontSize:13,color:'#64748b'}}>Number: {viewMessage.number}</div>
              <div style={{marginTop:10,whiteSpace:'pre-wrap'}} className="content">{viewMessage.content || viewMessage.text || ''}</div>
              <div style={{marginTop:12,fontSize:12,color:'#94a3b8'}}>{formatTime(viewMessage.timestamp)}</div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
