<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<meta http-equiv="refresh" content="30">
<title>আপডেট চলছে — মসলা মার্ট</title>
<style>
  /* Fully self-contained (no CDN / no assets): this page must render while the app is being redeployed. */
  :root { --g1:#0f3d22; --g2:#14532d; --g3:#1a6b3a; --gold:#c9a227; --cream:#fef9ee; }
  * { box-sizing:border-box; margin:0; padding:0; }
  html,body { height:100%; }
  body { font-family:'Noto Sans Bengali','Hind Siliguri','Segoe UI',system-ui,-apple-system,sans-serif; color:var(--cream);
         background:radial-gradient(1200px 600px at 50% -10%, #1f7a43 0%, var(--g2) 45%, var(--g1) 100%);
         display:flex; align-items:center; justify-content:center; padding:20px; overflow:hidden; }
  .bg-dot { position:fixed; border-radius:50%; background:rgba(201,162,39,.08); animation:float 9s ease-in-out infinite; pointer-events:none; }
  .bg-dot.a { width:280px; height:280px; left:-80px; top:12%; }
  .bg-dot.b { width:200px; height:200px; right:-50px; bottom:10%; animation-delay:-4s; }
  @keyframes float { 0%,100%{ transform:translateY(0) } 50%{ transform:translateY(-22px) } }
  .card { position:relative; width:100%; max-width:460px; text-align:center; padding:38px 28px 30px;
          background:rgba(255,255,255,.06); border:1px solid rgba(201,162,39,.35); border-radius:24px;
          backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); box-shadow:0 24px 60px rgba(0,0,0,.35); }
  .badge { width:88px; height:88px; margin:0 auto 20px; border-radius:50%; position:relative;
           background:linear-gradient(145deg,var(--gold),#e2bb45); display:flex; align-items:center; justify-content:center;
           box-shadow:0 8px 26px rgba(201,162,39,.45); }
  .badge::after { content:''; position:absolute; inset:-9px; border-radius:50%; border:2px dashed rgba(201,162,39,.55); animation:spin 14s linear infinite; }
  .badge svg { width:42px; height:42px; color:var(--g1); animation:spin 3.2s linear infinite; }
  @keyframes spin { to{ transform:rotate(360deg) } }
  .brand { font-size:13px; letter-spacing:.14em; color:var(--gold); font-weight:700; margin-bottom:10px; }
  h1 { font-size:26px; line-height:1.35; font-weight:800; margin-bottom:10px; }
  p.sub { font-size:15px; line-height:1.7; color:#d3e8d3; }
  .bar { margin:26px 0 10px; height:8px; border-radius:99px; background:rgba(255,255,255,.12); overflow:hidden; }
  .bar i { display:block; height:100%; width:40%; border-radius:99px; background:linear-gradient(90deg,var(--gold),#f0d36a,var(--gold)); animation:slide 1.7s ease-in-out infinite; }
  @keyframes slide { 0%{ transform:translateX(-110%) } 100%{ transform:translateX(280%) } }
  .status { font-size:13px; color:#b7d6b7; min-height:20px; }
  .status b { color:var(--gold); }
  .steps { display:flex; justify-content:center; gap:8px; margin:20px 0 6px; flex-wrap:wrap; }
  .steps span { font-size:12px; padding:5px 11px; border-radius:99px; background:rgba(255,255,255,.09); color:#cfe6cf; }
  .steps span.on { background:rgba(201,162,39,.22); color:#f6e3a1; }
  .btn { display:inline-block; margin-top:22px; padding:11px 24px; border-radius:12px; border:0; cursor:pointer;
         background:var(--gold); color:var(--g1); font-weight:800; font-size:14px; font-family:inherit; }
  .btn:hover { background:#e2bb45; }
  .note { margin-top:16px; font-size:12px; color:#8fb88f; }
  @media (max-width:420px){ h1{font-size:22px} .card{padding:32px 20px 26px} }
  @media (prefers-reduced-motion:reduce){ *{ animation:none!important } }
</style>
</head>
<body>
<div class="bg-dot a"></div><div class="bg-dot b"></div>
<main class="card" role="status" aria-live="polite">
  <div class="badge" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-3-6.7"/><path d="M21 4v5h-5"/></svg>
  </div>
  <div class="brand">মসলা মার্ট</div>
  <h1>আমরা সাইটটি আপডেট করছি</h1>
  <p class="sub">নতুন ফিচার ও উন্নতি যোগ হচ্ছে। এটি কয়েক মিনিটের বেশি লাগবে না — কাজ শেষ হলেই পেজ নিজে থেকে রিফ্রেশ হবে, আপনাকে কিছু করতে হবে না।</p>
  <div class="bar" aria-hidden="true"><i></i></div>
  <div class="status" id="st">আপডেট চলছে… <b id="sec"></b></div>
  <div class="steps" aria-hidden="true"><span class="on">কোড আপডেট</span><span class="on">ডেটা প্রস্তুত</span><span>সাইট চালু</span></div>
  <button class="btn" type="button" onclick="check(true)">এখনই দেখুন</button>
  <p class="note">অর্ডার বা ডেটা নিয়ে চিন্তার কিছু নেই — সবকিছু নিরাপদ আছে।</p>
</main>
<script>
(function () {
  var INTERVAL = 5, left = INTERVAL, busy = false, tries = 0;
  var sec = document.getElementById('sec'), st = document.getElementById('st'),
      steps = document.querySelectorAll('.steps span');
  var bn = function (n) { return String(n).replace(/\d/g, function (d) { return '০১২৩৪৫৬৭৮৯'[d]; }); };

  function done() {
    steps[2].className = 'on';
    st.textContent = '✓ আপডেট সম্পন্ন! পেজ খুলছে…';
    // On the static fallback page (/updating.html) go to the home page; otherwise reload the page the customer was on.
    setTimeout(function () { /updating\.html$/.test(location.pathname) ? (location.href = '/') : location.reload(); }, 600);
  }
  // The health route /up answers 200 only once the app is live again (503 while updating).
  function check(manual) {
    if (busy) return; busy = true;
    if (manual) { st.textContent = 'চেক করা হচ্ছে…'; }
    fetch('/up?t=' + Date.now(), { cache: 'no-store' })
      .then(function (r) { busy = false; if (r.ok) { done(); } else { tries++; left = INTERVAL; } })
      .catch(function () { busy = false; tries++; left = INTERVAL; });
  }
  setInterval(function () {
    if (busy) return;
    sec.textContent = '(পরবর্তী চেক ' + bn(left) + ' সেকেন্ডে)';
    if (--left <= 0) { left = INTERVAL; check(false); }
  }, 1000);
  sec.textContent = '(পরবর্তী চেক ' + bn(left) + ' সেকেন্ডে)';
})();
</script>
</body>
</html>
