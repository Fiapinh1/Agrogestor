<?php
require '../auth.php'; requireLogin();
require '../conexao.php'; require '../utils.php';

$q        = trim($_GET['q'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = (int)($_GET['per_page'] ?? 10);
$perPage  = in_array($perPage, [5,10,20,50], true) ? $perPage : 10;
$offset   = ($page - 1) * $perPage;

$where = ''; $params = [];
if ($q !== '') {
  $where = "WHERE (razao_social LIKE :q OR nome_fantasia LIKE :q OR cpf_cnpj LIKE :q OR email LIKE :q OR cidade LIKE :q OR uf LIKE :q)";
  $params[':q'] = "%{$q}%";
}

// total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes $where");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// página
$sql = "SELECT id, tipo_cliente, tipo_pessoa, razao_social, nome_fantasia,
               cpf_cnpj, email, telefone, cidade, uf, status
        FROM clientes
        $where
        ORDER BY razao_social
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k,$v,PDO::PARAM_STR);
$stmt->bindValue(':limit',$perPage,PDO::PARAM_INT);
$stmt->bindValue(':offset',$offset,PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// helper de querystring
function qs(array $extra = []){ $b=$_GET; foreach($extra as $k=>$v){$b[$k]=$v;} return '?'.http_build_query($b); }

$title = 'Clientes | AgroGestor';
include '../inc_header.php';

// >>> Defina $isAdmin uma vez por página <<<
$isAdmin = (user()['perfil'] ?? '') === 'admin';
?>

<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Clientes</h2>
  <form class="ms-auto d-flex gap-2" method="get" action="listar_clientes.php">
    <input type="text" name="q" value="<?= h($q) ?>" class="form-control"
           placeholder="Buscar por nome, fantasia, CNPJ/CPF, e-mail, cidade, UF" style="min-width:300px">
    <select name="per_page" class="form-select" style="width:120px">
      <?php foreach([5,10,20,50] as $n): ?>
        <option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>><?= $n ?>/pág</option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
    <?php if ($q!==''): ?>
      <a class="btn btn-outline-secondary" href="listar_clientes.php"><i class="bi bi-x-circle"></i> Limpar</a>
    <?php endif; ?>
    <?php if ($isAdmin): ?>
      <a href="cadastrar_cliente.php" class="btn btn-success"><i class="bi bi-building-add"></i> Novo</a>
    <?php endif; ?>
    <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Início</a>
  </form>
</div>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Nome/Razão</th>
        <th>Fantasia</th>
        <th>Tipo</th>
        <th>CNPJ/CPF</th>
        <th>Cidade/UF</th>
        <th>Status</th>
        <th class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7" class="text-muted">Nenhum cliente encontrado.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= h($r['razao_social']) ?></td>
          <td><?= h($r['nome_fantasia']) ?></td>
          <td><?= h(ucfirst($r['tipo_cliente'])) ?> (<?= h($r['tipo_pessoa']==='juridica'?'PJ':'PF') ?>)</td>
          <td><?= h($r['cpf_cnpj']) ?></td>
          <td><?= h($r['cidade']) ?>/<?= h($r['uf']) ?></td>
          <td>
            <span class="badge bg-<?= $r['status']==='ativo'?'success':($r['status']==='suspenso'?'warning':'secondary') ?>">
              <?= h($r['status']) ?>
            </span>
          </td>
          <td class="text-end">
            <?php if ($isAdmin): ?>
              <a class="btn btn-warning btn-sm" href="editar_cliente.php?id=<?= (int)$r['id'] ?>">
                <i class="bi bi-pencil-square"></i> Editar
              </a>
              <a class="btn btn-danger btn-sm" href="excluir_cliente.php?id=<?= (int)$r['id'] ?>"
                 onclick="return confirm('Excluir este cliente?');">
                <i class="bi bi-trash"></i> Excluir
              </a>
            <?php else: ?>
              <button class="btn btn-outline-secondary btn-sm ver-cliente" data-id="<?= (int)$r['id'] ?>">
                <i class="bi bi-eye"></i> Ver
              </button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages>1):
  $start=max(1,$page-2); $end=min($totalPages,$page+2); ?>
<nav class="mt-3" aria-label="Paginação">
  <ul class="pagination">
    <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="<?= $page<=1?'#':qs(['page'=>1]) ?>">« Primeiro</a></li>
    <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="<?= $page<=1?'#':qs(['page'=>$page-1]) ?>">‹ Anterior</a></li>
    <?php for($p=$start;$p<=$end;$p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="<?= qs(['page'=>$p]) ?>"><?= $p ?></a></li>
    <?php endfor; ?>
    <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="<?= $page>=$totalPages?'#':qs(['page'=>$page+1]) ?>">Próxima ›</a></li>
    <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="<?= $page>=$totalPages?'#':qs(['page'=>$totalPages]) ?>">Última »</a></li>
  </ul>
  <div class="text-muted small">Mostrando <?= count($rows) ?> de <?= $total ?> cliente(s).</div>
</nav>
<?php endif; ?>

<?php if (!$isAdmin): ?>
<!-- Modal somente para usuários não-admin -->
<div class="modal fade" id="modalVerCli" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <dt class="col-sm-3">Razão social</dt><dd class="col-sm-9" id="cl-razao"></dd>
          <dt class="col-sm-3">Fantasia</dt>    <dd class="col-sm-9" id="cl-fantasia"></dd>
          <dt class="col-sm-3">Tipo</dt>        <dd class="col-sm-9" id="cl-tipo"></dd>
          <dt class="col-sm-3">Documento</dt>   <dd class="col-sm-9" id="cl-doc"></dd>
          <dt class="col-sm-3">Cidade/UF</dt>   <dd class="col-sm-9" id="cl-cidadeuf"></dd>
          <dt class="col-sm-3">Status</dt>      <dd class="col-sm-9" id="cl-status"></dd>
          <dt class="col-sm-3">Contato</dt>     <dd class="col-sm-9" id="cl-contato"></dd>
          <dt class="col-sm-3">E-mail</dt>      <dd class="col-sm-9" id="cl-email"></dd>
          <dt class="col-sm-3">Telefone</dt>    <dd class="col-sm-9" id="cl-telefone"></dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.ver-cliente').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const id = btn.dataset.id;
    try{
      const r = await fetch('ver_cliente.php?id='+id, {headers:{'X-Requested-With':'fetch'}});
      if(!r.ok) throw new Error('Falha ao carregar');
      const d = await r.json();
      const set = (sel, val) => { const el = document.querySelector(sel); if (el) el.textContent = (val ?? '—'); };

      set('#cl-razao',     d.razao_social);
      set('#cl-fantasia',  d.fantasia);
      set('#cl-tipo',      d.tipo_label);
      set('#cl-doc',       d.documento_mask);
      set('#cl-cidadeuf',  (d.cidade??'') + (d.uf?'/'+d.uf:''));
      set('#cl-status',    d.status_label);
      set('#cl-contato',   d.contato);
      set('#cl-email',     d.email);
      set('#cl-telefone',  d.telefone);

      new bootstrap.Modal(document.getElementById('modalVerCli')).show();
    }catch(e){
      alert('Não foi possível abrir os dados.');
    }
  });
});
</script>
<?php endif; ?>

<?php include '../inc_footer.php'; ?>
