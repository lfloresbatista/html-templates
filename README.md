# 🎨 HTML Templates Collection

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![HTML5](https://img.shields.io/badge/HTML-5-orange.svg)](https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/HTML5)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](#contributing)

> 📚 Una colección curada de plantillas HTML profesionales, listas para usar y personalizar.

---

## 📋 Tabla de Contenidos

- [Descripción](#-descripción)
- [Templates Disponibles](#-templates-disponibles)
- [Cómo Usar](#-cómo-usar)
- [Estructura del Repositorio](#-estructura-del-repositorio)
- [Contribuir](#-contribuir)
- [Licencia](#-licencia)

---

## 📖 Descripción

**HTML Templates Collection** es un repositorio que reúne plantillas HTML modernas y funcionales para diferentes casos de uso. Cada template es:

| Característica | Detalle |
|----------------|---------|
| ✅ **Independiente** | Cada template funciona por sí mismo |
| ✅ **Responsive** | Adaptable a todos los dispositivos |
| ✅ **Sin Dependencias** | HTML, CSS y JS puro |
| ✅ **Fácil de Personalizar** | Código limpio y comentado |
| ✅ **Multilenguaje** | Soporte para varios idiomas |

---

## 🗂️ Templates Disponibles

### 1. 🚫 Suspended Account Page
> Plantilla para mostrar notificaciones de cuentas suspendidas por problemas administrativos o falta de pago.

| Estado | Idiomas | Características |
|--------|---------|-----------------|
| ✅ Disponible | 🇺🇸 🇪🇸 | Bilingüe, Responsive, Animaciones |

📁 **Ubicación:** [`/suspended`](./suspended)

```
┌─────────────────────────────────┐
│         ⚠️  SUSPENDIDO          │
│                                 │
│   Tu servicio ha sido           │
│   suspendida temporalmente      │
│                                 │
│   [🇺🇸] [🇪🇸]  ← Cambio idioma  │
└─────────────────────────────────┘
```

[📄 Ver README completo →](./suspended)

---

### 2. 📋 IT Service Form
> Plantilla profesional para formularios de servicio técnico con generación de PDF integrada.

| Estado | Backend | Características |
|--------|---------|-----------------|
| ✅ Disponible | PHP | Generación PDF, Login, Responsive |

📁 **Ubicación:** [`/IT-form`](./IT-form)

```
┌─────────────────────────────────┐
│  🏢 ITS Panama                  │
│  Formulario de Servicio Técnico │
├─────────────────────────────────┤
│  Cliente: _______________       │
│  Dirección: ____________        │
│  Reporte: _______________       │
│  Diagnóstico: ___________       │
│  Trabajo Realizado: ______      │
│                                 │
│  [📄 Generar PDF]               │
│                                 │
│  Powered by OkamiApps           │
└─────────────────────────────────┘
```

[📄 Ver README completo →](./IT-form)

---

### 🔜 Próximamente

Estamos trabajando en más plantillas:

- [ ] 🏠 Landing Page Genérica
- [ ] 📧 Página de "En Construcción"
- [ ] 🎉 Página de Gracias / Confirmación
- [ ] 📝 Formulario de Contacto Simple
- [ ] ❌ Página 404 Personalizada

¿Tienes una idea? ¡[Sugiere un nuevo template](https://github.com/lfloresbatista/html-templates/issues)!

---

## 🚀 Cómo Usar

### Opción 1: Clonar Todo el Repositorio
```bash
git clone https://github.com/lfloresbatista/html-templates.git
cd html-templates
```

### Opción 2: Descargar un Template Específico
1. Navega a la carpeta del template deseado
2. Descarga solo los archivos que necesitas
3. Ábrelos en tu navegador o intégralos en tu proyecto

### Opción 3: Usar con Servidor Local
```bash
# Desde la raíz del repositorio
python -m http.server 8000
# Visita: http://localhost:8000/suspended/index.html
```

---

## 📁 Estructura del Repositorio

```
html-templates/
├── README.md                 # 📖 Este archivo (guía general)
├── .gitignore                # Configuración de Git
├── LICENSE                   # Licencia MIT
│
├── suspended/                # 🚫 Template: Cuenta Suspendida
│   ├── README.md             # Documentación específica
│   ├── index.html            # Archivo principal
│   ├── logo.png              # Logo del footer
│   ├── en.png                # Bandera inglés
│   └── es.png                # Bandera español
│
├── IT-form/                  # 📋 Template: Formulario de Servicio Técnico
│   ├── README.md             # Documentación específica
│   ├── index.html            # Formulario principal
│   ├── login.html            # Página de login
│   ├── styles.css            # Estilos CSS
│   ├── script.js             # Lógica JavaScript
│   ├── logo.png              # Logo principal
│   ├── logo2.png             # Logo footer
│   ├── procesar_login.php    # Autenticación
│   ├── print_pdf.php         # Generador PDF
│   ├── dist/                 # Librerías JS
│   └── tcpdf/                # Librería PHP TCPDF
│
└── .../                      # 🔜 Más templates próximamente
```

---

## 🛠️ Personalización General

Cada template incluye su propia documentación, pero aquí hay algunos tips comunes:

### Cambiar Colores
Busca las variables CSS en cada `index.html`:
```css
:root {
  --primary-color: #tu-color;
  --background: #f0f0f0;
}
```

### Modificar Textos
Edita directamente el HTML o las funciones de JavaScript para cambios dinámicos.

### Agregar Más Idiomas
1. Añade las banderas correspondientes (`.png` o `.svg`)
2. Extiende la función `changeLanguage()` en el JavaScript
3. Agrega los nuevos textos traducidos

---

## 🤝 Contribuir

¡Las contribuciones son bienvenidas! Si quieres añadir un nuevo template o mejorar uno existente:

### Pasos para Contribuir

1. **Fork** el repositorio
2. **Crea una rama** para tu feature
   ```bash
   git checkout -b feature/nuevo-template
   ```
3. **Desarrolla** tu template siguiendo la estructura existente
4. **Documenta** tu template con un README.md dentro de su carpeta
5. **Commit** tus cambios
   ```bash
   git commit -m "Add: nuevo template de landing page"
   ```
6. **Push** a tu rama
   ```bash
   git push origin feature/nuevo-template
   ```
7. **Abre un Pull Request** 🎉

### Guía para Nuevos Templates

Si quieres añadir un nuevo template, asegúrate de incluir:

- [ ] `index.html` con el código funcional
- [ ] `README.md` específico del template
- [ ] Assets necesarios (imágenes, iconos, etc.)
- [ ] Código limpio y comentado
- [ ] Diseño responsive
- [ ] Soporte multilenguaje (recomendado)

---

## 📄 Licencia

Este proyecto está bajo la **Licencia MIT**. Cada template puede ser usado libremente en proyectos personales y comerciales.

Ver archivo [LICENSE](./LICENSE) para más detalles.

---

## 👨‍💻 Autor

**@lfloresbatista**

- 🌟 GitHub: [@lfloresbatista](https://github.com/lfloresbatista)
- 💡 Objetivo: Compartir plantillas útiles para la comunidad

---

## 📬 Contacto

¿Tienes preguntas o sugerencias?

- 🐛 Reporta un bug: [Issues](https://github.com/lfloresbatista/html-templates/issues)
- 💡 Sugiere un template: [Discussions](https://github.com/lfloresbatista/html-templates/discussions)
- 📧 Email: (disponible en el perfil de GitHub)

---

<div align="center">

### ¿Te gusta este proyecto?

[⭐ Dale una estrella](https://github.com/lfloresbatista/html-templates/stargazers) • [🍴 Haz un fork](https://github.com/lfloresbatista/html-templates/fork) • [📢 Comparte](https://twitter.com/intent/tweet?text=Check%20out%20this%20awesome%20HTML%20templates%20collection!)

---

**Hecho con ❤️ para la comunidad de desarrolladores**

[![GitHub stars](https://img.shields.io/github/stars/lfloresbatista/html-templates?style=social)](https://github.com/lfloresbatista/html-templates/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/lfloresbatista/html-templates?style=social)](https://github.com/lfloresbatista/html-templates/network/members)

</div>
