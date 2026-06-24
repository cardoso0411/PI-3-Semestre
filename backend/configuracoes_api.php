<?php
// backend/configuracoes_api.php

// 1. Ativa o buffer de saída para segurar qualquer texto inesperado
ob_start(); 

// 2. Configura os cabeçalhos para JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$configFile = __DIR__ . "/config.json";

try {
    // 3. Procura e carrega o seu conexao.php
    $caminhosOpcionais = [
        __DIR__ . "/../frontend/conexao.php", 
        __DIR__ . "/../conexao.php",          
        __DIR__ . "/conexao.php"              
    ];

    $conexaoCarregada = false;
    foreach ($caminhosOpcionais as $caminho) {
        if (file_exists($caminho)) {
            require_once $caminho;
            $conexaoCarregada = true;
            break;
        }
    }

    // 4. O PULO DO GATO: Desativa o display_errors que o conexao.php ativou
    // Isso impede que qualquer Warning ou Notice quebre o nosso JSON
    ini_set('display_errors', 0);
    error_reporting(0);

    // Se o conexao.php deu 'die()', pegamos a mensagem aqui
    $conteudoDoBuffer = ob_get_contents();
    if (!empty($conteudoDoBuffer) && !isset($pdo)) {
        ob_end_clean();
        throw new Exception("Erro vindo do conexao.php: " . strip_tags(trim($conteudoDoBuffer)));
    }

    if (!$conexaoCarregada) {
        throw new Exception("O arquivo 'conexao.php' não foi encontrado nas pastas do projeto.");
    }

    if (!isset($pdo)) {
        throw new Exception("A variável de conexão \$pdo não foi encontrada no escopo.");
    }

    // ========================================================
    // REQUISIÇÕES GET: Buscar dados do banco securtech
    // ========================================================
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        
        // Define o tempo padrão se o arquivo não existir ou falhar
        $pauseSeconds = 30; 
        if (file_exists($configFile)) {
            $configData = json_decode(@file_get_contents($configFile), true);
            if (isset($configData['pauseSeconds'])) {
                $pauseSeconds = (int)$configData['pauseSeconds'];
            }
        }

        // Consultas robustas usando a estrutura exata do seu SQL enviado
        $employees = $pdo->query("
            SELECT c.id_colab, c.nome_colab, c.usuario, n.cargo, s.nome_setor 
            FROM COLABORADORES c
            LEFT JOIN NIVEIS n ON c.id_cargo = n.id_cargo
            LEFT JOIN SETORES s ON c.id_setor = s.id_setor
            ORDER BY c.nome_colab
        ")->fetchAll(PDO::FETCH_ASSOC);

        $stations = $pdo->query("
            SELECT e.id_estacao, e.Nome_estacao, s.nome_setor, cam.id_cam, cam.status_cam
            FROM ESTACAO e
            LEFT JOIN SETORES s ON e.id_setor = s.id_setor
            LEFT JOIN CAMERAS cam ON e.id_cam = cam.id_cam
            ORDER BY e.Nome_estacao
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Tudo certo! Limpa o buffer e cospe o JSON perfeito
        ob_end_clean(); 
        echo json_encode([
            "success" => true,
            "pauseSeconds" => $pauseSeconds,
            "employees" => $employees,
            "stations" => $stations
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ========================================================
    // REQUISIÇÕES POST: Operações de escrita, alteração e exclusão
    // ========================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $acao = $input['acao'] ?? '';

        if ($acao === 'salvar_pausa') {
            $novoTempo = (int)($input['pauseSeconds'] ?? 30);
            if ($novoTempo < 5 || $novoTempo > 120) throw new Exception("Tempo de pausa deve ser de 5 a 120 segundos.");
            
            // Salva a configuração de pausa com segurança
            file_put_contents($configFile, json_encode(['pauseSeconds' => $novoTempo]));
            
            ob_end_clean();
            echo json_encode(["success" => true, "message" => "Tempo de pausa atualizado com sucesso!"]);
            exit;
        }

        if ($acao === 'adicionar_funcionario') {
            $nome = trim($input['nome'] ?? '');
            $usuarioColab = trim($input['matricula'] ?? '');

            if (empty($nome) || empty($usuarioColab)) throw new Exception("Nome e Usuário são obrigatórios.");

            // Seleciona chaves válidas baseadas no seu script DML
            $id_cargo = $pdo->query("SELECT id_cargo FROM NIVEIS LIMIT 1")->fetchColumn() ?: 2;
            $id_setor = $pdo->query("SELECT id_setor FROM SETORES LIMIT 1")->fetchColumn() ?: 1;

            $stmt = $pdo->prepare("INSERT INTO COLABORADORES (nome_colab, usuario, senha, id_cargo, id_setor) VALUES (?, ?, '123456', ?, ?)");
            $stmt->execute([$nome, $usuarioColab, $id_cargo, $id_setor]);

            ob_end_clean();
            echo json_encode(["success" => true, "message" => "Funcionário cadastrado!"]);
            exit;
        }

        if ($acao === 'deletar_funcionario') {
            $id = (int)($input['id_colab'] ?? 0);
            
            $stmt = $pdo->prepare("DELETE FROM COLABORADORES WHERE id_colab = ?");
            $stmt->execute([$id]);

            ob_end_clean();
            echo json_encode(["success" => true, "message" => "Funcionário removido com sucesso!"]);
            exit;
        }

        if ($acao === 'adicionar_estacao') {
            $nomeEstacao = trim($input['nome_estacao'] ?? '');
            if (empty($nomeEstacao)) throw new Exception("O nome da estação não pode ser vazio.");

            // Seleciona um setor e câmera reais do banco para respeitar as chaves estrangeiras
            $id_setor = $pdo->query("SELECT id_setor FROM SETORES LIMIT 1")->fetchColumn() ?: 1;
            $id_cam = $pdo->query("SELECT id_cam FROM CAMERAS LIMIT 1")->fetchColumn() ?: 1;

            $stmt = $pdo->prepare("INSERT INTO ESTACAO (id_setor, id_cam, Nome_estacao) VALUES (?, ?, ?)");
            $stmt->execute([$id_setor, $id_cam, $nomeEstacao]);

            ob_end_clean();
            echo json_encode(["success" => true, "message" => "Estação adicionada com sucesso!"]);
            exit;
        }
    }

} catch (Exception $e) {
    // Caso aconteça qualquer erro, limpa a sujeira do HTML e devolve um JSON limpo que o JS entende
    ob_end_clean(); 
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}