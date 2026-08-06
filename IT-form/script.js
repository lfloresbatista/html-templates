/**
 * Formulario IT-form — guardar, imprimir, compartir/descargar (mobile Web Share).
 */
(function () {
  'use strict';

  const state = {
    csrf: '',
    authenticated: false,
    allowPublicSave: false,
    saved: false,
    lastSave: null,
    lastPdfBlob: null,
    lastPdfName: 'informe.pdf',
    shareSupported: false,
  };

  const el = {};

  const pdfConfig = {
    margin: 10,
    filename: 'formulario_servicio.pdf',
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true },
    jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' },
    enableLinks: true,
  };

  function init() {
    el.form = document.getElementById('serviceForm');
    el.mensaje = document.getElementById('mensaje');
    el.btnGuardar = document.getElementById('btnGuardar');
    el.btnImprimir = document.getElementById('btnImprimir');
    el.btnCompartir = document.getElementById('btnCompartir');
    el.btnLimpiar = document.getElementById('btnLimpiar');
    el.themeToggle = document.getElementById('themeToggle');
    el.hint = document.getElementById('accionesHint');

    if (!el.form || !el.mensaje) {
      console.error('Formulario no encontrado');
      return;
    }

    const meta = document.querySelector('meta[name="csrf-token"]');
    const hidden = document.getElementById('csrf_token');
    state.csrf = (meta && meta.content) || (hidden && hidden.value) || '';

    detectShareSupport();
    setupEvents();
    setFechaActual();
    initTheme();
    setPostSaveEnabled(false);
    refreshSession();
  }

  function detectShareSupport() {
    state.shareSupported = typeof navigator.share === 'function';
    updateShareButtonLabel();
  }

  function updateShareButtonLabel() {
    if (!el.btnCompartir) return;
    if (state.shareSupported) {
      el.btnCompartir.textContent = '📤 Compartir';
      el.btnCompartir.setAttribute('aria-label', 'Compartir PDF');
    } else {
      el.btnCompartir.textContent = '⬇️ Descargar';
      el.btnCompartir.setAttribute('aria-label', 'Descargar PDF');
    }
  }

  function setPostSaveEnabled(on) {
    state.saved = !!on;
    if (el.btnImprimir) el.btnImprimir.disabled = !on;
    if (el.btnCompartir) el.btnCompartir.disabled = !on;
    if (el.hint) {
      el.hint.textContent = on
        ? 'Servicio guardado. Ya puede imprimir o compartir/descargar el PDF.'
        : 'Guarde el servicio para habilitar Imprimir y Compartir/Descargar.';
    }
  }

  async function refreshSession() {
    try {
      const res = await fetch('api/session.php', { credentials: 'same-origin' });
      const data = await res.json();
      if (data.success) {
        state.csrf = data.csrf_token || state.csrf;
        state.authenticated = !!data.authenticated;
        state.allowPublicSave = !!data.allow_public_save;
        const hidden = document.getElementById('csrf_token');
        if (hidden) hidden.value = state.csrf;
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', state.csrf);
        if (data.user && data.user.nombre) {
          const firma = document.getElementById('firmaTecnico');
          if (firma && (!firma.value || firma.readOnly)) {
            firma.value = data.user.nombre;
            firma.readOnly = true;
          }
        }
      }
    } catch (e) {
      console.warn('session', e);
    }
  }

  function initTheme() {
    const savedTheme = localStorage.getItem('itsTheme');
    const themeIcon = document.getElementById('themeIcon');
    const themeText = document.getElementById('themeText');
    if (savedTheme === 'dark') {
      document.body.classList.add('dark-mode');
      if (themeIcon) themeIcon.textContent = '☀️';
      if (themeText) themeText.textContent = 'Modo Claro';
    }
  }

  function setupEvents() {
    if (el.btnGuardar) el.btnGuardar.addEventListener('click', guardarEnBD);
    if (el.btnImprimir) el.btnImprimir.addEventListener('click', imprimirPDF);
    if (el.btnCompartir) el.btnCompartir.addEventListener('click', compartirODescargar);
    if (el.btnLimpiar) el.btnLimpiar.addEventListener('click', limpiarFormulario);
    if (el.themeToggle) el.themeToggle.addEventListener('click', toggleTheme);
    el.form.addEventListener('submit', (e) => {
      e.preventDefault();
      guardarEnBD();
    });
    el.form.addEventListener('input', (e) => {
      if (state.saved) {
        setPostSaveEnabled(false);
        state.lastPdfBlob = null;
      }
      validarCampo(e);
    });
  }

  function toggleTheme() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    const themeIcon = document.getElementById('themeIcon');
    const themeText = document.getElementById('themeText');
    if (themeIcon) themeIcon.textContent = isDark ? '☀️' : '🌙';
    if (themeText) themeText.textContent = isDark ? 'Modo Claro' : 'Modo Oscuro';
    localStorage.setItem('itsTheme', isDark ? 'dark' : 'light');
  }

  function setFechaActual() {
    const fechaInput = document.getElementById('fecha');
    if (fechaInput && !fechaInput.value) {
      const ahora = new Date();
      const offset = ahora.getTimezoneOffset() * 60000;
      fechaInput.value = new Date(ahora.getTime() - offset).toISOString().slice(0, 16);
    }
  }

  function validarCampo(e) {
    const campo = e.target;
    if (!campo.checkValidity && !campo.checkValidity) return;
    if (typeof campo.checkValidity === 'function') {
      if (!campo.checkValidity() && String(campo.value || '').length > 0) {
        campo.classList.add('invalid');
      } else {
        campo.classList.remove('invalid');
      }
    }
  }

  function validarFormulario() {
    let ok = true;
    el.form.querySelectorAll('[required]').forEach((campo) => {
      if (!String(campo.value || '').trim()) {
        campo.classList.add('invalid');
        ok = false;
      } else {
        campo.classList.remove('invalid');
      }
    });
    if (!ok) mostrarMensaje('Complete todos los campos requeridos', 'error');
    return ok;
  }

  function mostrarMensaje(mensaje, tipo) {
    if (!el.mensaje) return;
    el.mensaje.className = `mensaje ${tipo}`;
    el.mensaje.textContent = mensaje;
    el.mensaje.style.display = 'block';
    setTimeout(() => {
      el.mensaje.style.display = 'none';
    }, 4500);
  }

  function formPayload() {
    const fd = new FormData(el.form);
    fd.set('csrf_token', state.csrf);
    return fd;
  }

  async function guardarEnBD() {
    if (!validarFormulario()) return;
    if (!state.authenticated && !state.allowPublicSave) {
      mostrarMensaje('Debe iniciar sesión para guardar', 'error');
      return;
    }
    try {
      el.btnGuardar.disabled = true;
      el.btnGuardar.textContent = '⏳ Guardando...';
      await refreshSession();
      const res = await fetch('api/guardar_servicio.php', {
        method: 'POST',
        body: formPayload(),
        credentials: 'same-origin',
      });
      let result;
      try {
        result = await res.json();
      } catch (e) {
        throw new Error('Respuesta no JSON');
      }
      if (result.success) {
        state.lastSave = result.data || {};
        const ticketValue = state.lastSave.ticket || state.lastSave.numero_secuencia || '';
        mostrarMensaje(`✅ Guardado | Ticket: ${ticketValue}`, 'success');
        const ticket = document.getElementById('ticket');
        if (ticket && ticketValue) {
          ticket.value = ticketValue;
          ticket.readOnly = true;
        }
        // Prefer server PDF always after save (formato proyecto TCPDF). Client html2pdf solo fallback.
        setPostSaveEnabled(true);
        state.lastPdfBlob = null;
        prebuildServerPdf().catch(() => {});
      } else if (res.status === 401) {
        mostrarMensaje('❌ No autorizado. Inicie sesión.', 'error');
        setPostSaveEnabled(false);
      } else {
        mostrarMensaje(`❌ ${result.error || 'Error al guardar'}`, 'error');
        setPostSaveEnabled(false);
      }
    } catch (err) {
      console.error(err);
      mostrarMensaje('❌ Error de conexión', 'error');
    } finally {
      el.btnGuardar.disabled = false;
      el.btnGuardar.textContent = '💾 Guardar';
    }
  }

  async function prebuildServerPdf() {
    if (!state.authenticated) return;
    try {
      await refreshSession();
      const res = await fetch('print_pdf.php', {
        method: 'POST',
        body: formPayload(),
        credentials: 'same-origin',
      });
      if (!res.ok) throw new Error('pdf http ' + res.status);
      const blob = await res.blob();
      const ct = (res.headers.get('content-type') || blob.type || '').toLowerCase();
      if (!ct.includes('pdf') && blob.size < 800) throw new Error('not pdf');
      const ticketVal = (state.lastSave && state.lastSave.ticket)
        || (document.getElementById('ticket') && document.getElementById('ticket').value)
        || 'informe';
      state.lastPdfName = String(ticketVal).replace(/\.pdf$/i, '') + '.pdf';
      state.lastPdfBlob = blob;
    } catch (e) {
      console.warn('server pdf prebuild', e);
      state.lastPdfBlob = null;
    }
  }

  async function prebuildClientPdf() {
    if (typeof html2pdf === 'undefined') return;
    const ticketVal = (state.lastSave && state.lastSave.ticket)
      || (document.getElementById('ticket') && document.getElementById('ticket').value)
      || 'informe';
    state.lastPdfName = String(ticketVal).replace(/\.pdf$/i, '') + '.pdf';
    pdfConfig.filename = state.lastPdfName;
    const worker = html2pdf().set(pdfConfig).from(el.form).outputPdf('blob');
    state.lastPdfBlob = await worker;
  }

  async function ensurePdfBlob() {
    // Siempre intentar servidor primero (formato carta del proyecto)
    if (state.authenticated) {
      // si el blob cacheado no es del servidor (muy grande html2canvas) o vacío, regenerar
      if (state.lastPdfBlob && state.lastPdfBlob.size > 500) {
        // Preferir regenerar desde servidor si el blob es sospechosamente de cliente
        // html2pdf suele ser >50KB de raster; aún así forzamos servidor
      }
      state.lastPdfBlob = null;
      await prebuildServerPdf();
      if (state.lastPdfBlob) return state.lastPdfBlob;
    }
    await prebuildClientPdf();
    return state.lastPdfBlob;
  }

  async function imprimirPDF() {
    if (!state.saved) {
      mostrarMensaje('Guarde el servicio primero', 'error');
      return;
    }
    if (!validarFormulario()) return;
    try {
      el.btnImprimir.disabled = true;
      el.btnImprimir.textContent = '⏳ PDF...';
      const blob = await ensurePdfBlob();
      if (!blob) throw new Error('No se pudo generar PDF');
      const url = URL.createObjectURL(blob);
      const w = window.open(url, '_blank');
      if (!w) {
        // popup blocked → download
        downloadBlob(blob, state.lastPdfName);
        mostrarMensaje('PDF descargado (popup bloqueado)', 'success');
      } else {
        mostrarMensaje('PDF listo para imprimir', 'success');
      }
      setTimeout(() => URL.revokeObjectURL(url), 60000);
    } catch (e) {
      console.error(e);
      mostrarMensaje('❌ No se pudo imprimir/generar PDF', 'error');
    } finally {
      el.btnImprimir.disabled = false;
      el.btnImprimir.textContent = '🖨 Imprimir';
    }
  }

  function downloadBlob(blob, name) {
    const a = document.createElement('a');
    const url = URL.createObjectURL(blob);
    a.href = url;
    a.download = name || 'informe.pdf';
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 30000);
  }

  async function compartirODescargar() {
    if (!state.saved) {
      mostrarMensaje('Guarde el servicio primero', 'error');
      return;
    }
    if (!validarFormulario()) return;
    try {
      el.btnCompartir.disabled = true;
      el.btnCompartir.textContent = '⏳...';
      const blob = await ensurePdfBlob();
      if (!blob) throw new Error('PDF vacío');
      const file = new File([blob], state.lastPdfName, { type: 'application/pdf' });
      const title = 'Informe técnico ' + ((state.lastSave && state.lastSave.numero_secuencia) || '');
      const text = 'Informe de servicio técnico';

      let shared = false;
      if (navigator.share) {
        try {
          if (navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({ files: [file], title, text });
            shared = true;
          } else {
            // Algunos móviles no canShare files pero sí texto+url
            await navigator.share({ title, text });
            // Aún así ofrecer descarga del PDF
            downloadBlob(blob, state.lastPdfName);
            shared = true;
          }
        } catch (shareErr) {
          if (shareErr && shareErr.name === 'AbortError') {
            mostrarMensaje('Compartir cancelado', 'success');
            return;
          }
          // fallback download
        }
      }

      if (!shared) {
        downloadBlob(blob, state.lastPdfName);
        mostrarMensaje('⬇️ PDF descargado', 'success');
      } else {
        mostrarMensaje('✅ Compartido / enviado', 'success');
      }
    } catch (e) {
      console.error(e);
      mostrarMensaje('❌ No se pudo compartir/descargar', 'error');
    } finally {
      el.btnCompartir.disabled = false;
      updateShareButtonLabel();
    }
  }

  function limpiarFormulario() {
    if (!confirm('¿Limpiar todo el formulario?')) return;
    el.form.reset();
    const hidden = document.getElementById('csrf_token');
    if (hidden) hidden.value = state.csrf;
    setFechaActual();
    el.form.querySelectorAll('.invalid').forEach((c) => c.classList.remove('invalid'));
    state.lastSave = null;
    state.lastPdfBlob = null;
    setPostSaveEnabled(false);
    refreshSession();
    mostrarMensaje('Formulario limpiado', 'success');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
