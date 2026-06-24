<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../frontend/conexao.php";

$action = $_GET['action'] ?? "";

/* =========================
   EVENTOS (ALERTAS)
========================= */
if ($action === "events") {

    $sql = "
        SELECT 
            a.data_hora,
            c.nome_colab,
            cam.estacao,
            a.Status_EPI,
            a.Status_maq
        FROM ALERTAS a
        LEFT JOIN COLABORADORES c ON a.id_colab = c.id_colab
        LEFT JOIN CAMERAS cam ON a.id_cam = cam.id_cam
        ORDER BY a.data_hora DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $data = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $isViolation = ($row["Status_EPI"] === 'Ausência de Capacete');

        $data[] = [
            "timestamp" => $row["data_hora"],
            "employee"  => $row["nome_colab"],
            "station"   => $row["estacao"],
            "type"      => $row["Status_EPI"],
            "message"   => $isViolation ? "VIOLAÇÃO - Ausência de Capacete" : $row["Status_maq"]
        ];
    }

    echo json_encode($data);
    exit;
}

/* =========================
   VIOLAÇÕES POR COLABORADOR
========================= */
if ($action === "violationsByEmployee") {

    $sql = "
        SELECT 
            c.nome_colab,
            COUNT(*) AS violacoes
        FROM ALERTAS a
        JOIN COLABORADORES c ON a.id_colab = c.id_colab
        WHERE a.Status_EPI = 'Ausência de Capacete'
        GROUP BY c.id_colab, c.nome_colab
        ORDER BY violacoes DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $data = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $data[] = [
            "name" => $row["nome_colab"],
            "violacoes" => (int)$row["violacoes"]
        ];
    }

    echo json_encode($data);
    exit;
}

/* =========================
   TREND 7 DIAS
========================= */
if ($action === "trend") {

    $sql = "
        SELECT 
            DATE(data_hora) AS dia,
            COUNT(CASE WHEN Status_EPI = 'Ausência de Capacete' THEN 1 END) AS violacoes,
            COUNT(*) AS total_alertas
        FROM ALERTAS
        WHERE data_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(data_hora)
        ORDER BY dia ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $data = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $viol = (int)$row["violacoes"];
        $total = (int)$row["total_alertas"];

        // Calcula conformidade real (porcentagem de alertas sem violação)
        $conformidade = $total > 0 ? round((($total - $viol) / $total) * 100) : 100;

        $data[] = [
            "day"          => $row["dia"],
            "violacoes"    => $viol,
            "conformidade" => $conformidade
        ];
    }

    echo json_encode($data);
    exit;
}

echo json_encode(["error" => "Ação inválida"]);