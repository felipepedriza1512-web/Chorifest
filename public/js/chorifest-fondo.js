/* Chorifest – fondo ambiental: brillo de brasas, humo y chispas.
   Uso: <script src="chorifest-fondo.js"></script> antes de </body>. Funciona sobre cualquier página oscura. */
(function () {
  if (window.__chorifestFondo) return; window.__chorifestFondo = true;
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var c = document.createElement('canvas');
  c.setAttribute('aria-hidden', 'true');
  c.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:40;mix-blend-mode:screen';
  document.body.appendChild(c);
  var x = c.getContext('2d'), W, H, dpr = Math.min(devicePixelRatio || 1, 2), P = [], S = [];
  function size() { W = innerWidth; H = innerHeight; c.width = W * dpr; c.height = H * dpr; x.setTransform(dpr, 0, 0, dpr, 0, 0); }
  size(); addEventListener('resize', size);

  /* viñeta + grano (no bloquean clics) */
  var v = document.createElement('div');
  v.setAttribute('aria-hidden', 'true');
  v.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:39;background:radial-gradient(ellipse at 50% 55%,transparent 55%,rgba(0,0,0,.45) 100%)';
  document.body.appendChild(v);
  var g = document.createElement('div');
  g.setAttribute('aria-hidden', 'true');
  g.style.cssText = "position:fixed;inset:0;pointer-events:none;z-index:41;opacity:.07;mix-blend-mode:overlay;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E\")";
  document.body.appendChild(g);

  for (var i = 0; i < 5; i++) S.push({ x: Math.random(), y: .55 + Math.random() * .45, r: 160 + Math.random() * 180, sp: .00006 + Math.random() * .00008, ph: Math.random() * 6.28 });
  function spark() {
    P.push({ x: W * Math.random(), y: H * (1.02 + Math.random() * .04), vx: (Math.random() - .5) * .4, vy: -(.5 + Math.random() * 1.3),
      r: .6 + Math.random() * 1.7, life: 0, max: 160 + Math.random() * 240, ph: Math.random() * 6.28 });
  }
  function frame(t) {
    x.clearRect(0, 0, W, H);
    /* resplandor de brasas abajo, con parpadeo irregular */
    var fl = .8 + .12 * Math.sin(t * .007) + .08 * Math.sin(t * .0173 + 1.3) + .05 * Math.sin(t * .041);
    var gr = x.createRadialGradient(W / 2, H * 1.08, 0, W / 2, H * 1.08, Math.max(W, H) * .75);
    gr.addColorStop(0, 'rgba(255,110,30,' + (.34 * fl) + ')');
    gr.addColorStop(.35, 'rgba(190,50,15,' + (.16 * fl) + ')');
    gr.addColorStop(1, 'rgba(120,20,5,0)');
    x.fillStyle = gr; x.fillRect(0, 0, W, H);
    /* humo lento */
    for (var k = 0; k < S.length; k++) {
      var s = S[k], px = (s.x + Math.sin(t * s.sp + s.ph) * .08) * W, py = s.y * H - ((t * s.sp * 900 + k * 90) % (H * .6));
      var sg = x.createRadialGradient(px, py, 0, px, py, s.r);
      sg.addColorStop(0, 'rgba(255,225,200,.035)'); sg.addColorStop(1, 'rgba(255,225,200,0)');
      x.fillStyle = sg; x.fillRect(px - s.r, py - s.r, s.r * 2, s.r * 2);
    }
    /* chispas */
    if (!reduce && P.length < 55 && Math.random() < .4) spark();
    for (var i = P.length - 1; i >= 0; i--) {
      var p = P[i]; p.life++;
      if (p.life > p.max) { P.splice(i, 1); continue; }
      p.x += p.vx + Math.sin(p.life * .04 + p.ph) * .3; p.y += p.vy;
      var f = p.life / p.max, a = Math.sin(Math.PI * f) * (.6 + .4 * Math.sin(p.life * .3 + p.ph));
      x.beginPath(); x.fillStyle = 'rgba(255,' + (110 + Math.floor(100 * (1 - f))) + ',40,' + Math.max(a, 0) * .9 + ')';
      x.shadowColor = 'rgba(255,130,40,.9)'; x.shadowBlur = 7; x.arc(p.x, p.y, p.r, 0, 6.283); x.fill();
    }
    x.shadowBlur = 0;
    if (!reduce) requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
})();
