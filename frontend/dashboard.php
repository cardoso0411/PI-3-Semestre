
<?php
// 1. INICIA A SESSÃO PARA GUARDAR O LOGIN
session_start();

// Habilita a exibição de erros ocultos do PHP para ajudar no desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Se o colaborador já estiver logado, redireciona direto para a página certa
if (isset($_SESSION['colab_id'])) {
    if (strcasecmp($_SESSION['colab_cargo'], 'Supervisor') === 0) {
        header("Location: dashboard.php");
    } else {
        header("Location: operador.php");
    }
    exit;
}

$erro = "";

// 2. PROCESSA O FORMULÁRIO QUANDO ENVIADO (VIA POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Pega os dados dos campos 'name' do formulário
    $usuario_input = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_SPECIAL_CHARS);
    $senha_input = $_POST['password'];

    if (!empty($usuario_input) && !empty($senha_input)) {
        try {
            // Tenta incluir o arquivo de conexão. 
            // Se ele estiver na pasta raiz (acima de frontend), use: ../conexao.php
            // Se ele estiver dentro da mesma pasta frontend, mude para: conexao.php
            if (file_exists("conexao.php")) {
                require_once "conexao.php";
            } else {
                require_once "../conexao.php";
            }

            // Consulta estruturada exatamente com os nomes das tabelas em maiúsculo do seu banco
            $sql = "SELECT c.id_colab, c.nome_colab, c.usuario, c.senha, n.cargo 
                    FROM COLABORADORES c
                    INNER JOIN NIVEIS n ON c.id_cargo = n.id_cargo
                    WHERE c.usuario = :usuario";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':usuario', $usuario_input);
            $stmt->execute();
            
            $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica se encontrou o usuário e se a senha bate (texto puro igual no banco)
            if ($colaborador && $senha_input === $colaborador['senha']) {
                
                // Grava as variáveis na sessão do servidor
                $_SESSION['colab_id']      = $colaborador['id_colab'];
                $_SESSION['colab_nome']    = $colaborador['nome_colab'];
                $_SESSION['colab_usuario'] = $colaborador['usuario'];
                $_SESSION['colab_cargo']   = $colaborador['cargo']; 

                // Redireciona baseado no cargo do banco
                if (strcasecmp($colaborador['cargo'], 'Supervisor') === 0) {
                    header("Location: dashboard.php");
                } else {
                    header("Location: operador.php");
                }
                exit;

            } else {
                $erro = "Usuário ou senha incorretos no sistema.";
            }

        } catch (PDOException $e) {
            $erro = "Erro crítico de banco: " . $e->getMessage();
        }
    } else {
        $erro = "Por favor, preencha todos os campos.";
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Login — Securi&Tech EPI Guardian</title>
    <link rel="stylesheet" href="css/styles.css"/>
</head>
<body>
<div class="login">
    <div class="card">
        <div style="text-align:center;margin-bottom:20px">
            <div style="font-size:48px">🛡️</div>
            <h1>Securi&<span style="color:var(--primary)">Tech</span></h1>
            <div class="sub">EPI Guardian · Login do operador/supervisor</div>
        </div>

        <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            
            <?php if (!empty($erro)): ?>
                <div style="color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 12px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; border: 1px solid rgba(255,77,77,0.2)">
                    <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <div class="field">
                <label for="usuario">Usuário / Matrícula</label>
                <input id="usuario" name="usuario" placeholder="ex.: 20001" value="<?php echo isset($usuario_input) ? $usuario_input : ''; ?>" required/>
            </div>
            
            <div class="field">
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" placeholder="••••••" required/>
            </div>
            
            <button class="btn primary" style="width:100%;justify-content:center" type="submit">Entrar</button>
            
            <p class="muted" style="font-size:12px;margin-top:14px;text-align:center">
                <b>Credenciais de Teste no Banco:</b><br/>
                Supervisor: <b>20001</b> (senha: <b>senha123</b>)<br/>
                Operador: <b>10234</b> (senha: <b>operador1</b>)
            </p>
        </form>
    </div>
</div>

</body>
</html>