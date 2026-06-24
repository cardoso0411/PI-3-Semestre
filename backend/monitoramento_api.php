<?php
// backend/monitoramento_api.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../frontend/conexao.php";

try {
    $response = [
        "stations" => [],
        "events" => []
    ];

    // 1. BUSCAR ESTAÇÕES E STATUS EM TEMPO REAL (Otimizado por id_alerta)
    $sqlStations = "
        SELECT 
            cam.id_cam,
            cam.estacao,
            cam.setor_cam,
            cam.status_cam,
            a.Status_EPI,
            a.Status_maq,
            a.data_hora,
            c.nome_colab,
            c.id_colab 
        FROM CAMERAS cam
        -- Melhoria aqui: Vincula direto pelo ID do último alerta daquela câmera
        LEFT JOIN ALERTAS a ON a.id_alerta = (
            SELECT MAX(id_alerta) 
            FROM ALERTAS 
            WHERE id_cam = cam.id_cam
        )
        LEFT JOIN COLABORADORES c ON a.id_colab = c.id_colab
        ORDER BY cam.estacao
    ";

    $stmt = $pdo->prepare($sqlStations);
    $stmt->execute();
    $stationsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stationsData as $row) {
        $epiStatus = "compliant";
        $machineLocked = false;
        
        // Regras de negócio mapeadas com o seu CSS/Layout
        if ($row['status_cam'] === 'Manutenção' || $row['status_cam'] === 'Inativa') {
            $epiStatus = "offline";
        } elseif ($row['Status_EPI'] === 'Em Pausa' || $row['Status_maq'] === 'Pausa') {
            $epiStatus = "paused";
        } elseif (strpos($row['Status_EPI'] ?? '', 'Ausência') !== false) {
            $epiStatus = "violation";
            $machineLocked = true; // Trava a máquina automaticamente em violação
        }

        $response['stations'][] = [
            "id" => (string)$row['id_cam'],
            "name" => $row['estacao'] ?? 'Estação',
            "sector" => $row['setor_cam'] ?? 'Geral',
            "location" => "Câmera ID: " . $row['id_cam'],
            "employeeId" => $row['id_colab'] ?? null,
            "employeeName" => $row['nome_colab'] ?? 'Sem operador',
            "employeeMatricula" => $row['id_colab'] ?? '—',
            "epiStatus" => $epiStatus,
            "aiConfidence" => ($epiStatus === 'violation') ? 0.94 : 0.98, 
            "machineLocked" => $machineLocked,
            "cameraUrl" => "http://localhost:5000/video_feed?id=" . $row['id_cam'] 
        ];
    }

    // 2. BUSCAR OS ÚLTIMOS 30 EVENTOS PARA O LOG AO VIVO
    $sqlEvents = "
        SELECT 
            a.id_alerta,
            a.id_cam,
            cam.estacao,
            c.nome_colab,
            a.Status_EPI,
            a.Status_maq,
            a.data_hora
        FROM ALERTAS a
        INNER JOIN CAMERAS cam ON a.id_cam = cam.id_cam
        LEFT JOIN COLABORADORES c ON a.id_colab = c.id_colab
        ORDER BY a.data_hora DESC
        LIMIT 30
    ";

    $stmtEvents = $pdo->prepare($sqlEvents);
    $stmtEvents->execute();
    $eventsData = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

    foreach ($eventsData as $ev) {
        $type = "compliant";
        if (strpos($ev['Status_EPI'] ?? '', 'Ausência') !== false) {
            $type = "alert_triggered";
        } elseif ($ev['Status_EPI'] === 'Em Pausa') {
            $type = "safe_pause";
        }

        $response['events'][] = [
            "id" => $ev['id_alerta'],
            "stationId" => $ev['id_cam'],
            "employeeId" => $ev['nome_colab'] ?? 'Sistema',
            "type" => $type,
            "message" => "Status Maq: " . $ev['Status_maq'] . " | EPI: " . $ev['Status_EPI'],
            "confidence" => 0.95,
            "timestamp" => $ev['data_hora']
        ];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
exit;