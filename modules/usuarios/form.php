
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirPerfil(['admin', 'professor']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$usuario = [
    'nome' => '',
    'email' => '',
    'tipo' => 'aluno',
    'status' => 'ativo'
];

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Receber os dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tipo = $_POST['tipo'] ?? 'aluno';
    $status = $_POST['status'] ?? 'ativo';
    $senha = $_POST['senha'] ?? '';

    // Manter os dados preenchidos no formulário
    $usuario['nome'] = $nome;
    $usuario['email'] = $email;
    $usuario['tipo'] = $tipo;
    $usuario['status'] = $status;

    // Validar campos obrigatórios
    if (empty($nome) || empty($email) || empty($senha)) {

        $mensagem = 'Preencha todos os campos obrigatórios.';
        $tipoMensagem = 'erro';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensagem = 'Digite um e-mail válido.';
        $tipoMensagem = 'erro';

    } elseif (!in_array($tipo, ['aluno', 'professor', 'admin'], true)) {

        $mensagem = 'Tipo de usuário inválido.';
        $tipoMensagem = 'erro';

    } elseif (!in_array($status, ['ativo', 'bloqueado'], true)) {

        $mensagem = 'Status de usuário inválido.';
        $tipoMensagem = 'erro';

    } else {

        // Verificar se o e-mail já está cadastrado
        $stmt = $pdo->prepare(
            'SELECT id FROM usuarios WHERE email = :email'
        );

        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {

            $mensagem = 'Este e-mail já está cadastrado.';
            $tipoMensagem = 'erro';

        } else {

            // Proteger a senha
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            // Inserir usuário no banco de dados
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nome, email, senha, tipo, status)
                 VALUES (:nome, :email, :senha, :tipo, :status)'
            );

            $resultado = $stmt->execute([
                'nome' => $nome,
                'email' => $email,
                'senha' => $senhaHash,
                'tipo' => $tipo,
                'status' => $status
            ]);

            if ($resultado) {
                $mensagem = 'Usuário cadastrado com sucesso!';
                $tipoMensagem = 'sucesso';

                // Limpar os campos após o cadastro
                $usuario = [
                    'nome' => '',
                    'email' => '',
                    'tipo' => 'aluno',
                    'status' => 'ativo'
                ];
            } else {
                $mensagem = 'Não foi possível cadastrar o usuário.';
                $tipoMensagem = 'erro';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Editar' : 'Novo' ?> usuário</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">
    <div class="card">
        <h1><?= $id ? 'Editar usuário' : 'Novo usuário' ?></h1>

        <?php if (!empty($mensagem)): ?>
            <div class="alert"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>

            <label>E-mail</label>
            <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>

            <label>Senha <?= $id ? '(deixe vazia para não alterar)' : '' ?></label>
            <input type="password" name="senha" <?= $id ? '' : 'required' ?>>

            <label>Tipo</label>
            <select name="tipo">
                <option value="aluno" <?= $usuario['tipo'] === 'aluno' ? 'selected' : '' ?>>Aluno</option>
                <option value="professor" <?= $usuario['tipo'] === 'professor' ? 'selected' : '' ?>>Professor</option>
                <option value="admin" <?= $usuario['tipo'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>

            <label>Status</label>
            <select name="status">
                <option value="ativo" <?= $usuario['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="bloqueado" <?= $usuario['status'] === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
            </select>

            <button type="submit">Salvar</button>
        </form>
    </div>
</main>
</body>
</html>
