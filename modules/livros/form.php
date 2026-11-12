
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirPerfil(['admin', 'professor']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$livro = [
    'titulo' => '',
    'autor' => '',
    'ano_publicacao' => '',
    'quantidade_total' => 1,
    'quantidade_disponivel' => 1
];

$mensagem = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM livros WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $livro = $stmt->fetch() ?: $livro;

    if (empty($livro['titulo'])) {
        $mensagem = 'Livro não encontrado.';
        $id = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $livro['titulo'] = trim($_POST['titulo'] ?? '');
    $livro['autor'] = trim($_POST['autor'] ?? '');
    $livro['ano_publicacao'] = $_POST['ano_publicacao'] ?? '';
    $livro['quantidade_total'] = $_POST['quantidade_total'] ?? '';
    $livro['quantidade_disponivel'] = $_POST['quantidade_disponivel'] ?? '';

    $quantidade_total = filter_var($livro['quantidade_total'], FILTER_VALIDATE_INT);
    $quantidade_disponivel = filter_var($livro['quantidade_disponivel'], FILTER_VALIDATE_INT);

    if ($livro['titulo'] === '' || $livro['autor'] === '') {
        $mensagem = 'Preencha o título e o autor.';
    } elseif ($quantidade_total === false || $quantidade_total < 1) {
        $mensagem = 'A quantidade total deve ser maior que zero.';
    } elseif ($quantidade_disponivel === false || $quantidade_disponivel < 0) {
        $mensagem = 'A quantidade disponível não pode ser negativa.';
    } elseif ($quantidade_disponivel > $quantidade_total) {
        $mensagem = 'A quantidade disponível não pode ser maior que a quantidade total.';
    } elseif ($livro['ano_publicacao'] !== '' && filter_var($livro['ano_publicacao'], FILTER_VALIDATE_INT) === false) {
        $mensagem = 'Informe um ano de publicação válido.';
    } else {

        if ($id) {

            $stmt = $pdo->prepare(
                'UPDATE livros
                 SET titulo = :titulo,
                     autor = :autor,
                     ano_publicacao = :ano_publicacao,
                     quantidade_total = :quantidade_total,
                     quantidade_disponivel = :quantidade_disponivel
                 WHERE id = :id'
            );

            $stmt->execute([
                'titulo' => $livro['titulo'],
                'autor' => $livro['autor'],
                'ano_publicacao' => $livro['ano_publicacao'] === '' ? null : $livro['ano_publicacao'],
                'quantidade_total' => $quantidade_total,
                'quantidade_disponivel' => $quantidade_disponivel,
                'id' => $id
            ]);

            $mensagem = 'Livro atualizado com sucesso!';

        } else {

            $stmt = $pdo->prepare(
                'INSERT INTO livros
                (titulo, autor, ano_publicacao, quantidade_total, quantidade_disponivel)
                VALUES
                (:titulo, :autor, :ano_publicacao, :quantidade_total, :quantidade_disponivel)'
            );

            $stmt->execute([
                'titulo' => $livro['titulo'],
                'autor' => $livro['autor'],
                'ano_publicacao' => $livro['ano_publicacao'] === '' ? null : $livro['ano_publicacao'],
                'quantidade_total' => $quantidade_total,
                'quantidade_disponivel' => $quantidade_total
            ]);

            $mensagem = 'Livro cadastrado com sucesso!';

            $livro = [
                'titulo' => '',
                'autor' => '',
                'ano_publicacao' => '',
                'quantidade_total' => 1,
                'quantidade_disponivel' => 1
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Editar' : 'Novo' ?> livro</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">
    <div class="card">
        <h1><?= $id ? 'Editar livro' : 'Novo livro' ?></h1>

        <?php if (!empty($mensagem)): ?>
            <div class="alert"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Título</label>
            <input type="text" name="titulo" value="<?= htmlspecialchars($livro['titulo']) ?>" required>

            <label>Autor</label>
            <input type="text" name="autor" value="<?= htmlspecialchars($livro['autor']) ?>" required>

            <label>Ano de publicação</label>
            <input type="number" name="ano_publicacao" value="<?= htmlspecialchars((string) $livro['ano_publicacao']) ?>">

            <label>Quantidade total</label>
            <input type="number" name="quantidade_total" min="1" value="<?= htmlspecialchars((string) $livro['quantidade_total']) ?>" required>

            <label>Quantidade disponível</label>
            <input type="number" name="quantidade_disponivel" min="0" value="<?= htmlspecialchars((string) $livro['quantidade_disponivel']) ?>" required>

            <button type="submit">Salvar</button>
        </form>
    </div>
</main>
</body>
</html>
