<?php
/**
 * Update Template - Remove special characters
 * Fixes PDF generation issues with special characters
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("<h1>Error de conexión</h1><p>" . $conexion->connect_error . "</p>");
}

$conexion->set_charset("utf8mb4");

echo "<h1>Actualizar Plantilla de Contratos (Sin Caracteres Especiales)</h1>";
echo "<hr>";

// Template without special characters
$plantilla_correcta = "[TIPO]

Fecha: [FECHA]
Lugar: [LUGAR]

--------------------------------------------------------------------------------
                           CONTRATO DE SERVICIOS LABORALES
--------------------------------------------------------------------------------

COMPARECIENTES:

Por una parte, el senor JESUS HERNAN ORDONEZ REYES, en su calidad de Gerente y Propietario de [NOMBRE_EMPRESA], quien en lo sucesivo se denominara \"EL EMPLEADOR\", y por otra parte el/la senor/a [NOMBRE_COMPLETO], con numero de identidad [IDENTIDAD], quien en adelante se denominara \"EL EMPLEADO\", ambas partes con plena capacidad legal para contratar y obligarse, convienen en celebrar el presente CONTRATO DE SERVICIOS LABORALES, el cual se regira por las siguientes:

--------------------------------------------------------------------------------
                                    CLAUSULAS
--------------------------------------------------------------------------------

PRIMERA: OBJETO DEL CONTRATO
EL EMPLEADO se compromete a prestar sus servicios profesionales a EL EMPLEADOR, desempenando las funciones de [CARGO], las cuales incluyen especificamente: [SERVICIOS]. EL EMPLEADO declara tener la capacidad, conocimientos y experiencia necesarios para el desempeno de dichas funciones.

SEGUNDA: JORNADA LABORAL Y HORARIO
EL EMPLEADO se compromete a cumplir con el horario laboral establecido por EL EMPLEADOR, respetando estrictamente la puntualidad en el ingreso y salida. La jornada laboral sera la establecida por la empresa, con un dia de descanso semanal, siendo este el dia jueves, segun lo previamente convenido entre ambas partes. Este dia podra ser modificado por mutuo acuerdo o por necesidades operativas de la empresa, siempre manteniendo comunicacion previa y respetando el derecho al descanso semanal del empleado.

TERCERA: DISPONIBILIDAD Y COMUNICACION
EL EMPLEADO se compromete a mantener disponibilidad y comunicacion constante con EL EMPLEADOR durante su jornada laboral y, en casos de emergencia o situaciones extraordinarias relacionadas con el trabajo, debera estar disponible para atender llamadas o requerimientos urgentes, siempre dentro de los limites razonables y respetando su tiempo de descanso.

CUARTA: REMUNERACION Y FORMA DE PAGO
EL EMPLEADOR se compromete a pagar a EL EMPLEADO una remuneracion justa y puntual por los servicios prestados, segun los terminos y montos previamente acordados entre ambas partes. El pago se realizara de forma quincenal o mensual mediante efectivo, transferencia bancaria o cheque, en las fechas establecidas por la empresa. Cualquier modificacion en el monto o forma de pago debera ser acordada por escrito entre ambas partes.

QUINTA: PRESTACIONES Y BENEFICIOS
EL EMPLEADO tendra derecho a las prestaciones laborales establecidas por la legislacion hondurena vigente, incluyendo pero no limitandose a: vacaciones, aguinaldo, y demas beneficios que por ley le correspondan. EL EMPLEADOR se compromete a cumplir con todas las obligaciones patronales establecidas en el Codigo de Trabajo de Honduras.

SEXTA: CONFIDENCIALIDAD Y PROTECCION DE INFORMACION
EL EMPLEADO se compromete a mantener estricta confidencialidad sobre toda la informacion sensible, comercial, financiera, tecnica y operativa de la empresa a la que tenga acceso durante el desempeno de sus funciones. Esta obligacion de confidencialidad permanecera vigente incluso despues de la terminacion de la relacion laboral. El incumplimiento de esta clausula podra dar lugar a acciones legales por parte de EL EMPLEADOR.

SEPTIMA: RESPONSABILIDADES Y OBLIGACIONES DEL EMPLEADO
EL EMPLEADO se compromete a:
a) Realizar sus labores con profesionalismo, dedicacion, eficiencia y buena fe.
b) Cumplir con los estandares de calidad establecidos por la empresa.
c) Cuidar y hacer buen uso de los bienes, equipos, herramientas y materiales proporcionados por EL EMPLEADOR.
d) Acatar las politicas, normas y procedimientos internos de la empresa.
e) Mantener una conducta etica y profesional en todo momento.
f) Reportar cualquier irregularidad o situacion que pueda afectar a la empresa.
g) Colaborar con sus companeros de trabajo y superiores de manera respetuosa.

OCTAVA: CONDUCTA Y ETICA PROFESIONAL
EL EMPLEADO se compromete a mantener una conducta intachable, tanto dentro como fuera de las instalaciones de la empresa cuando actue en representacion de la misma. Debera tratar con respeto y cortesia a clientes, proveedores, companeros de trabajo y superiores. Queda estrictamente prohibido el acoso de cualquier tipo, la discriminacion, el uso de sustancias prohibidas, y cualquier conducta que atente contra la dignidad de las personas o la imagen de la empresa.

NOVENA: PROPIEDAD INTELECTUAL
Cualquier invencion, diseno, proceso, metodo o mejora desarrollada por EL EMPLEADO durante el desempeno de sus funciones y relacionada con las actividades de la empresa, sera propiedad exclusiva de EL EMPLEADOR. EL EMPLEADO renuncia expresamente a cualquier derecho de propiedad intelectual sobre dichas creaciones.

DECIMA: PROHIBICIONES
EL EMPLEADO no podra, durante la vigencia de este contrato:
a) Prestar servicios similares a empresas competidoras.
b) Divulgar informacion confidencial de la empresa.
c) Utilizar los recursos de la empresa para fines personales sin autorizacion.
d) Realizar actividades que constituyan conflicto de intereses.
e) Ausentarse de sus labores sin justificacion o autorizacion previa.

DECIMA PRIMERA: CAUSALES DE TERMINACION
Este contrato podra darse por terminado por las siguientes causas:
a) Mutuo acuerdo entre las partes, manifestado por escrito.
b) Renuncia voluntaria de EL EMPLEADO, con previo aviso de quince (15) dias.
c) Despido justificado por incumplimiento grave de las obligaciones laborales.
d) Causas establecidas en el Codigo de Trabajo de Honduras.

DECIMA SEGUNDA: PREAVISO
Cualquiera de las partes que desee dar por terminado este contrato debera notificar a la otra con un minimo de quince (15) dias de anticipacion, salvo en casos de despido justificado o renuncia por causa grave. El incumplimiento de este preaviso dara derecho a la parte afectada a reclamar la indemnizacion correspondiente segun la ley.

DECIMA TERCERA: MODIFICACIONES AL CONTRATO
Cualquier modificacion a los terminos de este contrato debera ser acordada por escrito y firmada por ambas partes. No se reconoceran modificaciones verbales o tacitas.

DECIMA CUARTA: RESOLUCION DE CONFLICTOS
Cualquier controversia o conflicto que surja de la interpretacion o ejecucion de este contrato sera resuelto de manera amigable entre las partes. En caso de no llegar a un acuerdo, las partes se someteran a la jurisdiccion de los tribunales competentes de Honduras, renunciando expresamente a cualquier otro fuero que pudiera corresponderles.

DECIMA QUINTA: LEGISLACION APLICABLE
Este contrato se rige por las disposiciones del Codigo de Trabajo de la Republica de Honduras y demas leyes laborales aplicables. Cualquier aspecto no contemplado en este documento se regira por lo establecido en la legislacion laboral vigente.

[CONTENIDO_ADICIONAL]

--------------------------------------------------------------------------------

ACEPTACION Y FIRMA:

Las partes manifiestan su plena conformidad con todos los terminos y condiciones establecidos en el presente contrato, y en senal de aceptacion lo suscriben en dos ejemplares de igual valor y contenido, en el lugar y fecha indicados al inicio de este documento.




Firma: _______________________          Firma: _______________________
Nombre: Jesus Hernan Ordoñez Reyes      Nombre: [NOMBRE_COMPLETO]
Cargo: Gerente / Propietario           Cargo: [CARGO]
Calidad: EL EMPLEADOR                   Calidad: EL EMPLEADO";

// Update the template
$stmt = $conexion->prepare("UPDATE plantillas_contrato SET contenido = ? WHERE activa = 1");
$stmt->bind_param("s", $plantilla_correcta);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "<p style='color: green; font-size: 18px;'><strong>✓ Plantilla actualizada exitosamente!</strong></p>";
        echo "<p>Se actualizaron " . $stmt->affected_rows . " registro(s).</p>";
        echo "<p style='color: blue;'>✓ Se eliminaron caracteres especiales (═, ñ, á, é, etc.)</p>";
        echo "<p style='color: blue;'>✓ Se reemplazaron con caracteres compatibles con PDF</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No se encontró ninguna plantilla activa para actualizar.</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error actualizando plantilla: " . $stmt->error . "</p>";
}

$stmt->close();
$conexion->close();

echo "<hr>";
echo "<h2 style='color: green;'>✓ Proceso Completado!</h2>";
echo "<p>Ahora los PDFs se generarán correctamente sin caracteres extraños.</p>";
echo "<p><a href='contratos.php' style='background: #1152d4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Ir a Contratos</a></p>";
?>
