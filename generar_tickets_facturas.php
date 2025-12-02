<?php
/**
 * Archivo: generar_documentos.php
 * Contiene funciones para generar facturas y tickets para diferentes operaciones del sistema
 * Requiere la librería TCPDF (puedes instalarla con composer require tecnickcom/tcpdf)
 */

// Incluir la librería TCPDF
require_once 'vendor/autoload.php';
use TCPDF;

class GeneradorDocumentos {
    private $pdf;
    private $tipo_documento; // 'ticket' o 'factura'
    private $datos_empresa;
    private $conexion;
    
    /**
     * Constructor
     * @param string $tipo_documento Tipo de documento ('ticket' o 'factura')
     * @param mysqli $conexion Conexión a la base de datos
     */
    public function __construct($tipo_documento = 'ticket', $conexion = null) {
        $this->tipo_documento = $tipo_documento;
        $this->conexion = $conexion;
        
        // Cargar datos de la empresa (ajusta según tu estructura de base de datos)
        $this->cargarDatosEmpresa();
        
        // Configurar el PDF según el tipo de documento
        $this->configurarPDF();
    }
    
    /**
     * Cargar datos de la empresa desde la base de datos
     */
    private function cargarDatosEmpresa() {
        // Valores por defecto si no hay conexión a la base de datos
        $this->datos_empresa = [
            'nombre' => 'Bodega Siloe',
            'direccion' => 'La Flecha, Macuelizo, Santa Bárbara.',
            'telefono' => '+504 9770-2487',
            'email' => 'bodega.siloe@reysystem.app',
            'nit' => 'NIT de la empresa',
            'regimen' => 'Régimen común',
            'resolucion' => 'Resolución DIAN 123456789'
        ];
        
        // Si hay conexión, cargar desde la base de datos
        if ($this->conexion) {
            $stmt = $this->conexion->prepare("SELECT * FROM configuracion_app LIMIT 1");
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $this->datos_empresa = $resultado->fetch_assoc();
            }
            $stmt->close();
        }
    }
    
    /**
     * Configurar el PDF según el tipo de documento
     */
    private function configurarPDF() {
        // Crear instancia de TCPDF
        if ($this->tipo_documento === 'factura') {
            // Configuración para factura (tamaño carta)
            $this->pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
            $this->pdf->SetMargins(10, 10, 10);
            $this->pdf->SetAutoPageBreak(true, 20);
        } else {
            // Configuración para ticket (tamaño más pequeño)
            $this->pdf = new TCPDF('P', 'mm', array(80, 200), true, 'UTF-8', false);
            $this->pdf->SetMargins(5, 5, 5);
            $this->pdf->SetAutoPageBreak(true, 10);
        }
        
        // Configuración general
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetFont('helvetica', '', 10);
    }
    
    /**
     * Generar documento para apertura de caja
     * @param array $datos Datos de la apertura de caja
     * @return string Ruta del archivo generado
     */
    public function generarAperturaCaja($datos) {
        $this->pdf->AddPage();
        
        // Encabezado del documento
        $this->agregarEncabezado('APERTURA DE CAJA');
        
        // Información de la apertura
        $this->pdf->SetFont('', 'B', 12);
        $this->pdf->Cell(0, 10, 'Información de Apertura', 0, 1, 'C');
        $this->pdf->SetFont('', '', 10);
        
        $this->pdf->Ln(5);
        $this->pdf->Cell(0, 8, 'Fecha y Hora: ' . $datos['fecha_apertura'], 0, 1);
        $this->pdf->Cell(0, 8, 'Cajero: ' . $datos['nombre_cajero'], 0, 1);
        $this->pdf->Cell(0, 8, 'Monto Inicial: L ' . number_format($datos['monto_inicial'], 2), 0, 1);
        
        if (isset($datos['observaciones']) && !empty($datos['observaciones'])) {
            $this->pdf->Ln(5);
            $this->pdf->Cell(0, 8, 'Observaciones: ' . $datos['observaciones'], 0, 1);
        }
        
        // Pie de página
        $this->agregarPiePagina();
        
        // Guardar y devolver la ruta del archivo
        return $this->guardarDocumento('apertura_caja_' . $datos['id']);
    }
    
    /**
     * Generar documento para cierre de caja
     * @param array $datos Datos del cierre de caja
     * @return string Ruta del archivo generado
     */
    public function generarCierreCaja($datos) {
        $this->pdf->AddPage();
        
        // Encabezado del documento
        $this->agregarEncabezado('CIERRE DE CAJA');
        
        // Información del cierre
        $this->pdf->SetFont('', 'B', 12);
        $this->pdf->Cell(0, 10, 'Información de Cierre', 0, 1, 'C');
        $this->pdf->SetFont('', '', 10);
        
        $this->pdf->Ln(5);
        $this->pdf->Cell(0, 8, 'Fecha y Hora: ' . $datos['fecha_cierre'], 0, 1);
        $this->pdf->Cell(0, 8, 'Cajero: ' . $datos['nombre_cajero'], 0, 1);
        $this->pdf->Cell(0, 8, 'Monto Inicial: L ' . number_format($datos['monto_inicial'], 2), 0, 1);
        $this->pdf->Cell(0, 8, 'Total Ventas: L ' . number_format($datos['total_ventas'], 2), 0, 1);
        $this->pdf->Cell(0, 8, 'Total Ingresos: L ' . number_format($datos['total_ingresos'], 2), 0, 1);
        $this->pdf->Cell(0, 8, 'Total Egresos: L ' . number_format($datos['total_egresos'], 2), 0, 1);
        
        // Línea separadora
        $this->pdf->Ln(3);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(0, 8, 'Monto Final: L ' . number_format($datos['monto_final'], 2), 0, 1);
        $this->pdf->SetFont('', '', 10);
        
        if (isset($datos['observaciones']) && !empty($datos['observaciones'])) {
            $this->pdf->Ln(5);
            $this->pdf->Cell(0, 8, 'Observaciones: ' . $datos['observaciones'], 0, 1);
        }
        
        // Pie de página
        $this->agregarPiePagina();
        
        // Guardar y devolver la ruta del archivo
        return $this->guardarDocumento('cierre_caja_' . $datos['id']);
    }
    
    /**
     * Generar documento para arqueo de caja
     * @param array $datos Datos del arqueo de caja
     * @return string Ruta del archivo generado
     */
    public function generarArqueoCaja($datos) {
        $this->pdf->AddPage();
        
        // Encabezado del documento
        $this->agregarEncabezado('ARQUEO DE CAJA');
        
        // Información del arqueo
        $this->pdf->SetFont('', 'B', 12);
        $this->pdf->Cell(0, 10, 'Información del Arqueo', 0, 1, 'C');
        $this->pdf->SetFont('', '', 10);
        
        $this->pdf->Ln(5);
        $this->pdf->Cell(0, 8, 'Fecha y Hora: ' . $datos['fecha_arqueo'], 0, 1);
        $this->pdf->Cell(0, 8, 'Cajero: ' . $datos['nombre_cajero'], 0, 1);
        $this->pdf->Cell(0, 8, 'Monto Esperado: L ' . number_format($datos['monto_esperado'], 2), 0, 1);
        $this->pdf->Cell(0, 8, 'Monto Contado: L ' . number_format($datos['monto_contado'], 2), 0, 1);
        
        // Línea separadora
        $this->pdf->Ln(3);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        $diferencia = $datos['monto_contado'] - $datos['monto_esperado'];
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(0, 8, 'Diferencia: L ' . number_format($diferencia, 2), 0, 1);
        $this->pdf->SetFont('', '', 10);
        
        // Detalle de denominaciones (si aplica)
        if (isset($datos['denominaciones']) && is_array($datos['denominaciones'])) {
            $this->pdf->Ln(5);
            $this->pdf->SetFont('', 'B', 10);
            $this->pdf->Cell(0, 8, 'Detalle de Denominaciones', 0, 1);
            $this->pdf->SetFont('', '', 10);
            
            foreach ($datos['denominaciones'] as $denominacion => $cantidad) {
                $subtotal = $denominacion * $cantidad;
                $this->pdf->Cell(0, 6, $cantidad . ' x L ' . number_format($denominacion, 2) . ' = L ' . number_format($subtotal, 2), 0, 1);
            }
        }
        
        if (isset($datos['observaciones']) && !empty($datos['observaciones'])) {
            $this->pdf->Ln(5);
            $this->pdf->Cell(0, 8, 'Observaciones: ' . $datos['observaciones'], 0, 1);
        }
        
        // Pie de página
        $this->agregarPiePagina();
        
        // Guardar y devolver la ruta del archivo
        return $this->guardarDocumento('arqueo_caja_' . $datos['id']);
    }
    
    /**
     * Generar documento para ventas
     * @param array $datos Datos de la venta
     * @param array $detalle_productos Detalle de los productos vendidos
     * @return string Ruta del archivo generado
     */
    public function generarVenta($datos, $detalle_productos) {
        $this->pdf->AddPage();
        
        // Encabezado del documento
        $this->agregarEncabezado(
            $this->tipo_documento === 'factura' ? 'FACTURA DE VENTA' : 'TICKET DE VENTA',
            $datos['numero_factura'] ?? null
        );
        
        // Información del cliente (si aplica)
        if (isset($datos['cliente']) && !empty($datos['cliente'])) {
            $this->pdf->SetFont('', 'B', 10);
            $this->pdf->Cell(0, 8, 'Datos del Cliente', 0, 1);
            $this->pdf->SetFont('', '', 10);
            
            $this->pdf->Cell(0, 6, 'Nombre: ' . $datos['cliente']['nombre'], 0, 1);
            $this->pdf->Cell(0, 6, 'NIT/DUI: ' . $datos['cliente']['identificacion'], 0, 1);
            $this->pdf->Cell(0, 6, 'Dirección: ' . $datos['cliente']['direccion'], 0, 1);
            
            $this->pdf->Ln(5);
        }
        
        // Información de la venta
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(0, 8, 'Información de la Venta', 0, 1);
        $this->pdf->SetFont('', '', 10);
        
        $this->pdf->Cell(0, 6, 'Fecha: ' . $datos['fecha_venta'], 0, 1);
        $this->pdf->Cell(0, 6, 'Vendedor: ' . $datos['nombre_vendedor'], 0, 1);
        $this->pdf->Cell(0, 6, 'Forma de Pago: ' . $datos['forma_pago'], 0, 1);
        
        // Mostrar banco si es transferencia
        if (isset($datos['forma_pago']) && $datos['forma_pago'] === 'Transferencia' && isset($datos['banco']) && !empty($datos['banco']) && $datos['banco'] !== 'N/A') {
            $this->pdf->Cell(0, 6, 'Banco: ' . $datos['banco'], 0, 1);
        }
        
        
        // Línea separadora
        $this->pdf->Ln(3);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        // Tabla de productos
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(0, 8, 'Detalle de Productos', 0, 1, 'C');
        $this->pdf->SetFont('', '', 9);
        
        // Encabezados de la tabla
        $this->pdf->Cell(60, 6, 'Producto', 0, 0, 'L');
        $this->pdf->Cell(20, 6, 'Cantidad', 0, 0, 'C');
        $this->pdf->Cell(30, 6, 'Precio', 0, 0, 'R');
        $this->pdf->Cell(30, 6, 'Subtotal', 0, 1, 'R');
        
        // Línea separadora
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(2);
        
        // Detalle de productos
        foreach ($detalle_productos as $producto) {
            $this->pdf->Cell(60, 6, $producto['nombre'], 0, 0, 'L');
            $this->pdf->Cell(20, 6, $producto['cantidad'], 0, 0, 'C');
            $this->pdf->Cell(30, 6, 'L ' . number_format($producto['precio'], 2), 0, 0, 'R');
            $this->pdf->Cell(30, 6, 'L ' . number_format($producto['subtotal'], 2), 0, 1, 'R');
        }
        
        // Línea separadora
        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        // Totales
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(110, 6, 'Subtotal:', 0, 0, 'R');
        $this->pdf->Cell(30, 6, 'L ' . number_format($datos['subtotal'], 2), 0, 1, 'R');
        
        if (isset($datos['descuento']) && $datos['descuento'] > 0) {
            $this->pdf->Cell(110, 6, 'Descuento:', 0, 0, 'R');
            $this->pdf->Cell(30, 6, '- L ' . number_format($datos['descuento'], 2), 0, 1, 'R');
        }
        
        if (isset($datos['impuesto']) && $datos['impuesto'] > 0) {
            $this->pdf->Cell(110, 6, 'Impuesto (15%):', 0, 0, 'R');
            $this->pdf->Cell(30, 6, 'L ' . number_format($datos['impuesto'], 2), 0, 1, 'R');
        }
        
        $this->pdf->SetFont('', 'B', 12);
        $this->pdf->Cell(110, 8, 'Total:', 0, 0, 'R');
        $this->pdf->Cell(30, 8, 'L ' . number_format($datos['total'], 2), 0, 1, 'R');
        
        if (isset($datos['observaciones']) && !empty($datos['observaciones'])) {
            $this->pdf->Ln(5);
            $this->pdf->SetFont('', '', 10);
            $this->pdf->Cell(0, 6, 'Observaciones: ' . $datos['observaciones'], 0, 1);
        }
        
        // Pie de página
        $this->agregarPiePagina();
        
        // Guardar y devolver la ruta del archivo
        return $this->guardarDocumento('venta_' . $datos['id']);
    }
    
    /**
     * Generar documento para ingreso de inventario
     * @param array $datos Datos del ingreso
     * @param array $detalle_productos Detalle de los productos ingresados
     * @return string Ruta del archivo generado
     */
    public function generarIngresoInventario($datos, $detalle_productos) {
        $this->pdf->AddPage();
        
        // Encabezado del documento
        $this->agregarEncabezado('INGRESO DE INVENTARIO');
        
        // Información del ingreso
        $this->pdf->SetFont('', 'B', 12);
        $this->pdf->Cell(0, 10, 'Información del Ingreso', 0, 1, 'C');
        $this->pdf->SetFont('', '', 10);
        
        $this->pdf->Ln(5);
        $this->pdf->Cell(0, 8, 'Fecha y Hora: ' . $datos['fecha_ingreso'], 0, 1);
        $this->pdf->Cell(0, 8, 'Responsable: ' . $datos['nombre_responsable'], 0, 1);
        $this->pdf->Cell(0, 8, 'Proveedor: ' . $datos['nombre_proveedor'], 0, 1);
        $this->pdf->Cell(0, 8, 'Número de Factura: ' . $datos['numero_factura'], 0, 1);
        
        if (isset($datos['observaciones']) && !empty($datos['observaciones'])) {
            $this->pdf->Cell(0, 8, 'Observaciones: ' . $datos['observaciones'], 0, 1);
        }
        
        // Línea separadora
        $this->pdf->Ln(3);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        // Tabla de productos
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(0, 8, 'Detalle de Productos', 0, 1, 'C');
        $this->pdf->SetFont('', '', 9);
        
        // Encabezados de la tabla
        $this->pdf->Cell(50, 6, 'Producto', 0, 0, 'L');
        $this->pdf->Cell(30, 6, 'Código', 0, 0, 'L');
        $this->pdf->Cell(20, 6, 'Cantidad', 0, 0, 'C');
        $this->pdf->Cell(30, 6, 'Costo', 0, 0, 'R');
        $this->pdf->Cell(30, 6, 'Subtotal', 0, 1, 'R');
        
        // Línea separadora
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(2);
        
        // Detalle de productos
        foreach ($detalle_productos as $producto) {
            $this->pdf->Cell(50, 6, $producto['nombre'], 0, 0, 'L');
            $this->pdf->Cell(30, 6, $producto['codigo'], 0, 0, 'L');
            $this->pdf->Cell(20, 6, $producto['cantidad'], 0, 0, 'C');
            $this->pdf->Cell(30, 6, 'L ' . number_format($producto['costo'], 2), 0, 0, 'R');
            $this->pdf->Cell(30, 6, 'L ' . number_format($producto['subtotal'], 2), 0, 1, 'R');
        }
        
        // Línea separadora
        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        // Totales
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(110, 6, 'Total del Ingreso:', 0, 0, 'R');
        $this->pdf->Cell(30, 6, 'L ' . number_format($datos['total'], 2), 0, 1, 'R');
        
        // Pie de página
        $this->agregarPiePagina();
        
        // Guardar y devolver la ruta del archivo
        return $this->guardarDocumento('ingreso_inventario_' . $datos['id']);
    }
    
    /**
     * Generar documento para egreso de inventario
     * @param array $datos Datos del egreso
     * @param array $detalle_productos Detalle de los productos egresados
     * @return string Ruta del archivo generado
     */
    public function generarEgresoInventario($datos, $detalle_productos) {
        $this->pdf->AddPage();
        
        // Encabezado del documento
        $this->agregarEncabezado('EGRESO DE INVENTARIO');
        
        // Información del egreso
        $this->pdf->SetFont('', 'B', 12);
        $this->pdf->Cell(0, 10, 'Información del Egreso', 0, 1, 'C');
        $this->pdf->SetFont('', '', 10);
        
        $this->pdf->Ln(5);
        $this->pdf->Cell(0, 8, 'Fecha y Hora: ' . $datos['fecha_egreso'], 0, 1);
        $this->pdf->Cell(0, 8, 'Responsable: ' . $datos['nombre_responsable'], 0, 1);
        $this->pdf->Cell(0, 8, 'Motivo: ' . $datos['motivo'], 0, 1);
        
        if (isset($datos['observaciones']) && !empty($datos['observaciones'])) {
            $this->pdf->Cell(0, 8, 'Observaciones: ' . $datos['observaciones'], 0, 1);
        }
        
        // Línea separadora
        $this->pdf->Ln(3);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        // Tabla de productos
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(0, 8, 'Detalle de Productos', 0, 1, 'C');
        $this->pdf->SetFont('', '', 9);
        
        // Encabezados de la tabla
        $this->pdf->Cell(50, 6, 'Producto', 0, 0, 'L');
        $this->pdf->Cell(30, 6, 'Código', 0, 0, 'L');
        $this->pdf->Cell(20, 6, 'Cantidad', 0, 0, 'C');
        $this->pdf->Cell(30, 6, 'Valor', 0, 0, 'R');
        $this->pdf->Cell(30, 6, 'Subtotal', 0, 1, 'R');
        
        // Línea separadora
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(2);
        
        // Detalle de productos
        foreach ($detalle_productos as $producto) {
            $this->pdf->Cell(50, 6, $producto['nombre'], 0, 0, 'L');
            $this->pdf->Cell(30, 6, $producto['codigo'], 0, 0, 'L');
            $this->pdf->Cell(20, 6, $producto['cantidad'], 0, 0, 'C');
            $this->pdf->Cell(30, 6, 'L ' . number_format($producto['valor'], 2), 0, 0, 'R');
            $this->pdf->Cell(30, 6, 'L ' . number_format($producto['subtotal'], 2), 0, 1, 'R');
        }
        
        // Línea separadora
        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        // Totales
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(110, 6, 'Total del Egreso:', 0, 0, 'R');
        $this->pdf->Cell(30, 6, 'L ' . number_format($datos['total'], 2), 0, 1, 'R');
        
        // Pie de página
        $this->agregarPiePagina();
        
        // Guardar y devolver la ruta del archivo
        return $this->guardarDocumento('egreso_inventario_' . $datos['id']);
    }
    
    /**
     * Generar documento para devolución de venta
     * @param array $datos Datos de la devolución
     * @param array $detalle_productos Detalle de los productos devueltos
     * @return string Ruta del archivo generado
     */
    public function generarDevolucionVenta($datos, $detalle_productos) {
        $this->pdf->AddPage();
        
        // Encabezado del documento
        $this->agregarEncabezado('DEVOLUCIÓN DE VENTA');
        
        // Información de la devolución
        $this->pdf->SetFont('', 'B', 12);
        $this->pdf->Cell(0, 10, 'Información de la Devolución', 0, 1, 'C');
        $this->pdf->SetFont('', '', 10);
        
        $this->pdf->Ln(5);
        $this->pdf->Cell(0, 8, 'Fecha y Hora: ' . $datos['fecha_devolucion'], 0, 1);
        $this->pdf->Cell(0, 8, 'Venta Original #: ' . $datos['id_venta_original'], 0, 1);
        $this->pdf->Cell(0, 8, 'Vendedor: ' . $datos['nombre_vendedor'], 0, 1);
        $this->pdf->Cell(0, 8, 'Cliente: ' . $datos['nombre_cliente'], 0, 1);
        $this->pdf->Cell(0, 8, 'Motivo: ' . $datos['motivo'], 0, 1);
        
        if (isset($datos['observaciones']) && !empty($datos['observaciones'])) {
            $this->pdf->Cell(0, 8, 'Observaciones: ' . $datos['observaciones'], 0, 1);
        }
        
        // Línea separadora
        $this->pdf->Ln(3);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        // Tabla de productos
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(0, 8, 'Detalle de Productos Devueltos', 0, 1, 'C');
        $this->pdf->SetFont('', '', 9);
        
        // Encabezados de la tabla
        $this->pdf->Cell(50, 6, 'Producto', 0, 0, 'L');
        $this->pdf->Cell(30, 6, 'Código', 0, 0, 'L');
        $this->pdf->Cell(20, 6, 'Cantidad', 0, 0, 'C');
        $this->pdf->Cell(30, 6, 'Precio', 0, 0, 'R');
        $this->pdf->Cell(30, 6, 'Subtotal', 0, 1, 'R');
        
        // Línea separadora
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(2);
        
        // Detalle de productos
        foreach ($detalle_productos as $producto) {
            $this->pdf->Cell(50, 6, $producto['nombre'], 0, 0, 'L');
            $this->pdf->Cell(30, 6, $producto['codigo'], 0, 0, 'L');
            $this->pdf->Cell(20, 6, $producto['cantidad'], 0, 0, 'C');
            $this->pdf->Cell(30, 6, 'L ' . number_format($producto['precio'], 2), 0, 0, 'R');
            $this->pdf->Cell(30, 6, 'L ' . number_format($producto['subtotal'], 2), 0, 1, 'R');
        }
        
        // Línea separadora
        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        // Totales
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(110, 6, 'Total Devuelto:', 0, 0, 'R');
        $this->pdf->Cell(30, 6, 'L ' . number_format($datos['total'], 2), 0, 1, 'R');
        
        // Pie de página
        $this->agregarPiePagina();
        
        // Guardar y devolver la ruta del archivo
        return $this->guardarDocumento('devolucion_venta_' . $datos['id']);
    }
    
    /**
     * Generar documento para deuda de venta
     * @param array $datos Datos de la deuda
     * @param array $detalle_productos Detalle de los productos
     * @return string Ruta del archivo generado
     */
    public function generarDeudaVenta($datos, $detalle_productos) {
        $this->pdf->AddPage();
        
        // Encabezado del documento
        $this->agregarEncabezado('REGISTRO DE DEUDA');
        
        // Información de la deuda
        $this->pdf->SetFont('', 'B', 12);
        $this->pdf->Cell(0, 10, 'Información de la Deuda', 0, 1, 'C');
        $this->pdf->SetFont('', '', 10);
        
        $this->pdf->Ln(5);
        $this->pdf->Cell(0, 8, 'ID Deuda: ' . $datos['id'], 0, 1);
        $this->pdf->Cell(0, 8, 'Fecha y Hora: ' . $datos['fecha_venta'], 0, 1);
        $this->pdf->Cell(0, 8, 'Vendedor: ' . $datos['nombre_vendedor'], 0, 1);
        
        if (isset($datos['cliente'])) {
            $this->pdf->Cell(0, 8, 'Cliente: ' . $datos['cliente']['nombre'], 0, 1);
            $this->pdf->Cell(0, 8, 'Identificación: ' . $datos['cliente']['identificacion'], 0, 1);
            $this->pdf->Cell(0, 8, 'Dirección: ' . $datos['cliente']['direccion'], 0, 1);
        }
        
        // Línea separadora
        $this->pdf->Ln(3);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        // Tabla de productos
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(0, 8, 'Detalle de Productos', 0, 1, 'C');
        $this->pdf->SetFont('', '', 9);
        
        // Encabezados de la tabla
        $this->pdf->Cell(60, 6, 'Producto', 0, 0, 'L');
        $this->pdf->Cell(20, 6, 'Cantidad', 0, 0, 'C');
        $this->pdf->Cell(30, 6, 'Precio', 0, 0, 'R');
        $this->pdf->Cell(30, 6, 'Subtotal', 0, 1, 'R');
        
        // Línea separadora
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(2);
        
        // Detalle de productos
        foreach ($detalle_productos as $producto) {
            $this->pdf->Cell(60, 6, $producto['nombre'], 0, 0, 'L');
            $this->pdf->Cell(20, 6, $producto['cantidad'], 0, 0, 'C');
            $this->pdf->Cell(30, 6, 'L ' . number_format($producto['precio'], 2), 0, 0, 'R');
            $this->pdf->Cell(30, 6, 'L ' . number_format($producto['subtotal'], 2), 0, 1, 'R');
        }
        
        // Línea separadora
        $this->pdf->Ln(2);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
        
        // Totales
        $this->pdf->SetFont('', 'B', 10);
        $this->pdf->Cell(110, 6, 'Subtotal:', 0, 0, 'R');
        $this->pdf->Cell(30, 6, 'L ' . number_format($datos['subtotal'], 2), 0, 1, 'R');
        
        if (isset($datos['impuesto']) && $datos['impuesto'] > 0) {
            $this->pdf->Cell(110, 6, 'Impuesto (15%):', 0, 0, 'R');
            $this->pdf->Cell(30, 6, 'L ' . number_format($datos['impuesto'], 2), 0, 1, 'R');
        }
        
        $this->pdf->SetFont('', 'B', 12);
        $this->pdf->Cell(110, 8, 'Total Deuda:', 0, 0, 'R');
        $this->pdf->Cell(30, 8, 'L ' . number_format($datos['total'], 2), 0, 1, 'R');
        
        if (isset($datos['observaciones']) && !empty($datos['observaciones'])) {
            $this->pdf->Ln(5);
            $this->pdf->SetFont('', '', 10);
            $this->pdf->Cell(0, 6, 'Observaciones: ' . $datos['observaciones'], 0, 1);
        }
        
        // Pie de página
        $this->agregarPiePagina();
        
        // Guardar y devolver la ruta del archivo
        return $this->guardarDocumento('deuda_venta_' . $datos['id']);
    }
    
    /**
     * Agregar encabezado al documento
     * @param string $titulo Título del documento
     * @param string $numero_documento Número del documento (opcional)
     */
    private function agregarEncabezado($titulo, $numero_documento = null) {
        // Logo de la empresa (si existe)
        if ($this->tipo_documento === 'factura') {
            // Para factura, logo más grande
            $this->pdf->Image('assets/img/logo.png', 10, 10, 30, 30, '', '', '', true, 150, '', false, false, 0, false, false, false);
            
            // Información de la empresa
            $this->pdf->SetFont('', 'B', 14);
            $this->pdf->Cell(0, 8, $this->datos_empresa['nombre'], 0, 1, 'C');
            $this->pdf->SetFont('', '', 10);
            $this->pdf->Cell(0, 5, $this->datos_empresa['direccion'], 0, 1, 'C');
            $this->pdf->Cell(0, 5, 'Tel: ' . $this->datos_empresa['telefono'] . ' | Email: ' . $this->datos_empresa['email'], 0, 1, 'C');
            $this->pdf->Cell(0, 5, 'NIT: ' . $this->datos_empresa['nit'] . ' | ' . $this->datos_empresa['regimen'], 0, 1, 'C');
            $this->pdf->Cell(0, 5, $this->datos_empresa['resolucion'], 0, 1, 'C');
            
            // Título y número de documento
            $this->pdf->Ln(5);
            $this->pdf->SetFont('', 'B', 16);
            $this->pdf->Cell(0, 10, $titulo, 0, 1, 'C');
            
            if ($numero_documento) {
                $this->pdf->SetFont('', 'B', 12);
                $this->pdf->Cell(0, 8, 'No. ' . $numero_documento, 0, 1, 'C');
            }
            
            $this->pdf->Ln(5);
        } else {
            // Para ticket, más compacto
            $this->pdf->SetFont('', 'B', 12);
            $this->pdf->Cell(0, 8, $this->datos_empresa['nombre'], 0, 1, 'C');
            $this->pdf->SetFont('', '', 8);
            $this->pdf->Cell(0, 5, $this->datos_empresa['direccion'], 0, 1, 'C');
            $this->pdf->Cell(0, 5, 'Tel: ' . $this->datos_empresa['telefono'], 0, 1, 'C');
            $this->pdf->Cell(0, 5, 'NIT: ' . $this->datos_empresa['nit'], 0, 1, 'C');
            
            // Título
            $this->pdf->Ln(3);
            $this->pdf->SetFont('', 'B', 12);
            $this->pdf->Cell(0, 8, $titulo, 0, 1, 'C');
            
            if ($numero_documento) {
                $this->pdf->SetFont('', 'B', 10);
                $this->pdf->Cell(0, 6, 'No. ' . $numero_documento, 0, 1, 'C');
            }
            
            $this->pdf->Ln(3);
        }
        
        // Línea separadora
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(3);
    }
    
    /**
     * Agregar pie de página al documento
     */
    private function agregarPiePagina() {
        // Línea separadora
        $this->pdf->Ln(5);
        $this->pdf->Cell(0, 0, '', 'T');
        $this->pdf->Ln(5);
        
        // Mensaje de agradecimiento
        $this->pdf->SetFont('', 'I', 10);
        $this->pdf->Cell(0, 8, '¡Gracias por su compra!', 0, 1, 'C');
        
        // Información de contacto
        $this->pdf->SetFont('', '', 8);
        $this->pdf->Cell(0, 5, 'Para cualquier consulta, contáctenos al ' . $this->datos_empresa['telefono'], 0, 1, 'C');
        $this->pdf->Cell(0, 5, 'Visítanos en ' . $this->datos_empresa['email'], 0, 1, 'C');
        
        // Fecha y hora de generación
        $this->pdf->Ln(3);
        $this->pdf->SetFont('', '', 8);
        $this->pdf->Cell(0, 5, 'Documento generado el ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    }
    
    /**
     * Guardar el documento en el servidor
     * @param string $nombre_archivo Nombre base del archivo
     * @return string Ruta del archivo generado
     */
    private function guardarDocumento($nombre_archivo) {
        // Crear directorio si no existe
        $directorio = 'documentos/' . $this->tipo_documento . 's/';
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Generar nombre único con timestamp
        $nombre_completo = $directorio . $nombre_archivo . '_' . date('YmdHis') . '.pdf';
        
        // Guardar el archivo
        $this->pdf->Output($nombre_completo, 'F');
        
        return $nombre_completo;
    }
    
    /**
     * Descargar el documento directamente al navegador
     * @param string $nombre_archivo Nombre del archivo para descargar
     */
    public function descargarDocumento($nombre_archivo) {
        $this->pdf->Output($nombre_archivo . '.pdf', 'D');
    }
    
    /**
     * Mostrar el documento en el navegador
     * @param string $nombre_archivo Nombre del archivo para mostrar
     */
    public function mostrarDocumento($nombre_archivo) {
        $this->pdf->Output($nombre_archivo . '.pdf', 'I');
    }
}

/**
 * Funciones de ayuda para generar documentos fácilmente
 */

/**
 * Generar ticket de venta
 * @param array $venta Datos de la venta
 * @param array $detalle_productos Detalle de los productos
 * @param mysqli $conexion Conexión a la base de datos
 * @return string Ruta del archivo generado
 */
function generarTicketVenta($venta, $detalle_productos, $conexion) {
    $generador = new GeneradorDocumentos('ticket', $conexion);
    return $generador->generarVenta($venta, $detalle_productos);
}

/**
 * Generar factura de venta
 * @param array $venta Datos de la venta
 * @param array $detalle_productos Detalle de los productos
 * @param mysqli $conexion Conexión a la base de datos
 * @return string Ruta del archivo generado
 */
function generarFacturaVenta($venta, $detalle_productos, $conexion) {
    $generador = new GeneradorDocumentos('factura', $conexion);
    return $generador->generarVenta($venta, $detalle_productos);
}

/**
 * Generar ticket de cierre de caja
 * @param array $cierre Datos del cierre de caja
 * @param mysqli $conexion Conexión a la base de datos
 * @return string Ruta del archivo generado
 */
function generarTicketCierreCaja($cierre, $conexion) {
    $generador = new GeneradorDocumentos('ticket', $conexion);
    return $generador->generarCierreCaja($cierre);
}

/**
 * Generar ticket de deuda de venta
 * @param array $deuda Datos de la deuda
 * @param array $detalle_productos Detalle de los productos
 * @param mysqli $conexion Conexión a la base de datos
 * @return string Ruta del archivo generado
 */
function generarTicketDeudaVenta($deuda, $detalle_productos, $conexion) {
    $generador = new GeneradorDocumentos('ticket', $conexion);
    return $generador->generarDeudaVenta($deuda, $detalle_productos);
}

/**
 * Generar ticket de arqueo de caja
 * @param array $arqueo Datos del arqueo de caja
 * @param mysqli $conexion Conexión a la base de datos
 * @return string Ruta del archivo generado
 */
function generarTicketArqueoCaja($arqueo, $conexion) {
    $generador = new GeneradorDocumentos('ticket', $conexion);
    return $generador->generarArqueoCaja($arqueo);
}

/**
 * Generar factura de ingreso de inventario
 * @param array $ingreso Datos del ingreso
 * @param array $detalle_productos Detalle de los productos
 * @param mysqli $conexion Conexión a la base de datos
 * @return string Ruta del archivo generado
 */
function generarFacturaIngresoInventario($ingreso, $detalle_productos, $conexion) {
    $generador = new GeneradorDocumentos('factura', $conexion);
    return $generador->generarIngresoInventario($ingreso, $detalle_productos);
}

/**
 * Generar ticket de egreso de inventario
 * @param array $egreso Datos del egreso
 * @param array $detalle_productos Detalle de los productos
 * @param mysqli $conexion Conexión a la base de datos
 * @return string Ruta del archivo generado
 */
function generarTicketEgresoInventario($egreso, $detalle_productos, $conexion) {
    $generador = new GeneradorDocumentos('ticket', $conexion);
    return $generador->generarEgresoInventario($egreso, $detalle_productos);
}

/**
 * Generar ticket de devolución de venta
 * @param array $devolucion Datos de la devolución
 * @param array $detalle_productos Detalle de los productos
 * @param mysqli $conexion Conexión a la base de datos
 * @return string Ruta del archivo generado
 */
function generarTicketDevolucionVenta($devolucion, $detalle_productos, $conexion) {
    $generador = new GeneradorDocumentos('ticket', $conexion);
    return $generador->generarDevolucionVenta($devolucion, $detalle_productos);
}

?>