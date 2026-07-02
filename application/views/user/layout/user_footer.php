<div id="kt_app_footer" class="app-footer  d-flex flex-column flex-md-row align-items-center flex-center flex-md-stack py-2 py-lg-4 ">
<!--begin::Copyright-->
<div class="text-gray-900 order-2 order-md-1">
<span class="text-muted fw-semibold me-1"><?php echo date('Y'); ?>&copy;</span>
<a href="https://nexman.in/best-mlm-software" target="_blank" class="text-gray-800 text-hover-primary">Nexman MLM Software</a>
</div>
<!--end::Copyright-->
</div>

<style>
#ai-assistant{position:fixed;right:20px;bottom:20px;z-index:9999;font-family:Inter,system-ui;}
#ai-toggle{width:54px;height:54px;border-radius:50%;border:0;cursor:pointer;
  background:linear-gradient(135deg,#4f46e5,#22c55e);color:#fff;font-size:22px;
  box-shadow:0 10px 26px rgba(16,24,40,.18);}
#ai-toggle:hover{transform:translateY(-1px);box-shadow:0 14px 32px rgba(16,24,40,.22);transition:.2s}

#ai-panel{width:360px;max-height:70vh;display:flex;flex-direction:column;
  background:#fff;border-radius:18px;box-shadow:0 18px 46px rgba(16,24,40,.22);
  overflow:hidden;border:1px solid #e6ebf2;}
@media (max-width: 600px){#ai-panel{width:92vw;left:4vw}}
.ai-header{display:flex;align-items:center;justify-content:space-between;
  padding:10px 12px;background:linear-gradient(90deg,#f1f5ff,#f2fff7);border-bottom:1px solid #e6ebf2}
.ai-title{font-weight:800;color:#0f172a}
#ai-close{border:0;background:transparent;font-size:18px;color:#475467;cursor:pointer}

.ai-body{padding:12px;height:320px;overflow:auto;background:#fbfcfe}
.ai-msg{max-width:86%;padding:10px 12px;border-radius:12px;margin:6px 0;line-height:1.35}
.ai-bot{background:#eef2ff;color:#0f172a;border:1px solid #d9e1ff}
.ai-user{background:#e7f9ef;color:#0f172a;border:1px solid #c6f1d7;margin-left:auto}
.ai-typing{font-size:12px;color:#667085;padding:4px 2px}

.ai-input{display:flex;gap:8px;padding:10px;border-top:1px solid #e6ebf2;background:#fff}
#ai-text{flex:1;border:1px solid #e2e8f0;border-radius:12px;padding:10px 12px;outline:0}
.ai-send{border:0;border-radius:12px;background:#111827;color:#fff;padding:10px 14px;font-weight:700;cursor:pointer}
.ai-send:hover{opacity:.9}
.ai-quick{padding:8px 10px;border-bottom:1px solid #e6ebf2;background:#fff}
.ai-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:6px}
.ai-tab{padding:6px 10px;border-radius:999px;border:1px solid #e5e7eb;
  background:#f8fafc;color:#334155;font-weight:700;cursor:pointer}
.ai-tab.active{background:#111827;color:#fff;border-color:#111827}
.ai-actions{display:block}
.ai-group{display:flex;gap:8px;flex-wrap:wrap}
.ai-group.hidden{display:none}
.ai-chip{padding:6px 10px;border-radius:10px;border:1px solid #e2e8f0;
  background:#fff;color:#0f172a;font-weight:700;cursor:pointer}
.ai-chip:hover{background:#f1f5f9}
</style>


<!-- <div id="ai-assistant">
  <button id="ai-toggle" aria-label="Open AI Assistant">
    <span>🤖</span>
  </button>

  <div id="ai-panel" hidden>
    <div class="ai-header">
      <div class="ai-title">Assistant</div>
      <button id="ai-close" aria-label="Close">✕</button>
    </div>

    <div class="ai-body" id="ai-body">
            <div class="ai-quick">
            <div class="ai-tabs" role="tablist" aria-label="Assistant categories">
                <button class="ai-tab active" data-group="balance" type="button">Balance</button>
                <button class="ai-tab" data-group="transactions" type="button">Transactions</button>
                <button class="ai-tab" data-group="downline" type="button">My Downline</button>
                <button class="ai-tab" data-group="team" type="button">Team</button>
                <button class="ai-tab" data-group="shop" type="button">Shop</button>
            </div>

            <div class="ai-actions">
                <div class="ai-group" data-group="balance">
                <button class="ai-chip" data-intent="balance.main"       type="button">Main Balance</button>
                <button class="ai-chip" data-intent="balance.commissions" type="button">Commissions</button>
                </div>

                <div class="ai-group hidden" data-group="transactions">
                <button class="ai-chip" data-intent="tx.last"   type="button">Last Transaction</button>
                <button class="ai-chip" data-intent="tx.recent" type="button">Recent 10</button>
                </div>

                <div class="ai-group hidden" data-group="downline">
                <button class="ai-chip" data-intent="downline.leftCount"  type="button">Left Count</button>
                <button class="ai-chip" data-intent="downline.rightCount" type="button">Right Count</button>
                <button class="ai-chip" data-intent="downline.investments" type="button">Left/Right Investment</button>
                <button class="ai-chip" data-intent="downline.totalInvestment" type="button">Total Investment</button>
                </div>

                <div class="ai-group hidden" data-group="team">
                <button class="ai-chip" data-intent="team.summary" type="button">Summary</button>
                </div>

                <div class="ai-group hidden" data-group="shop">
                <button class="ai-chip" data-intent="shop.myOrders"  type="button">My Orders</button>
                <button class="ai-chip" data-intent="shop.topOrders" type="button">Top Orders</button>
                <button class="ai-chip" data-intent="shop.favorites" type="button">Favorite Items</button>
                </div>
            </div>
            </div>

    </div>

    <form id="ai-form" class="ai-input">
      <input id="ai-text" type="text" placeholder="Type your question…" autocomplete="off" />
      <button class="ai-send" type="submit">Send</button>
    </form>
  </div>
</div> -->
<!-- 
<script>
(()=> {
  const btn = document.getElementById('ai-toggle');
  const panel = document.getElementById('ai-panel');
  const closeBtn = document.getElementById('ai-close');
  const form = document.getElementById('ai-form');
  const input = document.getElementById('ai-text');
  const body = document.getElementById('ai-body');

  const open = ()=>{ panel.hidden=false; input.focus(); }
  const close = ()=>{ panel.hidden=true; }
  btn.addEventListener('click', open);
  closeBtn.addEventListener('click', close);

  function addMsg(text, who='bot'){
    const el = document.createElement('div');
    el.className = `ai-msg ai-${who}`;
    el.innerText = text;
    body.appendChild(el);
    body.scrollTop = body.scrollHeight;
  }
  function typing(on){
    let el = document.querySelector('.ai-typing');
    if(on){
      if(!el){ el = document.createElement('div'); el.className='ai-typing'; el.textContent='Assistant is typing…'; body.appendChild(el); }
    }else{ el && el.remove(); }
    body.scrollTop = body.scrollHeight;
  }

  async function sendIntent(intent, params={}){
    typing(true);
    try{
      const res = await fetch('<?php echo base_url();?>api/assistant', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ intent, params: JSON.stringify(params) })
      });
      const data = await res.json();
      typing(false);
      renderResponse(data);
    }catch(e){
      typing(false); addMsg('Sorry, something went wrong.','bot');
    }
  }

  function renderResponse(data){
    if(data.text) addMsg(data.text,'bot');
    if(data.table){
      const wrap = document.createElement('div'); wrap.className='ai-msg ai-bot';
      const table = document.createElement('table');
      table.style.width='100%'; table.style.fontSize='12px'; table.style.borderCollapse='collapse';
      const thead = document.createElement('thead'); const trh = document.createElement('tr');
      data.table.headers.forEach(h=>{ const th=document.createElement('th');
        th.textContent=h; th.style.textAlign='left'; th.style.padding='4px 6px'; th.style.borderBottom='1px solid #e5e7eb';
        trh.appendChild(th); });
      thead.appendChild(trh); table.appendChild(thead);
      const tb=document.createElement('tbody');
      data.table.rows.forEach(r=>{
        const tr=document.createElement('tr');
        r.forEach(c=>{ const td=document.createElement('td'); td.textContent=c; td.style.padding='4px 6px'; tr.appendChild(td); });
        tb.appendChild(tr);
      });
      table.appendChild(tb); wrap.appendChild(table); body.appendChild(wrap);
      body.scrollTop = body.scrollHeight;
    }
  }

  // free-text still supported
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const text = input.value.trim(); if(!text) return;
    addMsg(text,'user'); input.value='';
    typing(true);
    try{
      const res = await fetch('api/assistant.php', {
        method:'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ message: text })
      });
      const data = await res.json(); typing(false); renderResponse(data);
    }catch(err){ typing(false); addMsg('Sorry, something went wrong.','bot'); }
  });

  // tabs
  document.querySelectorAll('.ai-tab').forEach(t=>{
    t.addEventListener('click', ()=>{
      document.querySelectorAll('.ai-tab').forEach(x=>x.classList.remove('active'));
      t.classList.add('active');
      const g = t.dataset.group;
      document.querySelectorAll('.ai-group').forEach(gr=>{
        gr.classList.toggle('hidden', gr.dataset.group !== g);
      });
    });
  });

  // chips -> intents
  document.querySelectorAll('.ai-chip').forEach(c=>{
    c.addEventListener('click', ()=>{
      const intent = c.dataset.intent;
      addMsg(c.textContent.trim(),'user');
      sendIntent(intent);
    });
  });
})();
</script> -->
