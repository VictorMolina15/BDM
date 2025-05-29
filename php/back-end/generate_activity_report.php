<?php
// php/back-end/generate_activity_report.php

require_once 'verified-session.php'; // Asegura que el usuario está logueado
require_once 'connection.php';

// --- IMPORTANT: ---
// --- 1. Descarga FPDF de fpdf.org ---
// --- 2. Coloca fpdf.php y la carpeta font/ en un directorio accesible, ej: ../lib/fpdf/ ---
// --- 3. Ajusta la siguiente ruta si es necesario ---

require_once __DIR__ . '/../../lib/fpdf/fpdf.php'; // Ajusta esta ruta si FPDF está en otro lugar

$current_user_id = $_SESSION['id_name'];
$username = $_SESSION['username'];

$db = new DBConnection();
$conn = $db->getConnection();

$GLOBALS['report_username'] = $username;
$GLOBALS['report_user_id_name'] = $current_user_id;

class PDF_Activity_Report extends FPDF {
    // Encabezado del PDF
    function Header() {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'Reporte de Actividad Reciente', 0, 1, 'C');
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 10, 'Usuario: ' . utf8_decode($GLOBALS['report_username']) . ' (@' . utf8_decode($GLOBALS['report_user_id_name']) . ')', 0, 1, 'C');
        $this->Ln(5); // Salto de línea
    }

    // Pie de página
    function Footer() {
        $this->SetY(-15); // Posición a 1.5 cm del final
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C'); // Número de página
    }

    // Función para crear una sección de actividad
    function ActivitySection($title, $data, $columnConfig, $formatCallback) {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, utf8_decode($title), 0, 1, 'L');
        $this->SetFont('Arial', '', 10);

        if (empty($data)) {
            $this->Cell(0, 7, utf8_decode('No hay actividad reciente en esta categoría.'), 0, 1);
            $this->Ln(5);
            return;
        }

        // Encabezados de las columnas
        $this->SetFillColor(230, 230, 230);
        $this->SetTextColor(0);
        $this->SetDrawColor(128, 128, 128);
        $this->SetLineWidth(0.3);
        foreach ($columnConfig as $column) {
            $this->Cell($column['width'], 7, utf8_decode($column['header']), 1, 0, 'C', true);
        }
        $this->Ln();

        // Datos
        $this->SetFillColor(245, 245, 245);
        $this->SetTextColor(0);
        $fill = false;
        foreach ($data as $row) {
            $formattedRow = $formatCallback($row);
            $cellIndex = 0;
            foreach ($columnConfig as $column) {
                $this->Cell($column['width'], 6, utf8_decode($formattedRow[$cellIndex++]), 'LRB', 0, 'L', $fill);
            }
            $this->Ln();
            $fill = !$fill;
        }
        $this->Ln(5);
    }
}

// --- Recopilación de Datos ---
$limit_per_activity = 10;

// 1. Publicaciones Creadas
$stmt_posts = $conn->prepare("SELECT content, created_at FROM posts WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit_val");
$stmt_posts->bindParam(':user_id', $current_user_id, PDO::PARAM_STR);
$stmt_posts->bindParam(':limit_val', $limit_per_activity, PDO::PARAM_INT);
$stmt_posts->execute();
$posts_activity = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);

// 2. Comentarios Realizados
$stmt_comments = $conn->prepare("
    SELECT c.content, c.created_at, SUBSTRING(p.content, 1, 50) AS post_preview
    FROM comments c
    JOIN posts p ON c.post_id = p.id
    WHERE c.user_id = :user_id ORDER BY c.created_at DESC LIMIT :limit_val
");
$stmt_comments->bindParam(':user_id', $current_user_id, PDO::PARAM_STR);
$stmt_comments->bindParam(':limit_val', $limit_per_activity, PDO::PARAM_INT);
$stmt_comments->execute();
$comments_activity = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

// 3. "Me Gusta" Dados
$stmt_likes = $conn->prepare("
    SELECT pl.created_at, SUBSTRING(p.content, 1, 50) AS post_preview
    FROM post_likes pl
    JOIN posts p ON pl.post_id = p.id
    WHERE pl.user_id = :user_id ORDER BY pl.created_at DESC LIMIT :limit_val
");
$stmt_likes->bindParam(':user_id', $current_user_id, PDO::PARAM_STR);
$stmt_likes->bindParam(':limit_val', $limit_per_activity, PDO::PARAM_INT); // Esta es la línea que causaba el error (aprox. 89)
$stmt_likes->execute();
$likes_activity = $stmt_likes->fetchAll(PDO::FETCH_ASSOC);

// 4. Comunidades Unidas
$stmt_community_joins = $conn->prepare("
    SELECT cm.joined_at, c.name_comm
    FROM community_members cm
    JOIN communities c ON cm.community_id = c.id
    WHERE cm.user_id = :user_id ORDER BY cm.joined_at DESC LIMIT :limit_val
");
$stmt_community_joins->bindParam(':user_id', $current_user_id, PDO::PARAM_STR);
$stmt_community_joins->bindParam(':limit_val', $limit_per_activity, PDO::PARAM_INT);
$stmt_community_joins->execute();
$community_joins_activity = $stmt_community_joins->fetchAll(PDO::FETCH_ASSOC);

// --- Creación del PDF ---
$pdf = new PDF_Activity_Report();
$pdf->AliasNbPages();
$pdf->AddPage();

$posts_columns = [
    ['header' => 'Contenido (Inicio)', 'width' => 130],
    ['header' => 'Fecha', 'width' => 60]
];
$comments_columns = [
    ['header' => 'Comentario (Inicio)', 'width' => 90],
    ['header' => 'En Post (Inicio)', 'width' => 60],
    ['header' => 'Fecha', 'width' => 40]
];
$likes_columns = [
    ['header' => 'Post (Inicio)', 'width' => 130],
    ['header' => 'Fecha del Me Gusta', 'width' => 60]
];
$community_columns = [
    ['header' => 'Comunidad', 'width' => 130],
    ['header' => 'Fecha de Union', 'width' => 60]
];

$pdf->ActivitySection(
    'Publicaciones Creadas',
    $posts_activity,
    $posts_columns,
    function($row) {
        return [
            substr(str_replace(["\n", "\r"], " ", $row['content']), 0, 70) . (strlen($row['content']) > 70 ? '...' : ''),
            date("Y-m-d H:i:s", strtotime($row['created_at']))
        ];
    }
);

$pdf->ActivitySection(
    'Comentarios Realizados',
    $comments_activity,
    $comments_columns,
    function($row) {
        return [
            substr(str_replace(["\n", "\r"], " ", $row['content']), 0, 50) . (strlen($row['content']) > 50 ? '...' : ''),
            substr(str_replace(["\n", "\r"], " ", $row['post_preview']), 0, 30) . (strlen($row['post_preview']) > 30 ? '...' : ''),
            date("Y-m-d H:i:s", strtotime($row['created_at']))
        ];
    }
);

$pdf->ActivitySection(
    'Me Gusta Dados',
    $likes_activity,
    $likes_columns,
    function($row) {
        return [
            substr(str_replace(["\n", "\r"], " ", $row['post_preview']), 0, 70) . (strlen($row['post_preview']) > 70 ? '...' : ''),
            date("Y-m-d H:i:s", strtotime($row['created_at']))
        ];
    }
);

$pdf->ActivitySection(
    'Comunidades Unidas',
    $community_joins_activity,
    $community_columns,
    function($row) {
        return [
            $row['name_comm'],
            date("Y-m-d H:i:s", strtotime($row['joined_at']))
        ];
    }
);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, utf8_decode('--- Fin del Reporte ---'), 0, 1, 'C');

$pdf->Output('D', 'Reporte_Actividad_' . $current_user_id . '_' . date('Ymd') . '.pdf');
exit;

?>