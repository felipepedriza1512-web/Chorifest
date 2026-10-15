# Chorifest - Gourmet Grill System

Sistema web de gestión de pedidos, cocina, domicilios y administración para Chorifest.

## ⚠️ CHECKLIST OBLIGATORIO: ANTES DE PUBLICAR EN PRODUCCIÓN

> **IMPORTANTE:** Cualquier copia o despliegue anterior a la corrección del auto-login  
> (commit `513e33b`, 12-Sep-2026) debe considerarse **COMPLETAMENTE INSEGURA** y destruirse.

### 1. 🔑 Configurar el archivo `.env` en el servidor

**Nunca subas credenciales al repositorio.** Las credenciales de producción se leen del archivo `.env` (que NO está en git).

```bash
# En el servidor (vía FTP o panel de archivos):
# 1. Copia .env.example → .env
# 2. Edita .env con los datos REALES del nuevo hosting
cp .env.example .env
nano .env
```

Variables obligatorias en `.env`:
```ini
DB_HOST=sql_NUEVO_HOST
DB_USER=if0_NUEVO_USUARIO
DB_PASS=NUEVA_CONTRASENA_SEGURA
DB_NAME=if0_NUEVO_NOMBRE_DB
APP_ENV=production
PHP_ERROR_LOG=/home/tu_usuario/logs/chorifest_errors.log
```

### 2. 🗄️ Base de datos de producción nueva (limpia)

Usa `database/install_production.sql`:
- Crea roles, estados, categorías y zonas de envío.
- Crea **1 solo administrador** con contraseña temporal `Chorifest@2026!`.
- El campo `debe_cambiar_password = 1` obliga a cambiar la contraseña en el primer login (implementar en `AuthController`).
- **NO incluye** usuarios de prueba ni datos inventados.

```bash
mysql -u usuario -p nombre_base < database/install_production.sql
```

### 3. 🚫 Archivos que NO se suben al hosting

Excluir de FTP/Git del servidor de producción:

| Carpeta/Archivo | Motivo |
|---|---|
| `tests/` | Scripts de prueba automatizados |
| `scripts/` | Herramientas de mantenimiento y depuración |
| `docs/`, `documentation/` | Documentación interna |
| `.git/` | Historial de código fuente |
| `*.sql` locales | Volcados con datos de prueba |
| `storage/backups/` | Respaldos locales |
| `node_modules/` | Dependencias de desarrollo |
| `.env` | Credenciales reales (nunca en git) |

### 4. 🔒 HTTPS obligatorio y cookie Secure

- Configura SSL/TLS en el panel del hosting (gratuito vía Let's Encrypt en Hostinger, cPanel, etc.).
- Agrega redirección 301 HTTP → HTTPS en el `.htaccess`.
- La cookie de sesión se marca automáticamente como `Secure` cuando `$_SERVER['HTTPS']` está activo.
- Descomentar la línea `Strict-Transport-Security` en `.htaccess` después de confirmar HTTPS.

### 5. 👥 Usuarios de prueba eliminados

Los siguientes usuarios **NUNCA** deben existir en producción:

| Correo | Rol | Contraseña conocida |
|---|---|---|
| `admin@gmail.com` | Administrador | `123456` |
| `cocina@gmail.com` | Cocinera | `123456` |
| `domi@gmail.com` | Domiciliario | `123456` |
| `juan@gmail.com` | Cliente | `123456` |
| `andres@chorizoexpress.com` | Cliente (hash000X) | hash inválido, sin acceso |

### 6. ⏰ Zona horaria (Colombia UTC-5)

- PHP: `date_default_timezone_set('America/Bogota')` en `config/database.php`.
- MySQL: `SET time_zone = '-05:00'` en cada conexión PDO.
- Si el hosting restringe `SET time_zone`, configurar `date.timezone = America/Bogota` en `php.ini`.

### 7. 📋 Política de contraseñas mínimas

Contraseñas deben:
- Tener mínimo **8 caracteres**
- No ser triviales: `123456`, `password`, `12345678`, `admin` están bloqueadas
- Aplicar en registro, login (feedback) y recuperación de contraseña

### 8. 🪵 display_errors desactivado en producción

`config/database.php` desactiva automáticamente `display_errors` cuando `APP_ENV=production`.  
Los errores se escriben en la ruta `PHP_ERROR_LOG` configurada en `.env` (fuera del directorio público).

---


## Despliegue en Hosting y Configuración de Zona Horaria

Al desplegar la aplicación en un servidor de hosting público (ej. InfinityFree, Hostinger, cPanel, Nginx, AWS, Heroku, etc.), ten en cuenta las siguientes recomendaciones clave:

### 1. Zona Horaria (PHP y MySQL PDO)
- En PHP, la zona horaria predeterminada se establece en `config/database.php` mediante:
  ```php
  date_default_timezone_set('America/Bogota');
  ```
- En la conexión PDO de MySQL (`getDatabaseConnection()`), se ejecuta la instrucción a nivel de sesión de conexión:
  ```php
  $pdo->exec("SET time_zone = '-05:00';");
  ```
- **Comprobación de Columnas TIMESTAMP:** Esta instrucción solo ajusta la zona horaria para la conexión activa de PDO. No modifica los valores físicos almacenados en las columnas `TIMESTAMP` ni `DATETIME` de la base de datos local.
- **Qué revisar en el Hosting:** Verifique que el servidor de hosting permita `SET time_zone`. Si el proveedor restringe esta orden en MySQL, configure la zona horaria predeterminada directamente en el archivo `php.ini` (`date.timezone = America/Bogota`) o en la variable global del motor MySQL (`default_time_zone = '-05:00'`).

### 2. Protección de Comprobantes (.htaccess vs Nginx)
- Los archivos `.htaccess` creados en `public/img/comprobantes/.htaccess` y `storage/comprobantes/.htaccess` con `Require all denied` funcionan exclusivamente en servidores web **Apache** o **LiteSpeed**.
- **Servidores Nginx:** Nginx ignora por completo los archivos `.htaccess`. Al desplegar en Nginx, debe realizar una de las siguientes opciones:
  1. Mover la carpeta de comprobantes **fuera** del directorio público `public/` (por ejemplo, en `storage/comprobantes/`).
  2. Bloquear el acceso a la carpeta en el archivo de configuración del sitio Nginx:
     ```nginx
     location ~ ^/public/img/comprobantes/ {
         deny all;
         return 403;
     }
     ```

### 3. Cookies de Sesión (HttpOnly y SameSite=Lax)
- La configuración de seguridad de sesión se centraliza en `config/database.php` y `controllers/auth/AuthMiddleware.php`:
  ```php
  session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
      'httponly' => true,
      'samesite' => 'Lax'
  ]);
  ```

### 4. Migraciones y Reversión (Rollback)
- **Fase 2 Migración:** `sql/migration_pago_devolucion.sql` (agrega columna `pendiente_devolucion TINYINT(1) NOT NULL DEFAULT 0` en tabla `pago`).
- **Fase 2 Rollback:** `sql/rollback_pago_devolucion.sql` (elimina la columna `pendiente_devolucion`).
- **Respaldos (mysqldump):** Almacenados en `storage/backups/`.

### 5. Reportes de Consumo de Inventario y Tipos de Salida
En la tabla `movimiento_inventario`, existen dos tipos de movimientos que representan salidas de insumos:
1. `SALIDA`: Registros antiguos o manuales de descarga de inventario.
2. `SALIDA_RECETA`: Nuevos registros automáticos generados al empacar un pedido según la receta de los productos.

**IMPORTANTE PARA REPORTES FUTUROS:**
Cualquier módulo, vista o consulta analítica de consumo total o reporte de costos de inventario debe sumar ambos tipos de movimiento (`tipo IN ('SALIDA', 'SALIDA_RECETA')`) para asegurar que el cálculo histórico y actual del consumo de ingredientes sea 100% exacto y consistente.

---

## Límites conocidos

- **Descuento de Bebidas en Combos:** En los productos tipo combo, la bebida descontada del inventario corresponde al insumo estándar configurado en la receta base del producto, incluso si el cliente selecciona una variante alternativa de bebida al momento de realizar su orden.


