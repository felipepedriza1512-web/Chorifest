const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://localhost/Chorifest';
const OUT_DIR = path.join(__dirname, '..', 'docs', 'screenshots');
const ARTIFACT_DIR = 'C:\\Users\\felip\\.gemini\\antigravity-ide\\brain\\0779d973-0cb0-46e4-a563-88afb36cd54e';

if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
}
if (!fs.existsSync(ARTIFACT_DIR)) {
    fs.mkdirSync(ARTIFACT_DIR, { recursive: true });
}

async function run() {
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    console.log('Navegador Chrome iniciado.');

    // Iniciar sesión como Admin
    const pageAdmin = await browser.newPage();
    await pageAdmin.setViewport({ width: 1440, height: 900 });
    await pageAdmin.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
    await pageAdmin.type('input[name="email"]', 'admin@gmail.com');
    await pageAdmin.type('input[name="password"]', '123456');
    await Promise.all([
        pageAdmin.waitForNavigation({ waitUntil: 'networkidle0' }),
        pageAdmin.click('button[type="submit"]')
    ]);

    // 1. ADMIN ZONAS - ESCRITORIO
    console.log('Capturando Admin Zonas (Desktop)...');
    await pageAdmin.goto(`${BASE_URL}/controllers/admin/zonas.php`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1500));
    const zonDesk = path.join(OUT_DIR, 'fase4_admin_zonas_desktop.png');
    await pageAdmin.screenshot({ path: zonDesk, fullPage: true });
    fs.copyFileSync(zonDesk, path.join(ARTIFACT_DIR, 'fase4_admin_zonas_desktop.png'));
    console.log(`[GUARDADO] ${zonDesk}`);

    // 2. ADMIN ZONAS - MÓVIL
    console.log('Capturando Admin Zonas (Mobile)...');
    await pageAdmin.setViewport({ width: 390, height: 844, isMobile: true });
    await pageAdmin.goto(`${BASE_URL}/controllers/admin/zonas.php`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1500));
    const zonMob = path.join(OUT_DIR, 'fase4_admin_zonas_mobile.png');
    await pageAdmin.screenshot({ path: zonMob, fullPage: true });
    fs.copyFileSync(zonMob, path.join(ARTIFACT_DIR, 'fase4_admin_zonas_mobile.png'));
    console.log(`[GUARDADO] ${zonMob}`);

    // 3. ADMIN PERSONAL / EMPLEADOS - ESCRITORIO
    console.log('Capturando Admin Personal (Desktop)...');
    await pageAdmin.setViewport({ width: 1440, height: 900, isMobile: false });
    await pageAdmin.goto(`${BASE_URL}/controllers/admin/empleados.php`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1000));
    const empDesk = path.join(OUT_DIR, 'fase4_admin_empleados_desktop.png');
    await pageAdmin.screenshot({ path: empDesk, fullPage: true });
    fs.copyFileSync(empDesk, path.join(ARTIFACT_DIR, 'fase4_admin_empleados_desktop.png'));
    console.log(`[GUARDADO] ${empDesk}`);

    // 4. ADMIN PERSONAL / EMPLEADOS - MÓVIL
    console.log('Capturando Admin Personal (Mobile)...');
    await pageAdmin.setViewport({ width: 390, height: 844, isMobile: true });
    await pageAdmin.goto(`${BASE_URL}/controllers/admin/empleados.php`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1000));
    const empMob = path.join(OUT_DIR, 'fase4_admin_empleados_mobile.png');
    await pageAdmin.screenshot({ path: empMob, fullPage: true });
    fs.copyFileSync(empMob, path.join(ARTIFACT_DIR, 'fase4_admin_empleados_mobile.png'));
    console.log(`[GUARDADO] ${empMob}`);

    // 5. CLIENTE CHECKOUT CON PIN DRAGGABLE - ESCRITORIO
    console.log('Capturando Cliente Checkout (Desktop)...');
    const pageCli = await browser.newPage();
    await pageCli.setViewport({ width: 1440, height: 900 });
    await pageCli.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
    await pageCli.type('input[name="email"]', 'juan@gmail.com');
    await pageCli.type('input[name="password"]', '123456');
    await Promise.all([
        pageCli.waitForNavigation({ waitUntil: 'networkidle0' }),
        pageCli.click('button[type="submit"]')
    ]);

    // Agregar un producto al carrito
    await pageCli.goto(`${BASE_URL}/controllers/cliente/catalogo.php`, { waitUntil: 'networkidle0' });
    await pageCli.evaluate(() => {
        // Simular carrito en sessionStorage/localStorage si aplica o agregar vía botón
        const btn = document.querySelector('button[onclick*="agregarAlCarrito"]');
        if (btn) btn.click();
    });
    await new Promise(r => setTimeout(r, 800));

    await pageCli.goto(`${BASE_URL}/controllers/cliente/checkout.php`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1500));
    const chkDesk = path.join(OUT_DIR, 'fase4_cliente_checkout_desktop.png');
    await pageCli.screenshot({ path: chkDesk, fullPage: true });
    fs.copyFileSync(chkDesk, path.join(ARTIFACT_DIR, 'fase4_cliente_checkout_desktop.png'));
    console.log(`[GUARDADO] ${chkDesk}`);

    // 6. CLIENTE CHECKOUT - MÓVIL
    console.log('Capturando Cliente Checkout (Mobile)...');
    await pageCli.setViewport({ width: 390, height: 844, isMobile: true });
    await pageCli.goto(`${BASE_URL}/controllers/cliente/checkout.php`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1500));
    const chkMob = path.join(OUT_DIR, 'fase4_cliente_checkout_mobile.png');
    await pageCli.screenshot({ path: chkMob, fullPage: true });
    fs.copyFileSync(chkMob, path.join(ARTIFACT_DIR, 'fase4_cliente_checkout_mobile.png'));
    console.log(`[GUARDADO] ${chkMob}`);

    await browser.close();
    console.log('Capturas de evidencia de la Fase 4 finalizadas exitosamente.');
}

run().catch(err => {
    console.error('Error durante la captura:', err);
    process.exit(1);
});
