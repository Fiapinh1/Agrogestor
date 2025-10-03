<?php
require 'conexao.php';
require 'auth.php';

// Se já logado, manda pra home
if (isLogged()) {
  header('Location: index.php'); exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';
  if ($email === '' || $senha === '') {
    $erro = 'Informe e-mail e senha.';
  } else {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND ativo = 1 LIMIT 1");
    $stmt->execute([':email' => $email]);
    $u = $stmt->fetch();
    if ($u && password_verify($senha, $u['senha_hash'])) {
      $_SESSION['user'] = [
        'id' => $u['id'],
        'nome' => $u['nome'],
        'email' => $u['email'],
        'perfil' => $u['perfil'],
      ];
      header('Location: index.php'); exit;
    } else {
      $erro = 'Credenciais inválidas.';
    }
  }
}

// Header simples (sem inc_header para evitar exigir login aqui)
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | AgroGestor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:420px;">
  <div class="card shadow-sm">
    <div class="card-body">
      <h4 class="mb-3"><i class="bi bi-hexagon"></i> AgroGestor</h4>

      <?php if ($erro): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($erro) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="mb-3">
          <label class="form-label">E-mail</label>
          <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Senha</label>
          <input type="password" name="senha" class="form-control" required>
        </div>
        <button class="btn btn-success w-100" type="submit">
          <i class="bi bi-box-arrow-in-right"></i> Entrar
        </button>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
