<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Factura') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ea580c;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --bg: #f9fafb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .invoice-container {
            background: #ffffff;
            width: 100%;
            max-width: 800px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--border);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .brand-logo {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: -1px;
            margin: 0;
        }

        .brand-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .invoice-details {
            text-align: right;
        }

        .invoice-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .invoice-meta {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 3px 0;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .info-block {
            width: 48%;
        }

        .info-title {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .info-content {
            font-size: 1rem;
            line-height: 1.5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            background-color: rgba(234, 88, 12, 0.05);
            color: var(--primary);
            font-weight: 600;
            text-align: left;
            padding: 12px 15px;
            border-bottom: 2px solid var(--primary);
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            font-size: 0.95rem;
        }

        .text-right {
            text-align: right;
        }

        .totals {
            width: 50%;
            margin-left: auto;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        .total-line.grand-total {
            border-bottom: none;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-main);
            padding-top: 15px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef08a; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px dashed var(--border);
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(234, 88, 12, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.2s;
        }

        .print-btn:hover {
            transform: translateY(-3px);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                max-width: 100%;
                padding: 0;
            }
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="invoice-container">
        <div class="header">
            <div>
                <h1 class="brand-logo">Chorifest</h1>
                <div class="brand-subtitle">Los mejores chorizos de la ciudad</div>
                <div class="brand-subtitle">NIT: 900.123.456-7</div>
                <div class="brand-subtitle">Tel: +57 300 123 4567</div>
            </div>
            <div class="invoice-details">
                <h2 class="invoice-title">Factura</h2>
                <div class="invoice-meta"><strong>Nº Pedido:</strong> #<?= str_pad($pedido['id_pedido'], 4, '0', STR_PAD_LEFT) ?></div>
                <div class="invoice-meta"><strong>Fecha:</strong> <?= date('d M Y, H:i', strtotime($pedido['fecha'])) ?></div>
                
                <?php 
                    $badge = 'badge-warning';
                    if ($pedido['estado_pago'] === 'Verificado') $badge = 'badge-success';
                    if ($pedido['estado_pago'] === 'Rechazado') $badge = 'badge-danger';
                ?>
                <div class="invoice-meta" style="margin-top: 10px;">
                    <strong>Estado Pago:</strong> 
                    <span class="badge <?= $badge ?>"><?= htmlspecialchars($pedido['estado_pago'] ?? 'Pendiente') ?></span>
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-block">
                <div class="info-title">Facturar a:</div>
                <div class="info-content">
                    <strong><?= htmlspecialchars($pedido['cliente_nombre']) ?></strong><br>
                    <?= htmlspecialchars($pedido['cliente_correo']) ?><br>
                    Tel: <?= htmlspecialchars($pedido['cliente_telefono']) ?>
                </div>
            </div>
            <div class="info-block">
                <div class="info-title">Detalles de Entrega:</div>
                <div class="info-content">
                    <strong>Dirección:</strong> <?= htmlspecialchars($pedido['direccion']) ?><br>
                    <strong>Barrio:</strong> <?= htmlspecialchars($pedido['barrio']) ?><br>
                    <strong>Zona:</strong> <?= htmlspecialchars($pedido['zona_nombre'] ?? 'Sin zona') ?><br>
                    <?php if (!empty($pedido['referencia'])): ?>
                        <em>Ref: <?= htmlspecialchars($pedido['referencia']) ?></em>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="text-right">Cant.</th>
                    <th class="text-right">Precio Unit.</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedido['productos'] as $prod): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($prod['nombre_producto']) ?></strong>
                    </td>
                    <td class="text-right"><?= $prod['cantidad'] ?></td>
                    <td class="text-right">$<?= number_format($prod['precio_unitario'], 2) ?></td>
                    <td class="text-right">$<?= number_format($prod['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="total-line">
                <span>Subtotal Productos:</span>
                <span>$<?= number_format($pedido['subtotal'], 2) ?></span>
            </div>
            <div class="total-line">
                <span>Costo Domicilio:</span>
                <span>$<?= number_format($pedido['costo_domicilio'], 2) ?></span>
            </div>
            <div class="total-line">
                <span>Método de Pago:</span>
                <strong><?= htmlspecialchars($pedido['metodo_pago'] ?? 'Efectivo') ?></strong>
            </div>
            <div class="total-line grand-total">
                <span>TOTAL:</span>
                <span>$<?= number_format($pedido['total'], 2) ?></span>
            </div>
        </div>

        <div class="footer">
            <p>¡Gracias por tu compra en Chorifest!</p>
            <p style="font-size: 0.8rem; opacity: 0.7;">Este documento es un comprobante de venta. Válido como garantía.</p>
        </div>
    </div>

    <?php if (!isset($isClient) || !$isClient): ?>
    <button class="print-btn" onclick="window.print()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Imprimir Factura
    </button>
    <?php else: ?>
    <!-- Auto-download for Client -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const element = document.querySelector('.invoice-container');
            const opt = {
                margin:       10,
                filename:     'Factura_Pedido_<?= str_pad($pedido['id_pedido'], 4, '0', STR_PAD_LEFT) ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Add a small notification logic
            let notify = document.createElement('div');
            notify.innerText = "Descargando factura en PDF...";
            notify.style.position = "fixed";
            notify.style.bottom = "20px";
            notify.style.right = "20px";
            notify.style.background = "var(--primary)";
            notify.style.color = "white";
            notify.style.padding = "10px 20px";
            notify.style.borderRadius = "8px";
            notify.style.boxShadow = "0 4px 10px rgba(0,0,0,0.2)";
            notify.style.fontWeight = "bold";
            notify.style.zIndex = "9999";
            document.body.appendChild(notify);

            // Generate PDF and then hide the notification
            html2pdf().set(opt).from(element).save().then(() => {
                setTimeout(() => {
                    notify.style.display = 'none';
                    // Optional: window.close() si abrieron en otra pestaña
                }, 3000);
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>
