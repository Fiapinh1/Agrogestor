<?php
// listar.php — COLABORADORES (somente leitura para usuário comum)
require_once 'auth.php';   requireLogin();
require_once 'conexao.php';
require_once 'utils.php';

$isAdmin = (user()['perfil'] ?? '') === 'admin';

// --------- Filtros / Paginação ---------
$q       = trim($_GET['q'] ?? '');
$perPage = (int)($_GET['per_page'] ?? 10);
$perPage = in_array($perPage, [5,10,20,50], true) ? $perPage : 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$where  = '';
$params = [];
if ($q !== '') {
  $where = "WHERE (c.nome LIKE :q OR c.cpf LIKE :q OR c.email LIKE :q OR c.cargo LIKE :q)";
  $params[':q'] = "%{$q}%";
}

// Total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM colaboradores c $where");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Página atual
$sql = "SELECT c.id, c.nome, c.cpf, c.cargo, c.email, c.admissao
        FROM colaboradores c
        $where
        ORDER BY c.nome
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
  $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// helper para query string
function qs(array $extra = []) {
  $b = $_GET;
  foreach ($extra as $k => $v) { $b[$k] = $v; }
  return '?' . http_build_query($b);
}

$title = 'Colaboradores | AgroGestor';
include 'inc_header.php';
?>
<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Colaboradores</h2>

  <form class="ms-auto d-flex gap-2" method="get" action="listar.php">
    <input type="text"
           class="form-control"
           name="q"
           value="<?= h($q) ?>"
           placeholder="Buscar por nome, CPF, e-mail ou cargo"
           style="min-width:300px">

    <select name="per_page" class="form-select" style="width:110px">
      <?php foreach ([5,10,20,50] as $n): ?>
        <option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>>
          <?= $n ?>/pág
        </option>
      <?php endforeach; ?>
    </select>

    <button class="btn btn-primary" type="submit">
      <i class="bi bi-search"></i> Buscar
    </button>

    <?php if ($q !== ''): ?>
      <a class="btn btn-outline-secondary" href="listar.php">
        <i class="bi bi-x-circle"></i> Limpar
      </a>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
      <a href="cadastrar.php" class="btn btn-success">
        <i class="bi bi-person-plus"></i> Novo
      </a>
    <?php endif; ?>

    <a href="index.php" class="btn btn-outline-secondary">
      <i class="bi bi-house"></i> Início
    </a>
  </form>
</div>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Nome</th>
        <th>CPF</th>
        <th>Cargo</th>
        <th>E-mail</th>
        <th>Admissão</th>
        <th class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr>
        <td colspan="6" class="text-muted">Nenhum colaborador encontrado.</td>
      </tr>
    <?php else: foreach ($rows as $r): ?>
      <tr>
        <td><?= h($r['nome']) ?></td>
        <td><?= h($r['cpf']) ?></td>
        <td><?= h($r['cargo']) ?></td>
        <td><?= h($r['email']) ?></td>
        <td><?= h(brDate($r['admissao'])) ?></td>
        <td class="text-end">
          <?php if ($isAdmin): ?>
            <a class="btn btn-warning btn-sm"
               href="editar.php?id=<?= (int)$r['id'] ?>">
              <i class="bi bi-pencil-square"></i> Editar
            </a>
            <a class="btn btn-danger btn-sm"
               href="excluir.php?id=<?= (int)$r['id'] ?>"
               onclick="return confirm('Excluir este colaborador?');">
              <i class="bi bi-trash"></i> Excluir
            </a>
          <?php else: ?>
            <button class="btn btn-outline-secondary btn-sm ver-colab"
                    data-id="<?= (int)$r['id'] ?>">
              <i class="bi bi-eye"></i> Ver
            </button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1):
  $start = max(1, $page - 2);
  $end   = min($totalPages, $page + 2);
?>
<nav class="mt-3" aria-label="Paginação">
  <ul class="pagination">
    <li class="page-item <?= $page<=1?'disabled':'' ?>">
      <a class="page-link" href="<?= $page<=1?'#':qs(['page'=>1]) ?>">« Primeiro</a>
    </li>
    <li class="page-item <?= $page<=1?'disabled':'' ?>">
      <a class="page-link" href="<?= $page<=1?'#':qs(['page'=>$page-1]) ?>">‹ Anterior</a>
    </li>

    <?php for ($p=$start; $p<=$end; $p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="<?= qs(['page'=>$p]) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>

    <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
      <a class="page-link" href="<?= $page>=$totalPages?'#':qs(['page'=>$page+1]) ?>">Próxima ›</a>
    </li>
    <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
      <a class="page-link" href="<?= $page>=$totalPages?'#':qs(['page'=>$totalPages]) ?>">Última »</a>
    </li>
  </ul>
  <div class="text-muted small">
    Mostrando <?= count($rows) ?> de <?= $total ?> colaborador(es).
  </div>
</nav>
<?php endif; ?>

<?php if (!$isAdmin): ?>
<!-- Modal: Ver Colaborador (somente leitura) -->
<div class="modal fade" id="modalVerColab" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Colaborador</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <dt class="col-sm-3">Nome</dt>         <dd class="col-sm-9" id="co-nome">—</dd>
          <dt class="col-sm-3">Cargo</dt>        <dd class="col-sm-9" id="co-cargo">—</dd>
          <dt class="col-sm-3">E-mail</dt>       <dd class="col-sm-9" id="co-email">—</dd>
          <dt class="col-sm-3">Telefone</dt>     <dd class="col-sm-9" id="co-telefone">—</dd>
          <dt class="col-sm-3">Admissão</dt>     <dd class="col-sm-9" id="co-admissao">—</dd>
          <dt class="col-sm-3">Setor/Frente</dt> <dd class="col-sm-9" id="co-setorfrente">—</dd>
          <dt class="col-sm-3">Status</dt>       <dd class="col-sm-9" id="co-status">—</dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modalEl  = document.getElementById('modalVerColab');
  const verModal = new bootstrap.Modal(modalEl);

  modalEl.addEventListener('hidden.bs.modal', () => {
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
  });

  document.querySelectorAll('.ver-colab').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      try {
        const r = await fetch('ver_colaborador.php?id=' + id, {
          headers: { 'X-Requested-With': 'fetch' }
        });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        const d = await r.json();

        const set = (sel, val) => {
          const el = document.querySelector(sel);
          if (el) el.textContent = val ?? '—';
        };

        set('#co-nome',        d.nome);
        set('#co-cargo',       d.cargo);
        set('#co-email',       d.email);
        set('#co-telefone',    d.telefone);
        set('#co-admissao',    d.admissao_label);
        set('#co-setorfrente', d.setor_frente);
        set('#co-status',      d.status_label);

        verModal.show();
      } catch (e) {
        console.error(e);
        alert('Não foi possível abrir os dados.');
      }
    });
  });
});
</script>
<?php endif; ?>

<?php include 'inc_footer.php'; ?>

<!-- Bootstrap 5 (bundle com Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
