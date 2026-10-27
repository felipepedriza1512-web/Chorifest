const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://localhost/Chorifest';
const OUT_DIR = path.join(__dirname, '..', 'docs', 'screenshots');
const ARTIFACT_DIR = 'C:\\Users\\felip\\.gemini\\antigravity-ide\\brain\\15c1ab91-0206-4966-b2f8-c2e5b96f4718';

async function run() {
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 950 });

    // Login Admin
    await page.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
    await page.type('input[name="email"]', 'admin@gmail.com');
    await page.type('input[name="password"]', '123456');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);

    const tabs = ['ventas', 'productos', 'domiciliarios', 'clientes', 'inventario'];

    for (const tab of tabs) {
        console.log(`Capturando pestaña: ${tab}...`);
        await page.goto(`${BASE_URL}/controllers/admin/reportes.php?tab=${tab}`, { waitUntil: 'networkidle0' });
        await new Promise(r => setTimeout(r, 1200));

        const filename = `fase5_reportes_${tab}.png`;
        const outPath = path.join(OUT_DIR, filename);
        await page.screenshot({ path: outPath, fullPage: true });
        fs.copyFileSync(outPath, path.join(ARTIFACT_DIR, filename));
        console.log(`[OK] ${filename}`);
    }

    // Capturar panel domiciliario con widget de cobros
    const pageDomi = await browser.newPage();
    await pageDomi.setViewport({ width: 1280, height: 950 });
    await pageDomi.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
    await pageDomi.type('input[name="email"]', 'domi@gmail.com');
    await pageDomi.type('input[name="password"]', '123456');
    await Promise.all([
        pageDomi.waitForNavigation({ waitUntil: 'networkidle0' }),
        pageDomi.click('button[type="submit"]')
    ]);
    await pageDomi.goto(`${BASE_URL}/controllers/domiciliario/rutas.php`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1200));

    const domiFile = 'fase5_domiciliario_cobros.png';
    const domiPath = path.join(OUT_DIR, domiFile);
    await pageDomi.screenshot({ path: domiPath, fullPage: true });
    fs.copyFileSync(domiPath, path.join(ARTIFACT_DIR, domiFile));
    console.log(`[OK] ${domiFile}`);

    await browser.close();
    console.log('Capturas de Fase 5 completadas exitosamente.');
}

run().catch(e => {
    console.error(e);
    process.exit(1);
});
