<?php
require_once '../auth.php'; requireLogin();
require_once '../conexao.php'; require_once '../utils.php';

/* Helpers locais (se não existirem no utils.php) */
if (!function_exists('brToIsoDate')) {
  function brToIsoDate(string $d=null) {
    $d = trim((string)$d);
    if ($d === '') return null;
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/',$d)) { [$dd,$mm,$yy]=explode('/',$d); return "$yy-$mm-$dd"; }
    return $d;
  }
}
if (!function_exists('brDate')) {
  function brDate(?string $iso): string {
    if (!$iso) return '—';
    $t = strtotime($iso);
    return $t ? date('d/m/Y', $t) : '—';
  }
}

/* -------------------- Filtros -------------------- */
$limit   = max(5, (int)($_GET['limit'] ?? 20));
$offset  = max(0, (int)($_GET['offset'] ?? 0));

$qCat      = trim($_GET['produto_categoria'] ?? '');
$qIns      = trim($_GET['insumo'] ?? '');
$qCliente  = trim($_GET['cliente'] ?? '');
$qPiloto   = trim($_GET['piloto'] ?? '');
$qNumero   = trim($_GET['numero_os'] ?? '');
$qCodigo   = trim($_GET['fazenda_codigo'] ?? '');
$qStatus   = trim($_GET['status'] ?? '');
$qPrazoDe  = trim($_GET['prazo_de'] ?? '');
$qPrazoAte = trim($_GET['prazo_ate'] ?? '');

$where  = [];
$params = [];

/* produto/insumo */
if ($qCat !== '') { $where[] = 'o.produto_categoria = :cat';      $params[':cat'] = $qCat; }
if ($qIns !== '') { $where[] = 'o.insumo_nome LIKE :ins';          $params[':ins'] = "%$qIns%"; }

/* cliente */
if ($qCliente !== '') {
  $where[] = "(c.nome_fantasia LIKE :cliente1 OR c.razao_social LIKE :cliente2)";
  $params[':cliente1'] = "%$qCliente%";
  $params[':cliente2'] = "%$qCliente%";
}

/* piloto (texto) */
if ($qPiloto !== '') { $where[] = "p.nome LIKE :piloto";           $params[':piloto'] = "%$qPiloto%"; }

/* nº OS e código fazenda */
if ($qNumero !== '') { $where[] = "o.numero_os LIKE :numero_os";   $params[':numero_os'] = "%$qNumero%"; }
if ($qCodigo !== '') { $where[] = "o.fazenda_codigo LIKE :fzcod";  $params[':fzcod'] = "%$qCodigo%"; }

/* status */
if ($qStatus !== '') { $where[] = "o.status = :status";            $params[':status'] = $qStatus; }

/* prazo (de / até) */
$isoDe  = brToIsoDate($qPrazoDe);
$isoAte = brToIsoDate($qPrazoAte);
if ($isoDe && $isoAte) {
  $where[] = "o.prazo_final BETWEEN :prz_de AND :prz_ate";
  $params[':prz_de'] = $isoDe;  $params[':prz_ate'] = $isoAte;
} elseif ($isoDe) {
  $where[] = "o.prazo_final >= :prz_de";
  $params[':prz_de'] = $isoDe;
} elseif ($isoAte) {
  $where[] = "o.prazo_final <= :prz_ate";
  $params[':prz_ate'] = $isoAte;
}

$wsql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

/* -------------------- Total -------------------- */
$countSql = "SELECT COUNT(*)
             FROM os o
             JOIN clientes c ON c.id = o.cliente_id
             LEFT JOIN colaboradores p ON p.id = o.piloto_id
             $wsql";
$stmtCnt = $pdo->prepare($countSql);
$stmtCnt->execute($params);
$total = (int)$stmtCnt->fetchColumn();

/* -------------------- Dados -------------------- */
$sql = "SELECT o.*,
               COALESCE(c.nome_fantasia, c.razao_social) AS cliente,
               o.fazenda_codigo,
               p.nome AS piloto_nome
        FROM os o
        JOIN clientes c ON c.id = o.cliente_id
        LEFT JOIN colaboradores p ON p.id = o.piloto_id
        $wsql
        ORDER BY o.criado_em DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $k=>$v) {
  $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit',  (int)$limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stmt->execute(); // ✅ CORRETO: execute SEM array!

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------- UI -------------------- */
$title = 'Ordens de Serviço | AgroGestor';
include '../inc_header.php';

$statuses = ['novo','recebido','planejado','em_execucao','pausado','concluido','cancelado'];

/* QS para export/mapa (sem paginação) */
$qsExport = $_GET; unset($qsExport['offset'], $qsExport['limit']);
$qsString = http_build_query($qsExport);

/* paginação */
$currentPage = (int)floor($offset / $limit) + 1;
$totalPages  = max(1, (int)ceil($total / $limit));

function badge_status(string $s): string {
  $map=[
    'novo'=>'secondary','recebido'=>'info','planejado'=>'primary',
    'em_execucao'=>'warning','pausado'=>'dark','concluido'=>'success','cancelado'=>'danger'
  ];
  $cls = $map[$s] ?? 'secondary';
  return "<span class='badge bg-$cls status-badge' data-status='".htmlspecialchars($s,ENT_QUOTES)."'>"
       . ucfirst(str_replace('_',' ',$s))
       . "</span>";
}
function label_objetivo(?string $o): string {
  $o = (string)$o;
  $map = [
    'aplicacao_total'      => 'Aplicação (Área Total)',
    'aplicacao_localizada' => 'Aplicação (Localizada)',
    'mapeamento'           => 'Mapeamento',
    'cotesia'              => 'Cotesia',
  ];
  return $map[$o] ?? $o;
}
?>
<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Ordens de Serviço</h2>
  <div class="ms-auto d-flex gap-2">
    <a href="mapa_os.php?<?= $qsString ?>" class="btn btn-outline-primary">
      <i class="bi bi-map"></i> Mapa
    </a>
    <?php if ((user()['perfil'] ?? '') === 'admin'): ?>
      <a href="cadastrar_os.php" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Nova OS
      </a>
    <?php endif; ?>
    <a href="../index.php" class="btn btn-outline-secondary">
      <i class="bi bi-house"></i> Início
    </a>
  </div>
</div>

<form class="row g-2 mb-3" method="get">
  <!-- Linha 1 -->
  <div class="col-md-3">
    <select name="produto_categoria" class="form-select">
      <option value="">— Categoria do produto —</option>
      <?php foreach (['herbicida','inseticida','fungicida','fertilizante','maturador'] as $c): ?>
        <option value="<?= $c ?>" <?= $qCat===$c?'selected':'' ?>><?= ucfirst($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4">
    <input class="form-control" name="insumo" placeholder="Insumo (nome comercial)" value="<?= h($qIns) ?>">
  </div>
  <div class="col-md-3">
    <select name="status" class="form-select">
      <option value="">— Status —</option>
      <?php foreach ($statuses as $st): ?>
        <option value="<?= $st ?>" <?= $qStatus===$st?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$st)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Linha 2 -->
  <div class="col-md-3">
    <input class="form-control" name="cliente" placeholder="Cliente" value="<?= h($qCliente) ?>">
  </div>
  <div class="col-md-3">
    <input class="form-control" name="piloto" placeholder="Piloto" value="<?= h($qPiloto) ?>">
  </div>
  <div class="col-md-2">
    <input class="form-control" name="numero_os" placeholder="Nº OS" value="<?= h($qNumero) ?>">
  </div>
  <div class="col-md-2">
    <input class="form-control" name="fazenda_codigo" placeholder="Cód. Fazenda" value="<?= h($qCodigo) ?>">
  </div>

  <!-- Linha 3 -->
  <div class="col-md-2">
    <input class="form-control" name="prazo_de" placeholder="Prazo de (dd/mm/aaaa)" value="<?= h($qPrazoDe) ?>">
  </div>
  <div class="col-md-2">
    <input class="form-control" name="prazo_ate" placeholder="Prazo até (dd/mm/aaaa)" value="<?= h($qPrazoAte) ?>">
  </div>

  <div class="col-md-4 d-flex align-items-center gap-2">
    <button class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
    <a class="btn btn-outline-secondary" href="listar_os.php"><i class="bi bi-x-circle"></i> Limpar</a>
  </div>

  <div class="col-md-4 d-flex align-items-center justify-content-end gap-2">
    <select class="form-select" style="max-width: 150px"
            onchange="location.href='?<?= htmlspecialchars($qsString,ENT_QUOTES) ?>&limit='+this.value">
      <?php foreach ([10,20,50,100] as $n): ?>
        <option value="<?= $n ?>" <?= $limit===$n?'selected':'' ?>><?= $n ?> / pág</option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Nº OS</th>
        <th>Cliente</th>
        <th>Cód. Faz.</th>
        <th>Fazenda</th>
        <th>Área (ha)</th>
        <th>Objetivo</th>
        <th>Produto</th>
        <th>Insumo</th>
        <th>Piloto</th>
        <th>Coordenada</th>
        <th>Status</th>
        <th>Prazo</th>
        <th class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="13" class="text-muted">Nenhuma OS encontrada.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <?php
          $isLate = ($r['status']!=='concluido' && $r['status']!=='cancelado'
                    && !empty($r['prazo_final']) && (new DateTime($r['prazo_final']) < new DateTime('today')));
          $clsPrazo = $isLate ? 'text-danger fw-bold' : 'text-muted';
        ?>
        <tr data-row-id="<?= (int)$r['id'] ?>">
          <td><?= h($r['numero_os']) ?></td>
          <td><?= h($r['cliente']) ?></td>
          <td><?= h($r['fazenda_codigo'] ?: '—') ?></td>
          <td><?= h($r['fazenda'] ?: '—') ?></td>
          <td><?= number_format((float)$r['area_ha'],2,',','.') ?></td>
          <td><?= h(label_objetivo($r['objetivo'])) ?></td>
          <td><?= ucfirst(h($r['produto_categoria'])) ?></td>
          <td><?= h($r['insumo_nome'] ?: '—') ?></td>
          <td><?= h($r['piloto_nome'] ?: '—') ?></td>
          <td><?= (isset($r['lat']) && isset($r['lon'])) ? h($r['lat']).', '.h($r['lon']) : '—' ?></td>
          <td class="td-status"><?= badge_status($r['status']) ?></td>
          <td class="<?= $clsPrazo ?>"><?= brDate($r['prazo_final']) ?></td>
          <td class="text-end">

            <?php if ((user()['perfil'] ?? '') === 'admin'): ?>
              <a class="btn btn-sm btn-outline-primary" href="editar_os.php?id=<?= (int)$r['id'] ?>" title="Editar">
                <i class="bi bi-pencil-square"></i>
              </a>
              <a class="btn btn-sm btn-outline-danger" href="excluir_os.php?id=<?= (int)$r['id'] ?>"
                 onclick="return confirm('Excluir esta OS?');" title="Excluir">
                <i class="bi bi-trash"></i>
              </a>
            <?php else: ?>
              <span class="text-muted small me-2">Somente leitura</span>
            <?php endif; ?>

            <!-- Botão moderno de Status (dropdown + AJAX) -->
            <div class="btn-group">
              <button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Alterar status">
                <i class="bi bi-kanban"></i> Status
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <?php
                  $icons = [
                    'novo'         => 'bi-asterisk',
                    'recebido'     => 'bi-inbox',
                    'planejado'    => 'bi-calendar-event',
                    'em_execucao'  => 'bi-lightning-charge',
                    'pausado'      => 'bi-pause-circle',
                    'concluido'    => 'bi-check2-circle',
                    'cancelado'    => 'bi-x-circle'
                  ];
                  $colors = [
                    'novo'         => 'secondary',
                    'recebido'     => 'info',
                    'planejado'    => 'primary',
                    'em_execucao'  => 'warning',
                    'pausado'      => 'dark',
                    'concluido'    => 'success',
                    'cancelado'    => 'danger'
                  ];
                  foreach ($statuses as $st):
                ?>
                  <li>
                    <a href="#" class="dropdown-item status-item"
                       data-id="<?= (int)$r['id'] ?>"
                       data-status="<?= $st ?>">
                      <i class="bi <?= $icons[$st] ?>"></i>
                      <span class="badge bg-<?= $colors[$st] ?> ms-2">
                        <?= ucfirst(str_replace('_',' ',$st)) ?>
                      </span>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>

          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php
  if ($total > $limit):
    function pageUrl($page, $limit, $qsExport) {
      $qs = $qsExport;
      $qs['limit']  = $limit;
      $qs['offset'] = max(0, ($page-1) * $limit);
      return '?' . http_build_query($qs);
    }
    $totalPages  = max(1, (int)ceil($total / $limit));
    $currentPage = (int)floor($offset / $limit) + 1;
?>
<nav class="mt-3" aria-label="Paginação">
  <ul class="pagination">
    <li class="page-item <?= $currentPage<=1?'disabled':'' ?>">
      <a class="page-link" href="<?= $currentPage<=1 ? '#' : pageUrl(1,$limit,$qsExport) ?>">« Primeiro</a>
    </li>
    <li class="page-item <?= $currentPage<=1?'disabled':'' ?>">
      <a class="page-link" href="<?= $currentPage<=1 ? '#' : pageUrl($currentPage-1,$limit,$qsExport) ?>">‹ Anterior</a>
    </li>
    <?php
      $start = max(1, $currentPage-2);
      $end   = min($totalPages, $currentPage+2);
      for ($p=$start; $p<=$end; $p++):
    ?>
      <li class="page-item <?= $p===$currentPage?'active':'' ?>">
        <a class="page-link" href="<?= pageUrl($p,$limit,$qsExport) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
    <li class="page-item <?= $currentPage>=$totalPages?'disabled':'' ?>">
      <a class="page-link" href="<?= $currentPage>=$totalPages ? '#' : pageUrl($currentPage+1,$limit,$qsExport) ?>">Próxima ›</a>
    </li>
    <li class="page-item <?= $currentPage>=$totalPages?'disabled':'' ?>">
      <a class="page-link" href="<?= $currentPage>=$totalPages ? '#' : pageUrl($totalPages,$limit,$qsExport) ?>">Última »</a>
    </li>
  </ul>
  <div class="text-muted small">
    Mostrando <strong><?= min($limit, $total - $offset) ?></strong> de <strong><?= $total ?></strong> OS
    (pág. <?= $currentPage ?>/<?= $totalPages ?>)
  </div>
</nav>
<?php endif; ?>

<div class="mt-3 d-flex gap-2">
  <a href="export_os_csv.php?<?= $qsString ?>" class="btn btn-outline-success">
    <i class="bi bi-filetype-csv"></i> Exportar CSV
  </a>
  <a href="export_os_kml.php?<?= $qsString ?>" class="btn btn-outline-secondary">
    <i class="bi bi-file-earmark-arrow-down"></i> Exportar KML
  </a>
</div>

<?php include '../inc_footer.php'; ?>

<script>
// Troca de status por AJAX (com fallback)
document.querySelectorAll('.status-item').forEach(el => {
  el.addEventListener('click', async (ev) => {
    ev.preventDefault();
    const id = el.dataset.id;
    const novo = el.dataset.status;

    if (novo === 'cancelado' && !confirm('Confirmar cancelamento desta OS?')) return;

    const fd = new FormData();
    fd.append('os_id', id);
    fd.append('status', novo);

    try {
      const resp = await fetch('mudar_status.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' } });
      let data = null; try { data = await resp.json(); } catch(_e){}
      if (!resp.ok) throw new Error('HTTP ' + resp.status);

      if (data && data.ok) {
        const tr = el.closest('tr');
        const tdStatus = tr.querySelector('.td-status');
        if (tdStatus) tdStatus.innerHTML = renderStatusBadge(data.status || novo);
      } else {
        location.reload();
      }
    } catch (e) {
      console.error(e);
      alert('Não foi possível alterar o status agora.');
    }
  });
});

function renderStatusBadge(status) {
  const map = { novo:'secondary', recebido:'info', planejado:'primary', em_execucao:'warning', pausado:'dark', concluido:'success', cancelado:'danger' };
  const cls = map[status] || 'secondary';
  const label = (status || '').replaceAll('_',' ');
  return `<span class="badge bg-${cls} status-badge" data-status="${status}">${ucFirst(label)}</span>`;
}
function ucFirst(s){ return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }
</script>
