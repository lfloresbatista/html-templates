# 📋 IT Service Form Template

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![HTML5](https://img.shields.io/badge/HTML-5-orange.svg)](https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/HTML5)
[![PHP](https://img.shields.io/badge/PHP-7+-purple.svg)](https://www.php.net/)
[![PDF Generation](https://img.shields.io/badge/PDF-Generation-red.svg)](#features)

> Plantilla HTML profesional para formularios de servicio técnico con generación de PDF integrada.

---

## 📋 Tabla de Contenidos

- [Descripción](#-descripción)
- [Características](#-características)
- [Vista Previa](#-vista-previa)
- [Inicio Rápido](#-inicio-rápido)
- [Estructura de Archivos](#-estructura-de-archivos)
- [Personalización](#-personalización)
- [Backend PHP](#-backend-php)
- [Dependencias](#-dependencias)
- [Contribuir](#-contribuir)
- [Licencia](#-licencia)

---

## 📖 Descripción

**IT Service Form** es una plantilla web completa diseñada para empresas de servicio técnico que necesitan registrar y documentar intervenciones. Permite:

- Registrar información del cliente y equipo
- Documentar diagnóstico y trabajo realizado
- Generar automáticamente un PDF profesional del servicio
- Imprimir o guardar el comprobante del servicio

---

## ✨ Características

| Característica | Descripción |
|----------------|-------------|
| 📄 **Generación PDF** | Crea PDFs profesionales con html2pdf |
| 🎨 **Diseño Limpio** | Interfaz moderna y fácil de usar |
| 📱 **Responsive** | Adaptable a diferentes dispositivos |
| 🔧 **Formulario Completo** | Todos los campos necesarios para servicio técnico |
| 💾 **Guardado Local** | Descarga automática del PDF generado |
| ⚡ **Sin Dependencias Externas** | Librerías incluidas localmente |
| 🖼️ **Personalizable** | Logos y colores editables |

---

## 👁️ Vista Previa

### Formulario de Servicio

El formulario incluye los siguientes campos:

```
┌─────────────────────────────────────────┐
│  🏢 ITS Panama                          │
│  Formulario de Servicio Técnico         │
├─────────────────────────────────────────┤
│  Cliente: _______________  Fecha: _____ │
│  Dirección: ____________  Ticket: _____ │
│                                         │
│  Reporte del Cliente:                   │
│  ┌───────────────────────────────────┐  │
│  │                                   │  │
│  └───────────────────────────────────┘  │
│                                         │
│  Diagnóstico Técnico:   Trabajo Realizado: │
│  ┌───────────────┐     ┌───────────────┐ │
│  │               │     │               │ │
│  └───────────────┘     └───────────────┘ │
│                                         │
│  Observaciones/Recomendaciones:         │
│  ┌───────────────────────────────────┐  │
│  │                                   │  │
│  └───────────────────────────────────┘  │
│                                         │
│  Recibido Conforme: ___  Firma: ______  │
│                                         │
│         [📄 Generar PDF]                │
│                                         │
│         Powered by OkamiApps            │
└─────────────────────────────────────────┘
```

### Login de Administración

Incluye página de login para acceso administrativo:

```
┌─────────────────────────┐
│     🏢 ITS Panama       │
│                         │
│    Iniciar Sesión       │
│                         │
│  Usuario: ___________   │
│  Contraseña: ________   │
│                         │
│  [Iniciar Sesión]       │
└─────────────────────────┘
```

---

## 🚀 Inicio Rápido

### Opción 1: Clonar el Repositorio

```bash
git clone https://github.com/lfloresbatista/html-templates.git
cd html-templates/IT-form
```

### Opción 2: Usar con Servidor Local

```bash
# Con PHP
php -S localhost:8000
# Visita: http://localhost:8000/IT-form/index.html

# Con Python
python -m http.server 8000
# Visita: http://localhost:8000/IT-form/index.html
```

### Requisitos

- Navegador web moderno (Chrome, Firefox, Edge, Safari)
- Servidor web (opcional, para funcionalidad PHP)
- PHP 7+ (para generación de PDF en servidor)

---

## 📁 Estructura de Archivos

```
IT-form/
├── README.md                 # Esta documentación
├── index.html                # Formulario principal
├── login.html                # Página de login
├── styles.css                # Estilos CSS
├── script.js                 # Lógica JavaScript para PDF
├── logo.png                  # Logo principal (ITS Panama)
├── logo2.png                 # Logo footer (OkamiApps)
├── procesar_login.php        # Script de autenticación
├── print_pdf.php             # Generador de PDF en servidor
├── info.php                  # Información del servidor
├── secret                    # Archivo de configuración secreta
├── dist/                     # Librerías JavaScript
│   ├── html2pdf.bundle.js    # Librería html2pdf completa
│   ├── html2pdf.min.js       # Librería html2pdf minimizada
│   └── FileSaver.min.js      # Librería para guardar archivos
└── tcpdf/                    # Librería TCPDF para PHP
    └── ...                   # Archivos de TCPDF
```

### Detalles de Archivos Principales

| Archivo | Propósito |
|---------|-----------|
| `index.html` | Formulario principal de servicio técnico |
| `login.html` | Página de autenticación para administradores |
| `styles.css` | Hoja de estilos personalizada |
| `script.js` | Funciones para generar PDF desde el navegador |
| `print_pdf.php` | Generación de PDF usando TCPDF en servidor |
| `procesar_login.php` | Validación de credenciales de usuario |

---

## 🛠️ Personalización

### Cambiar Colores

Edita las variables en `styles.css`:

```css
body {
  background-color: #c9f0ff;  /* Color de fondo general */
}

#encabezado {
  background-color: #c9f0ff;  /* Fondo del encabezado */
}

#titulo {
  color: #001F3F;             /* Color del título */
}

.btn-pdf {
  background-color: #4CAF50;  /* Color del botón PDF */
}
```

### Reemplazar Logos

1. **Logo Principal**: Reemplaza `logo.png` con tu logo (recomendado: 300x100px)
2. **Logo Footer**: Reemplaza `logo2.png` con tu logo de empresa (recomendado: 100x100px)

### Modificar Campos del Formulario

Edita `index.html` para agregar, quitar o modificar campos:

```html
<div class="fila">
  <div class="columna">
    <label for="nuevoCampo">Nombre del Campo:</label>
    <input type="text" id="nuevoCampo" name="nuevoCampo">
  </div>
</div>
```

### Cambiar Nombre del PDF

En `script.js`, modifica la configuración:

```javascript
const opciones = {
  filename: 'tu_nombre_personalizado.pdf',  // Cambiar nombre del archivo
  // ... otras opciones
};
```

---

## 🔌 Backend PHP

### Generación de PDF con TCPDF

El archivo `print_pdf.php` utiliza la librería TCPDF para generar PDFs en el servidor:

```php
// Ejemplo de uso
require_once('tcpdf/tcpdf.php');
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->Output('formulario.pdf', 'D');
```

### Autenticación

El sistema de login usa `procesar_login.php` para validar credenciales:

1. El usuario ingresa credenciales en `login.html`
2. `procesar_login.php` valida contra el archivo `secret`
3. Si es válido, permite el acceso al formulario

### Configuración de Seguridad

⚠️ **Importante**: Antes de usar en producción:

- [ ] Cambiar las credenciales por defecto
- [ ] Asegurar el archivo `secret` con permisos adecuados
- [ ] Implementar HTTPS
- [ ] Agregar protección CSRF
- [ ] Validar y sanitizar todos los inputs

---

## 📦 Dependencias

### Librerías Incluidas

| Librería | Versión | Propósito | Ubicación |
|----------|---------|-----------|-----------|
| html2pdf | Latest | Generación de PDF en cliente | `dist/` |
| FileSaver | Latest | Guardado de archivos | `dist/` |
| TCPDF | Latest | Generación de PDF en servidor | `tcpdf/` |

### Librerías de Terceros

- **html2pdf.js**: Conversión de HTML a PDF en el navegador
  - Repo: https://github.com/eKoopmans/html2pdf
  
- **TCPDF**: Librería PHP para generación de PDF
  - Repo: https://github.com/tecnickcom/TCPDF
  - License: GNU-LGPL v3

- **FileSaver.js**: Guardado de archivos en el cliente
  - Repo: https://github.com/eligrey/FileSaver.js

---

## 🌍 Soporte de Navegadores

| Navegador | Versión | Soporte |
|-----------|---------|---------|
| Chrome | Latest | ✅ Completo |
| Firefox | Latest | ✅ Completo |
| Safari | Latest | ✅ Completo |
| Edge | Latest | ✅ Completo |
| Opera | Latest | ✅ Completo |

---

## 🤝 Contribuir

¡Las contribuciones son bienvenidas! 

### Áreas de Mejora

- [ ] Añadir más campos personalizables al formulario
- [ ] Implementar validación de formularios más robusta
- [ ] Agregar soporte para múltiples idiomas
- [ ] Mejorar la accesibilidad (WCAG)
- [ ] Añadir plantillas de PDF adicionales
- [ ] Implementar firma digital en el PDF
- [ ] Agregar opción para adjuntar fotos del equipo

### Pasos para Contribuir

1. **Fork** el repositorio
2. **Crea** una rama feature (`git checkout -b feature/MejoraIncreible`)
3. **Commit** tus cambios (`git commit -m 'Añade Mejora Increible'`)
4. **Push** a la rama (`git push origin feature/MejoraIncreible`)
5. **Abre** un Pull Request

---

## 📄 Licencia

Este proyecto está licenciado bajo la **Licencia MIT**, excepto TCPDF que usa GNU-LGPL v3.

```
Copyright (c) @lfloresbatista

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software.
```

Ver archivo [LICENSE](../LICENSE) para más detalles.

**Nota**: La librería TCPDF incluida en este proyecto está bajo licencia GNU-LGPL v3. Consulta `tcpdf/LICENSE` para más información.

---

## 👨‍💻 Autor

**@lfloresbatista**

- 💼 GitHub: [@lfloresbatista](https://github.com/lfloresbatista)
- 🌟 Proyecto: Colección de Plantillas HTML de Uso Libre

---

## 🙏 Agradecimientos

- **html2pdf.js** por la excelente librería de conversión HTML a PDF
- **TCPDF** por la robusta librería PHP de generación de PDF
- **FileSaver.js** por facilitar el guardado de archivos en el navegador

---

<div align="center">

**¿Te resulta útil esta plantilla? ¡Dale una ⭐!**

Hecho con 💙 por @lfloresbatista

</div>
