<?php
require_once '../auth.php'; requireLogin(); requireRole('admin');
require_once '../conexao.php'; require_once '../utils.php';

$preColabId = isset($_GET['colaborador_id']) ? (int)$_GET['colaborador_id'] : 0;

if ($preColabId > 0) {
  $stmt = $pdo->prepare("SELECT c.id, c.nome, c.email
                           FROM colaboradores c
                      LEFT JOIN usuarios u ON u.colaborador_id = c.id
                          WHERE c.id = :id AND u.id IS NULL");
  $stmt->execute([':id'=>$preColabId]);
  $sel = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$sel) { $preColabId = 0; }
}

$colabs = $pdo->query("
  SELECT c.id, c.nome, c.email
    FROM colaboradores c
    LEFT JOIN usuarios u ON u.colaborador_id = c.id
   WHERE u.id IS NULL
ORDER BY c.nome
")->fetchAll(PDO::FETCH_ASSOC);

$title = 'Criar Usuário a partir de Colaborador | AgroGestor';
include '../inc_header.php';
?>
<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Novo Usuário</h2>
  <div class="ms-auto">
    <a href="listar_usuarios.php" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Voltar
    </a>
  </div>
</div>

<form class="card p-3" method="post" action="salvar_usuario.php">
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Colaborador</label>
      <select name="colaborador_id" class="form-select" required>
        <option value="">— selecione —</option>
        <?php foreach ($colabs as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ($preColabId == $c['id'] ? 'selected' : '') ?>>
            <?= h($c['nome']) ?> <?= $c['email'] ? ' — '.h($c['email']) : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Somente colaboradores que ainda não possuem usuário.</div>
    </div>

    <div class="col-md-4">
      <label class="form-label">Perfil</label>
      <select name="perfil" class="form-select" required>
        <option value="usuario">Usuário (padrão)</option>
        <option value="admin">Administrador</option>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Senha inicial</label>
      <input type="password" name="senha" class="form-control" placeholder="Deixe em branco para gerar">
      <div class="form-text">Você pode definir agora ou deixar que o sistema gere uma senha temporária.</div>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-person-plus"></i> Criar Usuário</button>
    <a href="listar_usuarios.php" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>

<?php include '../inc_footer.php'; ?>
