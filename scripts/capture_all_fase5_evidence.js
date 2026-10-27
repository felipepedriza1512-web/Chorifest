const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://localhost/Chorifest';
const OUT_DIR = path.join(__dirname, '..', 'docs', 'screenshots');
const ARTIFACT_DIR = 'C:\\Users\\felip\\.gemini\\antigravity-ide\\brain\\b083ef4a-b6bc-488b-86c2-daa6812cebf5';

if (!fs.existsSync(OUT_DIR)) fs.mkdirSync(OUT_DIR, { recursive: true });
if (!fs.existsSync(ARTIFACT_DIR)) fs.mkdirSync(ARTIFACT_DIR, { recursive: true });

async function saveScreenshot(page, filename) {
    const localPath = path.join(OUT_DIR, filename);
    const artifactPath = path.join(ARTIFACT_DIR, filename);
    await page.screenshot({ path: localPath, fullPage: true });
    fs.copyFileSync(localPath, artifactPath);
    console.log(`[OK] Captura guardada: ${filename}`);
}

async function run() {
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    console.log('--- INICIANDO CAPTURA DE PANELES Y REPORTES (DESKTOP & MOBILE) ---');

    // 1. Formulario de Login
    const pageLogin = await browser.newPage();
    await pageLogin.setViewport({ width: 1440, height: 900 });
    await pageLogin.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 600));
    await saveScreenshot(pageLogin, 'login_formulario_real.png');
    await pageLogin.close();

    // 2. Login y Capturas de los 4 Roles (Paneles)
    const roles = [
        { name: 'Admin', email: 'admin@gmail.com', pass: '123456', path: '/controllers/admin/dashboard.php', file: 'panel_admin_desktop.png' },
        { name: 'Cocinera', email: 'cocina@gmail.com', pass: '123456', path: '/controllers/cocinera/pedidos.php', file: 'panel_cocinera_desktop.png' },
        { name: 'Domiciliario', email: 'domi@gmail.com', pass: '123456', path: '/controllers/domiciliario/rutas.php', file: 'panel_domiciliario_desktop.png' },
        { name: 'Cliente', email: 'juan@gmail.com', pass: '123456', path: '/controllers/cliente/catalogo.php', file: 'panel_cliente_desktop.png' }
    ];

    for (const r of roles) {
        console.log(`Iniciando sesión rol: ${r.name}...`);
        const p = await browser.newPage();
        await p.setViewport({ width: 1440, height: 900 });
        await p.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
        await p.type('input[name="email"]', r.email);
        await p.type('input[name="password"]', r.pass);
        await Promise.all([
            p.waitForNavigation({ waitUntil: 'networkidle0' }),
            p.click('button[type="submit"]')
        ]);
        await new Promise(res => setTimeout(res, 800));
        await saveScreenshot(p, r.file);

        // Si es domiciliario, capturar también su panel de liquidaciones
        if (r.name === 'Domiciliario') {
            await p.goto(`${BASE_URL}/controllers/domiciliario/liquidaciones.php`, { waitUntil: 'networkidle0' });
            await new Promise(res => setTimeout(res, 800));
            await saveScreenshot(p, 'domiciliario_liquidaciones_desktop.png');

            // Versión mobile de liquidaciones del domiciliario
            await p.setViewport({ width: 390, height: 844, isMobile: true });
            await new Promise(res => setTimeout(res, 600));
            await saveScreenshot(p, 'domiciliario_liquidaciones_mobile.png');
        }

        await p.close();
    }

    // 3. Capturas de Reportes Administrativos (Desktop: 1440x950)
    console.log('--- CAPTURANDO REPORTES EN COMPUTADOR (DESKTOP) ---');
    const pageAdmin = await browser.newPage();
    await pageAdmin.setViewport({ width: 1440, height: 950 });
    await pageAdmin.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
    await pageAdmin.type('input[name="email"]', 'admin@gmail.com');
    await pageAdmin.type('input[name="password"]', '123456');
    await Promise.all([
        pageAdmin.waitForNavigation({ waitUntil: 'networkidle0' }),
        pageAdmin.click('button[type="submit"]')
    ]);

    const reportTabs = [
        { tab: 'ventas', file: 'reporte_ventas_desktop.png' },
        { tab: 'categorias', file: 'reporte_categorias_desktop.png' },
        { tab: 'productos', file: 'reporte_productos_desktop.png' },
        { tab: 'calificaciones', file: 'reporte_calificaciones_desktop.png' },
        { tab: 'domiciliarios', file: 'reporte_domiciliarios_desktop.png' },
        { tab: 'clientes', file: 'reporte_clientes_desktop.png' }
    ];

    for (const t of reportTabs) {
        console.log(`Captura Desktop: ${t.tab}...`);
        await pageAdmin.goto(`${BASE_URL}/controllers/admin/reportes.php?tab=${t.tab}`, { waitUntil: 'networkidle0' });
        await new Promise(r => setTimeout(r, 1200));
        await saveScreenshot(pageAdmin, t.file);
    }

    // 4. Capturas de Reportes Administrativos (Celular / Mobile: 390x844)
    console.log('--- CAPTURANDO REPORTES EN CELULAR (MOBILE) ---');
    await pageAdmin.setViewport({ width: 390, height: 844, isMobile: true });

    const mobileTabs = [
        { tab: 'ventas', file: 'reporte_ventas_celular.png' },
        { tab: 'categorias', file: 'reporte_categorias_celular.png' },
        { tab: 'calificaciones', file: 'reporte_calificaciones_celular.png' },
        { tab: 'domiciliarios', file: 'reporte_domiciliarios_celular.png' },
        { tab: 'clientes', file: 'reporte_clientes_celular.png' }
    ];

    for (const mt of mobileTabs) {
        console.log(`Captura Celular: ${mt.tab}...`);
        await pageAdmin.goto(`${BASE_URL}/controllers/admin/reportes.php?tab=${mt.tab}`, { waitUntil: 'networkidle0' });
        await new Promise(r => setTimeout(r, 1200));
        await saveScreenshot(pageAdmin, mt.file);
    }

    await pageAdmin.close();
    await browser.close();
    console.log('--- TODAS LAS CAPTURAS COMPLETADAS EXITOSAMENTE ---');
}

run().catch(e => {
    console.error('Error durante la captura:', e);
    process.exit(1);
});
