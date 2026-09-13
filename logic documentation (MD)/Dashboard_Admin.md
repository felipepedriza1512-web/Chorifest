# Documentación: Dashboard de Administrador

Este documento explica en detalle el funcionamiento, la estructura y el propósito de los archivos relacionados con el "Dashboard" (Panel de control) del rol de Administrador.

---

## 1. Controlador: `controllers/admin/dashboard.php`

Este archivo actúa como el intermediario (controlador) entre la base de datos (modelo) y lo que ve el usuario (vista). Su función principal es proteger la ruta, obtener los datos necesarios y enviarlos a la vista.

### Código Completo

```php
<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

$stats = $adminModel->getDashboardStats();
$chartData = $adminModel->getSalesChartData();

$currentPage = 'dashboard';
$pageTitle = 'Dashboard General';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/dashboard.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
```

### Explicación Línea por Línea y Funcionalidad

1. **`session_start();`**: Inicia la sesión de PHP. Es vital para poder acceder a las variables de sesión, como el ID del usuario logueado o su rol.
2. **`require_once __DIR__ . '/../auth/AuthMiddleware.php';`**: Incluye el middleware de autenticación. Este archivo contiene funciones para validar quién está intentando acceder.
3. **`checkRole(2);`**: Llama a una función del middleware que verifica si el usuario actual tiene el rol `2` (que corresponde a Administrador). Si no tiene este rol, probablemente lo redirigirá fuera de esta página. Es una **medida de seguridad crucial**.
4. **Inclusiones de Base de Datos y Modelo**: 
   - `require_once __DIR__ . '/../../config/database.php';`: Trae la configuración para conectarse a la base de datos.
   - `require_once __DIR__ . '/../../models/AdminModel.php';`: Incluye el modelo de Administrador, que es el archivo que contiene las consultas SQL reales (lógica de datos).
5. **Instanciación**:
   - `$db = getDatabaseConnection();`: Obtiene la conexión activa a la base de datos.
   - `$adminModel = new AdminModel($db);`: Crea un objeto del modelo, pasándole la conexión, para poder usar sus funciones.
6. **Obtención de Datos**:
   - `$stats = $adminModel->getDashboardStats();`: Pide al modelo las estadísticas generales (número de pedidos, ventas del día, etc.).
   - `$chartData = $adminModel->getSalesChartData();`: Pide al modelo los datos formateados para mostrar el gráfico de rendimiento de los últimos 7 días.
7. **Variables de Vista**:
   - `$currentPage = 'dashboard';` y `$pageTitle = 'Dashboard General';`: Definen variables que se utilizarán en el `admin_header.php` para resaltar en el menú en qué página estamos y ponerle título a la pestaña.
8. **Renderizado (Carga de Vistas)**:
   - Finalmente, se incluyen la cabecera general (`admin_header.php`), el cuerpo específico del dashboard (`dashboard.php` de la carpeta views), y el pie de página (`admin_footer.php`). Como se incluyeron aquí, estas vistas tendrán acceso a las variables `$stats` y `$chartData` que definimos arriba.

---

## 2. Vista: `views/Admin/dashboard.php`

Este archivo contiene únicamente el HTML, CSS (embebido) y Javascript necesarios para mostrar la interfaz del Dashboard al usuario. No hace consultas a la base de datos, solo muestra los datos que le envió el controlador.

### Código Completo

```php
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 30px;
        margin-bottom: 50px;
    }
    .stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 30px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.4);
        transition: var(--transition);
        cursor: default;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        border-color: var(--border-highlight);
        box-shadow: 0 15px 40px -10px rgba(0, 0, 0, 0.6), 0 0 20px rgba(234, 88, 12, 0.05);
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 2px;
        background: linear-gradient(90deg, transparent, var(--primary), transparent);
        opacity: 0;
        transition: var(--transition);
    }
    .stat-card:hover::before {
        opacity: 1;
    }
    
    .stat-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .stat-card h3 {
        color: var(--text-muted);
        font-size: 1rem;
        font-weight: 500;
        letter-spacing: -0.2px;
    }
    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: rgba(255,255,255,0.03);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border);
        color: var(--text-muted);
        transition: var(--transition);
    }
    .stat-card:hover .stat-icon {
        color: var(--primary);
        background: rgba(234, 88, 12, 0.1);
        border-color: rgba(234, 88, 12, 0.2);
    }
    
    .stat-card .value {
        font-size: 3rem;
        font-weight: 800;
        color: var(--text-main);
        letter-spacing: -1.5px;
        background: linear-gradient(135deg, #fff, #a1a1aa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .stat-card .value.highlight {
        background: linear-gradient(135deg, var(--primary), #fb923c);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        text-shadow: 0 0 20px var(--primary-glow);
    }
</style>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Pedidos Hoy</h3>
            <div class="stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </div>
        </div>
        <div class="value"><?= number_format($stats['pedidos_hoy']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Ventas Hoy</h3>
            <div class="stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
        </div>
        <div class="value">$<?= number_format($stats['ventas_hoy'], 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Pedidos Pendientes</h3>
            <div class="stat-icon" style="color: var(--primary); background: rgba(234, 88, 12, 0.1); border-color: rgba(234, 88, 12, 0.2);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
        </div>
        <div class="value highlight"><?= number_format($stats['pedidos_pendientes']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Productos Activos</h3>
            <div class="stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            </div>
        </div>
        <div class="value"><?= number_format($stats['productos_activos']) ?></div>
    </div>
</div>

<!-- Chart Section -->
<div class="card" style="padding: 30px; border-radius: 24px; position: relative; overflow: hidden;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h3 style="font-weight: 700; font-size: 1.5rem; letter-spacing: -0.5px; display: flex; align-items: center; gap: 10px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                Rendimiento de los últimos 7 días
            </h3>
            <p style="color: var(--text-muted); margin-top: 5px; font-size: 0.95rem;">Evolución de pedidos y ventas para medir tu crecimiento.</p>
        </div>
        <div style="background: rgba(234,88,12,0.1); padding: 8px 16px; border-radius: 20px; border: 1px solid rgba(234,88,12,0.2);">
            <span style="color: var(--primary); font-weight: 600; font-size: 0.9rem;">Actualizado en tiempo real</span>
        </div>
    </div>
    
    <div style="position: relative; height: 350px; width: 100%;">
        <canvas id="performanceChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('performanceChart').getContext('2d');
    
    // Parse data from PHP
    const chartData = <?= json_encode($chartData) ?>;
    
    // Create gradients
    const gradientVentas = ctx.createLinearGradient(0, 0, 0, 350);
    gradientVentas.addColorStop(0, 'rgba(234, 88, 12, 0.4)');
    gradientVentas.addColorStop(1, 'rgba(234, 88, 12, 0)');
    
    const gradientPedidos = ctx.createLinearGradient(0, 0, 0, 350);
    gradientPedidos.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
    gradientPedidos.addColorStop(1, 'rgba(16, 185, 129, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Ventas ($)',
                    data: chartData.ventas,
                    borderColor: '#ea580c', // Primary orange
                    backgroundColor: gradientVentas,
                    borderWidth: 3,
                    pointBackgroundColor: '#18181b',
                    pointBorderColor: '#ea580c',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Pedidos',
                    data: chartData.pedidos,
                    borderColor: '#10b981', // Emerald green
                    backgroundColor: gradientPedidos,
                    borderWidth: 3,
                    pointBackgroundColor: '#18181b',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: '#a1a1aa',
                        usePointStyle: true,
                        boxWidth: 8,
                        font: {
                            family: 'Inter',
                            size: 13,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 15, 18, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#a1a1aa',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    padding: 12,
                    boxPadding: 6,
                    usePointStyle: true,
                    titleFont: { family: 'Inter', size: 14 },
                    bodyFont: { family: 'Inter', size: 13 },
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 0) {
                                label += '$' + new Intl.NumberFormat('es-CO').format(context.parsed.y);
                            } else {
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(255,255,255,0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#a1a1aa',
                        font: { family: 'Inter' }
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    grid: {
                        color: 'rgba(255,255,255,0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#a1a1aa',
                        font: { family: 'Inter' },
                        callback: function(value) {
                            return '$' + (value / 1000) + 'k';
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false, // only want the grid lines for one axis to show up
                        drawBorder: false
                    },
                    ticks: {
                        color: '#10b981',
                        font: { family: 'Inter' },
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
```

### Explicación y Funcionalidad

1. **Etiquetas `<style>`**: En la parte superior, se definen los estilos CSS de las tarjetas ("Cards"). Se usan variables CSS (como `var(--surface)`) que asumen que hay una hoja de estilos global cargada en `admin_header.php`. Se le da un diseño muy moderno con sombras, hover effects, gradientes y animaciones suaves.
2. **Las "Stats Grid" (Tarjetas de Estadísticas)**:
   - Se crea un div contenedor `.stats-grid`.
   - Se crean 4 tarjetas para: **Pedidos Hoy, Ventas Hoy, Pedidos Pendientes, y Productos Activos**.
   - **Inyección de PHP**: Aquí es donde la vista usa los datos del controlador. Por ejemplo, en la tarjeta de "Pedidos Hoy" se imprime: `<?= number_format($stats['pedidos_hoy']) ?>`. Esto usa la variable `$stats` que fue proveída por el controlador, y formatea el número para que se lea mejor. Así se insertan los datos dinámicamente en el HTML.
3. **Sección del Gráfico (Chart Section)**:
   - Contiene el HTML para un lienzo o `<canvas id="performanceChart"></canvas>`. Aquí es donde la librería Chart.js dibujará el gráfico.
4. **Script de Chart.js**:
   - `<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>`: Carga la librería Chart.js desde un CDN para poder crear el gráfico de líneas.
   - En el bloque de `<script>`, se obtiene el contexto del canvas.
   - **Paso de PHP a JS**: `const chartData = <?= json_encode($chartData) ?>;` Esta línea es muy importante. Toma el arreglo PHP de los datos del gráfico (`$chartData`) y lo convierte en un formato JSON (JavaScript Object Notation), lo que permite que Javascript lo lea como un objeto/array nativo.
   - Luego, se configura Chart.js: Se crean gradientes, se establecen las etiquetas de los ejes (`labels: chartData.labels`), los datos de ventas (`chartData.ventas`) y los datos de pedidos (`chartData.pedidos`), además de mucha configuración visual (colores, ejes, interactividad al pasar el mouse - tooltips).
