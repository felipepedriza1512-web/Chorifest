<style>
/* CSS Animations */
@keyframes floating {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-15px); }
    100% { transform: translateY(0px); }
}

@keyframes pulseGlow {
    0% { box-shadow: 0 0 20px rgba(234, 88, 12, 0.2); }
    50% { box-shadow: 0 0 40px rgba(234, 88, 12, 0.5); }
    100% { box-shadow: 0 0 20px rgba(234, 88, 12, 0.2); }
}

.catalog-header {
    margin-bottom: 40px;
    text-align: center;
}
.catalog-title {
    font-size: 3.5rem;
    font-weight: 800;
    margin-bottom: 10px;
    background: linear-gradient(135deg, #fff, #a1a1aa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -1px;
}
.categories-bar {
    display: flex;
    gap: 15px;
    overflow-x: auto;
    padding-bottom: 15px;
    margin-bottom: 50px;
    justify-content: center;
}
.category-btn {
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 30px;
    color: var(--text-muted);
    text-decoration: none;
    font-weight: 600;
    white-space: nowrap;
    transition: var(--transition);
}
.category-btn:hover, .category-btn.active {
    background: rgba(234, 88, 12, 0.15);
    color: var(--primary);
    border-color: rgba(234, 88, 12, 0.3);
    box-shadow: 0 0 20px rgba(234, 88, 12, 0.2);
    transform: translateY(-2px);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 50px;
    perspective: 1500px; /* Stronger perspective */
    padding: 20px;
}

/* 3D Card Base */
.product-card-wrapper {
    position: relative;
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    animation: floating 4s ease-in-out infinite;
}

.product-card-wrapper:nth-child(even) {
    animation-delay: 1.5s;
}

.product-card-wrapper:hover {
    animation-play-state: paused;
    z-index: 100;
}

.product-card {
    background: rgba(15, 15, 18, 0.4);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    display: flex;
    flex-direction: column;
    height: 100%;
    position: relative;
    box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.8);
    transition: box-shadow 0.4s ease, border-color 0.4s ease;
    overflow: hidden; /* Prevent elements from bleeding out */
    user-select: none; /* Prevent accidental text highlighting during 3D tilts */
    -webkit-user-select: none;
}

.product-card-wrapper:hover .product-card {
    border-color: rgba(234, 88, 12, 0.4);
    box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.9), 0 0 40px rgba(234, 88, 12, 0.2);
}

/* Internal Elements (Safe 2D Simulated 3D) */
.product-image-container {
    width: 100%;
    height: 250px;
    position: relative;
    overflow: hidden;
    background: #000;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275), filter 0.5s ease;
}

.product-card-wrapper:hover .product-image {
    transform: scale(1.15) translateY(-5px); /* Strong scale for pop effect */
    filter: brightness(1.2);
}

.product-info {
    padding: 30px 25px;
    flex: 1;
    display: flex;
    flex-direction: column;
    transition: transform 0.4s ease;
}
.product-card-wrapper:hover .product-info {
    transform: translateY(-5px);
}

.product-title {
    font-size: 1.5rem;
    font-weight: 800;
    margin-bottom: 12px;
    color: var(--text-main);
    letter-spacing: -0.5px;
    transition: color 0.3s ease;
}
.product-card-wrapper:hover .product-title {
    color: var(--primary);
}

.product-desc {
    color: var(--text-muted);
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 25px;
    flex: 1;
}

.product-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    padding-top: 20px;
    transition: transform 0.4s ease;
    position: relative;
    z-index: 20;
}
.product-card-wrapper:hover .product-footer {
    transform: translateY(-5px);
}

.product-price {
    font-size: 1.6rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), #fb923c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.btn-add {
    background: linear-gradient(135deg, var(--primary), #fb923c);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(234, 88, 12, 0.3);
    position: relative;
    overflow: hidden;
}

.btn-add::before {
    content: '';
    position: absolute;
    top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s ease;
}

.btn-add:hover::before {
    left: 100%;
}

.btn-add:hover {
    box-shadow: 0 10px 25px rgba(234, 88, 12, 0.6);
    transform: scale(1.05);
}

.btn-disabled {
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-muted);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: none;
    cursor: not-allowed;
}
.btn-disabled:hover { transform: none; box-shadow: none; }

.badge-unavailable {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(239, 68, 68, 0.8);
    color: white;
    padding: 6px 14px;
    border-radius: 12px;
    font-weight: 800;
    font-size: 0.85rem;
    border: 1px solid rgba(239, 68, 68, 1);
    backdrop-filter: blur(5px);
    z-index: 10;
}

/* Light Reflection Layer */
.card-reflection {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(125deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 40%, rgba(255,255,255,0) 100%);
    z-index: 5;
    pointer-events: none;
    border-radius: 24px;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.product-card-wrapper:hover .card-reflection {
    opacity: 1;
}

/* Custom Scrollbar for categories */
.categories-bar::-webkit-scrollbar { height: 6px; }
.categories-bar::-webkit-scrollbar-track { background: transparent; }
.categories-bar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
.categories-bar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
</style>

<div class="catalog-header">
    <h1 class="catalog-title">Nuestro Menú</h1>
    <p style="color: var(--text-muted); font-size: 1.1rem;">Descubre los mejores sabores a la parrilla.</p>
</div>

<div class="categories-bar">
    <a href="catalogo.php" class="category-btn <?= $categoriaActiva === 0 ? 'active' : '' ?>">Todos</a>
    <?php foreach ($categorias as $cat): ?>
        <a href="catalogo.php?categoria=<?= $cat['id_categoria'] ?>" class="category-btn <?= $categoriaActiva == $cat['id_categoria'] ? 'active' : '' ?>">
            <?= htmlspecialchars($cat['nombre']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="products-grid">
    <?php if (empty($productos)): ?>
        <p style="color: #888; grid-column: 1/-1; text-align: center; padding: 50px; font-size: 1.2rem;">No hay productos disponibles en esta categoría.</p>
    <?php else: ?>
        <?php foreach ($productos as $prod): ?>
            <div class="product-card-wrapper">
                <div class="product-card">
                    <div class="card-reflection"></div>
                    
                    <?php 
                        $imgUrl = $productoModel->getImagenUrl($prod['nombre_producto'], $prod['id_producto'], 600);
                    ?>
                    <div class="product-image-container">
                        <?php if (!$prod['disponible']): ?>
                            <div class="badge-unavailable">Agotado</div>
                        <?php endif; ?>
                        <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($prod['nombre_producto']) ?>" class="product-image">
                    </div>
                    
                    <div class="product-info">
                        <h3 class="product-title"><?= htmlspecialchars($prod['nombre_producto']) ?></h3>
                        <p class="product-desc"><?= htmlspecialchars($prod['descripcion'] ?? 'Delicioso producto a la parrilla preparado al instante.') ?></p>
                        <div class="product-footer">
                            <span class="product-price">$<?= number_format($prod['precio'], 2) ?></span>
                            
                            <?php if ($prod['disponible']): ?>
                                <form action="carrito.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="id_producto" value="<?= $prod['id_producto'] ?>">
                                    <input type="hidden" name="cantidad" value="1">
                                    <button type="submit" class="btn-add">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                        Añadir
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn-add btn-disabled" disabled>Agotado</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.product-card-wrapper');
    
    cards.forEach(wrapper => {
        const card = wrapper.querySelector('.product-card');
        const reflection = wrapper.querySelector('.card-reflection');
        
        wrapper.addEventListener('mousemove', (e) => {
            const rect = wrapper.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            // EXTREME ROTATION
            const rotateX = ((y - centerY) / centerY) * -22; // Max rotation 22deg
            const rotateY = ((x - centerX) / centerX) * 22;
            
            card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(30px)`;
            
            // Move reflection based on mouse position
            const bgX = (x / rect.width) * 100;
            const bgY = (y / rect.height) * 100;
            reflection.style.background = `radial-gradient(circle at ${bgX}% ${bgY}%, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%)`;
        });
        
        // Add smoothing when leaving the card
        wrapper.addEventListener('mouseleave', () => {
            card.style.transition = 'transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            card.style.transform = `rotateX(0deg) rotateY(0deg) translateZ(0)`;
            reflection.style.background = 'none';
            
            setTimeout(() => {
                card.style.transition = 'box-shadow 0.4s ease, border-color 0.4s ease'; // reset transition for normal hover
            }, 600);
        });
        
        wrapper.addEventListener('mouseenter', () => {
            card.style.transition = 'transform 0.1s ease-out'; // Fast tracking when entering
        });
    });
});
</script>
