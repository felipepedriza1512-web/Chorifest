const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://chorifest.test';
const OUT_DIR = path.join(__dirname, '..', 'docs', 'screenshots');
const ARTIFACT_DIR = 'C:\\Users\\felip\\.gemini\\antigravity-ide\\brain\\14affb33-e8c2-4a61-b01e-d2a9ef7593fa\\screenshots';

if (!fs.existsSync(OUT_DIR)) {
    fs.mkdirSync(OUT_DIR, { recursive: true });
}
if (!fs.existsSync(ARTIFACT_DIR)) {
    fs.mkdirSync(ARTIFACT_DIR, { recursive: true });
}

const VIEWPORTS = [
    { name: 'desktop', width: 1280, height: 800, isMobile: false },
    { name: 'celular', width: 375, height: 667, isMobile: true, hasTouch: true }
];

async function login(page, email, password) {
    await page.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
    await page.type('input[name="email"]', email);
    await page.type('input[name="password"]', password);
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);
}

async function capture(page, name, vp) {
    const filename = `${name}_${vp.name}.png`;
    const outPath = path.join(OUT_DIR, filename);
    const artPath = path.join(ARTIFACT_DIR, filename);

    // Give Leaflet / fonts / charts 1.5s to render
    await new Promise(r => setTimeout(r, 1500));

    await page.screenshot({ path: outPath, fullPage: false });
    fs.copyFileSync(outPath, artPath);
    console.log(`[SAVED] ${filename}`);
}

async function run() {
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    console.log('Browser launched successfully.');

    for (const vp of VIEWPORTS) {
        console.log(`\n================== CAPTURING ${vp.name.toUpperCase()} ==================`);
        const context = await browser.createBrowserContext();
        const page = await context.newPage();
        await page.setViewport(vp);

        // 1. ADMIN SESSION
        console.log(`Logging in as Admin for ${vp.name}...`);
        await login(page, 'admin@chorifest.com', 'admin123');

        // Dashboard
        await page.goto(`${BASE_URL}/controllers/admin/dashboard.php`, { waitUntil: 'networkidle0' });
        await capture(page, '06_dashboard', vp);

        // Tabla Pedidos
        await page.goto(`${BASE_URL}/controllers/admin/pedidos.php`, { waitUntil: 'networkidle0' });
        await capture(page, '02_tabla_pedidos', vp);

        // Modal Cancelar (Open)
        await page.evaluate(() => {
            if (typeof rechazarPedido === 'function') {
                rechazarPedido(47);
            } else {
                const m = document.getElementById('modalCancelarPedido');
                if (m) m.style.display = 'flex';
            }
        });
        await capture(page, '04_modal_cancelar', vp);
        await page.evaluate(() => {
            if (typeof cerrarModalCancelar === 'function') cerrarModalCancelar();
        });

        // Detalle de Pedido (Seguimiento mapa, stepper e historial)
        await page.goto(`${BASE_URL}/controllers/cliente/seguimiento.php?id=47`, { waitUntil: 'networkidle0' });
        await capture(page, '03_detalle_pedido', vp);

        // Productos
        await page.goto(`${BASE_URL}/controllers/admin/productos.php`, { waitUntil: 'networkidle0' });
        await capture(page, '07_productos', vp);

        // Ingredientes
        await page.goto(`${BASE_URL}/controllers/admin/ingredientes.php`, { waitUntil: 'networkidle0' });
        await capture(page, '08_ingredientes', vp);

        // Zonas
        await page.goto(`${BASE_URL}/controllers/admin/zonas.php`, { waitUntil: 'networkidle0' });
        await capture(page, '09_zonas', vp);

        // Empleados
        await page.goto(`${BASE_URL}/controllers/admin/empleados.php`, { waitUntil: 'networkidle0' });
        await capture(page, '10_empleados', vp);

        await context.close();

        // 2. COCINERA SESSION (Tablero)
        const cocineraContext = await browser.createBrowserContext();
        const cocineraPage = await cocineraContext.newPage();
        await cocineraPage.setViewport(vp);
        console.log(`Logging in as Cocinera for ${vp.name}...`);
        await login(cocineraPage, 'cocina@chorifest.com', 'cocina123');
        await cocineraPage.goto(`${BASE_URL}/controllers/cocinera/pedidos.php`, { waitUntil: 'networkidle0' });
        await capture(cocineraPage, '01_tablero_cocina', vp);
        await cocineraContext.close();

        // 3. DOMICILIARIO SESSION (Modal Novedad)
        const domiContext = await browser.createBrowserContext();
        const domiPage = await domiContext.newPage();
        await domiPage.setViewport(vp);
        console.log(`Logging in as Domiciliario for ${vp.name}...`);
        await login(domiPage, 'domi@chorifest.com', 'domi123');
        await domiPage.goto(`${BASE_URL}/controllers/domiciliario/rutas.php`, { waitUntil: 'networkidle0' });
        // Trigger modal novedad
        await domiPage.evaluate(() => {
            if (typeof abrirModalNovedad === 'function') {
                abrirModalNovedad(47);
            } else {
                const m = document.getElementById('modalNovedadDomi');
                if (m) m.style.display = 'flex';
            }
        });
        await capture(domiPage, '05_modal_novedad', vp);
        await domiContext.close();
    }

    await browser.close();
    console.log('\nAll screenshots taken successfully!');
}

run().catch(err => {
    console.error('Fatal capture error:', err);
    process.exit(1);
});
