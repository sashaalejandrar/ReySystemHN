// ===================================
// IMPORTADOR DE CSV
// ===================================

function importarCSV(archivo) {
    if (!archivo) {
        mostrarNotificacion('warning', 'Sin archivo', 'Por favor selecciona un archivo CSV');
        return;
    }

    const reader = new FileReader();

    reader.onload = function (e) {
        try {
            const texto = e.target.result;
            procesarCSV(texto);
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('error', 'Error al leer CSV', 'Error al leer el archivo CSV: ' + error.message);
        }
    };

    reader.onerror = function () {
        mostrarNotificacion('error', 'Error de lectura', 'Error al leer el archivo');
    };

    reader.readAsText(archivo);
}

function procesarCSV(texto) {
    const lineas = texto.split('\n');

    if (lineas.length < 2) {
        mostrarNotificacion('warning', 'CSV Vacío', 'El archivo CSV está vacío o no tiene datos');
        return;
    }

    // Saltar la primera línea (header)
    let productosImportados = 0;

    for (let i = 1; i < lineas.length; i++) {
        const linea = lineas[i].trim();

        if (!linea) continue; // Saltar líneas vacías

        // Dividir por comas, pero respetando comillas
        const columnas = parsearLineaCSV(linea);

        if (columnas.length >= 3) {
            tabla.agregarProducto({
                nombre: columnas[0] || '',
                descripcionCorta: columnas[1] || '',
                marca: columnas[2] || '',
                descripcion: columnas[3] || '',
                tipoEmpaque: columnas[4] || 'Unidad',
                unidadesPorEmpaque: parseInt(columnas[5]) || 1,
                costoUnidad: parseFloat(columnas[6]) || 0,
                costoEmpaque: parseFloat(columnas[7]) || 0,
                precioUnidad: parseFloat(columnas[8]) || 0,
                precioEmpaque: parseFloat(columnas[9]) || 0,
                margen: parseFloat(columnas[10]) || 0,
                proveedor: columnas[11] || '',
                direccionProveedor: columnas[12] || '',
                contactoProveedor: columnas[13] || ''
            });

            productosImportados++;
        }
    }

    if (productosImportados > 0) {
        mostrarNotificacion('success', 'Importación Exitosa', `Se importaron ${productosImportados} productos del CSV`);
    } else {
        mostrarNotificacion('error', 'Error de Importación', 'No se pudieron importar productos. Verifica el formato del CSV');
    }

    // Limpiar el input de archivo
    document.getElementById('csvFile').value = '';
}

// Función auxiliar para parsear líneas CSV respetando comillas
function parsearLineaCSV(linea) {
    const resultado = [];
    let dentroComillas = false;
    let valorActual = '';

    for (let i = 0; i < linea.length; i++) {
        const char = linea[i];

        if (char === '"') {
            dentroComillas = !dentroComillas;
        } else if (char === ',' && !dentroComillas) {
            resultado.push(valorActual.trim());
            valorActual = '';
        } else {
            valorActual += char;
        }
    }

    // Agregar el último valor
    resultado.push(valorActual.trim());

    return resultado;
}

console.log('✅ Importador CSV cargado');
