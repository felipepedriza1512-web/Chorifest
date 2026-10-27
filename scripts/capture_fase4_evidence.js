const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://localhost/Chorifest';
const OUT_DIR = path.join(__dirname, '..', 'docs', 'screenshots');
const ARTIFACT_DIR = 'C:\\Users\\felip\\.gemini\\antigravity-ide\\brain\\e5a54b8d-cd36-4e4f-9b79-3245f5fb4637';

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

    // 1. CAPTURA DEL CATÁLOGO DEL CLIENTE (Mostrando bebidas con Máx: X uds)
    console.log('Capturando Catálogo del Cliente...');
    const pageCat = await browser.newPage();
    await pageCat.setViewport({ width: 1280, height: 900 });
    await pageCat.goto(`${BASE_URL}/controllers/cliente/catalogo.php`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1000));
    
    const catPath = path.join(OUT_DIR, 'fase4_catalogo_cliente_max_bebidas.png');
    const catArtPath = path.join(ARTIFACT_DIR, 'fase4_catalogo_cliente_max_bebidas.png');
    await pageCat.screenshot({ path: catPath, fullPage: true });
    fs.copyFileSync(catPath, catArtPath);
    console.log(`[GUARDADO] ${catPath}`);

    // 2. CAPTURA DEL PANEL DE RECETAS (Admin)
    console.log('Iniciando sesión como Administrador...');
    const pageAdmin = await browser.newPage();
    await pageAdmin.setViewport({ width: 1280, height: 900 });
    await pageAdmin.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
    await pageAdmin.type('input[name="email"]', 'admin@gmail.com');
    await pageAdmin.type('input[name="password"]', '123456');
    await Promise.all([
        pageAdmin.waitForNavigation({ waitUntil: 'networkidle0' }),
        pageAdmin.click('button[type="submit"]')
    ]);

    // Ir a Productos y abrir modal de receta del Combo Pareja
    console.log('Capturando Panel de Productos y Recetas...');
    await pageAdmin.goto(`${BASE_URL}/controllers/admin/productos.php`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1000));

    // Hacer click en el botón de ver/editar receta del producto 4 (Combo Pareja) o abrir modal
    const modalBtn = await pageAdmin.$('button[onclick*="abrirModalReceta(4"]');
    if (modalBtn) {
        await modalBtn.click();
        await new Promise(r => setTimeout(r, 800));
    }

    const recPath = path.join(OUT_DIR, 'fase4_panel_recetas_admin.png');
    const recArtPath = path.join(ARTIFACT_DIR, 'fase4_panel_recetas_admin.png');
    await pageAdmin.screenshot({ path: recPath, fullPage: false });
    fs.copyFileSync(recPath, recArtPath);
    console.log(`[GUARDADO] ${recPath}`);

    // 3. CAPTURA DE LA TABLA DE INGREDIENTES E INSUMOS
    console.log('Capturando Tabla de Ingredientes/Insumos...');
    await pageAdmin.goto(`${BASE_URL}/controllers/admin/ingredientes.php`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1000));

    const ingPath = path.join(OUT_DIR, 'fase4_tabla_ingredientes_admin.png');
    const ingArtPath = path.join(ARTIFACT_DIR, 'fase4_tabla_ingredientes_admin.png');
    await pageAdmin.screenshot({ path: ingPath, fullPage: true });
    fs.copyFileSync(ingPath, ingArtPath);
    console.log(`[GUARDADO] ${ingPath}`);

    await browser.close();
    console.log('Capturas finalizadas con éxito.');
}

run().catch(err => {
    console.error('Error en capturas:', err);
    process.exit(1);
});
