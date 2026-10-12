# BiblioTech — Sistema de Biblioteca Virtual

Sistema web de gestión bibliotecaria desarrollado en **PHP puro** (sin frameworks),
**MySQL/MariaDB** y **PDO**, pensado para ejecutarse en **XAMPP** desde
`http://localhost/BiblioTech/`.

Cumple los requerimientos funcionales **RF01–RF13** y los no funcionales
**RNF01–RNF09** de las instrucciones del curso (`docs/RF-BiblioTech.pdf`).

---

## Requisitos

| Componente | Versión utilizada |
|---|---|
| PHP | 8.2.12 (extensiones `pdo_mysql`, `mbstring`, `fileinfo`) |
| MariaDB / MySQL | 10.4.32 |
| Servidor web | Apache (XAMPP) |
| Base de datos | `Bibliotec` |

## Tecnologías

- PHP puro con estructura MVC sencilla y sin dependencias externas.
- PDO con consultas preparadas (anti inyección SQL).
- HTML5, CSS3 y JavaScript sin frameworks de front-end.
- Bootstrap no se utiliza: la interfaz usa CSS propio para respetar la identidad visual.

---

## Instalación en XAMPP

1. **Iniciar los servicios.** Abre el Panel de Control de XAMPP y arranca
   **Apache** y **MySQL**.

2. **Copiar el proyecto en `htdocs`.** La carpeta debe quedar dentro del
   directorio web de XAMPP, por ejemplo:

   ```
   C:\xampp\htdocs\BiblioTech\index.php
   ```

   Si el proyecto está en otra ubicación, basta con un enlace simbólico
   (o mover la carpeta):

   ```
   mklink /J "C:\xampp\htdocs\BiblioTech" "C:\ruta\al\proyecto"
   ```

3. **Crear e importar la base de datos.** La aplicación **no crea** la base de
   datos: se conecta a una existente. Créala vacía e importa el volcado incluido.

   Desde phpMyAdmin (`http://localhost/phpmyadmin`):

   - *Nueva* → nombre `Bibliotec` → cotejamiento `utf8mb4_general_ci`.
   - Pestaña *Importar* → selecciona
     [`database/bibliotech.demo.sql`](database/bibliotech.demo.sql) → *Continuar*.

   O desde la terminal (el volcado no crea la base, así que se crea primero):

   ```
   mysql -u root -e "CREATE DATABASE IF NOT EXISTS Bibliotec CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
   mysql -u root Bibliotec < database/bibliotech.demo.sql
   ```

   El volcado incluye la estructura completa (tablas, índices y llaves
   foráneas), 6 categorías, 30 libros, 8 usuarios de demostración, 1 préstamo,
   1 multa y 3 reservas.

   > `database/bibliotech.demo.sql` es la versión **pública y anonimizada** del
   > volcado (nombres y correos ficticios, contraseñas de demostración).
   > `database/bibliotech.sql` es el volcado original de desarrollo y no se
   > versiona porque contiene datos personales.

4. **Abrir el sistema.** Visita:

   ```
   http://localhost/BiblioTech/
   ```

### Credenciales de base de datos

Por defecto se usan los valores estándar de XAMPP (`root` sin contraseña) y no
hay ninguna contraseña escrita en el código. Si tu instalación usa otros datos,
define variables de entorno o crea `config/database.local.php`
(ignorado por Git; ver [`config/database.example.php`](config/database.example.php)):

```php
<?php
return [
    'usuario'  => 'root',
    'password' => 'TU_CONTRASEÑA',
];
```

Variables de entorno admitidas: `BIBLIOTECH_DB_HOST`, `BIBLIOTECH_DB_PORT`,
`BIBLIOTECH_DB_NAME`, `BIBLIOTECH_DB_USER`, `BIBLIOTECH_DB_PASS`.

---

## Credenciales de demostración

Todas las cuentas del volcado comparten la contraseña **`password`**.

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@bibliotech.test` | `password` |
| Bibliotecario | `biblio@bibliotech.test` | `password` |
| Estudiante | `carlos@bibliotech.test` | `password` |
| Estudiante | `ana@bibliotech.test` | `password` |

*(El formulario de inicio de sesión muestra este recordatorio.)*

---

## Estructura del proyecto

```
BiblioTech/
├── index.php              Punto de entrada único (front controller)
├── config/                Configuración, conexión, rutas y matriz de permisos
├── includes/              Núcleo: sesión, autenticación, CSRF, router, helpers
├── controllers/           Lógica de cada módulo
├── models/                Acceso a datos (consultas PDO)
├── views/                 Vistas organizadas por rol y módulo
├── public/assets/         CSS, JavaScript e imágenes
├── uploads/portadas/      Portadas subidas desde el explorador de archivos
├── database/              Volcado SQL, versión anonimizada y script de anonimizado
├── tests/                 Scripts de verificación
└── docs/                  Instrucciones y requerimientos de entrega
```

---

## Funcionalidades por rol

Cada perfil accede **únicamente** a las opciones autorizadas (RNF03). La
verificación se ejecuta en el servidor (`config/routes.php`) antes de cargar
cualquier controlador, de modo que escribir la URL directamente no salta el
control.

### Administrador

| Módulo | RF | Descripción |
|---|---|---|
| Panel administrativo | RF02 | Indicadores generales y reportes estadísticos |
| Gestión de libros | RF03 | Registrar, listar, editar y eliminar libros, con carga de portada |
| Gestión de categorías | RF08 | Crear, editar y eliminar categorías |
| Gestión de usuarios | RF09, RF10, RF11 | Registrar, buscar, filtrar, editar, activar, bloquear y eliminar |
| Roles y permisos | RF12 | Matriz de acceso y reasignación de roles |
| Préstamos, reservas y multas | RF13 | Supervisión, devoluciones, confirmación de reservas, pagos y condonaciones |
| Reportes | RF02 | Préstamos por mes y por categoría, libros más prestados, disponibilidad |

### Bibliotecario

| Módulo | RF | Descripción |
|---|---|---|
| Catálogo en modo lectura | RF04, RF05, RF06 | Consultar, buscar por título/autor/ISBN y filtrar por categoría |
| Control de stock | RF07 | Verificar ejemplares totales y disponibles |
| Préstamos y devoluciones | RF13 | Registrar préstamos y devoluciones |
| Reservas | RF13 | Confirmar y cancelar reservas |
| Multas | RF13 | Registrar pagos y condonaciones |

*Sin acceso a la administración de libros, categorías, usuarios ni al panel
administrativo.*

### Estudiante

| Módulo | RF | Descripción |
|---|---|---|
| Catálogo | RF04, RF05, RF06, RF07 | Consultar, buscar, filtrar y revisar disponibilidad |
| Mis préstamos | — | Consultar sus préstamos y su estado |
| Mis reservas | — | Reservar ejemplares y cancelar las propias |
| Mis multas | — | Consultar el estado de pago de sus multas |

Cada estudiante ve **únicamente** su propia información: el acceso se filtra
por el identificador del usuario autenticado.

---

## Reglas de negocio

Configurables en [`config/config.php`](config/config.php):

| Regla | Valor |
|---|---|
| Duración del préstamo | 7 días |
| Préstamos simultáneos por estudiante | 3 |
| Multa por día de retraso | S/ 1.50 |
| Vigencia de una reserva | 3 días |
| Reservas simultáneas por estudiante | 3 |

Reglas aplicadas por el sistema:

- Un préstamo descuenta el stock disponible y devolverlo lo restituye.
- No se puede prestar un título sin ejemplares disponibles ni repetir un
  préstamo abierto del mismo ejemplar.
- La devolución con retraso genera automáticamente la multa correspondiente y
  no se duplica si se registra dos veces.
- Mientras el estudiante tenga multas pendientes no puede crear reservas.
- Los préstamos y devoluciones se ejecutan dentro de una **transacción** para
  mantener el stock y las multas consistentes (RNF07).

---

## Verificación

Los script se ejecutan con el proyecto accesible por HTTP (Apache en marcha) y
comprueban el sistema real, no simulaciones:

```bash
php tests/test_conexion.php            # conexión y estructura de la base
php tests/pruebas_integracion.php      # 13 escenarios de aceptación
php tests/verificar_rutas.php          # todas las rutas x todos los roles
php tests/verificar_pantallas.php      # contenido real en las pantallas clave
```

`pruebas_integracion.php`, `verificar_rutas.php` y `verificar_pantallas.php`
aceptan opcionalmente otra dirección, por ejemplo si se usa el servidor
integrado de PHP:

```bash
php -S localhost:8099 -t .            # en otra terminal
php tests/verificar_rutas.php http://localhost:8099/index.php
```

| Script | Qué comprueba | Resultado |
|---|---|---|
| `test_conexion.php` | Conexión PDO y existencia de las tablas | 0 fallos |
| `pruebas_integracion.php` | Los 13 escenarios de aceptación, incluida la separación de permisos y el rechazo de CSRF | 58 / 58 |
| `verificar_rutas.php` | Las 34 rutas GET con los 4 estados de sesión y el destino de la redirección | 139 / 139 |
| `verificar_pantallas.php` | Que cada panel muestre datos reales de la base y no una página vacía | 21 / 21 |

Los datos que crean las pruebas se eliminan al terminar, de modo que `Bibliotec`
queda tal como estaba antes de ejecutarlas.

---

## Seguridad

| Medida | Implementación |
|---|---|
| Contraseñas | `password_hash()` / `password_verify()` (bcrypt); nunca se guardan en claro |
| Inyección SQL | Consultas preparadas con `ATTR_EMULATE_PREPARES => false` |
| XSS | Escape de toda salida con el helper `e()` |
| CSRF | Token por sesión validado en cada formulario POST |
| Control de acceso | Verificación del rol y del registro en el servidor, en cada petición |
| Sesiones | Regeneración del identificador al iniciar sesión (evita la fijación de sesión) |
| Credenciales | Mensaje genérico ante un intento fallido, sin revelar si el correo existe |
| Cuentas bloqueadas | Un usuario bloqueado no puede acceder ni con la contraseña correcta |
| Subida de archivos | Validación del tipo real, tamaño máximo (2 MB) y nombre generado con `random_bytes()` |
| Errores | Se registran en `storage/php-error.log`; nunca se muestran al usuario |

---

## Estado del proyecto

El avance se registra mediante **un commit por funcionalidad terminada y
comprobada**. Consulta `git log --oneline` para ver la evolución.
