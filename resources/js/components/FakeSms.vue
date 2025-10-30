<template>
  <div class="app">
    <header class="top">
      <div class="title">SMS Simulator & Test Interface</div>
      <div class="icons">
        <div class="icon" title="settings" v-html="gearSvg"></div>
        <div class="icon" title="profile" v-html="userSvg"></div>
      </div>
    </header>

    <section class="controls">
      <div class="tabs" role="tablist">
        <div :class="['tab', mode==='sent'? 'active':'' ]" @click="setMode('sent')">Sent Messages</div>
        <div :class="['tab', mode==='recv'? 'active':'' ]" @click="setMode('recv')">Received Responses</div>
      </div>
      <div>
        <button id="triggerBtn" class="trigger" @click="trigger">Trigger /get-message</button>
      </div>
    </section>

    <main id="mainArea">
      <div class="column" v-show="mode==='sent'">
        <div class="subtitle">Sent Messages To /send-message</div>
        <div class="panel">
          <div class="message-list">
            <div class="message" v-for="(m, idx) in sentMessages" :key="'s-'+idx">
              <div class="m-left">
                <div class="to">To: {{ m.number }}</div>
                <div class="from">From: TestApp</div>
                <div class="util">{{ m.util || '' }}</div>
                <div class="content">{{ m.content }}</div>
                <div class="status-line">
                  <div class="status-small">Status: {{ m.status || '' }}</div>
                  <div style="flex:1"></div>
                  <div v-if="m.delivered !== false" class="badge">Status: Delivered</div>
                </div>
              </div>
              <div class="m-right">{{ formatTime(m.timestamp) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="column" v-show="mode==='recv'">
        <div class="subtitle">Simulated Incoming Responses From /get-message</div>
        <div class="panel">
          <div class="message-list">
            <div class="message" v-for="(m, idx) in recvMessages" :key="'r-'+idx">
              <div class="m-left">
                <div class="to">From: {{ m.number }}</div>
                <div class="from">To: TestApp</div>
                <div class="util">{{ m.util || '' }}</div>
                <div class="content">{{ m.content }}</div>
                <div class="status-line">
                  <div class="status-small">Status: {{ m.status || '' }}</div>
                  <div style="flex:1"></div>
                  <div v-if="m.delivered !== false" class="badge">Status: Delivered</div>
                </div>
              </div>
              <div class="m-right">{{ formatTime(m.timestamp) }}</div>
            </div>

            <div v-if="callbackInfo" class="callback">
              <div class="status-small">Status: Pushed to Callback</div>
              <div>Callback URL: <a :href="callbackInfo.url" target="_blank">{{ callbackInfo.url }}</a></div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="status">
      <div style="display:flex;align-items:center"><div class="dot"></div>System Status: <span style="margin-left:8px">Running</span></div>
      <div>Cache Size: <span id="cacheSize">{{ cacheSize }}</span> entries</div>
    </footer>
  </div>
</template>

<script>
export default {
  name: 'FakeSms',
  data(){
    return {
      mode: 'recv',
      messages: [],
      callbackInfo: null,
      timer: null,
      gearSvg: `<!-- gear -->
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7z" fill="#cbd5e1"/><path d="M19.4 12.9c.04-.3.06-.61.06-.92s-.02-.62-.06-.92l2.11-1.65a.5.5 0 0 0 .12-.63l-2-3.46a.5.5 0 0 0-.6-.22l-2.49 1a7.07 7.07 0 0 0-1.6-.93l-.38-2.65A.5.5 0 0 0 13.8 2h-3.6a.5.5 0 0 0-.5.42l-.38 2.65c-.57.22-1.1.52-1.6.93l-2.49-1a.5.5 0 0 0-.6.22l-2 3.46a.5.5 0 0 0 .12.63L4.6 11.06c-.04.3-.06.61-.06.92s.02.62.06.92L2.49 14.55a.5.5 0 0 0-.12.63l2 3.46c.14.24.44.34.7.22l2.49-1c.5.4 1.03.71 1.6.93l.38 2.65c.06.28.3.48.58.48h3.6c.28 0 .52-.2.58-.48l.38-2.65c.57-.22 1.1-.52 1.6-.93l2.49 1c.26.12.56.02.7-.22l2-3.46a.5.5 0 0 0-.12-.63l-2.11-1.65z" fill="#94a3b8"/></svg>`,
      userSvg: `<!-- user -->
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z" fill="#cbd5e1"/><path d="M4 20c0-2.21 3.58-4 8-4s8 1.79 8 4v1H4v-1z" fill="#94a3b8"/></svg>`
    }
  },
  computed:{
    sentMessages(){
      return this.messages.filter(m => m.sender === 0);
    },
    recvMessages(){
      return this.messages.filter(m => m.sender === 1);
    },
    cacheSize(){
      return this.messages.length;
    }
  },
  methods:{
    setMode(m){ this.mode = m },
    formatTime(ts){ if(!ts) return ''; try{ const d = new Date(ts); return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}); }catch(e){ return ts } },
    escapeHtml(s){ if(!s) return ''; return String(s).replace(/[&<>\"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":"&#39;"}[c]; }); },
    async refresh(){
      try{
        const r = await fetch('/api/cache-watch');
        const j = await r.json();
        this.messages = j.messages || [];
      }catch(e){ console.error(e) }
    },
    async trigger(){
      const num = '+123456' + Math.floor(Math.random()*900 + 100);
      const text = 'Received! ' + ['We\'re here to help.','Hello!','Thanks, got it.'][Math.floor(Math.random()*3)];
      try{
        await fetch('/api/get-message', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({number:num,text:text})});
        await this.refresh();
      }catch(e){ console.error(e) }
    }
  },
  mounted(){
    this.refresh();
    this.timer = setInterval(()=>this.refresh(), 2500);
  },
  beforeDestroy(){ clearInterval(this.timer) }
}
</script>

<style scoped>
/* Smaller overrides specific to component (we use global CSS file for main styles) */
</style>
