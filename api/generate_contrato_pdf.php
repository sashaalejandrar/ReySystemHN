<?php
/**
 * API: Generate Contract PDF
 * Generates a real PDF file with proper signature formatting
 */

session_start();

if (!isset($_SESSION['usuario'])) {
    die('No autenticado');
}

// Include FPDF
require_once(__DIR__ . '/../fpdf/fpdf.php');

try {
    $contrato_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($contrato_id <= 0) {
        die('ID inválido');
    }
    
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        die('Error de conexión: ' . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Get contract data
    $stmt = $conexion->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $contrato_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die('Contrato no encontrado');
    }
    
    $contrato = $result->fetch_assoc();
    $stmt->close();
    
    // Get template
    $template_result = $conexion->query("SELECT contenido FROM plantillas_contrato WHERE activa = 1 LIMIT 1");
    
    if (!$template_result || $template_result->num_rows === 0) {
        die('No se encontró plantilla activa');
    }
    
    $template_data = $template_result->fetch_assoc();
    $template = $template_data['contenido'];
    
    $conexion->close();
    
    // Format date in Spanish
    $fecha = new DateTime($contrato['fecha_creacion']);
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $fecha_formateada = $fecha->format('d') . ' de ' . $meses[$fecha->format('n') - 1] . ' de ' . $fecha->format('Y');
    
    // Replace placeholders
    $contenido = str_replace('[TIPO]', strtoupper($contrato['tipo']), $template);
    $contenido = str_replace('[FECHA]', $fecha_formateada, $contenido);
    $contenido = str_replace('[LUGAR]', $contrato['lugar'], $contenido);
    $contenido = str_replace('[NOMBRE_COMPLETO]', $contrato['nombre_completo'], $contenido);
    $contenido = str_replace('[IDENTIDAD]', $contrato['identidad'], $contenido);
    $contenido = str_replace('[SERVICIOS]', $contrato['servicios'], $contenido);
    $contenido = str_replace('[CARGO]', $contrato['cargo'], $contenido);
    $contenido = str_replace('[NOMBRE_EMPRESA]', $contrato['nombre_empresa'], $contenido);
    $contenido = str_replace('[CONTENIDO_ADICIONAL]', $contrato['contenido_adicional'] ?: '', $contenido);
    
    // Split content at signature section
    $partes = explode('ACEPTACION Y FIRMA:', $contenido);
    $contenido_principal = isset($partes[0]) ? $partes[0] : $contenido;
    $texto_aceptacion = isset($partes[1]) ? trim(explode('Firma:', $partes[1])[0]) : '';
    
    // Create PDF
    $pdf = new FPDF('P', 'mm', 'Letter');
    $pdf->AddPage();
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 20);
    
    // Set font
    $pdf->SetFont('Times', '', 11);
    
    // Write main content
    $pdf->MultiCell(0, 5, utf8_decode($contenido_principal), 0, 'J');
    
    // Add signature section
    $pdf->Ln(5);
    $pdf->SetFont('Times', 'B', 12);
    $pdf->Cell(0, 10, 'ACEPTACION Y FIRMA:', 0, 1, 'L');
    
    $pdf->SetFont('Times', '', 11);
    $pdf->MultiCell(0, 5, utf8_decode($texto_aceptacion), 0, 'J');
    
    $pdf->Ln(10);
    
    // Signature table
    $pdf->SetFont('Times', '', 10);
    
    // Column widths
    $col1 = 85;
    $col2 = 85;
    
    // Left column - Employer signature
    $pdf->Cell($col1, 6, 'Firma: _______________________', 0, 0, 'L');
    
    // Right column - Employee signature
    // Check if employee has signature image
    if (!empty($contrato['firma_empleado'])) {
        // Decode base64 image
        $firma_data = $contrato['firma_empleado'];
        
        // Remove data:image/png;base64, prefix if present
        if (strpos($firma_data, 'data:image') === 0) {
            $firma_data = substr($firma_data, strpos($firma_data, ',') + 1);
        }
        
        $firma_decoded = base64_decode($firma_data);
        
        // Save temporary image
        $temp_file = sys_get_temp_dir() . '/firma_' . $contrato_id . '.png';
        file_put_contents($temp_file, $firma_decoded);
        
        // Get current position
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        
        // Add signature image ON the line (where the underscores are)
        // Position it right after "Firma: " text
        $pdf->Image($temp_file, $x + 8, $y - 3, 50, 10);
        
        // Clean up temp file
        unlink($temp_file);
    }
    
    // Employee signature line
    $pdf->Cell($col2, 6, 'Firma: _______________________', 0, 1, 'L');
    
    // Names
    $pdf->Cell($col1, 6, utf8_decode('Nombre: Jesús Hernán Ordoñez Reyes'), 0, 0, 'L');
    $pdf->Cell($col2, 6, utf8_decode('Nombre: ' . $contrato['nombre_completo']), 0, 1, 'L');
    
    // Cargo
    $pdf->Cell($col1, 6, 'Cargo: Gerente / Propietario', 0, 0, 'L');
    $pdf->Cell($col2, 6, utf8_decode('Cargo: ' . $contrato['cargo']), 0, 1, 'L');
    
    // Calidad
    $pdf->Cell($col1, 6, 'Calidad: EL EMPLEADOR', 0, 0, 'L');
    $pdf->Cell($col2, 6, 'Calidad: EL EMPLEADO', 0, 1, 'L');
    
    // Generate filename
    $filename = 'Contrato_' . str_replace(' ', '_', $contrato['nombre_completo']) . '_' . date('Y-m-d', strtotime($contrato['fecha_creacion'])) . '.pdf';
    
    // Output PDF
    $pdf->Output('D', $filename);
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
