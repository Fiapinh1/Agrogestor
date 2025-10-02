<?php
require '../auth.php';
requireLogin(); requireRole('admin');
require '../conexao.php';
require '../utils.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute([':id'=>$id]);
$u = $stmt->fetch();

if (!$u) {
  die("Usuário não encontrado.");
}

$title = 'Editar Usuário | AgroGestor';
include '../inc_header.php';
?>

<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Editar Usuário</h2>
  <a href="listar_usuarios.php" class="btn btn-outline-secondary ms-auto">
    <i class="bi bi-arrow-left"></i> Voltar
  </a>
</div>

<form action="atualizar_usuario.php" method="post" class="row g-3" autocomplete="off">
  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

  <div class="col-md-6">
    <label class="form-label">Nome</label>
    <input type="text" name="nome" value="<?= h($u['nome']) ?>" class="form-control" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">Email</label>
    <input type="email" name="email" value="<?= h($u['email']) ?>" class="form-control" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">Nova Senha (opcional)</label>
    <input type="password" name="senha" class="form-control" autocomplete="new-password">
    <small class="text-muted">Preencha apenas se quiser alterar.</small>
  </div>

  <div class="col-md-3">
    <label class="form-label">Perfil</label>
    <select name="perfil" class="form-select">
      <option value="usuario" <?= $u['perfil']=='usuario'?'selected':'' ?>>Usuário</option>
      <option value="admin" <?= $u['perfil']=='admin'?'selected':'' ?>>Admin</option>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">Ativo</label>
    <select name="ativo" class="form-select">
      <option value="1" <?= $u['ativo']?'selected':'' ?>>Sim</option>
      <option value="0" <?= !$u['ativo']?'selected':'' ?>>Não</option>
    </select>
  </div>

  <div class="col-12">
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-arrow-repeat"></i> Atualizar
    </button>
    <a href="listar_usuarios.php" class="btn btn-secondary">
      <i class="bi bi-x-circle"></i> Cancelar
    </a>
  </div>
</form>

<?php include '../inc_footer.php'; ?>
