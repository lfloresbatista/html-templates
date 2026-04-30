function mostrarMensaje(mensaje, tipo) {
    const mensajeDiv = document.getElementById('mensaje');
    mensajeDiv.style.display = 'block';
    mensajeDiv.textContent = mensaje;
    
    // Establecer el color según el tipo de mensaje
    if (tipo === 'error') {
        mensajeDiv.style.backgroundColor = '#ffdddd';
        mensajeDiv.style.color = '#8b0000';
    } else {
        mensajeDiv.style.backgroundColor = '#ddffdd';
        mensajeDiv.style.color = '#006400';
    }
    
    // Ocultar el mensaje después de 3 segundos
    setTimeout(() => {
        mensajeDiv.style.display = 'none';
    }, 3000);
}

function imprimirPDF() {
    const form = document.getElementById('serviceForm');
    
    // Configuración para html2pdf
    const opciones = {
        margin: 10,
        filename: 'formulario_servicio.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        enableLinks: true
    };

    try {
        // Crear el PDF
        html2pdf().from(form).set(opciones).save();
        mostrarMensaje('PDF generado y guardado exitosamente', 'success');
    } catch (error) {
        mostrarMensaje('Error al generar el PDF: ' + error.message, 'error');
    }
}
  