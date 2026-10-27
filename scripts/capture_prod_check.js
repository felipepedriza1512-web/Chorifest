const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://localhost/Chorifest';
const OUT_DIR = path.join(__dirname, '..', 'docs', 'screenshots');
const ARTIFACT_DIR = 'C:\\Users\\felip\\.gemini\\antigravity-ide\\brain\\15c1ab91-0206-4966-b2f8-c2e5b96f4718';

async function run() {
    const envFile = path.join(__dirname, '..', '.env');
    fs.writeFileSync(envFile, 'APP_ENV=production\nDB_HOST=127.0.0.1\nDB_USER=root\nDB_PASS=\nDB_NAME=chorifest\nDB_PORT=3306\n');
    try {
        const browser = await puppeteer.launch({
            executablePath: CHROME_PATH,
            headless: 'new',
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
        });
        const page = await browser.newPage();
        await page.setViewport({ width: 1280, height: 950 });

        await page.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
        await page.type('input[name="email"]', 'domi@gmail.com');
        await page.type('input[name="password"]', '123456');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle0' }),
            page.click('button[type="submit"]')
        ]);

        await page.goto(`${BASE_URL}/controllers/domiciliario/rutas.php`, { waitUntil: 'networkidle0' });
        await new Promise(r => setTimeout(r, 1500));

        // Verificar que el simulador no existe en el DOM
        const simBox = await page.$('.simulador-box');
        console.log('simBox en producción:', simBox ? 'EXISTE (FALLO)' : 'NO EXISTE (CORRECTO)');

        const p9 = path.join(OUT_DIR, 'evidencia_9_simulador_produccion_inexistente.png');
        await page.screenshot({ path: p9, fullPage: true });
        fs.copyFileSync(p9, path.join(ARTIFACT_DIR, 'evidencia_9_simulador_produccion_inexistente.png'));
        console.log('CAPTURA_PROD_OK, size:', fs.statSync(p9).size);
        await browser.close();
    } finally {
        if (fs.existsSync(envFile)) fs.unlinkSync(envFile);
    }
}

run().catch(e => {
    console.error(e);
    process.exit(1);
});
