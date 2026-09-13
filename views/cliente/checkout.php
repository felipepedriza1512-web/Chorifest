<style>
.checkout-container {
    display: flex;
    gap: 40px;
    align-items: flex-start;
}
.checkout-form {
    flex: 2;
    background: rgba(10, 10, 15, 0.6);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 30px;
}
.checkout-summary {
    flex: 1;
    background: rgba(10, 10, 15, 0.6);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 30px;
    position: sticky;
    top: 100px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}
.form-control {
    width: 100%;
    padding: 12px 15px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    color: white;
    font-size: 1rem;
}
.form-control:focus {
    outline: none;
    border-color: var(--primary);
    background: rgba(255, 255, 255, 0.1);
}
select.form-control option {
    background-color: var(--dark);
    color: white;
}
.btn-submit {
    display: block;
    width: 100%;
    text-align: center;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    padding: 15px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: bold;
    font-size: 1.1rem;
    margin-top: 30px;
    transition: transform 0.3s;
    border: none;
    cursor: pointer;
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(255, 75, 43, 0.4);
}
.success-message {
    background: rgba(46, 213, 115, 0.2);
    border: 1px solid #2ed573;
    color: #2ed573;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 20px;
}
.error-message {
    background: rgba(255, 71, 87, 0.2);
    border: 1px solid #ff4757;
    color: #ff4757;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
}
@media (max-width: 900px) {
    .checkout-container { flex-direction: column; }
    .checkout-form, .checkout-summary { width: 100%; }
}
</style>

<div class="catalog-header">
    <h1 class="catalog-title">Finalizar Compra</h1>
</div>

<?php if ($success): ?>
    <div class="success-message">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 15px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <h2>¡Pedido Confirmado!</h2>
        <p style="margin-top: 10px; font-size: 1.1rem;">Tu número de pedido es: <strong>#<?= str_pad($idPedidoGenerado, 5, '0', STR_PAD_LEFT) ?></strong></p>
        <p style="margin-top: 10px;">En breve comenzaremos a prepararlo. Puedes darle seguimiento desde la sección "Mis Pedidos".</p>
        <br>
        <a href="pedidos.php" class="btn-submit" style="display: inline-block; width: auto; padding: 10px 30px;">Ver mis pedidos</a>
    </div>
<?php else: ?>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="checkout-container">
        <form class="checkout-form" method="POST" action="checkout.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="checkout">
            
            <h3 style="margin-bottom: 20px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">Información de Envío</h3>
            
            <div class="form-group">
                <label for="id_zona">Zona de Envío *</label>
                <select id="id_zona" name="id_zona" class="form-control" required onchange="updateShippingCost()">
                    <option value="">Selecciona tu zona</option>
                    <?php foreach ($zonasEnvio as $zona): ?>
                        <option value="<?= $zona['id_zona'] ?>" data-cost="<?= $zona['costo_domicilio'] ?>"><?= htmlspecialchars($zona['nombre']) ?> (+ $<?= number_format($zona['costo_domicilio'], 0) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="direccion">Dirección exacta *</label>
                <input type="text" id="direccion" name="direccion" class="form-control" placeholder="Ej: Carrera 15 # 45-20, Apto 301" required>
            </div>

            <div class="form-group">
                <label for="barrio">Barrio</label>
                <input type="text" id="barrio" name="barrio" class="form-control">
            </div>

            <div class="form-group">
                <label for="referencia">Referencia / Indicaciones</label>
                <input type="text" id="referencia" name="referencia" class="form-control" placeholder="Ej: Casa esquinera reja negra, dejar en portería...">
            </div>

            <h3 style="margin-top: 40px; margin-bottom: 20px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">Opciones de Pago</h3>

            <div class="form-group">
                <label for="metodo_pago">Método de pago *</label>
                <select id="metodo_pago" name="metodo_pago" class="form-control" required onchange="toggleNequiInfo()">
                    <option value="">Selecciona cómo vas a pagar</option>
                    <option value="Efectivo (Contra entrega)">Efectivo (Pago al recibir)</option>
                    <option value="Transferencia / Nequi">Transferencia Bancaria / Nequi</option>
                    <option value="Tarjeta">Tarjeta (Datafono al entregar)</option>
                </select>
            </div>

            <div id="nequi-info" style="display: none; background: rgba(52, 152, 219, 0.1); border: 1px solid rgba(52, 152, 219, 0.3); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <h4 style="color: #3498db; margin-top: 0; margin-bottom: 10px;">Instrucciones para Transferencia</h4>
                <p style="margin: 5px 0;"><strong>Banco/App:</strong> Nequi</p>
                <p style="margin: 5px 0;"><strong>Número:</strong> 322 846 0174</p>
                <p style="margin: 5px 0; margin-bottom: 15px;"><strong>Nombre:</strong> Andres felipe pedriza</p>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="comprobante" style="color: white;">Sube tu comprobante (Captura de pantalla) *</label>
                    <input type="file" id="comprobante" name="comprobante" class="form-control" accept="image/*">
                </div>
            </div>

            <div id="datafono-info" style="display: none; background: rgba(234, 88, 12, 0.1); border: 1px solid rgba(234, 88, 12, 0.3); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <h4 style="color: var(--primary); margin-top: 0; margin-bottom: 10px;">Pago con Tarjeta en Casa</h4>
                <p style="margin: 5px 0;">Nuestro domiciliario llevará el <strong>datáfono</strong> hasta tu puerta.</p>
                <p style="margin: 5px 0;">Por favor, ten lista tu tarjeta (Débito o Crédito) al momento de recibir el pedido.</p>
            </div>

            <div class="form-group">
                <label for="observaciones">Observaciones del Pedido</label>
                <textarea id="observaciones" name="observaciones" class="form-control" rows="3" placeholder="Sin cebolla, salsas aparte, etc."></textarea>
            </div>

            <button type="submit" class="btn-submit">Confirmar Pedido</button>
        </form>

        <div class="checkout-summary">
            <h3>Resumen</h3>
            <div style="margin-top: 20px;">
                <?php 
                $subtotal = 0;
                $itemsCarrito = $_SESSION['carrito'] ?? [];
                if (!empty($itemsCarrito)) {
                    foreach ($itemsCarrito as $item) {
                        $itemTotal = ($item['producto']['precio'] ?? 0) * ($item['cantidad'] ?? 1);
                        $subtotal += $itemTotal;
                ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <span><?= $item['cantidad'] ?>x <?= htmlspecialchars($item['producto']['nombre_producto'] ?? 'Producto') ?></span>
                    <span>$<?= number_format($itemTotal, 2) ?></span>
                </div>
                <?php 
                    }
                } else {
                ?>
                <div style="color: #aaa; padding: 15px 0; text-align: center;">
                    Tu carrito está vacío. <a href="catalogo.php" style="color: var(--primary); text-decoration: none; font-weight: 700;">Ver menú &rarr;</a>
                </div>
                <?php } ?>
                
                <div style="display: flex; justify-content: space-between; margin-top: 15px; color: #aaa;">
                    <span>Subtotal</span>
                    <span>$<?= number_format($subtotal, 2) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 10px; color: #aaa;">
                    <span>Envío</span>
                    <span id="shipping-cost">$0.00</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--glass-border); font-size: 1.5rem; font-weight: 800; color: var(--primary);">
                    <span>Total</span>
                    <span id="total-price" data-subtotal="<?= $subtotal ?>">$<?= number_format($subtotal, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
    function updateShippingCost() {
        const select = document.getElementById('id_zona');
        const selectedOption = select.options[select.selectedIndex];
        let cost = 0;
        
        if(selectedOption.value !== "") {
            cost = parseFloat(selectedOption.getAttribute('data-cost'));
        }
        
        document.getElementById('shipping-cost').innerText = '$' + cost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        const subtotal = parseFloat(document.getElementById('total-price').getAttribute('data-subtotal'));
        const total = subtotal + cost;
        document.getElementById('total-price').innerText = '$' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function toggleNequiInfo() {
        const metodoPago = document.getElementById('metodo_pago').value;
        const nequiInfo = document.getElementById('nequi-info');
        const datafonoInfo = document.getElementById('datafono-info');
        const comprobanteInput = document.getElementById('comprobante');

        // Reset display
        nequiInfo.style.display = 'none';
        datafonoInfo.style.display = 'none';
        comprobanteInput.required = false;

        if (metodoPago === 'Transferencia / Nequi') {
            nequiInfo.style.display = 'block';
            comprobanteInput.required = true;
        } else if (metodoPago === 'Tarjeta') {
            datafonoInfo.style.display = 'block';
        }
    }
    </script>
<?php endif; ?>
