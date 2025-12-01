<?php
/**
 * Setup Script: Create Contract Tables via Browser
 * Access this file via browser: http://localhost/ReySystemDemo/setup_contratos.php
 */

// Database connection
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("<h1>Error de conexión</h1><p>" . $conexion->connect_error . "</p><p><strong>Asegúrate de que XAMPP/LAMPP esté corriendo!</strong></p>");
}

$conexion->set_charset("utf8mb4");

echo "<h1>Instalación de Módulo de Contratos</h1>";
echo "<hr>";

// Check if contratos table exists
$check_contratos = "SHOW TABLES LIKE 'contratos'";
$result = $conexion->query($check_contratos);

if ($result->num_rows > 0) {
    echo "<p>✓ Tabla 'contratos' ya existe.</p>";
} else {
    // Create contratos table
    $sql_contratos = "
    CREATE TABLE contratos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo VARCHAR(50) NOT NULL COMMENT 'Contrato o Convenio',
        fecha_creacion DATE NOT NULL,
        lugar VARCHAR(255) DEFAULT 'La Flecha, Macuelizo, Santa Bárbara',
        
        nombre_completo VARCHAR(255) NOT NULL,
        identidad VARCHAR(15) NOT NULL COMMENT 'Format: 0000-0000-00000',
        servicios TEXT NOT NULL,
        cargo VARCHAR(100) NOT NULL,
        
        nombre_empresa VARCHAR(255) NOT NULL,
        
        contenido_adicional TEXT,
        
        creado_por INT NOT NULL,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (creado_por) REFERENCES usuarios(Id) ON DELETE RESTRICT,
        INDEX idx_fecha (fecha_creacion),
        INDEX idx_tipo (tipo),
        INDEX idx_nombre (nombre_completo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conexion->query($sql_contratos)) {
        echo "<p style='color: green;'>✓ Tabla 'contratos' creada exitosamente.</p>";
    } else {
        die("<p style='color: red;'>✗ Error creando tabla contratos: " . $conexion->error . "</p>");
    }
}

// Check if plantillas_contrato table exists
$check_plantillas = "SHOW TABLES LIKE 'plantillas_contrato'";
$result = $conexion->query($check_plantillas);

if ($result->num_rows > 0) {
    echo "<p>✓ Tabla 'plantillas_contrato' ya existe.</p>";
    
    // Check if template exists
    $check_template = $conexion->query("SELECT COUNT(*) as total FROM plantillas_contrato WHERE activa = 1");
    $template_count = $check_template->fetch_assoc();
    
    if ($template_count['total'] == 0) {
        echo "<p style='color: orange;'>⚠ No hay plantilla activa. Insertando plantilla por defecto...</p>";
        $insert_template = true;
    } else {
        echo "<p>✓ Plantilla activa encontrada.</p>";
        $insert_template = false;
    }
} else {
    // Create plantillas_contrato table
    $sql_plantillas = "
    CREATE TABLE plantillas_contrato (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        contenido TEXT NOT NULL,
        activa TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_activa (activa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conexion->query($sql_plantillas)) {
        echo "<p style='color: green;'>✓ Tabla 'plantillas_contrato' creada exitosamente.</p>";
        $insert_template = true;
    } else {
        die("<p style='color: red;'>✗ Error creando tabla plantillas_contrato: " . $conexion->error . "</p>");
    }
}

// Insert default template if needed
if ($insert_template) {
    $default_template = "[TIPO]

Fecha: [FECHA]
Lugar: [LUGAR]

══════════════════════════════════════════════════════════════════════════════
                           CONTRATO DE SERVICIOS LABORALES
══════════════════════════════════════════════════════════════════════════════

COMPARECIENTES:

Por una parte, el señor JESÚS HERNÁN ORDOÑEZ REYES, en su calidad de Gerente y Propietario de [NOMBRE_EMPRESA], quien en lo sucesivo se denominará \"EL EMPLEADOR\", y por otra parte el/la señor/a [NOMBRE_COMPLETO], con número de identidad [IDENTIDAD], quien en adelante se denominará \"EL EMPLEADO\", ambas partes con plena capacidad legal para contratar y obligarse, convienen en celebrar el presente CONTRATO DE SERVICIOS LABORALES, el cual se regirá por las siguientes:

══════════════════════════════════════════════════════════════════════════════
                                    CLÁUSULAS
══════════════════════════════════════════════════════════════════════════════

PRIMERA: OBJETO DEL CONTRATO
EL EMPLEADO se compromete a prestar sus servicios profesionales a EL EMPLEADOR, desempeñando las funciones de [CARGO], las cuales incluyen específicamente: [SERVICIOS]. EL EMPLEADO declara tener la capacidad, conocimientos y experiencia necesarios para el desempeño de dichas funciones.

SEGUNDA: JORNADA LABORAL Y HORARIO
EL EMPLEADO se compromete a cumplir con el horario laboral establecido por EL EMPLEADOR, respetando estrictamente la puntualidad en el ingreso y salida. La jornada laboral será la establecida por la empresa, con un día de descanso semanal, siendo este el día jueves, según lo previamente convenido entre ambas partes. Este día podrá ser modificado por mutuo acuerdo o por necesidades operativas de la empresa, siempre manteniendo comunicación previa y respetando el derecho al descanso semanal del empleado.

TERCERA: DISPONIBILIDAD Y COMUNICACIÓN
EL EMPLEADO se compromete a mantener disponibilidad y comunicación constante con EL EMPLEADOR durante su jornada laboral y, en casos de emergencia o situaciones extraordinarias relacionadas con el trabajo, deberá estar disponible para atender llamadas o requerimientos urgentes, siempre dentro de los límites razonables y respetando su tiempo de descanso.

CUARTA: REMUNERACIÓN Y FORMA DE PAGO
EL EMPLEADOR se compromete a pagar a EL EMPLEADO una remuneración justa y puntual por los servicios prestados, según los términos y montos previamente acordados entre ambas partes. El pago se realizará de forma [quincenal/mensual] mediante [efectivo/transferencia bancaria/cheque], en las fechas establecidas por la empresa. Cualquier modificación en el monto o forma de pago deberá ser acordada por escrito entre ambas partes.

QUINTA: PRESTACIONES Y BENEFICIOS
EL EMPLEADO tendrá derecho a las prestaciones laborales establecidas por la legislación hondureña vigente, incluyendo pero no limitándose a: vacaciones, aguinaldo, y demás beneficios que por ley le correspondan. EL EMPLEADOR se compromete a cumplir con todas las obligaciones patronales establecidas en el Código de Trabajo de Honduras.

SEXTA: CONFIDENCIALIDAD Y PROTECCIÓN DE INFORMACIÓN
EL EMPLEADO se compromete a mantener estricta confidencialidad sobre toda la información sensible, comercial, financiera, técnica y operativa de la empresa a la que tenga acceso durante el desempeño de sus funciones. Esta obligación de confidencialidad permanecerá vigente incluso después de la terminación de la relación laboral. El incumplimiento de esta cláusula podrá dar lugar a acciones legales por parte de EL EMPLEADOR.

SÉPTIMA: RESPONSABILIDADES Y OBLIGACIONES DEL EMPLEADO
EL EMPLEADO se compromete a:
a) Realizar sus labores con profesionalismo, dedicación, eficiencia y buena fe.
b) Cumplir con los estándares de calidad establecidos por la empresa.
c) Cuidar y hacer buen uso de los bienes, equipos, herramientas y materiales proporcionados por EL EMPLEADOR.
d) Acatar las políticas, normas y procedimientos internos de la empresa.
e) Mantener una conducta ética y profesional en todo momento.
f) Reportar cualquier irregularidad o situación que pueda afectar a la empresa.
g) Colaborar con sus compañeros de trabajo y superiores de manera respetuosa.

OCTAVA: CONDUCTA Y ÉTICA PROFESIONAL
EL EMPLEADO se compromete a mantener una conducta intachable, tanto dentro como fuera de las instalaciones de la empresa cuando actúe en representación de la misma. Deberá tratar con respeto y cortesía a clientes, proveedores, compañeros de trabajo y superiores. Queda estrictamente prohibido el acoso de cualquier tipo, la discriminación, el uso de sustancias prohibidas, y cualquier conducta que atente contra la dignidad de las personas o la imagen de la empresa.

NOVENA: PROPIEDAD INTELECTUAL
Cualquier invención, diseño, proceso, método o mejora desarrollada por EL EMPLEADO durante el desempeño de sus funciones y relacionada con las actividades de la empresa, será propiedad exclusiva de EL EMPLEADOR. EL EMPLEADO renuncia expresamente a cualquier derecho de propiedad intelectual sobre dichas creaciones.

DÉCIMA: PROHIBICIONES
EL EMPLEADO no podrá, durante la vigencia de este contrato:
a) Prestar servicios similares a empresas competidoras.
b) Divulgar información confidencial de la empresa.
c) Utilizar los recursos de la empresa para fines personales sin autorización.
d) Realizar actividades que constituyan conflicto de intereses.
e) Ausentarse de sus labores sin justificación o autorización previa.

DÉCIMA PRIMERA: CAUSALES DE TERMINACIÓN
Este contrato podrá darse por terminado por las siguientes causas:
a) Mutuo acuerdo entre las partes, manifestado por escrito.
b) Renuncia voluntaria de EL EMPLEADO, con previo aviso de quince (15) días.
c) Despido justificado por incumplimiento grave de las obligaciones laborales.
d) Causas establecidas en el Código de Trabajo de Honduras.

DÉCIMA SEGUNDA: PREAVISO
Cualquiera de las partes que desee dar por terminado este contrato deberá notificar a la otra con un mínimo de quince (15) días de anticipación, salvo en casos de despido justificado o renuncia por causa grave. El incumplimiento de este preaviso dará derecho a la parte afectada a reclamar la indemnización correspondiente según la ley.

DÉCIMA TERCERA: MODIFICACIONES AL CONTRATO
Cualquier modificación a los términos de este contrato deberá ser acordada por escrito y firmada por ambas partes. No se reconocerán modificaciones verbales o tácitas.

DÉCIMA CUARTA: RESOLUCIÓN DE CONFLICTOS
Cualquier controversia o conflicto que surja de la interpretación o ejecución de este contrato será resuelto de manera amigable entre las partes. En caso de no llegar a un acuerdo, las partes se someterán a la jurisdicción de los tribunales competentes de Honduras, renunciando expresamente a cualquier otro fuero que pudiera corresponderles.

DÉCIMA QUINTA: LEGISLACIÓN APLICABLE
Este contrato se rige por las disposiciones del Código de Trabajo de la República de Honduras y demás leyes laborales aplicables. Cualquier aspecto no contemplado en este documento se regirá por lo establecido en la legislación laboral vigente.

[CONTENIDO_ADICIONAL]

══════════════════════════════════════════════════════════════════════════════

ACEPTACIÓN Y FIRMA:

Las partes manifiestan su plena conformidad con todos los términos y condiciones establecidos en el presente contrato, y en señal de aceptación lo suscriben en dos ejemplares de igual valor y contenido, en el lugar y fecha indicados al inicio de este documento.


_____________________________              _____________________________
Jesús Hernán Ordoñez Reyes                [NOMBRE_COMPLETO]
Gerente / Propietario                      [CARGO]
EL EMPLEADOR                               EL EMPLEADO";

    $stmt = $conexion->prepare("INSERT INTO plantillas_contrato (nombre, contenido, activa) VALUES (?, ?, 1)");
    $nombre = "Plantilla Estándar";
    $stmt->bind_param("ss", $nombre, $default_template);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Plantilla por defecto insertada exitosamente.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error insertando plantilla: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

$conexion->close();

echo "<hr>";
echo "<h2 style='color: green;'>✓ Instalación Completada!</h2>";
echo "<p>El módulo de contratos está listo para usar.</p>";
echo "<p><a href='contratos.php' style='background: #1152d4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Ir a Contratos</a></p>";
?>
