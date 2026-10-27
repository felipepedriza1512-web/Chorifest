const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const CHROME_PATH = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE_URL = 'http://localhost/Chorifest';
const OUT_DIR = path.join(__dirname, '..', 'docs', 'screenshots');
const ARTIFACT_DIR = 'C:\\Users\\felip\\.gemini\\antigravity-ide\\brain\\15c1ab91-0206-4966-b2f8-c2e5b96f4718';

if (!fs.existsSync(OUT_DIR)) fs.mkdirSync(OUT_DIR, { recursive: true });
if (!fs.existsSync(ARTIFACT_DIR)) fs.mkdirSync(ARTIFACT_DIR, { recursive: true });

function runSql(query) {
    const escaped = query.replace(/"/g, '\\"');
    execSync(`& "c:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysql.exe" -u root chorifest -e "${escaped}"`, { shell: 'powershell' });
}

async function run() {
    const idPedido = parseInt(fs.readFileSync(path.join(__dirname, 'test_pedido_id.txt'), 'utf8').trim());
    console.log(`Iniciando sesión de capturas para pedido #${idPedido}...`);

    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 950 });

    // 0. Login como Juan (Cliente)
    console.log('Iniciando sesión como Juan...');
    await page.goto(`${BASE_URL}/controllers/auth/login.php`, { waitUntil: 'networkidle0' });
    await page.type('input[name="email"]', 'juan@gmail.com');
    await page.type('input[name="password"]', '123456');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle0' }),
        page.click('button[type="submit"]')
    ]);

    // -------------------------------------------------------------
    // EVIDENCIA 1: Cliente NO ve el mapa en Estado 3 (En preparación)
    // -------------------------------------------------------------
    console.log('Capturando Evidencia 1: Pedido en preparación (Sin mapa)...');
    runSql(`UPDATE pedido SET id_estado = 3 WHERE id_pedido = ${idPedido}`);
    await page.goto(`${BASE_URL}/controllers/cliente/seguimiento.php?id=${idPedido}`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1200));

    const p1 = path.join(OUT_DIR, 'evidencia_1_sin_mapa_en_preparacion.png');
    await page.screenshot({ path: p1, fullPage: true });
    fs.copyFileSync(p1, path.join(ARTIFACT_DIR, 'evidencia_1_sin_mapa_en_preparacion.png'));
    console.log(`[OK] ${p1}`);

    // -------------------------------------------------------------
    // EVIDENCIA 2: Al pasar a "En camino", aparece mapa con marcadores (Posición 1) y Atribución OSM
    // -------------------------------------------------------------
    console.log('Capturando Evidencia 2: Pedido en camino (Posición 1)...');
    runSql(`UPDATE pedido SET id_estado = 5 WHERE id_pedido = ${idPedido}`);
    runSql(`INSERT INTO ubicacion_domiciliario (id_pedido, id_domiciliario, latitud, longitud, heading, velocidad, ultima_actualizacion) 
            VALUES (${idPedido}, 16, 2.915000, -75.290000, 45, 20, NOW()) 
            ON DUPLICATE KEY UPDATE latitud = 2.915000, longitud = -75.290000, ultima_actualizacion = NOW()`);

    await page.goto(`${BASE_URL}/controllers/cliente/seguimiento.php?id=${idPedido}`, { waitUntil: 'networkidle0' });
    // Esperar a que Leaflet cargue los tiles y OSRM responda la ruta
    await new Promise(r => setTimeout(r, 3500));

    const p2 = path.join(OUT_DIR, 'evidencia_2_en_camino_posicion_1.png');
    await page.screenshot({ path: p2, fullPage: true });
    fs.copyFileSync(p2, path.join(ARTIFACT_DIR, 'evidencia_2_en_camino_posicion_1.png'));
    console.log(`[OK] ${p2}`);

    // -------------------------------------------------------------
    // EVIDENCIA 3: Domiciliario se mueve (Posición 2 - actualización en vivo)
    // -------------------------------------------------------------
    console.log('Capturando Evidencia 3: Domiciliario se mueve (Posición 2)...');
    runSql(`UPDATE ubicacion_domiciliario SET latitud = 2.923000, longitud = -75.284000, ultima_actualizacion = NOW() WHERE id_pedido = ${idPedido}`);
    // Esperar el polling automático de 5 segundos de seguimiento.php
    await new Promise(r => setTimeout(r, 6500));

    const p3 = path.join(OUT_DIR, 'evidencia_3_en_camino_posicion_2.png');
    await page.screenshot({ path: p3, fullPage: true });
    fs.copyFileSync(p3, path.join(ARTIFACT_DIR, 'evidencia_3_en_camino_posicion_2.png'));
    console.log(`[OK] ${p3}`);

    // -------------------------------------------------------------
    // EVIDENCIA 4: Señal perdida (> 15 segundos sin GPS)
    // -------------------------------------------------------------
    console.log('Capturando Evidencia 4: Señal GPS perdida...');
    runSql(`UPDATE ubicacion_domiciliario SET ultima_actualizacion = DATE_SUB(NOW(), INTERVAL 52 SECOND) WHERE id_pedido = ${idPedido}`);
    await page.evaluate(() => {
        // Forzar llamada a la función del closure o recargar
        location.reload();
    });
    await new Promise(r => setTimeout(r, 3000));

    const p4 = path.join(OUT_DIR, 'evidencia_4_senal_perdida_aviso.png');
    await page.screenshot({ path: p4, fullPage: true });
    fs.copyFileSync(p4, path.join(ARTIFACT_DIR, 'evidencia_4_senal_perdida_aviso.png'));
    console.log(`[OK] ${p4}`);

    // -------------------------------------------------------------
    // EVIDENCIA 5: Resiliencia sin coordenadas (Pedido antiguo sin GPS)
    // -------------------------------------------------------------
    console.log('Capturando Evidencia 5: Pedido antiguo sin coordenadas...');
    const idPedOld = execSync('php -r "$db = require \'config/database.php\'; $pdo = getDatabaseConnection(); $pdo->exec(\\"INSERT INTO direccion (id_cliente, direccion, barrio, latitud, longitud) VALUES (13, \'Calle Vieja # 1-2\', \'Antiguo\', NULL, NULL)\\"); $idDir = $pdo->lastInsertId(); $pdo->exec(\\"INSERT INTO pedido (id_cliente, id_estado, id_direccion, id_zona, nombre_zona, costo_domicilio, subtotal, total, fecha) VALUES (13, 5, $idDir, 1, \'Zona Norte\', 3000.00, 20000.00, 23000.00, NOW())\\"); $idPed = $pdo->lastInsertId(); $pdo->exec(\\"INSERT INTO asignacion_domicilio (id_pedido, id_domiciliario, fecha_asignacion) VALUES ($idPed, 16, NOW())\\"); echo $idPed;"', { cwd: path.join(__dirname, '..') }).toString().trim();

    await page.goto(`${BASE_URL}/controllers/cliente/seguimiento.php?id=${idPedOld}`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1500));

    const p5 = path.join(OUT_DIR, 'evidencia_5_sin_coordenadas_no_rompe.png');
    await page.screenshot({ path: p5, fullPage: true });
    fs.copyFileSync(p5, path.join(ARTIFACT_DIR, 'evidencia_5_sin_coordenadas_no_rompe.png'));
    console.log(`[OK] ${p5}`);

    // Limpiar pedido antiguo
    runSql(`DELETE FROM asignacion_domicilio WHERE id_pedido = ${idPedOld}`);
    runSql(`DELETE FROM pedido WHERE id_pedido = ${idPedOld}`);

    // -------------------------------------------------------------
    // EVIDENCIA 6: Al marcar Entregado, el mapa desaparece y muestra calificación
    // -------------------------------------------------------------
    console.log('Capturando Evidencia 6: Pedido Entregado (Mapa desaparece)...');
    runSql(`UPDATE pedido SET id_estado = 6 WHERE id_pedido = ${idPedido}`);
    await page.goto(`${BASE_URL}/controllers/cliente/seguimiento.php?id=${idPedido}`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1500));

    const p6 = path.join(OUT_DIR, 'evidencia_6_entregado_mapa_desaparece.png');
    await page.screenshot({ path: p6, fullPage: true });
    fs.copyFileSync(p6, path.join(ARTIFACT_DIR, 'evidencia_6_entregado_mapa_desaparece.png'));
    console.log(`[OK] ${p6}`);

    // -------------------------------------------------------------
    // EVIDENCIA 7: Al marcar Cancelado, el mapa desaparece
    // -------------------------------------------------------------
    console.log('Capturando Evidencia 7: Pedido Cancelado (Mapa desaparece)...');
    runSql(`UPDATE pedido SET id_estado = 7 WHERE id_pedido = ${idPedido}`);
    await page.goto(`${BASE_URL}/controllers/cliente/seguimiento.php?id=${idPedido}`, { waitUntil: 'networkidle0' });
    await new Promise(r => setTimeout(r, 1500));

    const p7 = path.join(OUT_DIR, 'evidencia_7_cancelado_mapa_desaparece.png');
    await page.screenshot({ path: p7, fullPage: true });
    fs.copyFileSync(p7, path.join(ARTIFACT_DIR, 'evidencia_7_cancelado_mapa_desaparece.png'));
    console.log(`[OK] ${p7}`);

    // -------------------------------------------------------------
    // EVIDENCIA 8: Panel Domiciliario - Simulador apagado por defecto (Desarrollo)
    // -------------------------------------------------------------
    console.log('Capturando Evidencia 8: Panel Domiciliario (Simulador apagado por defecto)...');
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

    const p8 = path.join(OUT_DIR, 'evidencia_8_simulador_desarrollo_apagado.png');
    await pageDomi.screenshot({ path: p8, fullPage: true });
    fs.copyFileSync(p8, path.join(ARTIFACT_DIR, 'evidencia_8_simulador_desarrollo_apagado.png'));
    console.log(`[OK] ${p8}`);

    // -------------------------------------------------------------
    // EVIDENCIA 9: Panel Domiciliario - En PRODUCCIÓN el simulador NO existe
    // -------------------------------------------------------------
    console.log('Capturando Evidencia 9: Panel Domiciliario (Simulador no existe en producción)...');
    const envFile = path.join(__dirname, '..', '.env');
    fs.writeFileSync(envFile, 'APP_ENV=production\n');
    try {
        await pageDomi.goto(`${BASE_URL}/controllers/domiciliario/rutas.php`, { waitUntil: 'networkidle0' });
        await new Promise(r => setTimeout(r, 1200));

        const p9 = path.join(OUT_DIR, 'evidencia_9_simulador_produccion_inexistente.png');
        await pageDomi.screenshot({ path: p9, fullPage: true });
        fs.copyFileSync(p9, path.join(ARTIFACT_DIR, 'evidencia_9_simulador_produccion_inexistente.png'));
        console.log(`[OK] ${p9}`);
    } finally {
        if (fs.existsSync(envFile)) {
            fs.unlinkSync(envFile);
        }
    }

    await browser.close();
    console.log('\nTodas las capturas se guardaron exitosamente.');
}

run().catch(e => {
    console.error('Error durante la captura:', e);
    process.exit(1);
});
