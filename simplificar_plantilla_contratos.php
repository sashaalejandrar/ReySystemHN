<?php
/**
 * Update Template - Simplified Version for Employees
 * Simpler clauses, more direct and easy to understand
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("<h1>Error de conexión</h1><p>" . $conexion->connect_error . "</p>");
}

$conexion->set_charset("utf8mb4");

echo "<h1>Actualizar Plantilla de Contratos (Versión Simplificada)</h1>";
echo "<hr>";

// Simplified template for employees
$plantilla_simple = "[TIPO]

Fecha: [FECHA]
Lugar: [LUGAR]

--------------------------------------------------------------------------------
                       CONTRATO LABORAL
--------------------------------------------------------------------------------

PARTES:

EMPLEADOR: Jesus Hernan Ordonez Reyes, Gerente y Propietario de [NOMBRE_EMPRESA]

EMPLEADO: [NOMBRE_COMPLETO], Identidad: [IDENTIDAD]

Ambas partes acuerdan celebrar el presente contrato bajo las siguientes:

--------------------------------------------------------------------------------
                              CONDICIONES
--------------------------------------------------------------------------------

1. PUESTO Y FUNCIONES
El empleado trabajara como [CARGO] y realizara las siguientes tareas:
[SERVICIOS]

2. HORARIO
El empleado cumplira con el horario establecido por la empresa, siendo puntual en entrada y salida. Tendra un dia de descanso semanal (jueves), el cual puede cambiar segun necesidades de la empresa.

3. SALARIO
La empresa pagara al empleado de forma quincenal o mensual, segun lo acordado. El pago se hara en efectivo, transferencia o cheque.

4. RESPONSABILIDADES DEL EMPLEADO
El empleado se compromete a:
- Trabajar con dedicacion y profesionalismo
- Cuidar los equipos y materiales de la empresa
- Seguir las normas y politicas de la empresa
- Mantener buena conducta y respeto
- Guardar confidencialidad de la informacion de la empresa

5. PROHIBICIONES
El empleado NO puede:
- Trabajar para empresas competidoras
- Compartir informacion confidencial
- Usar recursos de la empresa para fines personales
- Faltar sin justificacion



6. TERMINACION DEL CONTRATO
El contrato puede terminar por:
- Acuerdo entre ambas partes
- Renuncia del empleado (con 15 dias de aviso)
- Despido justificado por incumplimiento
- Causas establecidas por ley

7. AVISO PREVIO
Si alguna parte desea terminar el contrato, debe avisar con 15 dias de anticipacion.

8. MODIFICACIONES
Cualquier cambio a este contrato debe hacerse por escrito y firmado por ambas partes.

9. LEGISLACION
Este contrato se rige por el Codigo de Trabajo de Honduras.

[CONTENIDO_ADICIONAL]

--------------------------------------------------------------------------------

ACEPTACION Y FIRMA:

Ambas partes estan de acuerdo con todo lo establecido en este contrato y lo firman en dos ejemplares.




Firma: _______________________          Firma: _______________________
Nombre: Jesus Hernan Ordonez Reyes      Nombre: [NOMBRE_COMPLETO]
Cargo: Gerente / Propietario           Cargo: [CARGO]
Calidad: EL EMPLEADOR                   Calidad: EL EMPLEADO";

// Update the template
$stmt = $conexion->prepare("UPDATE plantillas_contrato SET contenido = ? WHERE activa = 1");
$stmt->bind_param("s", $plantilla_simple);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "<p style='color: green; font-size: 18px;'><strong>✓ Plantilla actualizada exitosamente a versión SIMPLIFICADA!</strong></p>";
        echo "<p>Se actualizaron " . $stmt->affected_rows . " registro(s).</p>";
        echo "<p><strong>Cambios realizados:</strong></p>";
        echo "<ul>";
        echo "<li>✓ Reducido de 15 a 10 cláusulas</li>";
        echo "<li>✓ Lenguaje más simple y directo</li>";
        echo "<li>✓ Eliminadas cláusulas complejas (propiedad intelectual, etc.)</li>";
        echo "<li>✓ Formato más limpio y fácil de leer</li>";
        echo "<li>✓ Mantiene lo esencial: horario, salario, responsabilidades</li>";
        echo "</ul>";
        echo "<br><a href='contratos.php' style='background: #1152d4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir a Contratos</a>";
    } else {
        echo "<p style='color: orange;'><strong>⚠ No se encontró plantilla activa para actualizar.</strong></p>";
        echo "<p>Ejecuta primero: <a href='migrations/create_contratos_tables.php'>create_contratos_tables.php</a></p>";
    }
} else {
    echo "<p style='color: red;'><strong>✗ Error al actualizar:</strong> " . $stmt->error . "</p>";
}

$stmt->close();
$conexion->close();

echo "<hr>";
echo "<p><em>Plantilla simplificada para empleados de confianza - Versión 2.0</em></p>";
?>
