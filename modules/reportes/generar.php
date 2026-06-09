<?php
session_start();
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$tipo = $_GET['tipo'] ?? 'excel';
$reporte = $_GET['reporte'] ?? 'inventario';
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin = $_GET['fecha_fin'] ?? null;

function generarReporte($conn, $tipo, $reporte, $fecha_inicio = null, $fecha_fin = null) {
    $datos = null;
    $columnas = [];
    $titulo = '';
    $extraInfo = '';
    
    // Agregar info de fechas si aplica
    if ($fecha_inicio && $fecha_fin) {
        $extraInfo = 'Período: ' . date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin));
    }

    switch ($reporte) {
        // ==================== REPORTES ESTÁNDAR ====================
        case 'inventario':
            $sql = "SELECT e.*, 
                           CASE WHEN a.persona_id IS NOT NULL THEN p.nombres ELSE 'DISPONIBLE' END as asignado_a
                    FROM equipos e
                    LEFT JOIN asignaciones a ON e.id = a.equipo_id AND a.fecha_devolucion IS NULL
                    LEFT JOIN personas p ON a.persona_id = p.id
                    ORDER BY e.tipo_equipo, e.marca";
            $titulo = 'Reporte de Inventario General';
            $columnas = ['Código', 'Tipo', 'Marca', 'Modelo', 'Serie', 'Estado', 'Asignado A', 'Fecha Registro'];
            break;
            
        case 'prestamos':
            $sql = "SELECT p.nombres as persona, p.cedula, p.cargo,
                           e.tipo_equipo, e.marca, e.modelo, e.codigo_barras,
                           a.fecha_asignacion
                    FROM asignaciones a
                    JOIN personas p ON a.persona_id = p.id
                    JOIN equipos e ON a.equipo_id = e.id
                    WHERE a.fecha_devolucion IS NULL
                    ORDER BY a.fecha_asignacion DESC";
            $titulo = 'Reporte de Préstamos Activos';
            $columnas = ['Persona', 'Cédula', 'Cargo', 'Equipo', 'Marca', 'Modelo', 'Código', 'Fecha Préstamo'];
            break;
            
        case 'personas':
            $sql = "SELECT p.*, COUNT(DISTINCT a.equipo_id) as total_equipos
                    FROM personas p
                    LEFT JOIN asignaciones a ON p.id = a.persona_id AND a.fecha_devolucion IS NULL
                    GROUP BY p.id
                    ORDER BY p.nombres";
            $titulo = 'Reporte de Personas y Equipos Asignados';
            $columnas = ['Cédula', 'Nombres', 'Cargo', 'Correo', 'Teléfono', 'Equipos Asignados'];
            break;

        case 'componentes_por_equipo':
            $sql = "SELECT e.codigo_barras as equipo_codigo, e.tipo_equipo as equipo_tipo,
                           c.tipo as componente_tipo, c.nombre_componente, c.marca, c.modelo, c.serie, c.estado
                    FROM componentes c
                    JOIN equipos e ON c.equipo_id = e.id
                    ORDER BY e.codigo_barras, c.tipo";
            $titulo = 'Reporte de Componentes por Equipo';
            $columnas = ['Código Equipo', 'Tipo Equipo', 'Tipo Componente', 'Nombre', 'Marca', 'Modelo', 'Serie', 'Estado'];
            break;

        case 'componentes_mal_estado':
            $sql = "SELECT e.codigo_barras as equipo_codigo, e.tipo_equipo as equipo_tipo,
                           c.tipo as componente_tipo, c.nombre_componente, c.marca, c.modelo, c.serie, c.estado
                    FROM componentes c
                    JOIN equipos e ON c.equipo_id = e.id
                    WHERE c.estado IN ('Malo', 'Regular', 'Por reemplazar')
                    ORDER BY c.estado DESC, e.codigo_barras";
            $titulo = 'Reporte de Componentes en Mal Estado';
            $columnas = ['Código Equipo', 'Tipo Equipo', 'Tipo Componente', 'Nombre', 'Marca', 'Modelo', 'Serie', 'Estado'];
            break;

        case 'historial_componentes':
            $sql = "SELECT mc.*, c.nombre_componente, c.tipo, p.nombres as persona_nombre
                    FROM movimientos_componentes mc
                    LEFT JOIN componentes c ON mc.componente_id = c.id
                    LEFT JOIN personas p ON mc.persona_id = p.id
                    ORDER BY mc.fecha_movimiento DESC";
            $titulo = 'Reporte de Historial de Componentes';
            $columnas = ['Fecha', 'Tipo Movimiento', 'Componente', 'Tipo', 'Persona', 'Observaciones'];
            break;

        case 'equipos_sin_asignar':
            $sql = "SELECT e.*
                    FROM equipos e
                    LEFT JOIN asignaciones a ON e.id = a.equipo_id AND a.fecha_devolucion IS NULL
                    WHERE a.id IS NULL AND e.estado != 'Baja'
                    ORDER BY e.tipo_equipo, e.codigo_barras";
            $titulo = 'Reporte de Equipos sin Asignar';
            $columnas = ['Código', 'Tipo', 'Marca', 'Modelo', 'Serie', 'Estado', 'Fecha Ingreso'];
            break;

        case 'equipos_en_mantenimiento':
            $sql = "SELECT e.codigo_barras, e.tipo_equipo, e.marca, e.modelo, e.estado,
                           m.fecha_inicio, m.descripcion as motivo
                    FROM equipos e
                    JOIN mantenimientos m ON e.id = m.equipo_id
                    WHERE m.estado = 'En proceso'
                    ORDER BY m.fecha_inicio DESC";
            $titulo = 'Reporte de Equipos en Mantenimiento';
            $columnas = ['Código', 'Tipo', 'Marca', 'Modelo', 'Estado', 'Fecha Inicio', 'Motivo'];
            break;

        case 'personas_sin_equipos':
            $sql = "SELECT p.*
                    FROM personas p
                    LEFT JOIN asignaciones a ON p.id = a.persona_id AND a.fecha_devolucion IS NULL
                    WHERE a.id IS NULL
                    ORDER BY p.nombres";
            $titulo = 'Reporte de Personas sin Equipos Asignados';
            $columnas = ['Cédula', 'Nombres', 'Apellidos', 'Cargo', 'Departamento', 'Email', 'Teléfono'];
            break;

        // ==================== REPORTES POR RANGO DE FECHAS ====================
        case 'movimientos':
            $where = '';
            if ($fecha_inicio && $fecha_fin) {
                $where = "WHERE DATE(m.fecha_movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            } elseif ($fecha_inicio) {
                $where = "WHERE DATE(m.fecha_movimiento) >= '$fecha_inicio'";
            } elseif ($fecha_fin) {
                $where = "WHERE DATE(m.fecha_movimiento) <= '$fecha_fin'";
            }
            $sql = "SELECT m.fecha_movimiento, m.tipo_movimiento, e.codigo_barras, e.tipo_equipo,
                           p.nombres as persona_nombre, m.observaciones
                    FROM movimientos m
                    JOIN equipos e ON m.equipo_id = e.id
                    LEFT JOIN personas p ON m.persona_id = p.id
                    $where
                    ORDER BY m.fecha_movimiento DESC";
            $titulo = 'Reporte de Movimientos de Equipos';
            $columnas = ['Fecha', 'Tipo', 'Código', 'Equipo', 'Persona', 'Observaciones'];
            break;

        case 'asignaciones':
            $where = '';
            if ($fecha_inicio && $fecha_fin) {
                $where = "WHERE DATE(a.fecha_asignacion) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            } elseif ($fecha_inicio) {
                $where = "WHERE DATE(a.fecha_asignacion) >= '$fecha_inicio'";
            } elseif ($fecha_fin) {
                $where = "WHERE DATE(a.fecha_asignacion) <= '$fecha_fin'";
            }
            $sql = "SELECT a.fecha_asignacion, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
                           p.nombres as persona_nombre, p.cedula, a.observaciones
                    FROM asignaciones a
                    JOIN equipos e ON a.equipo_id = e.id
                    JOIN personas p ON a.persona_id = p.id
                    $where
                    ORDER BY a.fecha_asignacion DESC";
            $titulo = 'Reporte de Asignaciones Realizadas';
            $columnas = ['Fecha', 'Código', 'Tipo', 'Marca', 'Modelo', 'Persona', 'Cédula', 'Observaciones'];
            break;

        case 'mantenimientos':
            $where = '';
            if ($fecha_inicio && $fecha_fin) {
                $where = "WHERE DATE(m.fecha_inicio) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            } elseif ($fecha_inicio) {
                $where = "WHERE DATE(m.fecha_inicio) >= '$fecha_inicio'";
            } elseif ($fecha_fin) {
                $where = "WHERE DATE(m.fecha_inicio) <= '$fecha_fin'";
            }
            $sql = "SELECT m.fecha_inicio, m.fecha_fin, e.codigo_barras, e.tipo_equipo,
                           m.tipo, m.estado, m.descripcion, m.costo
                    FROM mantenimientos m
                    JOIN equipos e ON m.equipo_id = e.id
                    $where
                    ORDER BY m.fecha_inicio DESC";
            $titulo = 'Reporte de Mantenimientos Realizados';
            $columnas = ['Fecha Inicio', 'Fecha Fin', 'Código Equipo', 'Tipo Equipo', 'Tipo', 'Estado', 'Descripción', 'Costo'];
            break;

        case 'bajas':
            $where = '';
            if ($fecha_inicio && $fecha_fin) {
                $where = "WHERE DATE(m.fecha_movimiento) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            } elseif ($fecha_inicio) {
                $where = "WHERE DATE(m.fecha_movimiento) >= '$fecha_inicio'";
            } elseif ($fecha_fin) {
                $where = "WHERE DATE(m.fecha_movimiento) <= '$fecha_fin'";
            }
            $sql = "SELECT m.fecha_movimiento, e.codigo_barras, e.tipo_equipo, e.marca, e.modelo,
                           e.numero_serie, m.observaciones
                    FROM movimientos m
                    JOIN equipos e ON m.equipo_id = e.id
                    WHERE m.tipo_movimiento = 'BAJA' $where
                    ORDER BY m.fecha_movimiento DESC";
            $titulo = 'Reporte de Equipos Dados de Baja';
            $columnas = ['Fecha Baja', 'Código', 'Tipo', 'Marca', 'Modelo', 'Serie', 'Motivo'];
            break;

        default:
            die('Reporte no válido');
    }

    $result = $conn->query($sql);
    $datos = $result->fetch_all(MYSQLI_ASSOC);
    
    return [
        'titulo' => $titulo,
        'columnas' => $columnas,
        'datos' => $datos,
        'extraInfo' => $extraInfo
    ];
}

$resultado = generarReporte($conn, $tipo, $reporte, $fecha_inicio, $fecha_fin);
$titulo = $resultado['titulo'];
$columnas = $resultado['columnas'];
$datos = $resultado['datos'];
$extraInfo = $resultado['extraInfo'];

// Crear nuevo documento
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar título y encabezados
$sheet->setCellValue('A1', 'TECNOLÓGICO SAN ANTONIO - TESA');
$sheet->setCellValue('A2', $titulo);
$sheet->setCellValue('A3', 'Fecha: ' . date('d/m/Y H:i:s'));
if ($extraInfo) {
    $sheet->setCellValue('A4', $extraInfo);
    $startRow = 6;
} else {
    $startRow = 5;
}

// Estilos para títulos
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A1:A2')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF5A2D8C'));

// Encabezados de columnas
$col = 'A';
foreach ($columnas as $i => $titulo_col) {
    $cell = $col . $startRow;
    $sheet->setCellValue($cell, $titulo_col);
    $sheet->getStyle($cell)->getFont()->setBold(true);
    $sheet->getStyle($cell)->getFill()
          ->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setARGB('FF5A2D8C');
    $sheet->getStyle($cell)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
    $col++;
}

// Datos
$row = $startRow + 1;
foreach ($datos as $data) {
    $col = 'A';
    foreach ($data as $valor) {
        $sheet->setCellValue($col . $row, strip_tags($valor ?? ''));
        $col++;
    }
    $row++;
}

// Autoajustar columnas
$lastCol = chr(64 + count($columnas));
foreach (range('A', $lastCol) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Bordes para toda la tabla
if (!empty($datos)) {
    $lastRow = $row - 1;
    $sheet->getStyle('A' . $startRow . ':' . $lastCol . $lastRow)->getBorders()->getAllBorders()
          ->setBorderStyle(Border::BORDER_THIN);
}

// Generar archivo según tipo
if ($tipo == 'excel') {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $reporte . '_' . date('Ymd') . '.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
} elseif ($tipo == 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment;filename="' . $reporte . '_' . date('Ymd') . '.pdf"');
    $writer = new Mpdf($spreadsheet);
    $writer->save('php://output');
}
exit();
?>
