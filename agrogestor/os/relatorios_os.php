<?php
// os/relatorios_os.php  — Painel de Rendimento (ADMIN)
require_once '../auth.php';  requireLogin();
require_once '../conexao.php';
require_once '../utils.php';

$u = user();
$isAdmin = ( ($u['perfil'] ?? '') === 'admin' );
if (!$isAdmin) {
  http_response_code(403);
  echo "Acesso negado.";
  exit;
}

/* Helpers locais sem conflitar com utils.php */
if (!function_exists('brToIsoDate')) {
  function brToIsoDate(?string $d) {
    $d = trim((string)$d);
    if ($d==='') return null;
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/',$d)) { [$dd,$mm,$yy]=explode('/',$d); return "$yy-$mm-$dd"; }
    return $d;
  }
}
if (!function_exists('h')) {
  function h($x){ return htmlspecialchars((string)$x, ENT_QUOTES,'UTF-8'); }
}

/* -------- Filtros -------- */
$qDe      = trim($_GET['de'] ?? '');       // dd/mm/aaaa
$qAte     = trim($_GET['ate'] ?? '');
$qStatus  = trim($_GET['status'] ?? '');
$qCliente = trim($_GET['cliente'] ?? '');
$qPiloto  = (int)($_GET['piloto_id'] ?? 0);

$isoDe  = brToIsoDate($qDe);
$isoAte = brToIsoDate($qAte);

$where  = [];
$params = [];

// Janela por criado_em (mais estável); mude para prazo_final se preferir
if ($isoDe && $isoAte) { $where[]="o.criado_em BETWEEN :de AND :ate"; $params[':de']=$isoDe; $params[':ate']=$isoAte; }
elseif ($isoDe)        { $where[]="o.criado_em >= :de";               $params[':de']=$isoDe; }
elseif ($isoAte)       { $where[]="o.criado_em <= :ate";              $params[':ate']=$isoAte; }

if ($qStatus !== '')   { $where[]="o.status = :st";                   $params[':st']=$qStatus; }
if ($qPiloto > 0)      { $where[]="o.piloto_id = :pid";               $params[':pid']=$qPiloto; }

$joinCliente = '';
if ($qCliente !== '') {
  $joinCliente = "JOIN clientes c ON c.id=o.cliente_id";
  $where[] = "(c.nome_fantasia LIKE :cli OR c.razao_social LIKE :cli)";
  $params[':cli']="%$qCliente%";
}

$W = $where ? ('WHERE '.implode(' AND ',$where)) : '';

/* -------- Listas auxiliares -------- */
$pilotos = $pdo->query("SELECT id, nome FROM colaboradores WHERE is_piloto=1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

/* -------- KPIs gerais -------- */
$sqlKpis = "SELECT 
  COUNT(*) AS total_os,
  SUM(o.area_ha) AS area_total,
  SUM(CASE WHEN o.status='concluido' THEN 1 ELSE 0 END) AS os_concluidas,
  SUM(CASE WHEN o.status='concluido' THEN o.area_ha ELSE 0 END) AS area_concluida
FROM os o
$joinCliente
$W";
$k = $pdo->prepare($sqlKpis); $k->execute($params);
$kpis = $k->fetch(PDO::FETCH_ASSOC) ?: ['total_os'=>0,'area_total'=>0,'os_concluidas'=>0,'area_concluida'=>0];

/* -------- Distribuição de status -------- */
$sqlStatus = "SELECT o.status, COUNT(*) qtd
FROM os o
$joinCliente
$W
GROUP BY o.status";
$stStmt = $pdo->prepare($sqlStatus); $stStmt->execute($params);
$statusRows = $stStmt->fetchAll(PDO::FETCH_ASSOC);

/* -------- Série mensal -------- */
$sqlMensal = "SELECT DATE_FORMAT(o.criado_em,'%Y-%m') ym,
       COUNT(*) os,
       SUM(o.area_ha) area,
       SUM(CASE WHEN o.status='concluido' THEN o.area_ha ELSE 0 END) area_conc
FROM os o
$joinCliente
$W
GROUP BY ym
ORDER BY ym";
$ms = $pdo->prepare($sqlMensal); $ms->execute($params);
$mensal = $ms->fetchAll(PDO::FETCH_ASSOC);

/* -------- Ranking por piloto -------- */
$sqlPilotos = "SELECT 
   col.id AS piloto_id,
   col.nome AS piloto,
   COUNT(*) AS total_os,
   SUM(o.area_ha) AS area_total,
   SUM(CASE WHEN o.status='concluido' THEN 1 ELSE 0 END) AS os_concluidas,
   SUM(CASE WHEN o.status='concluido' THEN o.area_ha ELSE 0 END) AS area_concluida,
   AVG(o.area_ha) AS media_area,
   MAX(o.criado_em) AS ultima_os
FROM os o
LEFT JOIN colaboradores col ON col.id=o.piloto_id
$joinCliente
$W
GROUP BY col.id, col.nome
ORDER BY area_concluida DESC, area_total DESC";
$rp = $pdo->prepare($sqlPilotos); $rp->execute($params);
$ranking = $rp->fetchAll(PDO::FETCH_ASSOC);

$title = 'Relatórios de OS | AgroGestor';
include '../inc_header.php';

$qsExport = $_GET;  // para export CSV
?>
<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Relatórios de OS</h2>
  <div class="ms-auto d-flex gap-2">
    <a href="listar_os.php" class="btn btn-outline-secondary"><i class="bi bi-table"></i> Listagem</a>
    <a href="mapa_os.php" class="btn btn-outline-primary"><i class="bi bi-map"></i> Mapa</a>
  </div>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-md-2">
    <input name="de" class="form-control" placeholder="De (dd/mm/aaaa)" value="<?= h($qDe) ?>">
  </div>
  <div class="col-md-2">
    <input name="ate" class="form-control" placeholder="Até (dd/mm/aaaa)" value="<?= h($qAte) ?>">
  </div>
  <div class="col-md-2">
    <select name="status" class="form-select">
      <option value="">— Status —</option>
      <?php foreach (['novo','recebido','planejado','em_execucao','pausado','concluido','cancelado'] as $s): ?>
        <option value="<?= $s ?>" <?= $qStatus===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <input name="cliente" class="form-control" placeholder="Cliente" value="<?= h($qCliente) ?>">
  </div>
  <div class="col-md-3">
    <select name="piloto_id" class="form-select">
      <option value="0">— Todos os pilotos —</option>
      <?php foreach ($pilotos as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= $qPiloto===(int)$p['id']?'selected':'' ?>><?= h($p['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
    <a href="relatorios_os.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Limpar</a>
    <a class="btn btn-outline-success" href="export_relatorio_pilotos_csv.php?<?= http_build_query($qsExport) ?>">
      <i class="bi bi-filetype-csv"></i> Exportar ranking (CSV)
    </a>
  </div>
</form>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">OS (total)</div>
      <div class="fs-4 fw-bold"><?= (int)$kpis['total_os'] ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Área total (ha)</div>
      <div class="fs-4 fw-bold"><?= number_format((float)$kpis['area_total'],2,',','.') ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">OS concluídas</div>
      <div class="fs-4 fw-bold"><?= (int)$kpis['os_concluidas'] ?></div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm"><div class="card-body">
      <div class="text-muted small">Área concluída (ha)</div>
      <div class="fs-4 fw-bold"><?= number_format((float)$kpis['area_concluida'],2,',','.') ?></div>
    </div></div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-header">Série mensal (OS × Área)</div>
      <div class="card-body"><canvas id="chartMensal"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-header">Distribuição por status</div>
      <div class="card-body"><canvas id="chartStatus"></canvas></div>
    </div>
  </div>
</div>

<div class="card shadow-sm mt-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Ranking por piloto</span>
    <span class="text-muted small">Ordenado por área concluída</span>
  </div>
  <div class="table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead><tr>
        <th>Piloto</th><th class="text-end">OS</th><th class="text-end">OS concl.</th>
        <th class="text-end">Área total (ha)</th><th class="text-end">Área concl. (ha)</th>
        <th class="text-end">Média área (ha)</th><th class="text-end">Última OS</th>
      </tr></thead>
      <tbody>
        <?php if (!$ranking): ?>
          <tr><td colspan="7" class="text-muted">Sem dados para os filtros.</td></tr>
        <?php else: foreach ($ranking as $r): ?>
          <tr>
            <td><?= h($r['piloto'] ?? '—') ?></td>
            <td class="text-end"><?= (int)$r['total_os'] ?></td>
            <td class="text-end"><?= (int)$r['os_concluidas'] ?></td>
            <td class="text-end"><?= number_format((float)$r['area_total'],2,',','.') ?></td>
            <td class="text-end"><?= number_format((float)$r['area_concluida'],2,',','.') ?></td>
            <td class="text-end"><?= number_format((float)$r['media_area'],2,',','.') ?></td>
            <td class="text-end"><?= $r['ultima_os'] ? date('d/m/Y', strtotime($r['ultima_os'])) : '—' ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Série mensal
  const mensal = <?= json_encode($mensal, JSON_UNESCAPED_UNICODE) ?>;
  const labelsMensal = mensal.map(m => m.ym);
  const osMensal     = mensal.map(m => Number(m.os || 0));
  const areaMensal   = mensal.map(m => Number(m.area || 0));

  new Chart(document.getElementById('chartMensal'), {
    type: 'line',
    data: {
      labels: labelsMensal,
      datasets: [
        { label: 'OS', data: osMensal, yAxisID:'y1' },
        { label: 'Área (ha)', data: areaMensal, yAxisID:'y2' }
      ]
    },
    options: {
      interaction: { mode: 'index', intersect: false },
      scales: {
        y1: { type: 'linear', position:'left', beginAtZero:true },
        y2: { type: 'linear', position:'right', beginAtZero:true, grid:{ drawOnChartArea:false } }
      }
    }
  });

  // Distribuição por status
  const statusRows = <?= json_encode($statusRows, JSON_UNESCAPED_UNICODE) ?>;
  new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
      labels: statusRows.map(r => r.status.replaceAll('_',' ')),
      datasets: [{ data: statusRows.map(r => Number(r.qtd || 0)) }]
    }
  });
</script>

<?php include '../inc_footer.php'; ?>
