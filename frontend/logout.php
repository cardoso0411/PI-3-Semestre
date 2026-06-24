<?php
// Inicia a sessão atual para ter acesso a ela
session_start();

// Limpa todas as variáveis salvas na sessão ($_SESSION)
$_SESSION = array();

// Se desejar matar a sessão completamente, apague também o cookie da sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão no servidor
session_destroy();

// Redireciona o usuário de volta para a tela de login
header("Location: index.php");
exit;