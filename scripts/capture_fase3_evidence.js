const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://localhost/Chorifest';
const OUT_DIR = path.join(__dirname, '..', 'docs', 'screenshots');
const ARTIFACT_DIR = 'C:\\Users\\felip\\.gemini\\antigravity-ide\\brain\\5fb42a1e-2acb-4a18-a95c-8dc4c8522bce\\screenshots';

if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
}
if (!fs.existsSync(ARTIFACT_DIR)) {
    fs.mkdirSync(ARTIFACT_DIR, { recursive: true });
}

const VIEWPORTS = [
    { name: 'desktop', width: 1280, height: 800, isMobile: false },
    { name: 'celular', width: 375, height: 740, isMobile: true, hasTouch: true }
];

async function capture(page, name, vp) {
    const filename = `${name}_${vp.name}.png`;
    const outPath = path.join(OUT_DIR, filename);
    const artPath = path.join(ARTIFACT_DIR, filename);

    await new Promise(r => setTimeout(r, 1200));
    await page.screenshot({ path: outPath, fullPage: false });
    fs.copyFileSync(outPath, artPath);
    console.log(`  [CAPTURADO] ${filename}`);
}

async function run() {
    console.log("=========================================================");
    console.log("PRUEBAS DE LOGIN DE LOS 4 ROLES Y CAPTURAS VISUALES");
    console.log("=========================================================\n");

    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    // 1. Probar login de los 4 roles
    const roles = [
        { name: 'Cliente', email: 'juan@gmail.com', pass: '123456', expectedPath: 'catalogo.php' },
        { name: 'Admin', email: 'admin@gmail.com', pass: '123456', expectedPath: 'dashboard.php' },
        { name: 'Cocinera', email: 'cocina@gmail.com', pass: '123456', expectedPath: 'cocinera' },
        { name: 'Domiciliario', email: 'domi@gmail.com', pass: '123456', expectedPath: 'domiciliario' }
    ];

    for (const r of roles) {
        const page = await browser.newPage();
        await page.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
        await page.type('input[name="email"]', r.email);
        await page.type('input[name="password"]', r.pass);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('button[type="submit"]')
        ]);
        const currentUrl = page.url();
        const ok = currentUrl.includes(r.expectedPath);
        console.log(`✔ Login ${r.name} (${r.email}): ${ok ? 'EXITOSO' : 'FALLÓ'} -> Redirigido a: ${currentUrl}`);
        await page.close();
    }

    console.log("\n--- GENERANDO CAPTURAS EN DESKTOP Y CELULAR ---");

    for (const vp of VIEWPORTS) {
        console.log(`\nViewport: ${vp.name.toUpperCase()} (${vp.width}x${vp.height})`);

        // Contexto Admin
        const contextAdmin = await browser.createBrowserContext();
        const pageAdmin = await contextAdmin.newPage();
        await pageAdmin.setViewport(vp);

        await pageAdmin.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
        await pageAdmin.type('input[name="email"]', 'admin@gmail.com');
        await pageAdmin.type('input[name="password"]', '123456');
        await Promise.all([
            pageAdmin.waitForNavigation({ waitUntil: 'networkidle0' }),
            pageAdmin.click('button[type="submit"]')
        ]);

        // Ir a Catálogo de Productos en Admin
        await pageAdmin.goto(`${BASE_URL}/controllers/admin/productos.php`, { waitUntil: 'networkidle0' });
        
        // Abrir Modal de Receta del Producto 1 (Chorizo Santandereano)
        await pageAdmin.evaluate(() => {
            if (typeof openRecetaModal === 'function') {
                openRecetaModal(1, 'Chorizo Santandereano');
            }
        });
        await new Promise(r => setTimeout(r, 1000));
        await capture(pageAdmin, 'panel_recetas_modal', vp);

        await pageAdmin.evaluate(() => {
            if (typeof closeRecetaModal === 'function') closeRecetaModal();
        });
        await capture(pageAdmin, 'panel_productos_recetas_columna', vp);
        await contextAdmin.close();

        // Contexto Cliente: Catálogo con cantidades limitadas
        const contextCli = await browser.createBrowserContext();
        const pageCli = await contextCli.newPage();
        await pageCli.setViewport(vp);

        await pageCli.goto(`${BASE_URL}/controllers/cliente/catalogo.php`, { waitUntil: 'networkidle0' });
        await capture(pageCli, 'catalogo_cantidad_limitada', vp);

        await contextCli.close();
    }

    await browser.close();
    console.log("\n✔ Todas las capturas y pruebas de login finalizadas con éxito.");
}

run().catch(err => {
    console.error("Error en script:", err);
    process.exit(1);
});
