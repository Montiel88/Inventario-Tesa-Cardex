<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..')), '/') . '/');
}

$GLOBALS['actas_meses'] = array(
    "January" => "ENERO", "February" => "FEBRERO", "March" => "MARZO",
    "April" => "ABRIL", "May" => "MAYO", "June" => "JUNIO",
    "July" => "JULIO", "August" => "AGOSTO", "September" => "SEPTIEMBRE",
    "October" => "OCTUBRE", "November" => "NOVIEMBRE", "December" => "DICIEMBRE"
);

function actas_cargar_logo() {
    $ruta_logo_fisica = BASE_PATH . 'assets/img/logo-tesa.png';
    $logo_base64 = '';
    if (file_exists($ruta_logo_fisica)) {
        $imageData = base64_encode(file_get_contents($ruta_logo_fisica));
        $logo_base64 = 'data:image/png;base64,' . $imageData;
    } else {
        $ruta_alternativa = __DIR__ . '/../assets/img/logo-tesa.png';
        if (file_exists($ruta_alternativa)) {
            $imageData = base64_encode(file_get_contents($ruta_alternativa));
            $logo_base64 = 'data:image/png;base64,' . $imageData;
        }
    }
    return $logo_base64;
}

function actas_cargar_desde_id(&$conn, $acta_id) {
    $acta_id = intval($acta_id);
    if ($acta_id <= 0) return null;

    $stmt = $conn->prepare("SELECT a.*, p.*,
                                   u.nombre as usuario_nombre, u.email as usuario_email,
                                   u2.nombre as firmador_nombre
                            FROM actas a
                            LEFT JOIN personas p ON a.persona_id = p.id
                            LEFT JOIN usuarios u ON a.usuario_id = u.id
                            LEFT JOIN usuarios u2 ON a.firmado_por = u2.id
                            WHERE a.id = ?");
    if (!$stmt) return null;
    $stmt->bind_param('i', $acta_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) return null;
    $acta = $res->fetch_assoc();

    $equipos_ids = array_filter(array_map('intval', explode(',', $acta['equipos_ids'] ?? '')));
    $equipos = [];
    if (!empty($equipos_ids)) {
        $placeholders = implode(',', array_fill(0, count($equipos_ids), '?'));
        $stmtEq = $conn->prepare("SELECT e.* FROM equipos e WHERE e.id IN ($placeholders)");
        if ($stmtEq) {
            $types = str_repeat('i', count($equipos_ids));
            $stmtEq->bind_param($types, ...$equipos_ids);
            $stmtEq->execute();
            $resEq = $stmtEq->get_result();
            while ($eq = $resEq->fetch_assoc()) $equipos[] = $eq;
        }
    }

    $acta['equipos_list'] = $equipos;
    return $acta;
}

function actas_fecha_en_espanol($fecha_str) {
    $ts = is_numeric($fecha_str) ? $fecha_str : strtotime($fecha_str);
    if (!$ts) return '';
    $dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
              7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $d = (int)date('j', $ts);
    $m = (int)date('n', $ts);
    $y = (int)date('Y', $ts);
    return "Quito, $d de " . $meses[$m] . " de $y";
}

function actas_obtener_tabla($equipos, $con_componentes = false, &$conn = null, $persona_id = null) {
    $tabla = '';
    $contador = 1;
    $total_items = 0;

    $componentes = [];
    if ($con_componentes && $conn && $persona_id) {
        $sql_componentes = "SELECT c.*
                            FROM componentes c
                            JOIN movimientos_componentes mc ON c.id = mc.componente_id
                            WHERE mc.persona_id = ?
                              AND mc.tipo_movimiento = 'ASIGNACION'
                              AND NOT EXISTS (
                                  SELECT 1 FROM movimientos_componentes mc2
                                  WHERE mc2.componente_id = mc.componente_id
                                    AND mc2.tipo_movimiento = 'DEVOLUCION'
                                    AND mc2.fecha_movimiento > mc.fecha_movimiento
                              )
                            ORDER BY mc.fecha_movimiento DESC";
        $stmtC = $conn->prepare($sql_componentes);
        if ($stmtC) {
            $pid = intval($persona_id);
            $stmtC->bind_param('i', $pid);
            $stmtC->execute();
            $resC = $stmtC->get_result();
            if ($resC) while ($c = $resC->fetch_assoc()) $componentes[] = $c;
        }
    }

    if (!empty($equipos)) {
        foreach ($equipos as $eq) {
            $articulo = trim(($eq['tipo_equipo'] ?? '') . ' ' . ($eq['marca'] ?? '') . ' ' . ($eq['modelo'] ?? ''));
            if ($articulo === '' || $articulo === ' ') $articulo = 'Equipo';
            $serie = !empty($eq['numero_serie']) ? $eq['numero_serie'] : (empty($eq['serie']) ? 'N/A' : $eq['serie']);
            $tabla .= "
            <tr>
                <td style='text-align: center; width: 8%;'>$contador</td>
                <td style='width: 52%;'>" . htmlspecialchars($articulo) . "</td>
                <td style='width: 30%;'>" . htmlspecialchars($serie) . "</td>
                <td style='text-align: center; width: 10%;'>1</td>
            </tr>";
            $contador++;
            $total_items++;
        }
    }

    foreach ($componentes as $c) {
        $articulo = trim(($c['tipo'] ?? '') . ' ' . ($c['nombre_componente'] ?? '') . ' ' . ($c['marca'] ?? '') . ' ' . ($c['modelo'] ?? ''));
        if ($articulo === '' || $articulo === ' ') $articulo = 'Componente';
        $serie = !empty($c['numero_serie']) ? $c['numero_serie'] : 'N/A';
        $tabla .= "
        <tr>
            <td style='text-align: center; width: 8%;'>$contador</td>
            <td style='width: 52%;'>COMPONENTE - " . htmlspecialchars($articulo) . "</td>
            <td style='width: 30%;'>" . htmlspecialchars($serie) . "</td>
            <td style='text-align: center; width: 10%;'>1</td>
        </tr>";
        $contador++;
        $total_items++;
    }

    if ($total_items > 0) {
        $tabla .= "
        <tr style='font-weight: bold; background-color: #f0f0f0;'>
            <td colspan='3' style='text-align: right;'>TOTAL:</td>
            <td style='text-align: center;'>$total_items</td>
        </tr>";
    } else {
        $tabla = "<tr><td colspan='4' style='text-align: center; padding: 20px;'>No hay artículos en este acta.</td></tr>";
    }
    return $tabla;
}

function actas_guardar_pdf_y_redirigir(&$conn, $acta_id, $pdf_content, $mpdf = null) {
    $acta_id = intval($acta_id);
    $stmt = $conn->prepare("SELECT codigo_acta FROM actas WHERE id = ?");
    $codigo_acta = null;
    if ($stmt) {
        $stmt->bind_param('i', $acta_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $r = $res->fetch_assoc()) $codigo_acta = $r['codigo_acta'];
    }
    if (!$codigo_acta) $codigo_acta = 'ACTA-' . $acta_id;

    $dir = BASE_PATH . 'uploads/actas';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $ht = $dir . '/.htaccess';
    if (!file_exists($ht)) {
        @file_put_contents($ht, "Options -Indexes\n<FilesMatch \.php$>\n    Order Allow,Deny\n    Deny from all\n</FilesMatch>\n");
    }

    $nombre_archivo = preg_replace('/[^a-zA-Z0-9_-]/', '_', $codigo_acta) . '.pdf';
    $ruta_rel = 'uploads/actas/' . $nombre_archivo;
    $ruta_abs = BASE_PATH . $ruta_rel;

    if ($mpdf !== null) {
        $mpdf->Output($ruta_abs, \Mpdf\Output\Destination::FILE);
    } else {
        file_put_contents($ruta_abs, $pdf_content);
    }

    if (file_exists($ruta_abs)) {
        $stmtUp = $conn->prepare("UPDATE actas SET archivo_pdf = ? WHERE id = ?");
        if ($stmtUp) {
            $stmtUp->bind_param('si', $ruta_rel, $acta_id);
            $stmtUp->execute();
        }
    }

    header("Location: /inventario_ti/modules/actas/detalle.php?id=" . $acta_id . "&success=1");
    exit();
}

function actas_render_header_html($logo_base64, $titulo, $subtitulo, $codigo_acta = '') {
    $codigo_html = '';
    if (!empty($codigo_acta)) {
        $codigo_html = "<div class='codigo'>Código: <strong>" . htmlspecialchars($codigo_acta) . "</strong></div>";
    }
    $logo_html = !empty($logo_base64)
        ? "<img src='$logo_base64' alt='TESA' style='max-width: 70px; height: auto;' />"
        : "<div style='font-weight: bold; color: #5a2d8c;'>TECNOLÓGICO SAN ANTONIO</div>";
    return "
    <div class='header'>
        $logo_html
        <h1>TECNOLÓGICO SAN ANTONIO - TESA</h1>
        <h2>" . htmlspecialchars($subtitulo) . "</h2>
        $codigo_html
    </div>";
}
