# 🚫 Suspended Account Page Template

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![HTML5](https://img.shields.io/badge/HTML-5-orange.svg)](https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/HTML5)
[![Bilingual](https://img.shields.io/badge/Languages-EN%2FES-green.svg)](#features)

> Plantilla HTML limpia, responsive y bilingüe para mostrar notificaciones de cuentas suspendidas por problemas administrativos o falta de pago.

---

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Vista Previa](#-vista-previa)
- [Inicio Rápido](#-inicio-rápido)
- [Personalización](#-personalización)
- [Estructura de Archivos](#-estructura-de-archivos)
- [Soporte de Navegadores](#-soporte-de-navegadores)
- [Contribuir](#-contribuir)
- [Licencia](#-licencia)

---

## ✨ Características

| Característica | Descripción |
|----------------|-------------|
| 🌐 **Bilingüe** | Cambio instantáneo entre inglés y español |
| 📱 **Responsive** | Se ve genial en desktop, tablet y móvil |
| 🎨 **Diseño Moderno** | Layout limpio tipo tarjeta con animaciones suaves |
| ⚡ **Sin Dependencias** | HTML, CSS y JavaScript puro - sin frameworks |
| 🔧 **Fácil Personalización** | Estructura simple para modificaciones rápidas |
| ♿ **Accesible** | HTML semántico y texto alt para lectores de pantalla |
| 🚀 **Ligero** | Carga rápida con mínimo código |

---

## 👁️ Vista Previa

### Desktop View
```
┌─────────────────────────────────────────┐
│                                         │
│         ┌───────────────────┐           │
│         │   ⚠️ (Icono)      │           │
│         │                   │           │
│         │ Servicio Suspendido          │
│         │                   │           │
│         │ Queremos...       │           │
│         │ (mensaje)         │           │
│         │                   │           │
│         │  🇺🇸  🇪🇸          │           │
│         └───────────────────┘           │
│                                         │
│         Powered by OkamiApps            │
└─────────────────────────────────────────┘
```

### Elementos Visuales Clave
- 🎯 Layout de tarjeta centrada con efectos de sombra
- 🎨 Fondo con gradiente (#def2ff)
- ✨ Animación fade-up al cargar
- 🔄 Selector de idioma interactivo con banderas

---

## 🚀 Inicio Rápido

### Opción 1: Clonar el Repositorio
```bash
git clone https://github.com/lfloresbatista/html-templates.git
cd html-templates/suspended
```

### Opción 2: Descarga Directa
1. Descarga la carpeta `suspended`
2. Abre `index.html` en tu navegador

### Opción 3: Integración en Proyecto Existente
```html
<!-- Copia el contenido de index.html a tu proyecto -->
```

### Uso
Simplemente abre `index.html` en cualquier navegador moderno:
```bash
# Usando servidor local (recomendado)
python -m http.server 8000
# Visita: http://localhost:8000/suspended/index.html
```

---

## 🛠️ Personalización

### Cambiar Colores
Edita las variables CSS en la sección `<style>`:
```css
body {
  background: #def2ff;  /* Cambiar color de fondo */
}

.card {
  background: #fff;     /* Cambiar color de tarjeta */
  box-shadow: 3px 4px 10px 0px rgb(0 0 0 / 50%); /* Ajustar sombra */
}

.emphasize {
  color: rgb(0, 38, 255); /* Cambiar color de texto resaltado */
}
```

### Modificar Contenido de Texto
Actualiza el texto en la función JavaScript `changeLanguage()`:
```javascript
if (language === 'en') {
  title.textContent = 'Tu Título Personalizado';
  content.innerHTML = 'Tu mensaje personalizado aquí...';
}
```

### Agregar Más Idiomas
1. Crea imágenes de banderas (ej. `fr.png`, `de.png`)
2. Añade botones de cambio de idioma:
```html
<img src="fr.png" class="flag" alt="Français" onclick="changeLanguage('fr')">
```
3. Extiende la función `changeLanguage()` con nuevos casos de idioma

### Reemplazar Logo
Reemplaza `logo.png` en la carpeta `suspended` con tu propio logo (tamaño recomendado: 100x100px)

### Personalizar Mensajes
Los mensajes están en la función JavaScript. Puedes modificar:
- Título de la suspensión
- Mensaje explicativo
- Instrucciones de contacto
- Texto del footer

---

## 📁 Estructura de Archivos

```
suspended/
├── README.md             # Esta documentación
├── index.html            # Archivo HTML principal con CSS/JS embebido
├── logo.png              # Logo del footer (OkamiApps)
├── en.png                # Bandera de Estados Unidos (inglés)
└── es.png                # Bandera de España (español)
```

### Detalles de Archivos

| Archivo | Tamaño | Propósito |
|---------|--------|-----------|
| `index.html` | ~5KB | Página principal con estilos y lógica |
| `logo.png` | ~3KB | Logo corporativo en el footer |
| `en.png` | ~1KB | Icono para cambiar a inglés |
| `es.png` | ~1KB | Icono para cambiar a español |

---

## 🌍 Soporte de Navegadores

| Navegador | Versión | Soporte |
|-----------|---------|---------|
| Chrome | Latest | ✅ Completo |
| Firefox | Latest | ✅ Completo |
| Safari | Latest | ✅ Completo |
| Edge | Latest | ✅ Completo |
| Opera | Latest | ✅ Completo |
| IE 11 | - | ⚠️ Limitado |

---

## 🤝 Contribuir

¡Las contribuciones son bienvenidas! Así puedes ayudar:

1. **Fork** el repositorio
2. **Crea** una rama feature (`git checkout -b feature/MejoraIncreible`)
3. **Commit** tus cambios (`git commit -m 'Añade Mejora Increible'`)
4. **Push** a la rama (`git push origin feature/MejoraIncreible`)
5. **Abre** un Pull Request

### Áreas de Mejora
- [ ] Añadir más opciones de idioma
- [ ] Implementar detección automática de idioma del navegador
- [ ] Añadir soporte para modo oscuro
- [ ] Crear variaciones adicionales de la plantilla
- [ ] Mejorar accesibilidad (etiquetas ARIA, navegación por teclado)

---

## 📄 Licencia

Este proyecto está licenciado bajo la **Licencia MIT**:

```
Copyright (c) @lfloresbatista

Se concede permiso, libre de cargo, a cualquier persona que obtenga una copia
de este software y archivos de documentación asociados (el "Software"), para usar,
copiar, modificar, fusionar, publicar, distribuir, sublicenciar y/o vender
copias del Software, y permitir a las personas a quienes se les proporcione
el Software hacerlo, sujeto a las siguientes condiciones:

El aviso de copyright anterior y este aviso de permiso se incluirán en todas
las copias o partes sustanciales del Software.

EL SOFTWARE SE PROPORCIONA "TAL CUAL", SIN GARANTÍA DE NINGÚN TIPO, EXPRESA O
IMPLÍCITA, INCLUYENDO PERO NO LIMITADO A LAS GARANTÍAS DE COMERCIABILIDAD,
IDONEIDAD PARA UN PROPÓSITO PARTICULAR Y NO INFRACCIÓN. EN NINGÚN CASO LOS
AUTORES O TITULARES DE LOS DERECHOS DE AUTOR SERÁN RESPONSABLES DE CUALQUIER
RECLAMACIÓN, DAÑOS U OTRA RESPONSABILIDAD, YA SEA EN UNA ACCIÓN DE CONTRATO,
AGRAVIO O DE OTRO TIPO, QUE SURJA DE, FUERA DE O EN CONEXIÓN CON EL SOFTWARE
O EL USO U OTROS TRATOS EN EL SOFTWARE.
```

---

## 👨‍💻 Autor

**@lfloresbatista**

- 💼 GitHub: [@lfloresbatista](https://github.com/lfloresbatista)
- 🌟 Proyecto: Colección de Plantillas HTML de Uso Libre

---

## 🙏 Agradecimientos

- Iconos y gráficos diseñados para claridad y profesionalismo
- Inspirado en páginas modernas de notificación de suspensión
- Construido con ❤️ usando tecnologías web vanilla

---

<div align="center">

**¿Te resulta útil esta plantilla? ¡Dale una ⭐!**

Hecho con 💙 por @lfloresbatista

</div>
