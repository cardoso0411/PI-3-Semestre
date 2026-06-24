<?php
// index.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$erro = "";

if (isset($_SESSION['colab_id'])) {
    $cargo_salvo = isset($_SESSION['colab_cargo']) ? trim($_SESSION['colab_cargo']) : '';
    if (strcasecmp($cargo_salvo, 'Supervisor') === 0) {
        header("Location: dashboard.html");
    } else {
        header("Location: operador.html");
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_input = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_SPECIAL_CHARS);
    $senha_input = $_POST['password'];

    if (!empty($usuario_input) && !empty($senha_input)) {
        try {
            if (file_exists("conexao.php")) {
                require_once "conexao.php";
            } else {
                require_once "../conexao.php";
            }

            $sql = "SELECT c.id_colab, c.nome_colab, c.usuario, c.senha, n.cargo 
                    FROM COLABORADORES c
                    LEFT JOIN NIVEIS n ON c.id_cargo = n.id_cargo
                    WHERE c.usuario = :usuario";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':usuario', $usuario_input);
            $stmt->execute();
            
            $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($colaborador) {
                $senha_valida = false;
                if ($senha_input === $colaborador['senha'] || 
                    password_verify($senha_input, $colaborador['senha'])) {
                    $senha_valida = true;
                }

                if ($senha_valida) {
                    $_SESSION['colab_id']      = $colaborador['id_colab'];
                    $_SESSION['colab_nome']    = $colaborador['nome_colab'];
                    $_SESSION['colab_usuario'] = $colaborador['usuario'];
                    
                    $cargo_destino = !empty($colaborador['cargo']) ? trim($colaborador['cargo']) : 'Operador';
                    $_SESSION['colab_cargo']   = $cargo_destino; 

                    if (strcasecmp($cargo_destino, 'Supervisor') === 0) {
                        header("Location: dashboard.html");
                    } else {
                        header("Location: operador.html");
                    }
                    exit;
                } else {
                    $erro = "Senha incorreta.";
                }
            } else {
                $erro = "Usuário/Matrícula não encontrado.";
            }

        } catch (PDOException $e) {
            $erro = "Erro de banco de dados: " . $e->getMessage();
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
            <div class="sub">EPI Guardian · Autenticação do Sistema</div>
        </div>

        <form method="POST" action="">
            
            <?php if (!empty($erro)): ?>
                <div style="color: #ff4d4d; background: rgba(255,77,77,0.1); padding: 12px; border-radius: 6px; margin-bottom: 18px; font-size: 14px; text-align: center; border: 1px solid rgba(255,77,77,0.25)">
                    ⚠️ <?php echo $erro; ?>
                </div>
            <?php endif; ?>

            <div class="field">
                <label for="usuario">Usuário / Matrícula</label>
                <input id="usuario" name="usuario" placeholder="Digite seu usuário ou matrícula" value="<?php echo isset($usuario_input) ? $usuario_input : ''; ?>" required/>
            </div>
            
            <div class="field">
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" placeholder="••••••" required/>
            </div>
            
            <button class="btn primary" style="width:100%" type="submit">Entrar</button>
        </form>
    </div>
</div>
</body>
</html>