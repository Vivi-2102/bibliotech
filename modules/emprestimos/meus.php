
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirPerfil(['aluno']);

// Buscar somente os empréstimos do aluno conectado
$stmt = $pdo->prepare(
    "SELECT l.titulo, l.autor, e.data_retirada,
            e.data_devolucao_prevista, e.data_devolucao_real, e.status
     FROM emprestimos e
     INNER JOIN livros l ON l.id = e.livro_id
     WHERE e.usuario_id = :usuario_id
     ORDER BY e.id DESC"
);

$stmt->execute([
    'usuario_id' => $_SESSION['usuario_id']
]);

$emprestimos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Meus empréstimos</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">
    <h1>Meus empréstimos</h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Livro</th>
                    <th>Retirada</th>
                    <th>Devolução prevista</th>
                    <th>Devolução real</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($emprestimos as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['titulo']) ?></td>
                    <td><?= htmlspecialchars($item['data_retirada']) ?></td>
                    <td><?= htmlspecialchars($item['data_devolucao_prevista']) ?></td>
                    <td><?= htmlspecialchars($item['data_devolucao_real'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
