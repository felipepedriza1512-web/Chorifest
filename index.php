<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/ProductoModel.php';
require_once __DIR__ . '/controllers/auth/AuthController.php';
require_once __DIR__ . '/controllers/auth/AuthMiddleware.php';

enforceSecureSession();

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$productoModel = new ProductoModel($db);
$authController = new AuthController($userModel);

$productos = $productoModel->getProductos();
$categorias = $productoModel->getCategorias();

$message = null;
$isSuccess = true;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $result = $authController->handleRegister($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $result = $authController->handleLogin($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];

    if ($result['success']) {
        $rol = (int)($result['user']['rol'] ?? 1);
        header('Location: ' . getRoleRedirectUrl($rol));
        exit;
    }
}

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $message = "Sesión cerrada de forma segura.";
    $isSuccess = true;
}

$currentUser = $_SESSION['user'] ?? null;
$currentRole = isset($currentUser['rol']) ? (int)$currentUser['rol'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <title>Chorifest - Gourmet Grill System</title>
    <meta name="description" content="Chorifest - Premium Gourmet Grill System. Authentic flavors.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,800&family=Nunito:wght@600;700&family=Inter:wght@400;500;600&family=Lora:wght@500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/styles.css?v=<?= time() ?>">
    <style>
    :root {
      --intro-bg: #0a0303;
      --intro-ink: #F3ECDA;
      --intro-soft: #d2b8a2;
      --intro-accent: #A8391F;
      --intro-accent-hover: #C2452A;
      --intro-ember: #ff8a2a;
      --intro-serif: 'Fraunces', Georgia, 'Times New Roman', serif;
      --intro-sans: 'Nunito', system-ui, -apple-system, 'Segoe UI', sans-serif;
    }
    body.intro-active {
      overflow: hidden !important;
    }
    #intro-container {
      position: fixed;
      inset: 0;
      width: 100vw;
      height: 100vh;
      z-index: 99999;
      background: var(--intro-bg);
      color: var(--intro-ink);
      font-family: var(--intro-sans);
      overflow: hidden;
      box-sizing: border-box;
      padding-top: env(safe-area-inset-top, 0px);
      padding-bottom: env(safe-area-inset-bottom, 0px);
      transition: opacity 0.5s ease;
    }
    #intro-container #fx,
    #intro-container #em,
    #intro-container #bg {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
    }
    #intro-container #fx { z-index: 0; }
    #intro-container #bg { z-index: 1; object-fit: cover; opacity: 0; transition: opacity 1s; }
    #intro-container #em { z-index: 2; pointer-events: none; }
    /* grano y viñeta */
    #intro-container::before {
      content: "";
      position: absolute;
      inset: 0;
      pointer-events: none;
      z-index: 5;
      opacity: .1;
      mix-blend-mode: overlay;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    }
    #intro-container::after {
      content: "";
      position: absolute;
      inset: 0;
      pointer-events: none;
      z-index: 4;
      background: radial-gradient(ellipse at 50% 60%, transparent 40%, rgba(0,0,0,.7) 100%);
    }
    #intro-container .screen {
      position: absolute;
      inset: 0;
      z-index: 3;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 24px 24px 30vh;
      text-align: center;
    }
    #intro-container .screen[hidden] { display: none; }
    #intro-container h1 {
      margin: 0;
      font-family: var(--intro-serif);
      font-weight: 800;
      font-size: clamp(54px, 15vw, 120px);
      line-height: 1;
      letter-spacing: -.02em;
      color: var(--intro-ink);
      text-shadow: 0 0 60px rgba(255,120,30,.35), 0 3px 0 rgba(0,0,0,.45);
    }
    #intro-container h1 span {
      display: inline-block;
      opacity: 0;
      filter: blur(10px);
      animation: introLetter 1s calc(.9s + var(--i)*.07s) ease-out forwards;
    }
    @keyframes introLetter { to { opacity: 1; filter: none; } }
    #intro-container .sub {
      margin: 12px 0 0;
      font-family: var(--intro-serif);
      font-weight: 600;
      font-size: clamp(18px, 4.6vw, 26px);
      color: var(--intro-soft);
      opacity: 0;
      animation: introShow .9s 2s forwards;
    }
    #intro-container .cta {
      margin-top: 30px;
      font: 700 17px/1 var(--intro-sans);
      color: var(--intro-ink);
      background: var(--intro-accent);
      border: 0;
      border-radius: 999px;
      padding: 16px 38px;
      cursor: pointer;
      box-shadow: 0 8px 30px rgba(168,57,31,.45);
      opacity: 0;
      animation: introShow .7s 2.6s forwards;
      transition: background .15s, transform .15s;
    }
    #intro-container .cta:hover {
      background: var(--intro-accent-hover);
      transform: translateY(-2px);
    }
    #intro-container .cta:focus-visible,
    #intro-container .skip:focus-visible {
      outline: 3px solid var(--intro-ember);
      outline-offset: 3px;
    }
    #intro-container .skip {
      position: absolute;
      top: calc(env(safe-area-inset-top, 0px) + 14px);
      right: 16px;
      background: none;
      border: 0;
      color: var(--intro-soft);
      font: 600 15px var(--intro-sans);
      cursor: pointer;
      padding: 8px 10px;
      opacity: .75;
    }
    @keyframes introShow { to { opacity: 1; } }
    #intro-container .fade {
      position: absolute;
      inset: 0;
      background: var(--intro-bg);
      opacity: 0;
      pointer-events: none;
      z-index: 6;
    }
    @media (prefers-reduced-motion: reduce) {
      #intro-container *, #intro-container *::before, #intro-container *::after {
        animation-duration: .01ms !important;
        animation-delay: 0s !important;
      }
    }
    </style>
</head>
<body class="intro-active">

    <!-- ===== INTRO BIENVENIDA ===== -->
    <div id="intro-container">
        <canvas id="fx" aria-hidden="true"></canvas>
        <video id="bg" muted loop playsinline preload="auto" aria-hidden="true"></video>
        <canvas id="em" aria-hidden="true"></canvas>

        <main id="intro" class="screen">
            <button class="skip" id="skip" type="button">Saltar</button>
            <h1 aria-label="Chorifest"><span aria-hidden="true" style="--i:0">C</span><span aria-hidden="true" style="--i:1">h</span><span aria-hidden="true" style="--i:2">o</span><span aria-hidden="true" style="--i:3">r</span><span aria-hidden="true" style="--i:4">i</span><span aria-hidden="true" style="--i:5">f</span><span aria-hidden="true" style="--i:6">e</span><span aria-hidden="true" style="--i:7">s</span><span aria-hidden="true" style="--i:8">t</span></h1>
            <p class="sub">Gourmet Grill System</p>
            <button class="cta" id="enter" type="button">Ver el menú</button>
        </main>
        <div class="fade" id="fade"></div>
    </div>

    <!-- ===== NAVBAR ===== -->
    <header class="navbar">
        <a href="index.php" class="brand">
            <div class="brand-logo-container">
                <div class="brand-logo-circle">
                    <span class="brand-logo-text">CHORIFEST</span>
                </div>
            </div>
            <div class="brand-text">
                <span class="brand-name">CHORIFEST</span>
                <span class="brand-sub">GOURMET GRILL SYSTEM</span>
            </div>
        </a>
        <nav class="nav-links">
            <a href="#menu">Menú</a>
            <a href="#combos">Combos</a>
            <?php if ($currentUser): ?>
                <div class="user-chip">
                    <span class="user-avatar"><?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?></span>
                    <span class="nav-user"><?= htmlspecialchars($currentUser['name'] ?? 'Usuario') ?></span>
                </div>
                <?php if ($currentRole === 2): ?>
                    <a href="<?= baseUrl('/controllers/admin/dashboard.php') ?>" class="btn btn-primary btn-sm">Ir al Panel Admin</a>
                <?php elseif ($currentRole === 3): ?>
                    <a href="<?= baseUrl('/controllers/cocinera/pedidos.php') ?>" class="btn btn-primary btn-sm">Ir al Panel Cocina</a>
                <?php elseif ($currentRole === 4): ?>
                    <a href="<?= baseUrl('/controllers/domiciliario/rutas.php') ?>" class="btn btn-primary btn-sm">Ir al Panel Rutas</a>
                <?php else: ?>
                    <a href="<?= baseUrl('/controllers/cliente/catalogo.php') ?>" class="btn btn-primary btn-sm">Ir a Tienda</a>
                <?php endif; ?>
                <a href="<?= baseUrl('/controllers/auth/logout.php') ?>" class="btn btn-outline btn-sm">Cerrar sesión</a>
            <?php else: ?>
                <a href="<?= baseUrl('/controllers/auth/login.php') ?>" class="btn btn-primary btn-sm">Iniciar sesión</a>
                <a href="<?= baseUrl('/controllers/auth/register.php') ?>" class="btn btn-primary btn-sm">Registrarse</a>
            <?php endif; ?>
        </nav>
        <button class="hamburger" id="hamburger" aria-label="Menú">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
        </button>
    </header>

    <!-- ===== ALERT ===== -->
    <?php if (isset($message) && $message !== null): ?>
        <div class="alert-container">
            <div class="alert <?= $isSuccess ? 'alert-success' : 'alert-error' ?>">
                <span class="alert-icon"><?= $isSuccess ? '✓' : '⚠' ?></span>
                <div class="alert-content">
                    <span class="alert-msg"><?= htmlspecialchars($message) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===== HERO ===== -->
    <section class="hero">
        <div class="hero-bg">
            <video autoplay loop muted playsinline class="hero-video" poster="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80">
                <source src="https://cdn.pixabay.com/video/2023/10/22/185960-876722238_large.mp4" type="video/mp4">
            </video>
            <div class="hero-overlay"></div>
        </div>
        <div class="hero-content">
            <h1>Tradición a la brasa</h1>
            <p class="hero-desc">Descubre el auténtico sabor de nuestros chorizos artesanales y cortes premium. Una experiencia gourmet inigualable en cada bocado.</p>
            
            <div class="hero-actions">
                <a href="#menu" class="btn btn-primary btn-lg">Ver Menú</a>
            </div>
        </div>
    </section>

    <!-- ===== CATEGORÍAS ===== -->
    <section class="menu-section" id="menu">
        <div class="category-tabs">
            <button class="cat-tab active" data-cat="all">Todos</button>
            <?php foreach ($categorias as $cat): ?>
                <button class="cat-tab" data-cat="cat-<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></button>
            <?php endforeach; ?>
        </div>

        <!-- ===== GRID DE PRODUCTOS ===== -->
        <div class="products-grid">
            <?php foreach ($productos as $prod): ?>
                <div class="product-card-wrapper" data-cat="cat-<?= $prod['id_categoria'] ?>">
                    <article class="product-card <?= !$prod['disponible'] ? 'unavailable' : '' ?>" data-cat="cat-<?= $prod['id_categoria'] ?>">
                        <div class="card-reflection"></div>
                        <?php 
                            $imgUrl = $productoModel->getImagenUrl($prod['nombre_producto'], $prod['id_producto'], 600);
                        ?>
                        <div class="product-img <?= !$prod['disponible'] ? 'grayscale' : '' ?>">
                            <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($prod['nombre_producto']) ?>">
                            <?php if (!$prod['disponible']): ?>
                                <div class="overlay-badge" style="position: absolute; top: 15px; right: 15px; background: rgba(239, 68, 68, 0.8); color: white; padding: 6px 14px; border-radius: 12px; font-weight: 800; font-size: 0.85rem; border: 1px solid rgba(239, 68, 68, 1); backdrop-filter: blur(5px); z-index: 10;">NO DISPONIBLE</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3><?= htmlspecialchars($prod['nombre_producto']) ?></h3>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px; margin-bottom: 15px; line-height: 1.4; flex: 1;"><?= htmlspecialchars($prod['descripcion'] ?? 'Delicioso producto a la parrilla preparado al instante.') ?></p>
                            <div class="product-footer">
                                <span class="product-price">$<?= number_format($prod['precio'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="brand-logo-container mini">
                    <div class="brand-logo-circle">
                        <span class="brand-logo-text">CF</span>
                    </div>
                </div>
                <div>
                    <span class="brand-name">CHORIFEST</span>
                    <p>Gourmet Grill System</p>
                </div>
            </div>
            <div class="footer-links">
                <a href="#">Términos y Condiciones</a>
                <a href="#">Política de Privacidad</a>
                <a href="#">Contacto</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Chorifest. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- ===== INTRO SCRIPT ===== -->
    <script>
    (function(){
      var reduce=matchMedia('(prefers-reduced-motion: reduce)').matches;

      /* ---- paso al menú ---- */
      var introContainer=document.getElementById('intro-container'),
          fade=document.getElementById('fade'),
          busy=false;

      function swap(toMenu){
        if(busy)return;busy=true;var t=reduce?1:420;
        if(fade.animate) {
          fade.animate([{opacity:0},{opacity:1}],{duration:t,fill:'forwards'}).finished.then(function(){
            if(toMenu) {
              introContainer.style.display='none';
              document.body.classList.remove('intro-active');
            }
            return fade.animate([{opacity:1},{opacity:0}],{duration:t,fill:'forwards'}).finished;
          }).then(function(){busy=false;});
        } else {
          introContainer.style.display='none';
          document.body.classList.remove('intro-active');
          busy=false;
        }
      }

      var enterBtn = document.getElementById('enter');
      var skipBtn = document.getElementById('skip');
      if(enterBtn) enterBtn.onclick=function(){ swap(true); };
      if(skipBtn) skipBtn.onclick=function(){ swap(true); };

      /* ---- video real opcional: si existe fondo.mp4 junto a este archivo, se usa ---- */
      var v=document.getElementById('bg');
      if(v) {
        v.addEventListener('canplay',function(){v.style.opacity=.9;v.play().catch(function(){})});
        v.src='fondo.mp4';
      }

      /* ---- fuego (shader) ---- */
      var fx=document.getElementById('fx');
      if(fx) {
        var gl=fx.getContext('webgl',{antialias:false});
        var T0=performance.now();
        if(gl){
          var vs='attribute vec2 a;void main(){gl_Position=vec4(a,0.,1.);}';
          var fs='precision highp float;uniform vec2 r;uniform float t;'+
          'float h(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453);}'+
          'float n(vec2 p){vec2 i=floor(p),f=fract(p);f=f*f*(3.-2.*f);return mix(mix(h(i),h(i+vec2(1,0)),f.x),mix(h(i+vec2(0,1)),h(i+vec2(1,1)),f.x),f.y);}'+
          'float fbm(vec2 p){float a=.5,s=0.;for(int i=0;i<5;i++){s+=a*n(p);p=p*2.03+vec2(1.7,9.2);a*=.5;}return s;}'+
          'void main(){vec2 uv=gl_FragCoord.xy/r;float asp=r.x/r.y;vec2 p=vec2(uv.x*asp,uv.y);float T=t;'+
          'vec2 q=vec2(p.x*3.4,p.y*2.5-T*.8);'+
          'float w1=fbm(q);float w2=fbm(q*1.8+vec2(w1*1.8,-T*.45));'+
          'float shape=fbm(vec2(q.x+w2*.9,q.y+w1*.6));'+
          'float edge=1.-smoothstep(.5,1.,abs(uv.x-.5)*2.);'+
          'float hg=1.-smoothstep(.02,.5,uv.y);'+
          'float f=(shape*1.7-.34)*hg*1.55+hg*hg*.12-.1;f*=mix(.45,1.,edge);f=clamp(f,0.,1.);'+
          'vec3 col=vec3(.03,.008,.008)+vec3(.13,.03,.015)*pow(1.-uv.y,2.);'+
          'col+=vec3(1.,.1,.02)*smoothstep(.05,.45,f)*.9;'+
          'col=mix(col,vec3(1.,.45,.07),smoothstep(.3,.8,f));'+
          'col=mix(col,vec3(1.,.8,.4),smoothstep(.86,1.,f)*.55);'+
          'float s=fbm(vec2(p.x*1.3,p.y-T*.1)+w1);col+=vec3(.45,.3,.22)*s*.08*smoothstep(.15,.5,uv.y)*(1.-smoothstep(.6,1.,uv.y));'+
          'float b1=smoothstep(.008,.003,abs(uv.y-.2)),b2=smoothstep(.006,.002,abs(uv.y-.14));col*=1.-.93*max(b1,b2);'+
          'col*=.93+.07*sin(T*11.+sin(T*3.7)*3.);gl_FragColor=vec4(col,1.);}';
          function sh(type,src){var s=gl.createShader(type);gl.shaderSource(s,src);gl.compileShader(s);return s}
          var pr=gl.createProgram();gl.attachShader(pr,sh(gl.VERTEX_SHADER,vs));gl.attachShader(pr,sh(gl.FRAGMENT_SHADER,fs));gl.linkProgram(pr);gl.useProgram(pr);
          var b=gl.createBuffer();gl.bindBuffer(gl.ARRAY_BUFFER,b);gl.bufferData(gl.ARRAY_BUFFER,new Float32Array([-1,-1,1,-1,-1,1,1,1]),gl.STATIC_DRAW);
          var a=gl.getAttribLocation(pr,'a');gl.enableVertexAttribArray(a);gl.vertexAttribPointer(a,2,gl.FLOAT,false,0,0);
          var ur=gl.getUniformLocation(pr,'r'),ut=gl.getUniformLocation(pr,'t');
          function rs(){var k=.6;fx.width=Math.round(innerWidth*k);fx.height=Math.round(innerHeight*k);gl.viewport(0,0,fx.width,fx.height)}
          rs();addEventListener('resize',rs);
          (function draw(now){
            if(introContainer.style.display==='none')return;
            gl.uniform2f(ur,fx.width,fx.height);gl.uniform1f(ut,reduce?3:(now-T0)/1000);
            gl.drawArrays(gl.TRIANGLE_STRIP,0,4);
            if(!reduce)requestAnimationFrame(draw);
          })(T0);
        }
      }

      /* ---- brasas ---- */
      if(reduce)return;
      var c=document.getElementById('em');
      if(!c)return;
      var x=c.getContext('2d'),W,H,P=[],dpr=Math.min(devicePixelRatio||1,2);
      function size(){W=innerWidth;H=innerHeight;c.width=W*dpr;c.height=H*dpr;x.setTransform(dpr,0,0,dpr,0,0)}
      size();addEventListener('resize',size);
      function spawn(){P.push({x:W*(.1+Math.random()*.8),y:H*(.86+Math.random()*.1),vx:(Math.random()-.5)*.4,vy:-(.6+Math.random()*1.4),
        r:.7+Math.random()*2,life:0,max:120+Math.random()*180,ph:Math.random()*6.28})}
      (function loop(){
        if(introContainer.style.display==='none')return;
        x.clearRect(0,0,W,H);
        if(P.length<90&&Math.random()<.6)spawn();
        for(var i=P.length-1;i>=0;i--){var p=P[i];p.life++;
          if(p.life>p.max){P.splice(i,1);continue}
          p.x+=p.vx+Math.sin(p.life*.045+p.ph)*.35;p.y+=p.vy;
          var k=p.life/p.max,a=Math.sin(Math.PI*k)*(.65+.35*Math.sin(p.life*.35+p.ph));
          x.beginPath();x.fillStyle='rgba(255,'+(110+Math.floor(100*(1-k)))+',40,'+Math.max(a,0)+')';
          x.shadowColor='rgba(255,130,40,.95)';x.shadowBlur=8;x.arc(p.x,p.y,p.r,0,6.283);x.fill();}
        requestAnimationFrame(loop);
      })();
    })();
    </script>

    <!-- ===== EXISTING SITE SCRIPTS ===== -->
    <script>
        // Smooth Category Filtering
        const tabs = document.querySelectorAll('.cat-tab');
        const cards = document.querySelectorAll('.product-card-wrapper');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const cat = tab.dataset.cat;
                
                cards.forEach(card => {
                    if (cat === 'all' || card.dataset.cat === cat) {
                        card.style.display = 'block';
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'scale(1)';
                        }, 10);
                    } else {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            card.style.display = 'none';
                        }, 300);
                    }
                });
            });
        });

        // Add button interaction
        document.querySelectorAll('.btn-add-circle:not(.disabled)').forEach(btn => {
            btn.addEventListener('click', function () {
                const originalContent = this.innerHTML;
                this.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                this.classList.add('success');
                
                setTimeout(() => {
                    this.innerHTML = originalContent;
                    this.classList.remove('success');
                }, 1500);
            });
        });

        // Hamburger Menu
        document.getElementById('hamburger').addEventListener('click', () => {
            document.querySelector('.nav-links').classList.toggle('open');
        });

        // 3D Hover Effect for Cards
        document.querySelectorAll('.product-card-wrapper').forEach(wrapper => {
            const card = wrapper.querySelector('.product-card');
            const reflection = wrapper.querySelector('.card-reflection');
            
            wrapper.addEventListener('mousemove', (e) => {
                const rect = wrapper.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = ((y - centerY) / centerY) * -15; 
                const rotateY = ((x - centerX) / centerX) * 15;
                
                card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(20px)`;
                
                if(reflection) {
                    const bgX = (x / rect.width) * 100;
                    const bgY = (y / rect.height) * 100;
                    reflection.style.background = `radial-gradient(circle at ${bgX}% ${bgY}%, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 60%)`;
                    reflection.style.opacity = '1';
                }
            });
            
            wrapper.addEventListener('mouseleave', () => {
                card.style.transition = 'transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                card.style.transform = `rotateX(0deg) rotateY(0deg) translateZ(0)`;
                if(reflection) {
                    reflection.style.opacity = '0';
                    reflection.style.background = 'none';
                }
                
                setTimeout(() => {
                    card.style.transition = 'transform 0.2s, box-shadow 0.2s'; 
                }, 500);
            });
            
            wrapper.addEventListener('mouseenter', () => {
                card.style.transition = 'transform 0.1s ease-out'; 
            });
        });
    </script>
    <script src="<?= function_exists('baseUrl') ? baseUrl('/chorifest-fondo.js') : 'chorifest-fondo.js' ?>"></script>
</body>
</html>
