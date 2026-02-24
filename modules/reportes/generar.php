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

// Configurar según el tipo de reporte
switch ($reporte) {
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
        
    default:
        die('Reporte no válido');
}

$result = $conn->query($sql);

// Crear nuevo documento
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar título y encabezados
$sheet->setCellValue('A1', 'TECNOLÓGICO SAN ANTONIO - TESA');
$sheet->setCellValue('A2', $titulo);
$sheet->setCellValue('A3', 'Fecha: ' . date('d/m/Y H:i:s'));

// Estilos para títulos
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A1:A2')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF5A2D8C'));

// Encabezados de columnas
$col = 'A';
foreach ($columnas as $titulo_col) {
    $sheet->setCellValue($col . '5', $titulo_col);
    $sheet->getStyle($col . '5')->getFont()->setBold(true);
    $sheet->getStyle($col . '5')->getFill()
          ->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setARGB('FF5A2D8C');
    $sheet->getStyle($col . '5')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
    $col++;
}

// Datos
$row = 6;
while ($data = $result->fetch_assoc()) {
    $col = 'A';
    foreach ($data as $valor) {
        $sheet->setCellValue($col . $row, strip_tags($valor ?? ''));
        $col++;
    }
    $row++;
}

// Autoajustar columnas
foreach (range('A', $col) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Bordes para toda la tabla
$lastRow = $row - 1;
$lastCol = count($columnas) - 1;
$lastColLetter = chr(65 + $lastCol);
$sheet->getStyle('A5:' . $lastColLetter . $lastRow)->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

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