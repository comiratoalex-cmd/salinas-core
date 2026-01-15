<?php
// index.php — SALINAS CORE Dashboard (Glass + dblclick card + close button)
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title>SALINAS CORE</title>

  <!-- Seu CSS -->
  <link rel="stylesheet" href="/assets/dashboard.css?v=20260113_5">

  <!-- Pequeno extra pro botão fechar do card -->
  <style>
    .cardClose{
      position:absolute;
      top:10px; right:10px;
      width:36px; height:36px;
      border-radius:12px;
      border:1px solid rgba(255,255,255,.16);
      background: rgba(255,255,255,.08);
      color: rgba(255,255,255,.92);
      font-weight: 900;
      cursor: pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
    }
    .cardClose:active{ transform: scale(.98); }
    .hoverCard{ display:none; }
    .hoverCard.show{ display:block; }
  </style>
</head>

<body>
  <div class="app">

    <aside class="sidebar">
      <div class="brand">
        <div class="brand-title">SALINAS CORE</div>
        <div class="brand-sub">Entry/Exit sessions + Online now (no blinking)</div>
      </div>

      <div class="nav">
        <div class="nav-item active">Dashboard</div>
        <div class="nav-item">Sessions</div>
        <div class="nav-item">Online</div>
        <div class="nav-item">Banned</div>
      </div>
    </aside>

    <main class="main">
      <div class="topbar">
        <div>
          <h1>Dashboard</h1>
          <div class="muted tiny">updated: <span id="lastUpdate">—</span></div>
        </div>

        <div class="topbar-actions">
          <label class="toggle">
            <input id="silentToggle" type="checkbox" checked>
            <span>silent auto refresh</span>
          </label>

          <button class="btn" id="refreshBtn">Refresh</button>
        </div>
      </div>

      <section class="cards">
        <div class="card stat">
          <div class="label">Total sessions</div>
          <div class="value" id="statSessions">—</div>
        </div>
        <div class="card stat">
          <div class="label">Unique avatars</div>
          <div class="value" id="statUnique">—</div>
        </div>
        <div class="card stat">
          <div class="label">Total minutes</div>
          <div class="value" id="statMinutes">—</div>
        </div>
        <div class="card stat">
          <div class="label">Online now</div>
          <div class="value" id="statOnline">—</div>
        </div>
      </section>

      <section class="content">
        <!-- LEFT: SESSIONS TABLE -->
        <div class="card left">
          <div class="toolbar">
            <div class="toolbar-left">
              <input class="input" id="searchInput" placeholder="Search avatar / region / parcel..." />
              <select class="select" id="rowsSelect">
                <option value="50">50 rows</option>
                <option value="100" selected>100 rows</option>
                <option value="200">200 rows</option>
              </select>
            </div>
            <div class="tiny">Sessions</div>
          </div>

          <div class="tableWrap">
            <table class="table" id="sessionsTable">
              <thead>
                <tr>
                  <th>Avatar</th>
                  <th>Status</th>
                  <th>Region / Parcel</th>
                  <th>Entry</th>
                  <th>Exit</th>
                  <th class="rightText">Minutes</th>
                </tr>
              </thead>
              <tbody id="sessionsBody">
                <tr><td colspan="6" class="muted">Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RIGHT: ONLINE + BANS -->
        <div class="right">
          <div class="card panel">
            <div class="panel-head">
              <div class="panel-title">
                <span class="dot green"></span>
                Online Now (parcel)
              </div>
              <div class="tiny">Realtime presence (backend)</div>
            </div>
            <div class="panel-body" id="onlineBox">
              <div class="muted">Loading...</div>
            </div>
          </div>

          <div class="card panel">
            <div class="panel-head">
              <div class="panel-title">
                <span class="dot red"></span>
                Banned
              </div>
              <div class="tiny">Active bans list</div>
            </div>
            <div class="panel-body" id="bansBox">
              <div class="muted">Loading...</div>
            </div>
          </div>

          <div class="card panel">
            <div class="panel-head">
              <div class="panel-title">Diagnóstico</div>
              <div class="tiny">Se endpoint voltar HTML/erro, aparece aqui</div>
            </div>
            <div class="panel-body">
              <pre id="diag" class="tiny" style="white-space:pre-wrap;word-break:break-word;margin:0;color:rgba(255,255,255,.70)">—</pre>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <!-- HOVER / VISIT CARD -->
  <div id="hoverCard" class="hoverCard" aria-hidden="true">
    <button id="cardClose" class="cardClose" title="Fechar" aria-label="Fechar">✕</button>

    <div class="hoverTop">
      <img id="cardImg" class="hoverImg" src="" alt="">
      <div>
        <div id="cardName" class="hoverName">—</div>
        <div id="cardUUID" class="hoverLine tiny">—</div>
      </div>
    </div>

    <div id="cardLocation" class="hoverLine">—</div>
    <div id="cardSlurl" class="hoverLine">—</div>

    <div class="hoverActions">
      <a id="cardProfile" class="hoverLink" href="#" target="_blank" rel="noopener">Profile</a>
      <a id="cardMap" class="hoverLink" href="#" target="_blank" rel="noopener">Map</a>
    </div>
  </div>

<script>
/* =============================
   CONFIG ENDPOINTS
   ============================= */
const API = {
  stats:   "/api/stats.php",
  sessions:"/api/sessions.php",
  online:  "/api/online.php",
  bans:    "/api/bans.php?active=1",
};

/* =============================
   HELPERS
   ============================= */
const $ = (id) => document.getElementById(id);

function nowStr(){
  const d = new Date();
  const pad = (n)=> String(n).padStart(2,"0");
  return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

async function fetchJSON(url){
  const res = await fetch(url, { cache:"no-store" });
  const text = await res.text();

  // se voltou HTML, loga no diagnóstico
  if (text.trim().startsWith("<")) {
    $("diag").textContent = `HTML/ERRO em ${url}\n\n` + text.slice(0, 800);
    throw new Error("Endpoint returned HTML");
  }

  try {
    return JSON.parse(text);
  } catch (e) {
    $("diag").textContent = `JSON inválido em ${url}\n\n` + text.slice(0, 800);
    throw e;
  }
}

function slImage(uuid){
  // sua base atual
  return `https://world.secondlife.com/resident/${uuid}/image`;
}
function slProfile(uuid){
  return `https://world.secondlife.com/resident/${uuid}`;
}
function mapsFromSlurl(slurl){
  // já é maps.secondlife.com normalmente; usa direto
  return slurl;
}

function safeText(v){
  return (v===null || v===undefined || v==="") ? "—" : String(v);
}

/* =============================
   VISIT CARD (dblclick + close)
   ============================= */
let cardOpen = false;

// posiciona próximo do clique em desktop
function positionCardNear(x, y){
  const card = $("hoverCard");
  const w = 360;
  const pad = 12;
  let left = Math.min(window.innerWidth - w - pad, Math.max(pad, x + 12));
  let top  = Math.min(window.innerHeight - 220 - pad, Math.max(pad, y + 12));
  card.style.left = left + "px";
  card.style.top = top + "px";
}

// abre card
function openCard(payload, event){
  const card = $("hoverCard");

  $("cardName").textContent = safeText(payload.avatar_name);
  $("cardUUID").textContent = safeText(payload.avatar_uuid);

  const img = payload.avatar_image || slImage(payload.avatar_uuid);
  $("cardImg").src = img;
  $("cardImg").onerror = () => { $("cardImg").src = "/assets/avatar_fallback.png"; };

  $("cardLocation").textContent = `${safeText(payload.region)} • ${safeText(payload.parcel)}`;
  $("cardSlurl").textContent = safeText(payload.slurl);

  $("cardProfile").href = slProfile(payload.avatar_uuid);
  $("cardMap").href = mapsFromSlurl(payload.slurl);

  // desktop posiciona no clique; mobile CSS já fixa embaixo
  if (event && event.clientX !== undefined) positionCardNear(event.clientX, event.clientY);

  card.classList.add("show");
  card.setAttribute("aria-hidden","false");
  cardOpen = true;
}

function closeCard(){
  const card = $("hoverCard");
  card.classList.remove("show");
  card.setAttribute("aria-hidden","true");
  cardOpen = false;
}

$("cardClose").addEventListener("click", closeCard);

// fecha ao clicar fora do card
document.addEventListener("mousedown", (e)=>{
  if(!cardOpen) return;
  const card = $("hoverCard");
  if(!card.contains(e.target)) closeCard();
});

// ESC fecha
document.addEventListener("keydown", (e)=>{
  if(e.key === "Escape") closeCard();
});

/* Double tap (mobile) */
let lastTapTime = 0;
function isDoubleTap(){
  const now = Date.now();
  const dt = now - lastTapTime;
  lastTapTime = now;
  return dt < 320; // janela do double-tap
}

/* =============================
   RENDER SESSIONS
   - abre card com dblclick
   ============================= */
let SESSIONS = [];

function renderSessions(){
  const q = $("searchInput").value.trim().toLowerCase();
  const limit = parseInt($("rowsSelect").value, 10);

  let rows = SESSIONS;

  if (q) {
    rows = rows.filter(r => {
      const blob = `${r.avatar_name||""} ${r.avatar_uuid||""} ${r.region||""} ${r.parcel||""} ${r.slurl||""}`.toLowerCase();
      return blob.includes(q);
    });
  }

  rows = rows.slice(0, limit);

  const tbody = $("sessionsBody");
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="6" class="muted">No data</td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map(r=>{
    const status = (r.exit_time ? "exit" : "entry");
    const tagClass = status === "entry" ? "entry" : "exit";
    const minutes = (r.duration_minutes ?? r.duration_minutes === 0) ? r.duration_minutes : "—";

    const img = r.avatar_image || slImage(r.avatar_uuid);

    // data-* pra abrir card
    const data = encodeURIComponent(JSON.stringify({
      avatar_uuid: r.avatar_uuid,
      avatar_name: r.avatar_name,
      avatar_image: img,
      region: r.region,
      parcel: r.parcel,
      slurl: r.slurl
    }));

    return `
      <tr class="sessionRow" data-card="${data}">
        <td>
          <div class="avatarCell">
            <img class="avatarImg" src="${img}" onerror="this.src='/assets/avatar_fallback.png'" alt="">
            <div class="avatarMeta">
              <div class="avatarName">${safeText(r.avatar_name)}</div>
              <div class="avatarSub">${safeText(r.slurl)}</div>
            </div>
          </div>
        </td>
        <td><span class="tag ${tagClass}">${status.toUpperCase()}</span></td>
        <td>
          <div style="font-weight:800">${safeText(r.region)}</div>
          <div class="tiny muted">${safeText(r.parcel)}</div>
        </td>
        <td class="tiny">${safeText(r.entry_time)}</td>
        <td class="tiny">${safeText(r.exit_time)}</td>
        <td class="rightText" style="font-weight:900">${minutes}</td>
      </tr>
    `;
  }).join("");

  // dblclick abre card
  document.querySelectorAll(".sessionRow").forEach(row=>{
    row.addEventListener("dblclick", (e)=>{
      const payload = JSON.parse(decodeURIComponent(row.dataset.card));
      openCard(payload, e);
    });

    // mobile: double-tap
    row.addEventListener("touchend", (e)=>{
      if (isDoubleTap()){
        const payload = JSON.parse(decodeURIComponent(row.dataset.card));
        openCard(payload, e.changedTouches?.[0] || null);
      }
    }, {passive:true});
  });
}

/* =============================
   ONLINE PANEL
   ============================= */
function renderOnline(list){
  const box = $("onlineBox");
  if (!Array.isArray(list) || list.length === 0){
    box.innerHTML = `<div class="muted">No one online</div>`;
    return;
  }

  box.innerHTML = list.map(r=>{
    const img = r.avatar_image || slImage(r.avatar_uuid);
    const data = encodeURIComponent(JSON.stringify({
      avatar_uuid: r.avatar_uuid,
      avatar_name: r.avatar_name,
      avatar_image: img,
      region: r.region,
      parcel: r.parcel,
      slurl: r.slurl
    }));
    return `
      <div class="onlineItem pulse" data-card="${data}">
        <img class="avatarImg" src="${img}" onerror="this.src='/assets/avatar_fallback.png'" alt="">
        <div class="avatarMeta" style="min-width:0">
          <div class="avatarName">${safeText(r.avatar_name)}</div>
          <div class="tiny muted">${safeText(r.parcel)}</div>
        </div>
        <a class="profileBtn" href="${slProfile(r.avatar_uuid)}" target="_blank" rel="noopener">Profile</a>
      </div>
    `;
  }).join("");

  // dblclick abre card
  box.querySelectorAll("[data-card]").forEach(item=>{
    item.addEventListener("dblclick", (e)=>{
      const payload = JSON.parse(decodeURIComponent(item.dataset.card));
      openCard(payload, e);
    });
    item.addEventListener("touchend", (e)=>{
      if (isDoubleTap()){
        const payload = JSON.parse(decodeURIComponent(item.dataset.card));
        openCard(payload, e.changedTouches?.[0] || null);
      }
    }, {passive:true});
  });
}

/* =============================
   BANS PANEL
   ============================= */
function renderBans(list){
  const box = $("bansBox");
  if (!Array.isArray(list) || list.length === 0){
    box.innerHTML = `<div class="muted">No active bans</div>`;
    return;
  }

  box.innerHTML = list.map(r=>{
    const img = r.avatar_image || slImage(r.avatar_uuid);
    return `
      <div class="banItem">
        <img class="avatarImg" src="${img}" onerror="this.src='/assets/avatar_fallback.png'" alt="">
        <div class="avatarMeta" style="min-width:0">
          <div class="avatarName">${safeText(r.avatar_name)}</div>
          <div class="tiny muted">${safeText(r.reason || "—")}</div>
        </div>
        <a class="profileBtn" href="${slProfile(r.avatar_uuid)}" target="_blank" rel="noopener">Profile</a>
      </div>
    `;
  }).join("");
}

/* =============================
   LOAD ALL
   ============================= */
async function loadAll(){
  $("diag").textContent = "—";

  try{
    const [stats, sessions, online, bans] = await Promise.all([
      fetchJSON(API.stats),
      fetchJSON(API.sessions),
      fetchJSON(API.online),
      fetchJSON(API.bans),
    ]);

    // stats
    $("statSessions").textContent = safeText(stats.total_sessions ?? stats.total ?? stats.sessions);
    $("statUnique").textContent   = safeText(stats.unique_avatars ?? stats.unique ?? stats.avatars);
    $("statMinutes").textContent  = safeText(stats.total_minutes ?? stats.minutes ?? stats.totalMinutes);
    $("statOnline").textContent   = safeText(stats.online_now ?? stats.online ?? (Array.isArray(online) ? online.length : "—"));

    // sessions
    SESSIONS = Array.isArray(sessions) ? sessions : (sessions.rows || []);
    renderSessions();

    // online + bans
    renderOnline(Array.isArray(online) ? online : []);
    renderBans(Array.isArray(bans) ? bans : []);

    $("lastUpdate").textContent = nowStr();
  }catch(err){
    $("lastUpdate").textContent = "ERROR";
    if (!$("diag").textContent || $("diag").textContent === "—") {
      $("diag").textContent = String(err?.message || err);
    }
  }
}

/* =============================
   UI Events
   ============================= */
$("refreshBtn").addEventListener("click", loadAll);
$("searchInput").addEventListener("input", ()=> renderSessions());
$("rowsSelect").addEventListener("change", ()=> renderSessions());

/* Silent refresh (não pisca a página, só re-renderiza DOM) */
let timer = null;
function startAuto(){
  stopAuto();
  timer = setInterval(()=>{
    if ($("silentToggle").checked) loadAll();
  }, 8000);
}
function stopAuto(){
  if (timer) clearInterval(timer);
  timer = null;
}
$("silentToggle").addEventListener("change", ()=>{
  if ($("silentToggle").checked) startAuto();
  else stopAuto();
});

/* INIT */
loadAll();
startAuto();
</script>
</body>
</html>
