// ===================================
// PARSEO INTELIGENTE CON IA (GROQ)
// ===================================

/**
 * Parsea texto de factura usando IA (Groq) para extracción inteligente
 * Esto reemplaza los patrones estáticos con análisis dinámico
 */
async function parsearTextoConIA(texto) {
    console.log('🤖 Iniciando parseo inteligente con Groq IA...');

    try {
        const prompt = `Analiza esta factura y extrae TODOS los productos en formato JSON.

TEXTO DE LA FACTURA:
${texto}

INSTRUCCIONES:
1. Extrae TODOS los productos de la tabla
2. Para cada producto extrae:
   - codigo: código de barras si existe (13 dígitos o el código mostrado). Si NO hay código, usa cadena vacía ""
   - nombre: descripción del producto SIN incluir el código (solo el nombre descriptivo)
   - cantidad: cantidad (número entero)
   - precio: precio unitario (número decimal, sin símbolos)

3. IMPORTANTE: 
   - El campo "nombre" NO debe incluir el código, solo la descripción
   - Si NO hay código de barras en la factura, deja "codigo" como cadena vacía ""
   - El sistema buscará el producto por nombre si no hay código
   - Convierte precios de formato "L 15.00" a solo "15.00"
   - Ignora filas de SUBTOTAL, IVA, TOTAL
   - Extrae TODOS los productos de la tabla

EJEMPLOS:
- Con código: {"codigo": "7421001643011", "nombre": "Limpiox Pequeño 450ml MZ", "cantidad": 4, "precio": 15.00}
- Sin código: {"codigo": "", "nombre": "Limpiox Pequeño 450ml MZ", "cantidad": 4, "precio": 15.00}

FORMATO DE SALIDA (JSON puro, sin markdown):
{
  "productos": [
    {"codigo": "7421001643011", "nombre": "Limpiox Pequeño 450ml MZ", "cantidad": 4, "precio": 15.00},
    {"codigo": "7506306223035", "nombre": "Sedal Ceramidas Shampoo", "cantidad": 3, "precio": 65.00}
  ]
}

Responde SOLO con el JSON, sin explicaciones adicionales.`;

        const response = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer YOUR_GROQ_API_KEY_HERE'
            },
            body: JSON.stringify({
                model: 'llama-3.3-70b-versatile',
                messages: [
                    {
                        role: 'user',
                        content: prompt
                    }
                ],
                temperature: 0.1,
                max_tokens: 4096
            })
        });

        if (!response.ok) {
            throw new Error(`Groq API error: ${response.status}`);
        }

        const data = await response.json();
        const contenido = data.choices[0].message.content;

        console.log('📄 Respuesta de Groq:', contenido);

        // Extraer JSON de la respuesta
        let jsonData;
        try {
            // Intentar parsear directamente
            jsonData = JSON.parse(contenido);
        } catch (e) {
            // Si falla, buscar JSON en la respuesta
            const jsonMatch = contenido.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                jsonData = JSON.parse(jsonMatch[0]);
            } else {
                throw new Error('No se pudo extraer JSON de la respuesta');
            }
        }

        if (!jsonData.productos || !Array.isArray(jsonData.productos)) {
            throw new Error('Formato de respuesta inválido');
        }

        console.log(`✅ ${jsonData.productos.length} productos extraídos con IA`);

        // Formatear productos para el sistema
        const productosFormateados = jsonData.productos.map(p => {
            const codigo = String(p.codigo || '');
            let nombre = String(p.nombre || '').trim();

            // Limpiar el nombre: quitar el código si Groq lo incluyó por error
            if (codigo && nombre.startsWith(codigo)) {
                nombre = nombre.substring(codigo.length).trim();
            }

            // Formatear nombre final: solo el nombre descriptivo (sin código)
            // El código se mostrará en un campo separado en la UI

            return {
                codigo: codigo,
                nombre: nombre, // Solo el nombre, sin código
                cantidad: parseInt(p.cantidad) || 1,
                precio: parseFloat(p.precio) || 0
            };
        });

        return {
            success: true,
            productos: productosFormateados
        };

    } catch (error) {
        console.error('❌ Error en parseo con IA:', error);
        return {
            success: false,
            productos: [],
            error: error.message
        };
    }
}

/**
 * Verifica productos en la base de datos
 */
async function verificarProductosEnBD(productos) {
    try {
        console.log('🔍 Verificando productos en base de datos...');

        const response = await fetch('api/verificar_productos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ productos: productos })
        });

        const data = await response.json();

        if (data.success) {
            console.log(`✅ ${data.productos.length} productos verificados en BD`);
            return data;
        } else {
            throw new Error(data.message || 'Error al verificar productos');
        }

    } catch (error) {
        console.error('❌ Error verificando productos:', error);
        // Si falla la verificación, devolver productos sin verificar
        return {
            success: true,
            productos: productos.map(p => ({
                ...p,
                existe: false,
                stockActual: 0
            }))
        };
    }
}
