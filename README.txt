ORTIZ INMOBILIARIA - Sistema de Gestión
========================================

DATOS DE ACCESO AL SISTEMA
---------------------------
URL: http://www.createxpro.net.pe/grupo01/login.php

Usuarios disponibles (contraseñas MD5):
  admin     / 123456
  lgonzales / (su contraseña original)
  mflores   / (su contraseña original)

INSTALACIÓN VÍA FILEZILLA
--------------------------
1. Abre FileZilla
2. Conecta con:
   Host:     ftp.createxpro.net.pe
   Usuario:  grup01@createxpro.net.pe
   Contraseña: 5&Kii23sds
   Puerto:   21

3. En el panel DERECHO navega a: /public_html/grupo01/
   (crea la carpeta grupo01 si no existe)

4. Sube TODOS los archivos de esta carpeta manteniendo la estructura

5. Importa el SQL en phpMyAdmin:
   - Entra al cPanel de createxpro.net.pe
   - Abre phpMyAdmin
   - Selecciona la BD: bpuma_grup02
   - Importa el archivo ortiz.sql

6. Abre: http://www.createxpro.net.pe/grupo01/login.php

ESTRUCTURA DE ARCHIVOS
----------------------
grupo01/
├── login.php           <- Inicio de sesión
├── logout.php
├── index.php           <- Dashboard con gráficas
├── .htaccess
├── config/
│   ├── db.php          <- Conexión a BD (ya configurada)
│   └── auth.php        <- Sesión y roles
├── includes/
│   ├── layout.php      <- Header + Sidebar
│   └── layout_end.php  <- Footer
├── assets/
│   ├── css/main.css    <- Estilos completos
│   └── js/main.js
└── modules/
    ├── propiedades/    <- Catálogo, vista, form
    ├── clientes/       <- Lista, detalle, form
    ├── empleados/      <- Solo Admin
    ├── contratos/      <- Lista, detalle, form
    ├── financiaciones/ <- Control de cuotas
    ├── reservas/       <- Gestión de reservas
    └── auditoria/      <- Log del sistema

CREDENCIALES BD (ya configuradas en config/db.php)
---------------------------------------------------
DB_HOST: localhost
DB_USER: bpuma_grup02
DB_PASS: 5&Kii23sds
DB_NAME: ortiz
