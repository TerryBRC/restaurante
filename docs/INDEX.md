# Índice de Documentación

Este proyecto cuenta con documentación completa organizada en los siguientes archivos:

## 📄 Documentos Principales

### [README.md](../README.md)
**Documento principal del proyecto**

Contenido:
- Descripción general del sistema
- Características principales
- Arquitectura MVC
- Estructura del proyecto
- Base de datos (resumen)
- Instalación y configuración
- Módulos del sistema
- Sistema de roles
- Flujos de trabajo
- Tecnologías utilizadas
- Solución de problemas

**Audiencia**: Todos los usuarios (desarrolladores, administradores, nuevos miembros del equipo)

---

### [DATABASE.md](DATABASE.md)
**Documentación técnica de la base de datos**

Contenido:
- Diagrama de relaciones entre tablas
- Descripción detallada de cada tabla
- Campos, tipos de datos y restricciones
- Vistas de base de datos
- Procedimientos almacenados
- Índices y optimizaciones
- Migraciones disponibles
- Consultas SQL útiles
- Consideraciones de diseño

**Audiencia**: Desarrolladores backend, DBAs

---

### [API.md](API.md)
**Documentación de rutas y endpoints**

Contenido:
- Sistema de enrutamiento
- Todas las rutas disponibles organizadas por módulo
- Parámetros de entrada y salida
- Formatos de respuesta (JSON/HTML)
- Códigos de estado HTTP
- Ejemplos de uso con JavaScript/AJAX
- Seguridad (CSRF, validación)
- Autenticación y sesiones

**Audiencia**: Desarrolladores frontend y backend

---

### [DEVELOPMENT.md](DEVELOPMENT.md)
**Guía completa para desarrolladores**

Contenido:
- Configuración del entorno de desarrollo
- Arquitectura MVC explicada en detalle
- Flujo de una petición HTTP
- Cómo crear nuevos módulos (paso a paso)
- Ejemplos completos de código
- Trabajo con AJAX
- Buenas prácticas de programación
- Seguridad (SQL injection, XSS, CSRF)
- Debugging y logging
- Testing manual
- Deployment y producción

**Audiencia**: Desarrolladores

---

## 🗂️ Organización de Archivos

```
restaurante/
├── README.md                    # Documento principal
├── docs/
│   ├── INDEX.md                 # Este archivo
│   ├── DATABASE.md              # Documentación de BD
│   ├── API.md                   # Documentación de rutas
│   └── DEVELOPMENT.md           # Guía de desarrollo
├── backups/                     # Respaldos de BD
│   ├── rest_barDumb.sql         # Backup completo
│   └── *.sql                    # Migraciones
├── config/                      # Configuración
├── controllers/                 # Controladores MVC
├── models/                      # Modelos de datos
├── views/                       # Vistas
├── helpers/                     # Utilidades
└── assets/                      # Recursos estáticos
```

## 🎯 Guía de Lectura por Rol

### Para Nuevos Desarrolladores
1. **Inicio**: [README.md](../README.md) - Entender el proyecto
2. **Base de Datos**: [DATABASE.md](DATABASE.md) - Conocer el modelo de datos
3. **Desarrollo**: [DEVELOPMENT.md](DEVELOPMENT.md) - Aprender a programar en el proyecto
4. **API**: [API.md](API.md) - Consultar rutas y endpoints

### Para Administradores del Sistema
1. **Inicio**: [README.md](../README.md) - Sección de instalación
2. **Configuración**: README.md - Sección de configuración
3. **Solución de problemas**: README.md - Sección de troubleshooting

### Para Desarrolladores Frontend
1. **API**: [API.md](API.md) - Endpoints disponibles
2. **Ejemplos**: API.md - Ejemplos de uso con AJAX
3. **Desarrollo**: [DEVELOPMENT.md](DEVELOPMENT.md) - Sección de AJAX

### Para Desarrolladores Backend
1. **Base de Datos**: [DATABASE.md](DATABASE.md) - Esquema completo
2. **Desarrollo**: [DEVELOPMENT.md](DEVELOPMENT.md) - Crear modelos y controladores
3. **API**: [API.md](API.md) - Agregar nuevas rutas

### Para DBAs
1. **Base de Datos**: [DATABASE.md](DATABASE.md) - Todo el documento
2. **Migraciones**: DATABASE.md - Sección de migraciones
3. **Backups**: DATABASE.md - Sección de respaldos

## 🔍 Búsqueda Rápida

### Temas Comunes

| Tema | Documento | Sección |
|------|-----------|---------|
| Instalación | README.md | Configuración → Instalación |
| Crear nuevo módulo | DEVELOPMENT.md | Crear Nuevos Módulos |
| Agregar ruta | API.md | Sistema de Enrutamiento |
| Estructura de tablas | DATABASE.md | Tablas Detalladas |
| Ejemplos de código | DEVELOPMENT.md | Todo el documento |
| Seguridad | DEVELOPMENT.md | Buenas Prácticas → Seguridad |
| Testing | DEVELOPMENT.md | Testing |
| Deployment | DEVELOPMENT.md | Deployment |
| Consultas SQL | DATABASE.md | Consultas Útiles |
| AJAX | API.md | Ejemplos de Uso |

## 📚 Recursos Adicionales

### Dentro del Proyecto
- `config/routes.php` - Definición de todas las rutas
- `backups/` - Scripts SQL de base de datos
- `helpers/` - Utilidades y helpers

### Externos
- [PHP Manual](https://www.php.net/manual/es/)
- [PDO Documentation](https://www.php.net/manual/es/book.pdo.php)
- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.0/)
- [MySQL Reference](https://dev.mysql.com/doc/)
- [escpos-php GitHub](https://github.com/mike42/escpos-php)

## 🔄 Actualización de Documentación

Esta documentación debe actualizarse cuando:
- Se agreguen nuevas tablas a la base de datos
- Se creen nuevas rutas o endpoints
- Se implementen nuevos módulos
- Cambien los flujos de trabajo
- Se modifique la arquitectura

**Última actualización**: Enero 2026  
**Versión del sistema**: 1.0

---

**Nota**: Todos los enlaces son relativos a la ubicación de este archivo en `docs/INDEX.md`
