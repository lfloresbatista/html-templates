# 🚫 Suspended Account Page Template

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![HTML5](https://img.shields.io/badge/HTML-5-orange.svg)](https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/HTML5)
[![Bilingual](https://img.shields.io/badge/Languages-EN%2FES-green.svg)](#features)

> A clean, responsive, and bilingual HTML template for displaying suspended account notices due to administrative issues or non-payment.

---

## 📋 Table of Contents

- [Features](#-features)
- [Preview](#-preview)
- [Quick Start](#-quick-start)
- [Customization](#-customization)
- [File Structure](#-file-structure)
- [Browser Support](#-browser-support)
- [Contributing](#-contributing)
- [License](#-license)
- [Author](#-author)

---

## ✨ Features

| Feature | Description |
|---------|-------------|
| 🌐 **Bilingual Support** | Switch between English and Spanish instantly |
| 📱 **Fully Responsive** | Looks great on desktop, tablet, and mobile devices |
| 🎨 **Modern Design** | Clean card-based layout with smooth animations |
| ⚡ **Zero Dependencies** | Pure HTML, CSS, and JavaScript - no frameworks required |
| 🔧 **Easy to Customize** | Simple structure for quick modifications |
| ♿ **Accessible** | Semantic HTML and alt text for screen readers |
| 🚀 **Lightweight** | Fast loading time with minimal code footprint |

---

## 👁️ Preview

### Desktop View
```
┌─────────────────────────────────────────┐
│                                         │
│         ┌───────────────────┐           │
│         │   ⚠️ (Icon)       │           │
│         │                   │           │
│         │  Service Suspended│           │
│         │                   │           │
│         │  We want to...    │           │
│         │  (message)        │           │
│         │                   │           │
│         │  🇺🇸  🇪🇸          │           │
│         └───────────────────┘           │
│                                         │
│         Powered by OkamiApps            │
└─────────────────────────────────────────┘
```

### Key Visual Elements
- 🎯 Centered card layout with shadow effects
- 🎨 Gradient background (#def2ff)
- ✨ Fade-up animation on load
- 🔄 Interactive language switcher with flag icons

---

## 🚀 Quick Start

### Option 1: Clone the Repository
```bash
git clone https://github.com/yourusername/html-templates.git
cd html-templates/suspended
```

### Option 2: Direct Download
1. Download the `suspended` folder
2. Open `index.html` in your browser

### Option 3: CDN Integration
```html
<!-- Copy the contents of index.html to your project -->
```

### Usage
Simply open `index.html` in any modern web browser:
```bash
# Using a local server (recommended)
python -m http.server 8000
# Visit: http://localhost:8000/suspended/index.html
```

---

## 🛠️ Customization

### Change Colors
Edit the CSS variables in the `<style>` section:
```css
body {
  background: #def2ff;  /* Change background color */
}

.card {
  background: #fff;     /* Change card color */
  box-shadow: 3px 4px 10px 0px rgb(0 0 0 / 50%); /* Adjust shadow */
}

.emphasize {
  color: rgb(0, 38, 255); /* Change highlighted text color */
}
```

### Modify Text Content
Update the text in the JavaScript `changeLanguage()` function:
```javascript
if (language === 'en') {
  title.textContent = 'Your Custom Title';
  content.innerHTML = 'Your custom message here...';
}
```

### Add More Languages
1. Create flag images (e.g., `fr.png`, `de.png`)
2. Add language switcher buttons:
```html
<img src="fr.png" class="flag" alt="Français" onclick="changeLanguage('fr')">
```
3. Extend the `changeLanguage()` function with new language cases

### Replace Logo
Replace `logo.png` in the `suspended` folder with your own logo (recommended size: 100x100px)

---

## 📁 File Structure

```
html-templates/
├── README.md                 # This file
└── suspended/
    ├── index.html            # Main HTML file with embedded CSS/JS
    ├── logo.png              # Footer logo (OkamiApps)
    ├── en.png                # English flag icon
    └── es.png                # Spanish flag icon
```

---

## 🌍 Browser Support

| Browser | Version | Support |
|---------|---------|---------|
| Chrome | Latest | ✅ Full |
| Firefox | Latest | ✅ Full |
| Safari | Latest | ✅ Full |
| Edge | Latest | ✅ Full |
| Opera | Latest | ✅ Full |
| IE 11 | - | ⚠️ Limited |

---

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/AmazingFeature`)
3. **Commit** your changes (`git commit -m 'Add some AmazingFeature'`)
4. **Push** to the branch (`git push origin feature/AmazingFeature`)
5. **Open** a Pull Request

### Areas for Improvement
- [ ] Add more language options
- [ ] Implement automatic browser language detection
- [ ] Add dark mode support
- [ ] Create additional template variations
- [ ] Improve accessibility (ARIA labels, keyboard navigation)

---

## 📄 License

This project is licensed under the **MIT License** - see below for details:

```
Copyright (c) @lfloresbatista

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 👨‍💻 Author

**@lfloresbatista**

- 💼 GitHub: [@lfloresbatista](https://github.com/lfloresbatista)
- 🌟 Project: HTML Templates for Free Use

---

## 🙏 Acknowledgments

- Icons and graphics designed for clarity and professionalism
- Inspired by modern suspension notice pages
- Built with ❤️ using vanilla web technologies

---

<div align="center">

**If you find this template useful, please give it a ⭐!**

Made with 💙 by @lfloresbatista

</div>
