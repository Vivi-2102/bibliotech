
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirLogin();

$livros = $pdo->query('SELECT COUNT(*) FROM livros')->fetchColumn();

// Administrador visualiza todos os usuários
// Professor visualiza somente os alunos
if ($_SESSION['tipo'] === 'admin') {

    $usuarios = $pdo->query(
        'SELECT COUNT(*) FROM usuarios'
    )->fetchColumn();

} elseif ($_SESSION['tipo'] === 'professor') {

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM usuarios WHERE tipo = 'aluno'"
    );

    $stmt->execute();
    $usuarios = $stmt->fetchColumn();

} else {

    $usuarios = 0;

}

$emprestimos = $pdo->query("SELECT COUNT(*) FROM emprestimos WHERE status = 'ativo'")->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — BiblioTech</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<main class="container">
    <h1>Olá, <?= htmlspecialchars($_SESSION['nome']) ?>!</h1>
    <p>Perfil: <strong><?= htmlspecialchars($_SESSION['tipo']) ?></strong></p>

    <section class="grid">
        <div class="card"><h2>📚 Livros</h2><strong><?= $livros ?></strong></div>
        <div class="card"><h2>👥 Usuários</h2><strong><?= $usuarios ?></strong></div>
        <div class="card"><h2>📋 Empréstimos ativos</h2><strong><?= $emprestimos ?></strong></div>
    </section>

    <div class="card">
        <h2>Próximas tarefas</h2>
        <ul>
            <li>Completar CRUD de usuários.</li>
            <li>Completar CRUD de livros.</li>
            <li>Implementar regras de empréstimo e devolução.</li>
            <li>Testar permissões dos três perfis.</li>
        </ul>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
