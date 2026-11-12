
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirPerfil(['admin', 'professor']);

$usuarios = $pdo->query(
    "SELECT id, nome FROM usuarios WHERE tipo = 'aluno' AND status = 'ativo' ORDER BY nome"
)->fetchAll();

$livros = $pdo->query(
    "SELECT id, titulo, quantidade_disponivel
     FROM livros
     WHERE quantidade_disponivel > 0
     ORDER BY titulo"
)->fetchAll();

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
    $livro_id = filter_input(INPUT_POST, 'livro_id', FILTER_VALIDATE_INT);
    $data_devolucao_prevista = $_POST['data_devolucao_prevista'] ?? '';

    if (!$usuario_id || !$livro_id || $data_devolucao_prevista === '') {

        $mensagem = 'Preencha todos os campos corretamente.';

    } else {

        try {

            // Iniciar transação
            $pdo->beginTransaction();

            // Verificar se o aluno existe e está ativo
            $stmt = $pdo->prepare(
                "SELECT id FROM usuarios
                 WHERE id = :id AND tipo = 'aluno' AND status = 'ativo'"
            );

            $stmt->execute(['id' => $usuario_id]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                throw new Exception('Aluno não encontrado ou inativo.');
            }

            // Verificar se o livro existe e possui disponibilidade
            $stmt = $pdo->prepare(
                'SELECT quantidade_disponivel
                 FROM livros
                 WHERE id = :id
                 FOR UPDATE'
            );

            $stmt->execute(['id' => $livro_id]);
            $livro = $stmt->fetch();

            if (!$livro) {
                throw new Exception('Livro não encontrado.');
            }

            if ($livro['quantidade_disponivel'] <= 0) {
                throw new Exception('Não existe livro disponível para empréstimo.');
            }

            // Registrar o empréstimo
            $stmt = $pdo->prepare(
                'INSERT INTO emprestimos
                (usuario_id, livro_id, data_retirada, data_devolucao_prevista, status)
                VALUES
                (:usuario_id, :livro_id, :data_retirada, :data_devolucao_prevista, :status)'
            );

            $stmt->execute([
                'usuario_id' => $usuario_id,
                'livro_id' => $livro_id,
                'data_retirada' => date('Y-m-d'),
                'data_devolucao_prevista' => $data_devolucao_prevista,
                'status' => 'ativo'
            ]);

            // Diminuir a quantidade disponível do livro
            $stmt = $pdo->prepare(
                'UPDATE livros
                 SET quantidade_disponivel = quantidade_disponivel - 1
                 WHERE id = :id AND quantidade_disponivel > 0'
            );

            $stmt->execute(['id' => $livro_id]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception('Não foi possível atualizar a quantidade disponível.');
            }

            // Confirmar as operações
            $pdo->commit();

            $mensagem = 'Empréstimo registrado com sucesso!';

            // Atualizar a lista de livros disponíveis
            $livros = $pdo->query(
                "SELECT id, titulo, quantidade_disponivel
                 FROM livros
                 WHERE quantidade_disponivel > 0
                 ORDER BY titulo"
            )->fetchAll();

        } catch (Exception $e) {

            // Desfazer operações em caso de erro
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $mensagem = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo empréstimo</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">
    <div class="card">
        <h1>Novo empréstimo</h1>

        <?php if (!empty($mensagem)): ?>
            <div class="alert"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Aluno</label>
            <select name="usuario_id" required>
                <option value="">Selecione</option>
                <?php foreach ($usuarios as $usuario): ?>
                    <option value="<?= $usuario['id'] ?>">
                        <?= htmlspecialchars($usuario['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Livro</label>
            <select name="livro_id" required>
                <option value="">Selecione</option>
                <?php foreach ($livros as $livro): ?>
                    <option value="<?= $livro['id'] ?>">
                        <?= htmlspecialchars($livro['titulo']) ?>
                        — <?= $livro['quantidade_disponivel'] ?> disponível(is)
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Data prevista para devolução</label>
            <input type="date" name="data_devolucao_prevista" required>

            <button type="submit">Registrar empréstimo</button>
        </form>
    </div>
</main>
</body>
</html>
