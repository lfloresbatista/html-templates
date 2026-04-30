/**
 * Módulo principal del formulario de servicio técnico
 * Maneja la generación de PDFs, validación de formularios y guardado en BD
 */
(function() {
    'use strict';

    // Elementos del DOM
    const elements = {
        form: null,
        mensaje: null,
        btnGenerarPDF: null,
        btnLimpiar: null,
        btnGuardar: null,
        themeToggle: null
    };

    // Configuración para html2pdf
    const pdfConfig = {
        margin: 10,
        filename: 'formulario_servicio.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        enableLinks: true
    };

    /**
     * Inicializa los elementos del DOM y eventos
     */
    function init() {
        elements.form = document.getElementById('serviceForm');
        elements.mensaje = document.getElementById('mensaje');
        elements.btnGenerarPDF = document.getElementById('btnGenerarPDF');
        elements.btnLimpiar = document.getElementById('btnLimpiar');
        elements.btnGuardar = document.getElementById('btnGuardar');
        elements.themeToggle = document.getElementById('themeToggle');

        if (!elements.form || !elements.mensaje) {
            console.error('Elementos del formulario no encontrados');
            return;
        }

        setupEventListeners();
        setFechaActual();
        initTheme();
    }

    /**
     * Inicializar el tema (claro/oscuro)
     */
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

    /**
     * Configura los listeners de eventos
     */
    function setupEventListeners() {
        if (elements.btnGenerarPDF) {
            elements.btnGenerarPDF.addEventListener('click', generarPDF);
        }

        if (elements.btnLimpiar) {
            elements.btnLimpiar.addEventListener('click', limpiarFormulario);
        }

        if (elements.btnGuardar) {
            elements.btnGuardar.addEventListener('click', guardarEnBD);
        }

        if (elements.themeToggle) {
            elements.themeToggle.addEventListener('click', toggleTheme);
        }

        elements.form.addEventListener('submit', (e) => {
            e.preventDefault();
            generarPDF();
        });

        elements.form.addEventListener('input', validarCampo);
    }

    /**
     * Alternar entre modo claro y oscuro
     */
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        
        if (themeIcon) themeIcon.textContent = isDark ? '☀️' : '🌙';
        if (themeText) themeText.textContent = isDark ? 'Modo Claro' : 'Modo Oscuro';
        
        localStorage.setItem('itsTheme', isDark ? 'dark' : 'light');
    }

    /**
     * Establece la fecha y hora actual en el campo correspondiente
     */
    function setFechaActual() {
        const fechaInput = document.getElementById('fecha');
        if (fechaInput && !fechaInput.value) {
            const ahora = new Date();
            const offset = ahora.getTimezoneOffset() * 60000;
            const fechaLocal = new Date(ahora.getTime() - offset);
            fechaInput.value = fechaLocal.toISOString().slice(0, 16);
        }
    }

    /**
     * Valida un campo individual
     * @param {Event} e - Evento de input
     */
    function validarCampo(e) {
        const campo = e.target;
        const esValido = campo.checkValidity();
        
        if (!esValido && campo.value.length > 0) {
            campo.classList.add('invalid');
        } else {
            campo.classList.remove('invalid');
        }
    }

    /**
     * Valida todo el formulario
     * @returns {boolean} - True si el formulario es válido
     */
    function validarFormulario() {
        let esValido = true;
        const camposRequeridos = elements.form.querySelectorAll('[required]');

        camposRequeridos.forEach(campo => {
            if (!campo.value.trim()) {
                campo.classList.add('invalid');
                esValido = false;
            } else {
                campo.classList.remove('invalid');
            }
        });

        if (!esValido) {
            mostrarMensaje('Por favor complete todos los campos requeridos', 'error');
        }

        return esValido;
    }

    /**
     * Muestra un mensaje al usuario
     * @param {string} mensaje - Mensaje a mostrar
     * @param {string} tipo - Tipo de mensaje ('success' o 'error')
     */
    function mostrarMensaje(mensaje, tipo) {
        if (!elements.mensaje) return;

        elements.mensaje.className = `mensaje ${tipo}`;
        elements.mensaje.textContent = mensaje;
        elements.mensaje.style.display = 'block';

        setTimeout(() => {
            elements.mensaje.style.display = 'none';
        }, 3000);
    }

    /**
     * Genera el PDF del formulario
     */
    async function generarPDF() {
        if (!validarFormulario()) {
            return;
        }

        if (typeof html2pdf === 'undefined') {
            mostrarMensaje('Error: Librería html2pdf no cargada', 'error');
            console.error('html2pdf no está disponible');
            return;
        }

        try {
            elements.btnGenerarPDF.disabled = true;
            elements.btnGenerarPDF.textContent = '⏳ Generando...';

            const nombreCliente = document.getElementById('cliente').value.trim() || 'cliente';
            const fecha = document.getElementById('fecha').value || 'fecha';
            
            pdfConfig.filename = `servicio_${nombreCliente.replace(/\s+/g, '_')}_${fecha.slice(0, 10)}.pdf`;

            await html2pdf()
                .set(pdfConfig)
                .from(elements.form)
                .save();

            mostrarMensaje('✅ PDF generado exitosamente', 'success');
        } catch (error) {
            console.error('Error al generar PDF:', error);
            mostrarMensaje(`❌ Error al generar PDF: ${error.message}`, 'error');
        } finally {
            elements.btnGenerarPDF.disabled = false;
            elements.btnGenerarPDF.textContent = '📄 Generar PDF';
        }
    }

    /**
     * Limpia el formulario y restablece valores
     */
    function limpiarFormulario() {
        if (confirm('¿Está seguro de que desea limpiar todo el formulario?')) {
            elements.form.reset();
            setFechaActual();
            
            elements.form.querySelectorAll('.invalid').forEach(campo => {
                campo.classList.remove('invalid');
            });

            mostrarMensaje('Formulario limpiado', 'success');
        }
    }

    /**
     * Guarda el formulario en la base de datos
     */
    async function guardarEnBD() {
        if (!validarFormulario()) {
            return;
        }

        try {
            elements.btnGuardar.disabled = true;
            elements.btnGuardar.textContent = '⏳ Guardando...';

            const formData = new FormData(elements.form);
            
            const response = await fetch('api/guardar_servicio.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                mostrarMensaje(
                    `✅ ${result.message} | N° ${result.data.numero_secuencia}`, 
                    'success'
                );
                
                // Actualizar el campo ticket con el número de secuencia
                const ticketInput = document.getElementById('ticket');
                if (ticketInput) {
                    ticketInput.value = result.data.numero_secuencia;
                }
            } else {
                mostrarMensaje(`❌ ${result.error}`, 'error');
            }
        } catch (error) {
            console.error('Error al guardar:', error);
            mostrarMensaje('❌ Error de conexión. Verifique la configuración de la BD.', 'error');
        } finally {
            elements.btnGuardar.disabled = false;
            elements.btnGuardar.textContent = '💾 Guardar';
        }
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
  