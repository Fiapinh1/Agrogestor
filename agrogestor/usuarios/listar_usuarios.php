<?php
require '../auth.php';
requireLogin();
requireRole('admin');

require '../conexao.php';
require '../utils.php';

// --- Parâmetros de busca e paginação ---
$q        = trim($_GET['q'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = (int)($_GET['per_page'] ?? 10);
$perPage  = in_array($perPage, [5,10,20,50], true) ? $perPage : 10;
$offset   = ($page - 1) * $perPage;

// WHERE dinâmico
$whereSql = '';
$params   = [];
if ($q !== '') {
  $whereSql = "WHERE (nome LIKE :q OR email LIKE :q OR perfil LIKE :q)";
  $params[':q'] = "%{$q}%";
}

// Total para paginação
$sqlCount = "SELECT COUNT(*) FROM usuarios {$whereSql}";
$stmt = $pdo->prepare($sqlCount);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Lista paginada
$sql = "SELECT id, nome, email, perfil, ativo
        FROM usuarios
        {$whereSql}
        ORDER BY nome
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v, PDO::PARAM_STR); }
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// helper para manter querystring
function qs(array $extra = []) {
  $base = $_GET;
  foreach ($extra as $k=>$v) { $base[$k] = $v; }
  return '?' . http_build_query($base);
}

$title = 'Usuários | AgroGestor';
include '../inc_header.php';
?>

<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Usuários do Sistema</h2>

  <!-- Busca -->
  <form class="ms-auto d-flex gap-2" method="get" action="listar_usuarios.php">
    <input type="text" name="q" class="form-control" placeholder="Buscar por nome, e-mail ou perfil" value="<?= h($q) ?>" style="min-width:280px">
    <select name="per_page" class="form-select" style="width:120px">
      <?php foreach ([5,10,20,50] as $n): ?>
        <option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>><?= $n ?>/pág</option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">
      <i class="bi bi-search"></i> Buscar
    </button>
    <?php if ($q !== ''): ?>
      <a class="btn btn-outline-secondary" href="listar_usuarios.php">
        <i class="bi bi-x-circle"></i> Limpar
      </a>
    <?php endif; ?>
    <a href="cadastrar_usuario.php" class="btn btn-success">
      <i class="bi bi-person-plus"></i> Novo Usuário
    </a>
    <a href="../index.php" class="btn btn-outline-secondary">
      <i class="bi bi-house"></i> Início
    </a>
  </form>
</div>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Perfil</th>
        <th>Ativo</th>
        <th class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5" class="text-muted">Nenhum usuário encontrado.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $u): ?>
          <tr>
            <td><?= h($u['nome']) ?></td>
            <td><?= h($u['email']) ?></td>
            <td>
              <span class="badge bg-<?= $u['perfil']==='admin' ? 'danger' : 'secondary' ?>">
                <?= h($u['perfil']) ?>
              </span>
            </td>
            <td><?= $u['ativo'] ? 'Sim' : 'Não' ?></td>
            <td class="text-end">
              <a href="editar_usuario.php?id=<?= (int)$u['id'] ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil-square"></i>
              </a>
              <a href="excluir_usuario.php?id=<?= (int)$u['id'] ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Excluir este usuário?');">
                <i class="bi bi-trash"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Paginação -->
<?php if ($totalPages > 1): 
  $start = max(1, $page - 2);
  $end   = min($totalPages, $page + 2);
?>
<nav aria-label="Paginação" class="mt-3">
  <ul class="pagination">
    <li class="page-item <?= $page<=1?'disabled':'' ?>">
      <a class="page-link" href="<?= $page<=1 ? '#' : qs(['page'=>1]) ?>">« Primeiro</a>
    </li>
    <li class="page-item <?= $page<=1?'disabled':'' ?>">
      <a class="page-link" href="<?= $page<=1 ? '#' : qs(['page'=>$page-1]) ?>">‹ Anterior</a>
    </li>

    <?php for ($p=$start; $p<=$end; $p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="<?= qs(['page'=>$p]) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>

    <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
      <a class="page-link" href="<?= $page>=$totalPages ? '#' : qs(['page'=>$page+1]) ?>">Próxima ›</a>
    </li>
    <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
      <a class="page-link" href="<?= $page>=$totalPages ? '#' : qs(['page'=>$totalPages]) ?>">Última »</a>
    </li>
  </ul>
  <div class="text-muted small">
    Mostrando <?= count($rows) ?> de <?= $total ?> usuário(s).
  </div>
</nav>
<?php endif; ?>

<?php include '../inc_footer.php'; ?>
