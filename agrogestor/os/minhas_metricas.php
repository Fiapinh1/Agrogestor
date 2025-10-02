<?php
// os/minhas_metricas.php — Painel do piloto (usuário comum)
require_once '../auth.php';  requireLogin();
require_once '../conexao.php';
require_once '../utils.php';

$u = user();
$isAdmin  = ( ($u['perfil'] ?? '') === 'admin' );
$colabId  = (int)($u['colaborador_id'] ?? 0);

if (!$colabId) {
  // tenta buscar
  $q = $pdo->prepare("SELECT colaborador_id FROM usuarios WHERE id=:id");
  $q->execute([':id'=>(int)($u['id'] ?? 0)]);
  $colabId = (int)$q->fetchColumn();
}
if ($isAdmin && isset($_GET['piloto_id']) && (int)$_GET['piloto_id']>0) {
  $colabId = (int)$_GET['piloto_id']; // admin pode inspecionar qualquer piloto
}
if (!$colabId) {
  echo "Não encontramos seu vínculo de colaborador."; exit;
}

/* helpers */
if (!function_exists('brToIsoDate')) {
  function brToIsoDate(?string $d) {
    $d = trim((string)$d);
    if ($d==='') return null;
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/',$d)) { [$dd,$mm,$yy]=explode('/',$d); return "$yy-$mm-$dd"; }
    return $d;
  }
}
if (!function_exists('h')) { function h($x){ return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8'); } }

/* filtros básicos (data/status) */
$qDe      = trim($_GET['de'] ?? '');
$qAte     = trim($_GET['ate'] ?? '');
$qStatus  = trim($_GET['status'] ?? '');

$isoDe  = brToIsoDate($qDe);
$isoAte = brToIsoDate($qAte);

$where  = ["o.piloto_id = :pid"];
$params = [':pid'=>$colabId];

if ($isoDe && $isoAte) { $where[]="o.criado_em BETWEEN :de AND :ate"; $params[':de']=$isoDe; $params[':ate']=$isoAte; }
elseif ($isoDe)        { $where[]="o.criado_em >= :de";               $params[':de']=$isoDe; }
elseif ($isoAte)       { $where[]="o.criado_em <= :ate";              $params[':ate']=$isoAte; }

if ($qStatus!=='')     { $where[]="o.status = :st";                   $params[':st']=$qStatus; }

$W = 'WHERE '.implode(' AND ',$where);

/* kpis do piloto */
$sqlK = "SELECT 
  COUNT(*) total_os,
  SUM(o.area_ha) area_total,
  SUM(CASE WHEN o.status='concluido' THEN 1 ELSE 0 END) os_concluidas,
  SUM(CASE WHEN o.status='concluido' THEN o.area_ha ELSE 0 END) area_concluida
FROM os o
$W";
$kp = $pdo->prepare($sqlK); $kp->execute($params);
$kpis = $kp->fetch(PDO::FETCH_ASSOC) ?: ['total_os'=>0,'area_total'=>0,'os_concluidas'=>0,'area_concluida'=>0];

/* últimos trabalhos */
$sqlUlt = "SELECT o.numero_os, COALESCE(c.nome_fantasia,c.razao_social) cliente, o.fazenda, o.area_ha, o.status, o.criado_em
FROM os o
JOIN clientes c ON c.id=o.cliente_id
$W
ORDER BY o.criado_em DESC
LIMIT 15";
$ul = $pdo->prepare($sqlUlt); $ul->execute($params);
$ultimos = $ul->fetchAll(PDO::FETCH_ASSOC);

/* série mensal do piloto */
$sqlM = "SELECT DATE_FORMAT(o.criado_em,'%Y-%m') ym, COUNT(*) os, SUM(o.area_ha) area
FROM os o
$W
GROUP BY ym ORDER BY ym";
$sm = $pdo->prepare($sqlM); $sm->execute($params);
$mensal = $sm->fetchAll(PDO::FETCH_ASSOC);

/* nome do piloto */
$nomePiloto = $pdo->prepare("SELECT nome FROM colaboradores WHERE id=:id");
$nomePiloto->execute([':id'=>$colabId]);
$nome = $nomePiloto->fetchColumn();

$title = 'Meu Rendimento | AgroGestor';
include '../inc_header.php';
?>
<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Meu Rendimento</h2>
  <div class="ms-auto text-muted">Piloto: <strong><?= h($nome ?: '—') ?></strong></div>
</div>

<form method="get" class="row g-2 mb-3">
  <?php if ($isAdmin): ?>
  <input type="hidden" name="piloto_id" value="<?= (int)$colabId ?>">
  <?php endif; ?>
  <div class="col-md-2"><input class="form-control" name="de"  placeholder="De (dd/mm/aaaa)"  value="<?= h($qDe) ?>"></div>
  <div class="col-md-2"><input class="form-control" name="ate" placeholder="Até (dd/mm/aaaa)" value="<?= h($qAte) ?>"></div>
  <div class="col-md-3">
    <select name="status" class="form-select">
      <option value="">— Status —</option>
      <?php foreach (['novo','recebido','planejado','em_execucao','pausado','concluido','cancelado'] as $s): ?>
        <option value="<?= $s ?>" <?= $qStatus===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
    <a href="minhas_metricas.php<?= $isAdmin?('?piloto_id='.$colabId):'' ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Limpar</a>
  </div>
</form>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
    <div class="text-muted small">OS (total)</div>
    <div class="fs-4 fw-bold"><?= (int)$kpis['total_os'] ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
    <div class="text-muted small">Área total (ha)</div>
    <div class="fs-4 fw-bold"><?= number_format((float)$kpis['area_total'],2,',','.') ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
    <div class="text-muted small">OS concluídas</div>
    <div class="fs-4 fw-bold"><?= (int)$kpis['os_concluidas'] ?></div>
  </div></div></div>
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
    <div class="text-muted small">Área concluída (ha)</div>
    <div class="fs-4 fw-bold"><?= number_format((float)$kpis['area_concluida'],2,',','.') ?></div>
  </div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-header">Série mensal (OS × Área)</div>
      <div class="card-body"><canvas id="chartMensal"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header">Últimas OS</div>
      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead><tr><th>Nº</th><th>Cliente</th><th>Fazenda</th><th class="text-end">Área (ha)</th><th>Status</th><th>Data</th></tr></thead>
          <tbody>
            <?php if (!$ultimos): ?>
              <tr><td colspan="6" class="text-muted">Sem registros.</td></tr>
            <?php else: foreach ($ultimos as $r): ?>
              <tr>
                <td><?= h($r['numero_os']) ?></td>
                <td><?= h($r['cliente']) ?></td>
                <td><?= h($r['fazenda']) ?></td>
                <td class="text-end"><?= number_format((float)$r['area_ha'],2,',','.') ?></td>
                <td><?= ucfirst(str_replace('_',' ', $r['status'])) ?></td>
                <td><?= date('d/m/Y', strtotime($r['criado_em'])) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const mensal = <?= json_encode($mensal, JSON_UNESCAPED_UNICODE) ?>;
  const labels = mensal.map(m => m.ym);
  const os     = mensal.map(m => Number(m.os || 0));
  const area   = mensal.map(m => Number(m.area || 0));

  new Chart(document.getElementById('chartMensal'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label:'OS', data:os, yAxisID:'y1' },
        { label:'Área (ha)', data:area, yAxisID:'y2' }
      ]
    },
    options: {
      interaction:{ mode:'index', intersect:false },
      scales:{
        y1:{ type:'linear', position:'left', beginAtZero:true },
        y2:{ type:'linear', position:'right', beginAtZero:true, grid:{ drawOnChartArea:false } }
      }
    }
  });
</script>

<?php include '../inc_footer.php'; ?>
