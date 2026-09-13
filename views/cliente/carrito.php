<style>
/* CSS for cart */
.cart-container {
    display: flex;
    gap: 40px;
    align-items: flex-start;
}
.cart-items {
    flex: 2;
    background: rgba(10, 10, 15, 0.6);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 30px;
}
.cart-summary {
    flex: 1;
    background: rgba(10, 10, 15, 0.6);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 30px;
    position: sticky;
    top: 100px;
}
.cart-item {
    display: flex;
    align-items: center;
    padding: 20px 0;
    border-bottom: 1px solid var(--glass-border);
    gap: 20px;
}
.cart-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.item-image {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    object-fit: cover;
}
.item-details {
    flex: 1;
}
.item-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 5px;
}
.item-price {
    color: var(--primary);
    font-weight: 800;
}
.item-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}
.qty-form {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.05);
    padding: 5px 10px;
    border-radius: 10px;
}
.qty-btn {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0 5px;
}
.qty-input {
    width: 30px;
    background: transparent;
    border: none;
    color: white;
    text-align: center;
    font-weight: bold;
    font-size: 1rem;
}
.btn-remove {
    background: rgba(255, 65, 108, 0.2);
    color: #ff8a8a;
    border: none;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-remove:hover {
    background: rgba(255, 65, 108, 0.4);
    color: white;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 1.1rem;
}
.summary-total {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--glass-border);
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--primary);
}
.btn-checkout {
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
}
.btn-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(255, 75, 43, 0.4);
}
.empty-cart {
    text-align: center;
    padding: 50px;
    color: #888;
}

@media (max-width: 900px) {
    .cart-container { flex-direction: column; }
    .cart-items, .cart-summary { width: 100%; }
}
</style>

<div class="catalog-header">
    <h1 class="catalog-title">Mi Carrito</h1>
</div>

<?php if (empty($_SESSION['carrito'])): ?>
    <div class="cart-items empty-cart">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom:20px; opacity:0.5;">
            <circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        <h2>Tu carrito está vacío</h2>
        <p style="margin: 15px 0 30px 0;">¡Agrega algunos deliciosos productos del menú!</p>
        <a href="catalogo.php" class="btn-checkout" style="width: auto; display: inline-block; padding: 10px 30px;">Ir al menú</a>
    </div>
<?php else: ?>
    <div class="cart-container">
        <div class="cart-items">
            <?php 
            $subtotal = 0;
            foreach ($_SESSION['carrito'] as $id => $item): 
                $prod = $item['producto'];
                $qty = $item['cantidad'];
                $itemTotal = $prod['precio'] * $qty;
                $subtotal += $itemTotal;
                
                // Obtener imagen basada en el nombre usando el helper del modelo
                $imgUrl = $productoModel->getImagenUrl($prod['nombre_producto'], $id, 200);
            ?>
                <div class="cart-item">
                    <img src="<?= $imgUrl ?>" alt="Producto" class="item-image">
                    <div class="item-details">
                        <div class="item-title"><?= htmlspecialchars($prod['nombre_producto']) ?></div>
                        <div class="item-price">$<?= number_format($prod['precio'], 2) ?> c/u</div>
                    </div>
                    <div class="item-actions">
                        <form action="carrito.php" method="POST" class="qty-form">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id_producto" value="<?= $id ?>">
                            <button type="button" class="qty-btn" onclick="this.parentNode.querySelector('input[type=number]').stepDown(); this.parentNode.submit();">-</button>
                            <input type="number" name="cantidad" value="<?= $qty ?>" min="1" max="99" class="qty-input" readonly>
                            <button type="button" class="qty-btn" onclick="this.parentNode.querySelector('input[type=number]').stepUp(); this.parentNode.submit();">+</button>
                        </form>
                        
                        <form action="carrito.php" method="POST" style="margin:0;">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="id_producto" value="<?= $id ?>">
                            <button type="submit" class="btn-remove">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <h3>Resumen del Pedido</h3>
            <div style="margin-top: 20px;">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>$<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Costo de envío</span>
                    <span style="color:#888; font-size:0.9rem;">(Se calculará después)</span>
                </div>
                
                <div class="summary-total">
                    <span>Total estimado</span>
                    <span>$<?= number_format($subtotal, 2) ?></span>
                </div>

                <a href="checkout.php" class="btn-checkout">Proceder al pago</a>
                
                <form action="carrito.php" method="POST" style="margin-top: 15px; text-align:center;">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" style="background:none; border:none; color:#888; cursor:pointer; text-decoration:underline;">Vaciar carrito</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
