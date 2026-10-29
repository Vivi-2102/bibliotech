<?php
session_start();
require_once __DIR__ . '/database/conexao.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: modules/dashboard/index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preencha e-mail e senha.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, nome, email, senha, tipo, status
             FROM usuarios
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            $erro = 'E-mail ou senha inválidos.';
        } elseif ($usuario['status'] !== 'ativo') {
            $erro = 'Usuário bloqueado.';
        } else {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['tipo'] = $usuario['tipo'];

            header('Location: modules/dashboard/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — BiblioTech</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
<main class="login-card">
    <h1>📚 BiblioTech</h1>
    <p>Sistema de Biblioteca Escolar</p>

    <?php if ($erro): ?>
        <div class="alert error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="email">E-mail</label>
        <input id="email" type="email" name="email" required>

        <label for="senha">Senha</label>
        <input id="senha" type="password" name="senha" required>

        <button type="submit">Entrar</button>
    </form>

   
</main>
</body>
</html>
