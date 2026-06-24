<?php
// backend/reativar_api.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . "/../frontend/conexao.php";

// Recebe os dados enviados pelo JavaScript
$dados = json_decode(file_get_contents("php://input"), true);
$id_cam = $dados['id_cam'] ?? null;

if (!$id_cam) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID da câmera não fornecido."]);
    exit;
}

try {
    // 1. Descobre qual foi o último colaborador associado a essa câmera para manter o histórico coerente
    $sqlColab = "SELECT id_colab FROM ALERTAS WHERE id_cam = :id_cam ORDER BY data_hora DESC LIMIT 1";
    $stmt = $pdo->prepare($sqlColab);
    $stmt->execute([':id_cam' => $id_cam]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Se não achar nenhum, usa o id 3 (João) como padrão de contingência
    $id_colab = $resultado['id_colab'] ?? 3;

    // 2. Insere um NOVO alerta com a data/hora de AGORA limpando a violação
    $sqlInsert = "
        INSERT INTO ALERTAS (id_colab, id_cam, Status_EPI, Status_maq, data_hora) 
        VALUES (:id_colab, :id_cam, 'Uso Correto', 'Operação Normal', NOW())
    ";
    
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        ':id_colab' => $id_colab,
        ':id_cam'   => $id_cam
    ]);

    echo json_encode(["success" => true, "message" => "Máquina reativada com sucesso!"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro no banco: " . $e->getMessage()]);
}
exit;