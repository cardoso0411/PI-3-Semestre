<?php
// backend/dashboard_api.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . "/../frontend/conexao.php";

try {
    $response = [
        "metrics" => [
            "compliant"  => 0,
            "violations" => 0,
            "paused"     => 0,
            "offline"    => 0
        ],
        "stations" => []
    ];

    // === ESTAÇÕES EM TEMPO REAL ===
    $sqlStations = "
        SELECT 
            cam.id_cam,
            cam.estacao,
            cam.setor_cam AS localizacao,
            cam.status_cam,
            a.Status_EPI,
            a.Status_maq,
            a.data_hora,
            c.nome_colab
        FROM CAMERAS cam
        LEFT JOIN ALERTAS a ON cam.id_cam = a.id_cam AND a.data_hora = (
            SELECT MAX(data_hora) 
            FROM ALERTAS 
            WHERE id_cam = cam.id_cam
        )
        LEFT JOIN COLABORADORES c ON a.id_colab = c.id_colab
        ORDER BY cam.estacao
    ";

    $stmt = $pdo->prepare($sqlStations);
    $stmt->execute();
    $stationsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contadores dinâmicos para sincronizar com a tabela de cima
    $compliantCount  = 0;
    $violationsCount = 0;
    $pausedCount     = 0;
    $offlineCount    = 0;

    foreach ($stationsData as $row) {
        $status = "compliant";
        $statusText = "Conforme";
        $operationTime = 0;

        // REGRA 1: Se a câmera/máquina está em manutenção ou inativa -> Fica OFFLINE
        if ($row['status_cam'] === 'Manutenção' || $row['status_cam'] === 'Inativa') {
            $status = "offline";
            $statusText = "OFFLINE";
            $offlineCount++;
            $operationTime = 0;
        } else {
            // Se a máquina está ativa, verificamos os estados do Alerta
            $statusEPI = $row['Status_EPI'] ?? '';
            $statusMaq = $row['Status_maq'] ?? '';

            // REGRA 2: Se o funcionário entrou em pausa (Valida tanto no EPI quanto no status da máquina)
            if ($statusEPI === 'Em Pausa' || $statusMaq === 'Pausa' || $statusMaq === 'Em Pausa') {
                $status = "paused";
                $statusText = "EM PAUSA";
                $pausedCount++;
                
                // Calcula há quanto tempo ele está em pausa
                if (!empty($row['data_hora'])) {
                    $dataAlerta = new DateTime($row['data_hora']);
                    $agora = new DateTime();
                    $diferenca = $dataAlerta->diff($agora);
                    $operationTime = ($diferenca->days * 24 * 60) + ($diferenca->h * 60) + $diferenca->i;
                }
            } 
            // REGRA 3: Se houver detecção de falta de EPI -> VIOLAÇÃO
            elseif (strpos($statusEPI, 'Ausência') !== false) {
                $status = "violation";
                $statusText = "VIOLAÇÃO";
                $violationsCount++;
                
                // Trava o tempo de operação (Ex: parado em 45 min até que cliquem em reativar)
                $operationTime = 45; 
            } 
            // REGRA 4: Padrão Seguro -> CONFORME
            else {
                $status = "compliant";
                $statusText = "Conforme";
                $compliantCount++;

                // Tempo de operação dinâmico correndo em minutos
                if (!empty($row['data_hora'])) {
                    $dataAlerta = new DateTime($row['data_hora']);
                    $agora = new DateTime();
                    $diferenca = $dataAlerta->diff($agora);
                    $operationTime = ($diferenca->days * 24 * 60) + ($diferenca->h * 60) + $diferenca->i;
                }
            }
        }
        
        $response['stations'][] = [
            "id_cam"        => (int)$row['id_cam'],
            "name"          => $row['estacao'] ?? 'Estação Desconhecida',
            "location"      => $row['localizacao'] ?? 'Sem localização',
            "employee"      => $row['nome_colab'] ?? 'Sem operador',
            "status"        => $status,
            "statusText"    => $statusText,
            "operationTime" => $operationTime
        ];
    }

    // Dados de contingência apenas se o banco de dados estiver 100% zerado/vazio
    if (empty($response['stations'])) {
        $response['stations'] = [
            ["id_cam" => 1, "name" => "Estação A1", "location" => "Linha Principal", "employee" => "João Silva", "status" => "compliant",  "statusText" => "Conforme", "operationTime" => 85],
            ["id_cam" => 3, "name" => "Estação B2", "location" => "Área de Corte",   "employee" => "Maria Santos","status" => "violation", "statusText" => "VIOLAÇÃO", "operationTime" => 45],
            ["id_cam" => 4, "name" => "Estação C3", "location" => "Fundição",       "employee" => "Sem operador", "status" => "offline",     "statusText" => "OFFLINE", "operationTime" => 0]
        ];
        $compliantCount  = 1;
        $violationsCount = 1;
        $pausedCount     = 0;
        $offlineCount    = 1;
    }

    // Alimenta os cards superiores com os mesmos dados da lista inferior
    $response['metrics'] = [
        "compliant"  => $compliantCount,
        "violations" => $violationsCount,
        "paused"     => $pausedCount,
        "offline"    => $offlineCount
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
exit;