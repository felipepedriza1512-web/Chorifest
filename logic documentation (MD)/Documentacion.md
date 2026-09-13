
## 1. Conexión a la Base de Datos

La conexión a la base de datos se maneja de forma centralizada utilizando el patrón Singleton o una única función global que retorna la instancia de conexión.

### Archivo de Configuración
- **Ruta:** `config/database.php`
- **Método:** `getDatabaseConnection()`
- **Detalle:** Retorna una instancia del objeto `PDO`. Configura los atributos de conexión, maneja los errores mediante excepciones (`PDO::ERRMODE_EXCEPTION`) y establece el conjunto de caracteres. 
- **Uso:** En cada controlador, se invoca esta función para obtener la conexión y se inyecta en las clases de los Modelos (Dependency Injection).

---

## 2. Instancias y Modelos (Capa de Datos)

El sistema utiliza el patrón **MVC (Modelo-Vista-Controlador)** de forma estructurada. Los modelos se encargan de la lógica de negocio y de interactuar con la base de datos. Se instancian en los controladores pasándoles la conexión `$db`.

### Modelos Disponibles (`Models/`)

| Modelo | Descripción | Instanciación Común |
|---|---|---|
| **`UserModel`** | Gestión de usuarios (login, registro, recuperación de contraseña). | `$userModel = new UserModel($db);` |
| **`AdminModel`** | Lógica exclusiva del panel de administración (gestión de roles, estadísticas, zonas de envío, pedidos globales). | `$adminModel = new AdminModel($db);` |
| **`ProductoModel`** | Gestión del catálogo, categorías y detalles de los productos (tanto para visualización cliente como edición admin). | `$productoModel = new ProductoModel($db);` |
| **`PedidoModel`** | Manejo de pedidos de los clientes (creación, seguimiento, checkout, historial). | `$pedidoModel = new PedidoModel($db);` |
| **`StaffModel`** | Funciones específicas para empleados de nivel operativo (Cocinera, Domiciliario). | `$staffModel = new StaffModel($db);` |

---

## 3. Middleware y Seguridad de Sesiones

Para manejar la persistencia del usuario y proteger las rutas, se ha implementado un Middleware de Seguridad.

- **Archivo:** `controllers/auth/AuthMiddleware.php`
- **Funciones Principales:**
  - `enforceSecureSession()`: Inicia la sesión, aplica cabeceras anti-caché (`Cache-Control`) para evitar el retroceso del navegador a páginas protegidas, y ejecuta el temporizador de inactividad (30 minutos para auto-logout).
  - `checkRole($requiredRole)`: Verifica que el usuario tenga el ID de rol específico para acceder a ese panel. Si no, lo redirige al panel correspondiente.
  - `checkClientOrGuest()`: Garantiza que la página solo sea accedida por un Cliente (`rol = 1`) o un visitante anónimo (invitado). Restringe el acceso a roles de empleado (quienes son enviados a sus paneles).

---

## 4. Enrutamiento y Controladores

Al ser una arquitectura PHP nativa sin un enrutador frontal (Front-Controller Router como Laravel), **el enrutamiento se da a través de la estructura de carpetas física**. Cada archivo PHP en `controllers/` actúa como controlador de ruta (Endpoint).

### 4.1. Panel Público y Cliente (`controllers/cliente/`)

- **Ruta Pública Principal:** `/public/index.php` (Página de aterrizaje "Landing Page", accesible por invitados. Aplica restricciones visuales según sesión).
- **Catálogo:** `/controllers/cliente/catalogo.php`
  - *Función:* Muestra la cuadrícula dinámica de productos. Extrae datos de `ProductoModel`.
- **Carrito:** `/controllers/cliente/carrito.php`
  - *Función:* Gestiona la sesión temporal de productos elegidos, sumar/restar cantidades.
- **Checkout:** `/controllers/cliente/checkout.php`
  - *Función:* Procesa la confirmación del carrito, genera la orden final mediante `PedidoModel`.
- **Mis Pedidos:** `/controllers/cliente/pedidos.php`
  - *Función:* Panel privado del cliente para ver el historial y estado de sus órdenes.

### 4.2. Panel de Autenticación (`controllers/auth/`)

- **Login:** `/controllers/auth/login.php` (Validación de credenciales).
- **Register:** `/controllers/auth/register.php` (Creación de nueva cuenta Cliente).
- **Logout:** `/controllers/auth/logout.php` (Destrucción total de `$_SESSION` y cookies físicas).
- **Recuperación:** `/controllers/auth/forgot_password.php` y `reset_password.php`.
- **`AuthController.php`:** Clase auxiliar que orquesta y encapsula la lógica de los formularios (ej. `handleLogin()`, `handleRegister()`).

### 4.3. Panel de Administrador (`controllers/admin/`)
*Módulo protegido: Requiere Rol 2.*

- **Dashboard:** `/controllers/admin/dashboard.php`
  - *Función:* Pantalla principal, resumen de métricas y estadísticas operativas (`AdminModel->getDashboardStats()`).
- **Gestión de Productos:** `/controllers/admin/productos.php`
  - *Función:* CRUD (Crear, Leer, Actualizar, Ocultar) del catálogo. Usa `ProductoModel`.
- **Gestión de Pedidos:** `/controllers/admin/pedidos.php`
  - *Función:* Vista global para administrar todas las órdenes del sistema.
- **Zonas de Envío:** `/controllers/admin/zonas.php`
  - *Función:* Parametrización de las tarifas de domicilio y tiempos de entrega.

### 4.4. Panel de Cocinera (`controllers/cocinera/`)
*Módulo protegido: Requiere Rol 3.*

- **Módulo de Pedidos:** `/controllers/cocinera/pedidos.php`
  - *Función:* Tablero de producción (Kanban-style o lista) para cambiar el estado de las órdenes de "Recibido" a "En preparación" y finalmente "Listo para envío". Usa `StaffModel`.

### 4.5. Panel de Domiciliario (`controllers/domiciliario/`)
*Módulo protegido: Requiere Rol 4.*

- **Módulo de Rutas/Entregas:** `/controllers/domiciliario/rutas.php`
  - *Función:* Interfaz para los repartidores. Muestra los pedidos listos para despacho. Permite marcarlos como "En Camino" y "Entregado". Usa `StaffModel`.

---

## 5. Flujo de Ejecución Típico (Ciclo de Vida)

Para cualquier petición a un panel protegido, este es el orden de carga lógico:

1. **HTTP Request** al controlador (ej. `admin/productos.php`).
2. Se incluye el `AuthMiddleware.php` y se llama a `checkRole(2);`.
3. El Middleware carga la sesión segura, destruye caché y verifica acceso.
4. Se importa `config/database.php` y se inyecta en el Modelo requerido (ej. `ProductoModel`).
5. El controlador procesa las peticiones `$_POST` (formularios, acciones CRUD).
6. El controlador obtiene los datos limpios llamando a los métodos del Modelo (ej. `$productos = $productoModel->getProductos();`).
7. El controlador incluye las vistas finales (`views/layouts/admin_header.php`, `views/admin/productos.php`, `footer`).

---

## 6. Especificación de Actores

### Actor: Administrador (Identificador: ACT-001)
- **Descripción:** El administrador es el que va a poder gestionar y mover los pedidos entrantes.
- **Características:** Maneja el sistema moviendo y agregando productos y precios nuevos. Puede rechazar y aceptar pedidos.
- **Relación:** Su relación principal es con el cliente. Al recibir un pedido, el administrador lo acepta y lo despacha hacia la cocinera.
- **Referencias:** RQ1 (Manejar y editar el menú), RQ2 (Ver y Mover Pedidos), RQ3 (Configurar los Envíos), RQ4 (Sacar Cuentas y Reportes), RQ5 (Controlar lo que hay en Bodega).
- **Comentarios:** Este actor es el único autorizado para modificar los costos de producción y ver los reportes de ventas totales.

**Atributos:**
| Nombre | Descripción | Tipo |
|---|---|---|
| Nombre | El nombre completo del administrador. | Texto |
| Password | La contraseña con la cual podrá ingresar al sistema. | Password |
| Teléfono | El número de contacto para fines de comunicación, alertas y gestión. | Teléfono |
| Gmail | Se podría usar como fuente de mensajes del personal. | Gmail |
| Contraseña | Contraseña vinculada al correo. | Contraseña |
| Edad | Para identificar la edad del administrador. | Edad |

### Actor: Cliente (Identificador: ACT-002)
- **Descripción:** El Cliente podrá ver los productos disponibles para escoger el producto y la cantidad deseada.
- **Características:** Podrá llenar el carrito con los productos deseados y visualizar su pedido en tiempo real al ordenarlo.
- **Relación:** Su relación más importante es con el administrador, ya que al hacer el pedido el mensaje le llega a este para que lo acepte o rechace.
- **Referencias:** RQ1 (Gestión de Pedidos Sencillos), RQ2 (Pagos flexibles), RQ3 (Seguimiento del pedido), RQ4 (Guardar direcciones con frecuencia), RQ5 (Calificación y comentarios).
- **Comentarios:** Este actor es el que hace mover el sistema ya que es el que hace el pedido.

**Atributos:**
| Nombre | Descripción | Tipo |
|---|---|---|
| Nombre | El nombre completo del Cliente. | Texto |
| Password | La contraseña con la cual podrá ingresar al sistema. | Password |
| Teléfono | Número de contacto telefónico para fines de promociones. | Teléfono |
| Gmail | Se podría usar para enviarse invitaciones y ofertas. | Gmail |
| Contraseña | Contraseña vinculada al correo. | Contraseña |
| Barrio | Para calcular el tiempo de entrega y verificar la zona de cobertura. | Barrio |

### Actor: Domiciliario (Identificador: ACT-003)
- **Descripción:** El domiciliario podrá ver la información del cliente.
- **Características:** Al entregar un pedido, marcará el botón (Pedido exitoso). Si hay contratiempos presionará (Pedido en proceso). Si el cliente lo rechaza, presionará (Pedido fallido).
- **Relación:** Su relación directa es con el cliente, retomando su información y permitiendo que sus movimientos sean vistos en tiempo real.
- **Referencias:** RQ1 (Visualizar lista de asignados), RQ2 (Cambiar estado a Entregado), RQ3 (Registrar problemas), RQ4 (Mostrar caja), RQ5 (Acceder a ubicación en Google Maps).

**Atributos:**
| Nombre | Descripción | Tipo |
|---|---|---|
| Nombre | El nombre completo del Domiciliario. | Texto |
| Password | La contraseña con la cual podrá ingresar al sistema. | Password |
| Teléfono | Número de contacto. | Teléfono |
| Gmail | Correo electrónico de contacto. | Gmail |
| Contraseña | Contraseña vinculada al correo. | Contraseña |
| Placa_Vehiculo | Para que el cliente sepa qué vehículo busca y registrar las entregas. | Texto |
| Tipo_Vehiculo | Afecta el tiempo de entrega y la zona que puede cubrir. | Texto |

### Actor: Cocinera (Identificador: ACT-004)
- **Descripción:** La cocinera es la que prepara el producto y marcará “EN PREPARACION” y “LISTO”.
- **Características:** Al llegar la notificación, marca en preparación. Luego de empacar, marca listo para que el Admin despache.
- **Relación:** La relación directa de la cocinera será con el administrador.
- **Referencias:** RQ1 (Visualizar pedidos entrantes), RQ2 (Marcar preparación o listo), RQ3 (Descontar inventario de la carne), RQ4 (Notificar productos agotados).

**Atributos:**
| Nombre | Descripción | Tipo |
|---|---|---|
| Nombre | El nombre completo de la Cocinera. | Texto |
| Password | La contraseña para ingresar al sistema. | Password |
| Teléfono | Número de contacto. | Teléfono |
| Gmail | Correo electrónico. | Gmail |
| Contraseña | Contraseña vinculada al correo. | Contraseña |
| Turno | Para asignarle pedidos correctamente y evitar órdenes fuera de jornada. | Texto |

---

## 7. Especificación de Casos de Uso

A continuación, se presenta la especificación detallada de cada caso de uso identificado en el sistema de gestión para Chorifest, agrupados por módulo funcional.

### 7.1. Sistema de Logueo
#### CU-001: Iniciar Sesión
- **Actores:** Administrador, Cliente, Cocinera, Domiciliario (ACT-001, ACT-002, ACT-003, ACT-004)
- **Tipo:** Primario
- **Precondición:** El usuario debe estar registrado previamente en el sistema y contar con credenciales activas.
- **Postcondición:** El sistema valida las credenciales y redirige al usuario a su panel correspondiente.
- **Resumen:** Gestionar el ingreso seguro al sistema para operar el flujo de pedidos.

**Curso Normal:**
1. **Usuario:** Ingresa sus credenciales (correo electrónico y contraseña).
2. **Sistema:** Recibe la solicitud, encripta y consulta en la BD la validez del usuario.
3. **Sistema:** Verifica el rol asignado y genera un token (sesión activa).
4. **Sistema:** Muestra el panel interactivo correspondiente al rol.

**Cursos Alternos:**
- *Credenciales incorrectas:* Muestra mensaje "Usuario o contraseña incorrectos" y permite reintentar.
- *Campos vacíos:* Resalta los campos en rojo solicitando completarlos.
- *Usuario inactivo:* Bloquea ingreso y pide contactar al Administrador.

### 7.2. Módulo del Cliente
#### CU-CL01: Realizar Pedido
- **Actores:** Cliente (ACT-002)
- **Tipo:** Primario
- **Precondición:** Tener sesión iniciada y productos en el carrito.
- **Postcondición:** El pedido se registra con estado "Pendiente" y llega al Administrador.

**Curso Normal:**
1. **Cliente:** Revisa carrito de compras y presiona "Confirmar Pedido".
2. **Cliente:** Selecciona/ingresa dirección de entrega, barrio y teléfono.
3. **Sistema:** Calcula el valor total incluyendo el costo de envío.
4. **Cliente:** Confirma la transacción.
5. **Sistema:** Registra el pedido y notifica en tiempo real al Administrador.

**Cursos Alternos:**
- *Fuera de cobertura:* Si el barrio está fuera de zona, alerta que no es posible el envío.
- *Producto sin stock:* Si se agotó un producto, el sistema notifica y ajusta el carrito.

#### CU-CL02: Rastrear Pedido
- **Actores:** Cliente (ACT-002)
- **Tipo:** Secundario
- **Precondición:** El cliente debe tener un pedido activo confirmado.
- **Postcondición:** Despliega en pantalla el estado actual y avance en tiempo real.

**Curso Normal:**
1. **Cliente:** Accede a "Mis Pedidos" y selecciona el pedido activo.
2. **Sistema:** Obtiene el estado actual en la base de datos.
3. **Sistema:** Muestra la línea de tiempo del estado e información del domiciliario.

**Cursos Alternos:**
- *Sin pedidos:* El sistema muestra "No tienes pedidos en curso en este momento".

### 7.3. Módulo del Administrador
#### CU-AD01: Supervisar Pedidos
- **Actores:** Administrador (ACT-001)
- **Tipo:** Primario
- **Precondición:** Existen pedidos ingresados por los clientes.
- **Postcondición:** El pedido es aceptado/rechazado o asignado.

**Curso Normal:**
1. **Admin:** Visualiza la lista de pedidos entrantes.
2. **Admin:** Selecciona un pedido y presiona "Aceptar Pedido".
3. **Sistema:** Cambia el estado a "Aceptado" y lo envía a la Cocinera.
4. **Admin:** Cuando cocina marca "Listo", selecciona un Domiciliario disponible y asigna.

**Cursos Alternos:**
- *Rechazar pedido:* Si selecciona "Rechazar", ingresa motivo y el sistema notifica la cancelación al cliente.

#### CU-AD02: Gestionar Menú
- **Actores:** Administrador (ACT-001)
- **Tipo:** Primario
- **Precondición:** Haber iniciado sesión.
- **Postcondición:** El menú se actualiza con nuevos productos/precios.

**Curso Normal:**
1. **Admin:** Accede al módulo "Gestión de Menú" (Catálogo).
2. **Admin:** Selecciona crear o modificar.
3. **Admin:** Ingresa nombre, descripción, precio y foto.
4. **Sistema:** Actualiza el catálogo en tiempo real.

**Cursos Alternos:**
- *Datos inválidos:* Si el precio es <= 0, exige valor válido.

### 7.4. Módulo de la Cocinera
#### CU-CO01: Ver Pedidos Entrantes
- **Actores:** Cocinera (ACT-004)
- **Tipo:** Primario
- **Precondición:** Pedido aceptado por el Administrador.
- **Postcondición:** Pedido visible marcado como "Recibido en cocina".

**Curso Normal:**
1. **Sistema:** Recibe la orden aceptada.
2. **Sistema:** Emite alerta sonora/visual.
3. **Cocinera:** Visualiza los detalles y cantidades.
4. **Sistema:** Actualiza estado a "Recibido en cocina".

**Cursos Alternos:**
- *Pérdida de conexión:* Almacena órdenes y sincroniza al reconectar.

#### CU-CO02: Marcar como Listo
- **Actores:** Cocinera (ACT-004)
- **Tipo:** Primario
- **Precondición:** Pedido en lista de comandas.
- **Postcondición:** Cambia estado a "Listo para despacho".

**Curso Normal:**
1. **Cocinera:** Presiona "En preparación".
2. **Cocinera:** Al finalizar el empaque, presiona "Listo para despacho".
3. **Sistema:** Actualiza BD y notifica al Administrador.

**Cursos Alternos:**
- *Corrección de error:* Permite revertir el estado en los siguientes 2 minutos.

### 7.5. Módulo del Domiciliario
#### CU-DO01: Ver Pedido Asignado
- **Actores:** Domiciliario (ACT-003)
- **Tipo:** Primario
- **Precondición:** Pedido asignado por el Admin.
- **Postcondición:** Accede a datos de entrega y mapa.

**Curso Normal:**
1. **Sistema:** Notifica asignación.
2. **Domiciliario:** Abre la aplicación y selecciona el pedido.
3. **Domiciliario:** Visualiza dirección y presiona "Ver mapa".
4. **Sistema:** Abre Google Maps / Waze con ruta precargada.

**Cursos Alternos:**
- *Sin confirmación:* Si no lo abre en 5 minutos, alerta al Admin.

#### CU-DO02: Cambiar Estado y Novedades
- **Actores:** Domiciliario (ACT-003)
- **Tipo:** Primario
- **Precondición:** Domiciliario en proceso de entrega.
- **Postcondición:** Marca como "Entregado" o registra novedad.

**Curso Normal:**
1. **Domiciliario:** Llega al cliente.
2. **Domiciliario:** Presiona "Pedido Exitoso / Entregado".
3. **Sistema:** Actualiza estado y notifica a Cliente y Admin.

**Cursos Alternos:**
- *Registro novedad:* Si hay problema, selecciona "Reportar Novedad".
- *Sin datos móviles:* Guarda estado y sincroniza luego.


**Database.php**
Archivo centralizado para la gestión de la conexión a la base de datos MySQL del sistema Chorifest. Simplifica el mantenimiento y asegura la consistencia en el manejo de conexiones.

<?php

function getDatabaseConnection(): PDO
{
    $host = '127.0.0.1';
    $user = 'root';
    $password = '';
    $dbname = 'chorizo_express';
    $port = 3306;

    try {
        // Nos conectamos directamente a la base de datos chorizo_express
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;port=$port", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    } catch (PDOException $e) {
        die('Error de conexión a la base de datos: ' . $e->getMessage());
    }
}

**AuthController.php**
Controlador central que gestiona toda la lógica relacionada con la autenticación y gestión de usuarios en el sistema Chorifest. Maneja el registro, inicio de sesión, cierre de sesión, recuperación de contraseña y validaciones de roles.

<?php

class AuthController
{
    private UserModel $userModel;

    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    public function handleRegister(array $input): array
    {
        $password = $input['password'] ?? '';
        $confirmPassword = $input['password_confirm'] ?? '';

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        return $this->userModel->register(
            $input['name'] ?? '',
            $input['telefono'] ?? '',
            $input['email'] ?? '',
            $password
        );
    }

    public function handleLogin(array $input): array
    {
        $result = $this->userModel->login(
            $input['email'] ?? '',
            $input['password'] ?? ''
        );

        if ($result['success'] ?? false) {
            $_SESSION['user'] = $result['user'];
            session_regenerate_id(true);
        }

        return $result;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }

    public function handleForgotPassword(array $input): array
    {
        $email = strtolower(trim($input['email'] ?? ''));

        if ($email === '') {
            return ['success' => false, 'message' => 'Ingresa tu correo electrónico.'];
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            return ['success' => true, 'message' => 'Si el correo existe, se enviará un código de recuperación.'];
        }

        $code = sprintf("%06d", mt_rand(1, 999999));
        
        if ($this->userModel->saveRecoveryCode($email, $code)) {
            $subject = "Código de Recuperación - Chorifest";
            $message = "Tu código de recuperación es: " . $code . "\nExpira en 15 minutos.";
            $headers = "From: noreply@chorifest.local\r\n";
            
            mail($email, $subject, $message, $headers);
        }

        return ['success' => true, 'message' => 'Si el correo existe, se enviará un código de recuperación.'];
    }

    public function handleResetPassword(array $input): array
    {
        $email = strtolower(trim($input['email'] ?? ''));
        $code = trim($input['code'] ?? '');
        $password = $input['password'] ?? '';
        $confirmPassword = $input['password_confirm'] ?? '';

        if ($email === '' || $code === '' || $password === '') {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        if (!$this->userModel->verifyRecoveryCode($email, $code)) {
            return ['success' => false, 'message' => 'El código de recuperación es incorrecto o ha expirado.'];
        }

        if ($this->userModel->updatePassword($email, $password)) {
            return ['success' => true, 'message' => 'Contraseña actualizada correctamente.'];
        }

        return ['success' => false, 'message' => 'Hubo un error al actualizar la contraseña.'];
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }
}

**AuthMiddleware.php**
Archivo que implementa un middleware para gestionar la autenticación y autorización de usuarios en el sistema Chorifest. Se encarga de validar si un usuario está autenticado y si tiene el rol adecuado para acceder a determinadas rutas.

<?php

function enforceSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Prevent caching of secured pages
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    // Session timeout (e.g. 30 minutes)
    $timeout_duration = 1800;
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header('Location: /controllers/auth/login.php');
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

function checkRole(int $requiredRole) {
    enforceSecureSession();
    
    if (!isset($_SESSION['user'])) {
        header('Location: /controllers/auth/login.php');
        exit;
    }
    
    $userRole = (int)($_SESSION['user']['rol'] ?? 1);
    
    if ($userRole !== $requiredRole) {
        if ($userRole === 2) {
            header('Location: /controllers/admin/dashboard.php');
        } elseif ($userRole === 3) {
            header('Location: /controllers/cocinera/pedidos.php');
        } elseif ($userRole === 4) {
            header('Location: /controllers/domiciliario/rutas.php');
        } else {
            header('Location: /controllers/cliente/catalogo.php');
        }
        exit;
    }
}

function checkClientOrGuest() {
    enforceSecureSession();
    
    if (isset($_SESSION['user'])) {
        $userRole = (int)($_SESSION['user']['rol'] ?? 1);
        if ($userRole !== 1) {
            if ($userRole === 2) {
                header('Location: /controllers/admin/dashboard.php');
            } elseif ($userRole === 3) {
                header('Location: /controllers/cocinera/pedidos.php');
            } elseif ($userRole === 4) {
                header('Location: /controllers/domiciliario/rutas.php');
            }
            exit;
        }
    }
}

**forgot_password.php**
Archivo PHP que implementa la lógica para la recuperación de contraseña en el sistema Chorifest. Maneja el envío del formulario de solicitud de recuperación y la validación del código de recuperación.

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/AuthController.php';

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$authController = new AuthController($userModel);

if ($authController->isLoggedIn()) {
    header('Location: ../../public/index.php');
    exit;
}

$message = null;
$isSuccess = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'forgot_password') {
    $result = $authController->handleForgotPassword($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];

    if ($isSuccess) {
        header('Location: reset_password.php?email=' . urlencode($_POST['email']));
        exit;
    }
}

require_once __DIR__ . '/../../views/auth/forgot_password.php';

**login.php** 
Archivo PHP que implementa la lógica para el inicio de sesión en el sistema Chorifest. Maneja el envío del formulario de inicio de sesión y la validación de credenciales.
 
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/AuthController.php';

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$authController = new AuthController($userModel);

// Redirigir si ya está logueado
if ($authController->isLoggedIn()) {
    $rol = $_SESSION['user']['rol'] ?? 1;
    if ($rol == 2) {
        header('Location: ../admin/dashboard.php');
    } elseif ($rol == 3) {
        header('Location: ../cocinera/pedidos.php');
    } elseif ($rol == 4) {
        header('Location: ../domiciliario/rutas.php');
    } else {
        header('Location: ../cliente/catalogo.php');
    }
    exit;
}

$message = null;
$isSuccess = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $result = $authController->handleLogin($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];

    if ($isSuccess) {
        $rol = $_SESSION['user']['rol'] ?? 1;
        if ($rol == 2) {
            header('Location: ../admin/dashboard.php');
        } elseif ($rol == 3) {
            header('Location: ../cocinera/pedidos.php');
        } elseif ($rol == 4) {
            header('Location: ../domiciliario/rutas.php');
        } else {
            header('Location: ../cliente/catalogo.php');
        }
        exit;
    }
}

// Cargar la vista de login
require_once __DIR__ . '/../../views/auth/login.php';

**logout.php**
Archivo PHP que implementa la lógica para cerrar la sesión en el sistema Chorifest. Maneja el cierre de sesión y redirige al usuario a la página de inicio.

<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';

enforceSecureSession();

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: login.php');
exit;

**register.php**
Archivo PHP que implementa la lógica para el registro de usuarios en el sistema Chorifest. Maneja el envío del formulario de registro y la validación de datos.

<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';

enforceSecureSession();

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: login.php');
exit;

**reset_password.php**
Archivo PHP que implementa la lógica para el restablecimiento de contraseña en el sistema Chorifest. Maneja el envío del formulario de restablecimiento y la validación de datos.

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/AuthController.php';

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$authController = new AuthController($userModel);

if ($authController->isLoggedIn()) {
    header('Location: ../../public/index.php');
    exit;
}

$message = null;
$isSuccess = true;
$email = $_GET['email'] ?? ($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    $result = $authController->handleResetPassword($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];
}

require_once __DIR__ . '/../../views/auth/reset_password.php';

**Admin**
**dashboard.php**
Archivo PHP que implementa la lógica para el panel de control del administrador en el sistema Chorifest. Maneja la visualización de métricas, estadísticas y acceso a las funcionalidades del sistema.

<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';

enforceSecureSession();

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: login.php');
exit;

**empleados.php**
Archivo PHP que implementa la lógica para la gestión de empleados en el sistema Chorifest. Maneja la visualización, creación, edición y eliminación de empleados.

<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2); // Only admin can access
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'id_rol' => (int)($_POST['id_rol'] ?? 0),
            'estado' => $_POST['estado'] ?? 'ACTIVO'
        ];
        
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        // Specific role data
        if ($data['id_rol'] === 3) {
            $data['turno'] = trim($_POST['turno'] ?? '');
        } elseif ($data['id_rol'] === 4) {
            $data['tipo_vehiculo'] = trim($_POST['tipo_vehiculo'] ?? '');
            $data['placa'] = trim($_POST['placa'] ?? '');
            $data['estado_domiciliario'] = trim($_POST['estado_domiciliario'] ?? 'ACTIVO');
        }

        if ($action === 'create') {
            $adminModel->createEmpleado($data);
        } else {
            $id = (int)$_POST['id_usuario'];
            $adminModel->updateEmpleado($id, $data);
        }
        
        header('Location: empleados.php');
        exit;
    }
}

$empleados = $adminModel->getEmpleados();

$currentPage = 'empleados';
$pageTitle = 'Gestión de Personal';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/empleados.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';

**pedidos.php**
Archivo PHP que implementa la lógica para la gestión de pedidos en el sistema Chorifest. Maneja la visualización, creación, edición y eliminación de pedidos.       

<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $idPedido = (int)$_POST['id_pedido'];
    $idEstado = (int)$_POST['id_estado'];
    if ($idPedido > 0 && $idEstado > 0) {
        $adminModel->updatePedidoStatus($idPedido, $idEstado);
    }
    header('Location: pedidos.php');
    exit;
}

$pedidos = $adminModel->getPedidos();
$estados = $adminModel->getEstadosPedido();

$currentPage = 'pedidos';
$pageTitle = 'Gestión de Pedidos';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/pedidos.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';

**productos.php**
Archivo PHP que implementa la lógica para la gestión de productos en el sistema Chorifest. Maneja la visualización, creación, edición y eliminación de productos.

<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2); // Only admin can access
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
    
    $data = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
        'precio' => (float)($_POST['precio'] ?? 0),
        'categoria' => trim($_POST['categoria'] ?? ''),
        'estado' => $_POST['estado'] ?? 'ACTIVO'
    ];

    // Handle file upload
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../public/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $imagePath = 'images/' . basename($_FILES['imagen']['name']);
        $targetFile = $uploadDir . basename($_FILES['imagen']['name']);
        
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetFile)) {
            $data['imagen'] = $imagePath;
        }
    }

    if ($action === 'create') {
        $adminModel->createProducto($data);
    } elseif ($action === 'update') {
        $adminModel->updateProducto($idProducto, $data);
    }
    
    header('Location: productos.php');
    exit;
}

$productos = $adminModel->getProductos();
$categorias = ['CHORIZOS', 'COMPLEMENTOS', 'BEBIDAS'];

$currentPage = 'productos';
$pageTitle = 'Gestión de Productos';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/productos.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';

**zonas.php**
Archivo PHP que implementa la lógica para la gestión de zonas en el sistema Chorifest. Maneja la visualización, creación, edición y eliminación de zonas.

<?php
session_start();
require_once __DIR__ . '/../auth/AuthMiddleware.php';
checkRole(2);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/AdminModel.php';

$db = getDatabaseConnection();
$adminModel = new AdminModel($db);

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

$zonas = $adminModel->getZonasEnvio();

$currentPage = 'zonas';
$pageTitle = 'Zonas de Envío';

require_once __DIR__ . '/../../views/layouts/admin_header.php';
require_once __DIR__ . '/../../views/admin/zonas.php';
require_once __DIR__ . '/../../views/layouts/admin_footer.php';
