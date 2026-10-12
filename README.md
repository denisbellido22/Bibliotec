# BiblioTech — Sistema de Biblioteca Virtual

Sistema web de gestión bibliotecaria desarrollado en **PHP puro** (sin frameworks),
**MySQL/MariaDB** y **PDO**, pensado para ejecutarse en **XAMPP** desde
`http://localhost/BiblioTech/`.

> Documentación completa de instalación, credenciales y cumplimiento de
> requerimientos en [`docs/`](docs/) y en las secciones siguientes de este README.

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

## Estructura del proyecto

```
BiblioTech/
├── index.php              Punto de entrada único (front controller)
├── config/                Configuración y conexión a la base de datos
├── includes/              Núcleo: sesión, autenticación, CSRF, helpers, layout
├── controllers/           Lógica de cada módulo
├── models/                Acceso a datos (consultas PDO)
├── views/                 Vistas organizadas por rol y módulo
├── public/assets/         CSS, JavaScript e imágenes
├── uploads/portadas/      Portadas subidas desde el explorador de archivos
├── database/              Script SQL original y migraciones incrementales
├── tests/                 Scripts de verificación de los escenarios del PDF
└── docs/                  Documentación de entrega
```

## Estado del proyecto

El avance se registra mediante **un commit por funcionalidad terminada y
comprobada**. Consulta `git log --oneline` para ver la evolución.

---

_Pendiente de completar: instalación en XAMPP, credenciales de demostración y
tabla de cumplimiento de requerimientos (se añadirán en la etapa final)._
