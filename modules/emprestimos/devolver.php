
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirPerfil(['admin', 'professor']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php');
    exit;
}

// Buscar o empréstimo
$stmt = $pdo->prepare(
    'SELECT id, livro_id, status
     FROM emprestimos
     WHERE id = :id'
);

$stmt->execute(['id' => $id]);
$emprestimo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emprestimo) {
    $mensagem = 'Empréstimo não encontrado.';
} elseif ($emprestimo['status'] === 'devolvido') {
    $mensagem = 'Este livro já foi devolvido.';
} elseif (!in_array($emprestimo['status'], ['ativo', 'atrasado'], true)) {
    $mensagem = 'Este empréstimo não pode ser devolvido.';
} else {

    try {

        // Iniciar transação
        $pdo->beginTransaction();

        // Buscar e bloquear o empréstimo durante a operação
        $stmt = $pdo->prepare(
            'SELECT livro_id, status
             FROM emprestimos
             WHERE id = :id
             FOR UPDATE'
        );

        $stmt->execute(['id' => $id]);
        $emprestimo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$emprestimo) {
            throw new Exception('Empréstimo não encontrado.');
        }

        if (!in_array($emprestimo['status'], ['ativo', 'atrasado'], true)) {
            throw new Exception('Este empréstimo já foi devolvido ou não pode ser devolvido.');
        }

        // Registrar a data real e alterar o status
        $stmt = $pdo->prepare(
            "UPDATE emprestimos
             SET data_devolucao_real = :data_devolucao_real,
                 status = 'devolvido'
             WHERE id = :id"
        );

        $stmt->execute([
            'data_devolucao_real' => date('Y-m-d'),
            'id' => $id
        ]);

        // Aumentar a quantidade disponível do livro
        $stmt = $pdo->prepare(
            'UPDATE livros
             SET quantidade_disponivel = quantidade_disponivel + 1
             WHERE id = :livro_id'
        );

        $stmt->execute([
            'livro_id' => $emprestimo['livro_id']
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new Exception('Não foi possível atualizar a quantidade do livro.');
        }

        // Confirmar as operações
        $pdo->commit();

        $mensagem = 'Devolução registrada com sucesso!';

    } catch (Exception $e) {

        // Desfazer as operações em caso de erro
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $mensagem = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Devolução</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">
    <div class="card">
        <h1>Registrar devolução</h1>
        <div class="alert"><?= htmlspecialchars($mensagem) ?></div>
        <a class="button" href="index.php">Voltar</a>
    </div>
</main>
</body>
</html>
