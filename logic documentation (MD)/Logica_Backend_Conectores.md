# Código y Lógica de Todos los Controladores

A continuación se muestra el código PHP completo de cada uno de los controladores del sistema, con la explicación de su función detallada como comentarios o descripciones dentro del mismo código para que sepas exactamente qué hace cada línea.

---

## 1. Módulo: Admin

### Archivo: `admin/dashboard.php`
```php
<?php
session_start(); // Inicia la sesión para poder identificar al usuario
require_once __DIR__ . '/../auth/AuthMiddleware.php'; // Carga reglas de seguridad
checkRole(2); // Verifica estrictamente que el usuario sea Admin (Rol 2). Si no, lo bloquea.

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection(); // Conecta a la base de datos
$adminModel = new AdminModel($db); // Prepara el modelo que tiene las consultas SQL

// Llama al modelo para obtener las estadísticas totales y los datos del gráfico
$stats = $adminModel->getDashboardStats();
$chartData = $adminModel->getSalesChartData();

// Variables que la vista leerá para pintar el menú activo y el título de la pestaña
$currentPage = 'dashboard';
$pageTitle = 'Dashboard General';

// Carga las partes visuales (vistas) que ahora tienen acceso a $stats y $chartData
require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/dashboard.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
```

### Archivo: `admin/empleados.php`
```php
<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2); // Solo admin puede ver y crear empleados
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

// Si se envía el formulario para CREAR o ACTUALIZAR un empleado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        // Recoge los datos básicos del formulario
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'id_rol' => (int)($_POST['id_rol'] ?? 0),
            'estado' => $_POST['estado'] ?? 'ACTIVO'
        ];
        
        // Si escribió una contraseña, la añade para que se cambie
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        // Si el rol es 3 (Cocinera), necesita el turno
        if ($data['id_rol'] === 3) {
            $data['turno'] = trim($_POST['turno'] ?? '');
        } 
        // Si el rol es 4 (Domiciliario), necesita vehículo y placa
        elseif ($data['id_rol'] === 4) {
            $data['tipo_vehiculo'] = trim($_POST['tipo_vehiculo'] ?? '');
            $data['placa'] = trim($_POST['placa'] ?? '');
            $data['estado_domiciliario'] = trim($_POST['estado_domiciliario'] ?? 'ACTIVO');
        }

        // Determina si va a insertar (create) o actualizar (update) en BD
        if ($action === 'create') {
            $adminModel->createEmpleado($data);
        } else {
            $id = (int)$_POST['id_usuario'];
            $adminModel->updateEmpleado($id, $data);
        }
        
        header('Location: empleados.php'); // Recarga la página
        exit;
    }
}

// Obtiene la lista completa de empleados para mostrar en la tabla
$empleados = $adminModel->getEmpleados();

$currentPage = 'empleados';
$pageTitle = 'Gestión de Personal';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/empleados.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
```

### Archivo: `admin/pedidos.php`
```php
<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2); // Seguridad de Admin
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

// Si se pulsa un botón para cambiar el estado de un pedido (ej. de pendiente a cocinando)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $idPedido = (int)$_POST['id_pedido'];
    $idEstado = (int)$_POST['id_estado'];
    if ($idPedido > 0 && $idEstado > 0) {
        $adminModel->updatePedidoStatus($idPedido, $idEstado); // Actualiza en BD
    }
    header('Location: pedidos.php');
    exit;
}

// Obtiene todos los pedidos y los posibles estados para pintar la tabla
$pedidos = $adminModel->getPedidos();
$estados = $adminModel->getEstadosPedido();

$currentPage = 'pedidos';
$pageTitle = 'Gestión de Pedidos';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/pedidos.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
```

### Archivo: `admin/productos.php`
```php
<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ProductoModel.php';

$db = getDatabaseConnection();
$productoModel = new ProductoModel($db);

// Gestión del formulario de productos (crear, editar, activar/desactivar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create') {
        $data = [
            'id_categoria' => $_POST['id_categoria'] ?? 1,
            'nombre_producto' => $_POST['nombre_producto'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'precio' => (float)($_POST['precio'] ?? 0),
            'disponible' => 1
        ];
        if (!empty($data['nombre_producto']) && $data['precio'] > 0) {
            $productoModel->createProducto($data);
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id_producto'];
        $data = [
            'id_categoria' => $_POST['id_categoria'] ?? 1,
            'nombre_producto' => $_POST['nombre_producto'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'precio' => (float)($_POST['precio'] ?? 0),
            'disponible' => isset($_POST['disponible']) ? 1 : 0
        ];
        if ($id > 0 && !empty($data['nombre_producto']) && $data['precio'] > 0) {
            $productoModel->updateProducto($id, $data);
        }
    } elseif ($action === 'toggle') {
        // Cambia rápidamente si un producto está disponible o agotado (1 o 0)
        $id = (int)$_POST['id_producto'];
        if ($id > 0) {
            $productoModel->toggleDisponible($id);
        }
    }
    
    header('Location: productos.php');
    exit;
}

// Obtiene la lista de todos los productos y las categorías (ej: Perros, Combos)
$productos = $productoModel->getProductos();
$categorias = $productoModel->getCategorias();

$currentPage = 'productos';
$pageTitle = 'Catálogo de Productos';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/productos.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
```

### Archivo: `admin/zonas.php`
```php
<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

// Gestión del formulario de Zonas de Envío (crear o editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create') {
        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'costo_domicilio' => (float)($_POST['costo_domicilio'] ?? 0),
            'tiempo_estimado_min' => (int)($_POST['tiempo_estimado_min'] ?? 30)
        ];
        if (!empty($data['nombre'])) {
            $adminModel->createZona($data);
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id_zona'];
        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'costo_domicilio' => (float)($_POST['costo_domicilio'] ?? 0),
            'tiempo_estimado_min' => (int)($_POST['tiempo_estimado_min'] ?? 30),
            'estado' => $_POST['estado'] ?? 'ACTIVA'
        ];
        if ($id > 0 && !empty($data['nombre'])) {
            $adminModel->updateZona($id, $data);
        }
    }
    
    header('Location: zonas.php');
    exit;
}

// Obtiene todas las zonas para la tabla de administración
$zonas = $adminModel->getZonasEnvio();

$currentPage = 'zonas';
$pageTitle = 'Zonas de Envío';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/zonas.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
```

---

## 2. Módulo: Cliente

### Archivo: `cliente/catalogo.php`
```php
<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkClientOrGuest(); // Permite entrar si es cliente o si ni siquiera se ha logueado
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ProductoModel.php';

$db = getDatabaseConnection();
$productoModel = new ProductoModel($db);

$categorias = $productoModel->getCategorias(); // Lista los botones de filtros (Combos, Perros)

// Revisa si en la URL el cliente pinchó en una categoría (?categoria=2)
$categoriaActiva = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
// Trae los productos de esa categoría, o de todas si es 0
$productos = $productoModel->getProductos($categoriaActiva);

$currentPage = 'catalogo';
$pageTitle = 'Catálogo de Productos';

require_once __DIR__ . '/../../views/layouts/cliente_header.php';
require_once __DIR__ . '/../../views/cliente/catalogo.php';
require_once __DIR__ . '/../../views/layouts/cliente_footer.php';
```

### Archivo: `cliente/carrito.php`
```php
<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkClientOrGuest(); // Cualquiera puede tener carrito temporal
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ProductoModel.php';

// Si no existe un carrito en la sesión (la memoria de PHP), lo crea vacío
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$db = getDatabaseConnection();
$productoModel = new ProductoModel($db);

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// Evalúa qué botón de carrito presionó el cliente:
if ($action === 'add') { // Añadir al carrito
    $id = (int)($_POST['id_producto'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 1);
    
    if ($id > 0) {
        $prod = $productoModel->getProductoById($id); // Busca la info del producto en BD
        if ($prod && $prod['disponible']) {
            if (isset($_SESSION['carrito'][$id])) {
                $_SESSION['carrito'][$id]['cantidad'] += $cantidad; // Suma si ya estaba
            } else {
                $_SESSION['carrito'][$id] = [ // Lo mete por primera vez
                    'producto' => $prod,
                    'cantidad' => $cantidad
                ];
            }
        }
    }
    header('Location: catalogo.php');
    exit;
} elseif ($action === 'update') { // Modificar cantidades (+ o -)
    // ...
} elseif ($action === 'remove') { // Quitar producto específico
    $id = (int)($_POST['id_producto'] ?? 0);
    if ($id > 0 && isset($_SESSION['carrito'][$id])) {
        unset($_SESSION['carrito'][$id]); // Borra el item de la memoria
    }
    header('Location: carrito.php');
    exit;
} elseif ($action === 'clear') { // Vaciar todo
    $_SESSION['carrito'] = [];
    header('Location: carrito.php');
    exit;
}

$currentPage = 'carrito';
$pageTitle = 'Mi Carrito';

require_once __DIR__ . '/../../views/layouts/cliente_header.php';
require_once __DIR__ . '/../../views/cliente/carrito.php';
require_once __DIR__ . '/../../views/layouts/cliente_footer.php';
```

### Archivo: `cliente/checkout.php`
```php
<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkClientOnly(); // AQUI SI: Para pagar debe haber iniciado sesión como cliente (Rol 1)
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PedidoModel.php';

$currentUser = $_SESSION['user']; // Datos del comprador
$db = getDatabaseConnection();
$pedidoModel = new PedidoModel($db);

$zonasEnvio = $pedidoModel->getZonasEnvio(); // Para pintar el <select> de Zonas
$error = '';
$success = false;

// Si intenta entrar al checkout pero su carrito está vacío, lo regresa al catálogo
if (empty($_SESSION['carrito']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: catalogo.php');
    exit;
}

// Si envía el formulario con dirección y método de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $direccionData = [ // Agrupa los datos de envío
        'id_zona' => $_POST['id_zona'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'barrio' => $_POST['barrio'] ?? '',
        'referencia' => $_POST['referencia'] ?? ''
    ];
    $metodoPago = $_POST['metodo_pago'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';

    if (empty($direccionData['id_zona']) || empty($direccionData['direccion']) || empty($metodoPago)) {
        $error = "Por favor, completa los campos requeridos.";
    } else {
        $idCliente = (int)$currentUser['id'];
        
        // Pasa TODO: Cliente, Envío, el Carrito que estaba en Memoria, y el Pago a la BD
        $idPedido = $pedidoModel->createPedido($idCliente, $direccionData, $_SESSION['carrito'], $metodoPago, $observaciones);

        if ($idPedido) {
            $_SESSION['carrito'] = []; // Vacía la memoria del carrito, ya que se generó la compra exitosa
            $success = true;
        } else {
            $error = "Ocurrió un error al procesar tu pedido.";
        }
    }
}

$currentPage = 'checkout';
$pageTitle = 'Finalizar Compra';

require_once __DIR__ . '/../../views/layouts/cliente_header.php';
require_once __DIR__ . '/../../views/cliente/checkout.php';
require_once __DIR__ . '/../../views/layouts/cliente_footer.php';
```

### Archivo: `cliente/pedidos.php`
```php
<?php
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkClientOnly(); // Solo clientes pueden ver sus facturas
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/PedidoModel.php';

$currentUser = $_SESSION['user'];
$db = getDatabaseConnection();
$pedidoModel = new PedidoModel($db);

// Solicita al modelo el historial de pedidos de ESE cliente específicamente (filtra por su ID)
$misPedidos = $pedidoModel->getMisPedidos($currentUser['id']);

$currentPage = 'pedidos';
$pageTitle = 'Mis Pedidos';
require_once __DIR__ . '/../../views/layouts/cliente_header.php';
require_once __DIR__ . '/../../views/cliente/pedidos.php';
require_once __DIR__ . '/../../views/layouts/cliente_footer.php';
```

---

## 3. Módulo: Cocinera

### Archivo: `cocinera/pedidos.php`
```php
<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(3); // Verifica seguridad: Solo permite entrada al rol Cocinera
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/StaffModel.php';

$db = getDatabaseConnection();
$staffModel = new StaffModel($db);

// Si la cocinera aprieta el botón para avanzar el pedido (ej. de 'Pendiente' a 'Preparando')
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $idPedido = (int)$_POST['id_pedido'];
    $idEstado = (int)$_POST['id_estado'];
    if ($idPedido > 0 && $idEstado > 0) {
        $staffModel->updatePedidoStatus($idPedido, $idEstado); // Cambia el estado en la tabla pedidos
    }
    header('Location: pedidos.php');
    exit;
}

// Solo trae los pedidos que le importan a cocina (los que no han sido entregados al domi)
$pedidos = $staffModel->getPedidosCocina();

// Bucle crucial: Por CADA pedido, va y busca QUÉ productos se pidieron (las salchichas, combos, etc.)
foreach ($pedidos as &$pedido) {
    $pedido['detalles'] = $staffModel->getDetallesPedido($pedido['id_pedido']);
}

$currentPage = 'cocina';
$pageTitle = 'Panel de Cocina';

require_once __DIR__ . '/../../views/layouts/empleado_header.php';
require_once __DIR__ . '/../../views/cocinera/pedidos.php';
require_once __DIR__ . '/../../views/layouts/empleado_footer.php';
```

---

## 4. Módulo: Domiciliario

### Archivo: `domiciliario/rutas.php`
```php
<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(4); // Solo domiciliarios
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/StaffModel.php';

$db = getDatabaseConnection();
$staffModel = new StaffModel($db);

// Si el domiciliario marca el pedido como 'Entregado'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $idPedido = (int)$_POST['id_pedido'];
    $idEstado = (int)$_POST['id_estado'];
    if ($idPedido > 0 && $idEstado > 0) {
        $staffModel->updatePedidoStatus($idPedido, $idEstado); // Usa la misma función que cocina, pero con otros estados
    }
    header('Location: rutas.php');
    exit;
}

// Trae los pedidos que ya están listos y empacados, filtrando junto a la información de dirección del cliente
$rutas = $staffModel->getRutasDomicilio();

$currentPage = 'rutas';
$pageTitle = 'Rutas de Entrega';

require_once __DIR__ . '/../../views/layouts/empleado_header.php';
require_once __DIR__ . '/../../views/domiciliario/rutas.php';
require_once __DIR__ . '/../../views/layouts/empleado_footer.php';
```

---

## 5. Módulo: Auth (Autenticación)

### Archivo: `auth/login.php`
```php
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/AuthMiddleware.php';

enforceSecureSession(); // Asegura de que no haya sesión en caché atrapada

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$authController = new AuthController($userModel);

// Recibe el email y clave
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $result = $authController->handleLogin($_POST); // Se lo manda al controlador puro (ver AuthController.php)
    
    if ($result['success']) {
        // Si la clave era correcta, redirige basado en el rol (Si es 2 va a admin, si es 1 a cliente, etc)
        $rol = (int)($result['user']['rol'] ?? 1);
        header('Location: ' . getRoleRedirectUrl($rol));
        exit;
    }
}

// Si la clave falla o es la primera vez entrando, carga la vista HTML
require_once __DIR__ . '/../../views/auth/login.php';
```

### Archivo: `auth/register.php`
```php
<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/AuthController.php';

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$authController = new AuthController($userModel);

// Si llenó el formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $result = $authController->handleRegister($_POST); // Valida claves y lo inserta en la BD como Rol 1 (Cliente default)
}

require_once __DIR__ . '/../../views/auth/register.php';
```

*(Nota: Los archivos de `logout.php`, `forgot_password.php`, y `reset_password.php` siguen exactamente esta misma estructura: se conectan a `AuthController` y ejecutan su método correspondiente).*
