<?php
// 1. INICIA A SESSÃO E PROTEGE A PÁGINA
session_start();

// Se não houver sessão ativa ou o cargo não for Supervisor, expulsa o usuário
if (!isset($_SESSION['colab_id']) || strcasecmp($_SESSION['colab_cargo'], 'Supervisor') !== 0) {
    header("Location: index.php");
    exit;
}

// 2. IMPORTA A CONEXÃO COM O BANCO DE DADOS
// Note que usamos "../conexao.php" assumindo que o arquivo está na raiz do projeto (uma pasta acima)
require_once "../conexao.php";

// Pegamos o nome do supervisor logado para usar onde for necessário
$nome_supervisor = $_SESSION['colab_nome'];

try {
    // 3. BUSCA AS ESTAÇÕES E CÂMERAS DIRETO DO BANCO DE DADOS
    // Fazemos um JOIN entre ESTACAO e CAMERAS para trazer as informações completas
    $sql_estacoes = "SELECT e.Nome_estacao, c.setor_cam, c.status_cam, c.id_cam 
                     FROM ESTACAO e
                     INNER JOIN CAMERAS c ON e.id_cam = c.id_cam";
    
    $stmt = $pdo->query($sql_estacoes);
    $estacoes_banco = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. BUSCA OS ÚLTIMOS ALERTAS DO BANCO PARA COMPLEMENTAR OS DADOS
    $sql_alertas = "SELECT a.*, c.nome_colab 
                    FROM ALERTAS a
                    INNER JOIN COLABORADORES c ON a.id_colab = c.id_colab
                    ORDER BY a.data_hora DESC";
    $stmt_alertas = $pdo->query($sql_alertas);
    $alertas_banco = $stmt_alertas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Em caso de erro no banco, exibe a mensagem amigável
    die("Erro ao carregar dados do Dashboard: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Dashboard — EPI Guardian</title>
    <link rel="stylesheet" href="css/styles.css"/>
</head>
<body>

<div data-layout="supervisor" data-active="dashboard" data-user-name="<?php echo htmlspecialchars($nome_supervisor); ?>"></div>

<script src="js/mock-data.js"></script>
<script src="js/api.js"></script>
<script src="js/auth.js"></script>
<script src="js/realtime.js"></script>
<script src="js/layout.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // Exemplo de como usar o nome do supervisor vindo do banco direto no JavaScript se quiser:
    const nomeSupervisor = "<?php echo htmlspecialchars($nome_supervisor); ?>";
    console.log("Supervisor logado de forma dinâmica pelo PHP: " + nomeSupervisor);

    // Seleciona o container criado dinamicamente pelo seu script 'layout.js'
    const root = document.getElementById("page-content"); 
    if (!root) return;

    // Injeta a estrutura base do Dashboard na tela
    root.innerHTML = `
        <div style="margin-bottom: 20px;">
            <h2>Olá, ${nomeSupervisor}! 👋</h2>
            <p class="muted">Painel de Monitoramento Securi&Tech EPI Guardian</p>
        </div>
        <div class="grid cols-4" id="metrics"></div>
        <div class="card">
          <div class="flex-between" style="margin-bottom:12px">
            <h3 style="margin:0">Estações em tempo real (Mapeadas via Banco)</h3>
            <span class="muted" id="updated"></span>
          </div>
          <div class="grid cols-3" id="stations"></div>
        </div>`;

    // Pegamos os dados reais trazidos pelo PHP e convertemos em um Array JSON para o JavaScript ler
    const dadosEstacoesBanco = <?php echo json_encode($estacoes_banco); ?>;
    const dadosAlertasBanco = <?php echo json_encode($alertas_banco); ?>;

    // O sistema continuará escutando o simulador em tempo real (realtime.js), 
    // mas agora podemos cruzar as informações ou exibir as informações fixas que estão no seu banco.
    REALTIME.subscribe((stations) => {
        const compl = stations.filter(s => s.epiStatus === "compliant").length;
        const viol  = stations.filter(s => s.epiStatus === "violation").length;
        const paus  = stations.filter(s => s.epiStatus === "paused").length;
        const off   = stations.filter(s => s.epiStatus === "offline").length;

        // Atualiza os Cards Superiores de Métrica
        document.getElementById("metrics").innerHTML = [
          ["Conformes", compl, "var(--success)"],
          ["Violações", viol, "var(--danger)"],
          ["Em pausa", paus, "var(--warning)"],
          ["Offline", off, "var(--muted)"],
        ].map(([l,v,c]) => `<div class="card metric"><div class="label">${l}</div><div class="value" style="color:${c}">${v}</div></div>`).join("");

        // Atualiza a listagem de Estações de Trabalho na tela
        document.getElementById("stations").innerHTML = stations.map((s, index) => {
          const emp = findEmployee(s.employeeId);
          
          // Tratamento dinâmico: Se a estação existir no seu banco de dados, usamos o nome do banco
          // Caso contrário, mantemos o nome fictício que vem do simulador realtime.js
          const nomeEstacaoReal = dadosEstacoesBanco[index] ? dadosEstacoesBanco[index].Nome_estacao : s.name;
          const localEstacaoReal = dadosEstacoesBanco[index] ? dadosEstacoesBanco[index].setor_cam : s.location;

          return `<div class="card">
            <div class="flex-between"><b>${nomeEstacaoReal}</b>${statusBadge(s.epiStatus)}</div>
            <div class="muted" style="font-size:12px;margin:4px 0 10px">${localEstacaoReal}</div>
            <div class="row"><span class="muted">Operador Atual:</span> ${emp?.name || "—"}</div>
            <div class="row"><span class="muted">Confiança IA:</span> ${(s.aiConfidence*100).toFixed(0)}%</div>
            <div class="row"><span class="muted">Operação:</span> ${s.operationMinutes} min</div>
            <div class="row"><span class="muted">Status Máquina:</span> <span style="color:${s.machineLocked?'var(--danger)':'var(--success)'}">${s.machineLocked?'Bloqueada':'Liberada'}</span></div>
          </div>`;
        }).join("");

        document.getElementById("updated").textContent = "Atualizado " + new Date().toLocaleTimeString("pt-BR");
    });

    REALTIME.start();
});
</script>
</body>
</html>