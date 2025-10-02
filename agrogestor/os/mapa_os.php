<?php
// os/mapa_os.php
require_once '../auth.php';  requireLogin();
require_once '../conexao.php';
require_once '../utils.php';

/* Helpers locais sem colidir com utils.php */
if (!function_exists('brToIsoDate')) {
  function brToIsoDate(?string $d) {
    $d = trim((string)$d);
    if ($d==='') return null;
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/',$d)) { [$dd,$mm,$yy]=explode('/',$d); return "$yy-$mm-$dd"; }
    return $d;
  }
}
if (!function_exists('badge_status')) {
  function badge_status(string $s): string {
    $map = [
      'novo'=>'secondary','recebido'=>'info','planejado'=>'primary',
      'em_execucao'=>'warning','pausado'=>'dark','concluido'=>'success','cancelado'=>'danger'
    ];
    $cls = $map[$s] ?? 'secondary';
    return "<span class='badge bg-$cls'>".ucfirst(str_replace('_',' ',$s))."</span>";
  }
}

/* Perfil / piloto amarrado ao usuário */
$u        = user();
$isAdmin  = ( ($u['perfil'] ?? '') === 'admin' );
$uid      = (int)($u['id'] ?? 0);
$colabId  = (int)($u['colaborador_id'] ?? 0);

if (!$colabId && $uid) {
  $q = $pdo->prepare("SELECT colaborador_id FROM usuarios WHERE id=:id");
  $q->execute([':id'=>$uid]);
  $colabId = (int)$q->fetchColumn();
}

/* -------------------- Filtros -------------------- */
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

/* Regra de visualização */
if ($isAdmin) {
  $where[] = "1=1";
} else {
  // piloto: só as OS dele
  $where[] = "o.piloto_id = :pid";
  $params[':pid'] = $colabId ?: 0;
}

/* produto/insumo */
if ($qCat   !== '') { $where[] = 'o.produto_categoria = :cat';     $params[':cat'] = $qCat; }
if ($qIns   !== '') { $where[] = 'o.insumo_nome LIKE :ins';         $params[':ins'] = "%$qIns%"; }

/* cliente/piloto (texto) */
if ($qCliente !== '') {
  $where[] = "(c.nome_fantasia LIKE :cliente OR c.razao_social LIKE :cliente)";
  $params[':cliente'] = "%$qCliente%";
}
if ($qPiloto !== '' && $isAdmin) { // filtro por piloto só faz sentido p/ admin
  $where[] = "p.nome LIKE :piloto";
  $params[':piloto'] = "%$qPiloto%";
}

/* nº OS / cód. fazenda */
if ($qNumero !== '') { $where[] = "o.numero_os LIKE :numero_os";   $params[':numero_os'] = "%$qNumero%"; }
if ($qCodigo !== '') { $where[] = "o.fazenda_codigo LIKE :fzcod";  $params[':fzcod'] = "%$qCodigo%"; }

/* status */
if ($qStatus !== '') { $where[] = "o.status = :status";            $params[':status'] = $qStatus; }

/* prazo de/até */
$isoDe  = brToIsoDate($qPrazoDe);
$isoAte = brToIsoDate($qPrazoAte);
if ($isoDe && $isoAte) {
  $where[] = "o.prazo_final BETWEEN :d1 AND :d2";
  $params[':d1'] = $isoDe;  $params[':d2'] = $isoAte;
} elseif ($isoDe) {
  $where[] = "o.prazo_final >= :d1";
  $params[':d1'] = $isoDe;
} elseif ($isoAte) {
  $where[] = "o.prazo_final <= :d2";
  $params[':d2'] = $isoAte;
}

/* só com coordenadas válidas */
$where[] = "(o.lat IS NOT NULL AND o.lon IS NOT NULL)";
$wsql = 'WHERE '.implode(' AND ', $where);

/* -------------------- Query -------------------- */
$sql = "SELECT 
          o.id, o.numero_os, o.fazenda, o.fazenda_codigo, o.area_ha, o.objetivo,
          o.produto_categoria, o.insumo_nome, o.status, o.prazo_final,
          o.lat, o.lon, 
          COALESCE(c.nome_fantasia, c.razao_social) AS cliente,
          p.nome AS piloto_nome
        FROM os o
        JOIN clientes c ON c.id = o.cliente_id
        LEFT JOIN colaboradores p ON p.id = o.piloto_id
        $wsql
        ORDER BY o.criado_em DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* dados em JS */
$points = [];
foreach ($rows as $r) {
  $ok = is_numeric($r['lat']) && is_numeric($r['lon']);
  if (!$ok) continue;
  $points[] = [
    'id'        => (int)$r['id'],
    'lat'       => (float)$r['lat'],
    'lon'       => (float)$r['lon'],
    'numero'    => $r['numero_os'],
    'cliente'   => $r['cliente'],
    'fazenda'   => $r['fazenda'],
    'faz_cod'   => $r['fazenda_codigo'],
    'area'      => (float)$r['area_ha'],
    'obj'       => $r['objetivo'],
    'cat'       => $r['produto_categoria'],
    'insumo'    => $r['insumo_nome'],
    'status'    => $r['status'],
    'prazo'     => $r['prazo_final'] ? date('d/m/Y', strtotime($r['prazo_final'])) : null,
    'piloto'    => $r['piloto_nome'] ?? null,
  ];
}

$title = 'Mapa de OS | AgroGestor';
include '../inc_header.php';

/* manter filtros nos links/botões */
$qs = http_build_query($_GET);
$statuses = ['novo','recebido','planejado','em_execucao','pausado','concluido','cancelado'];
?>

<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Mapa de OS</h2>
  <div class="ms-auto d-flex gap-2">
    <a href="listar_os.php?<?= $qs ?>" class="btn btn-outline-secondary"><i class="bi bi-table"></i> Listagem</a>
    <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Início</a>
  </div>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-md-3">
    <select name="produto_categoria" class="form-select">
      <option value="">— Categoria do produto —</option>
      <?php foreach (['herbicida','inseticida','fungicida','fertilizante','maturador'] as $c): ?>
        <option value="<?= $c ?>" <?= $qCat===$c?'selected':'' ?>><?= ucfirst($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4"><input class="form-control" name="insumo" placeholder="Insumo (nome comercial)" value="<?= h($qIns) ?>"></div>
  <div class="col-md-3">
    <select name="status" class="form-select">
      <option value="">— Status —</option>
      <?php foreach ($statuses as $st): ?>
        <option value="<?= $st ?>" <?= $qStatus===$st?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$st)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3"><input class="form-control" name="cliente" placeholder="Cliente" value="<?= h($qCliente) ?>"></div>
  <div class="col-md-3">
    <input class="form-control" name="piloto" placeholder="Piloto" value="<?= h($qPiloto) ?>" <?= $isAdmin?'':'disabled title="Somente admin"' ?>>
    <?php if (!$isAdmin): ?><input type="hidden" name="piloto" value=""><?php endif; ?>
  </div>
  <div class="col-md-2"><input class="form-control" name="numero_os" placeholder="Nº OS" value="<?= h($qNumero) ?>"></div>
  <div class="col-md-2"><input class="form-control" name="fazenda_codigo" placeholder="Cód. Fazenda" value="<?= h($qCodigo) ?>"></div>

  <div class="col-md-2"><input class="form-control" name="prazo_de" placeholder="Prazo de (dd/mm/aaaa)" value="<?= h($qPrazoDe) ?>"></div>
  <div class="col-md-2"><input class="form-control" name="prazo_ate" placeholder="Prazo até (dd/mm/aaaa)" value="<?= h($qPrazoAte) ?>"></div>

  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
    <a class="btn btn-outline-secondary" href="mapa_os.php"><i class="bi bi-x-circle"></i> Limpar</a>
  </div>
</form>

<div id="map" style="height: 72vh; border-radius: .5rem; overflow: hidden;"></div>

<!-- Leaflet (+ MarkerCluster via CDN, se você já tem local pode trocar) -->
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
/>
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
/>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
  // dados da OS com coordenadas válidas
  const osData = <?= json_encode($points, JSON_UNESCAPED_UNICODE) ?>;

  // Basemaps: Satélite (default) e OSM
  const esriSat = L.tileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    { attribution: 'Esri, Maxar — World Imagery' }
  );
  const osm = L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    { attribution: '&copy; OpenStreetMap contributors' }
  );

  // Brasil approx
  const map = L.map('map', { layers: [esriSat] }).setView([-14.235, -51.925], 4);

  L.control.layers(
    { 'Satélite (Esri)': esriSat, 'Mapa (OSM)': osm }, // base layers
    null,
    { collapsed: false }
  ).addTo(map);

  const cluster = L.markerClusterGroup({ disableClusteringAtZoom: 12 });

  // cores para status (círculos simples)
  const statusColors = {
    'novo':'#6c757d', 'recebido':'#0dcaf0', 'planejado':'#0d6efd',
    'em_execucao':'#ffc107', 'pausado':'#343a40', 'concluido':'#198754', 'cancelado':'#dc3545'
  };

  const bounds = [];

  osData.forEach(d => {
    const color = statusColors[d.status] || '#2e7';
    const marker = L.circleMarker([d.lat, d.lon], {
      radius: 7, color: '#000', weight: 1, fillColor: color, fillOpacity: .9
    });

    const objetivoLabel = (function(v){
      if(v==='aplicacao_total') return 'Aplicação (Área Total)';
      if(v==='aplicacao_localizada') return 'Aplicação (Localizada)';
      if(v==='mapeamento') return 'Mapeamento';
      if(v==='cotesia') return 'Cotesia';
      return v || '—';
    })(d.obj);

    let html = `
      <div style="min-width:260px">
        <div class="fw-bold mb-1">OS #${d.numero ?? '—'}</div>
        <div><b>Cliente:</b> ${d.cliente ?? '—'}</div>
        <div><b>Fazenda:</b> ${d.fazenda ?? '—'} <span class="text-muted">${d.faz_cod ? '('+d.faz_cod+')':''}</span></div>
        <div><b>Área:</b> ${d.area?.toLocaleString('pt-BR', {minimumFractionDigits:2}) ?? '—'} ha</div>
        <div><b>Objetivo:</b> ${objetivoLabel}</div>
        <div><b>Produto:</b> ${d.cat ?? '—'} | <b>Insumo:</b> ${d.insumo ?? '—'}</div>
        <div><b>Status:</b> <?= str_replace("\n","", badge_status('__STATUS__')) ?>`.replace('__STATUS__', '${d.status}') + `</div>
        <div><b>Prazo:</b> ${d.prazo ?? '—'} | <b>Piloto:</b> ${d.piloto ?? '—'}</div>
        <?php if ($isAdmin): ?>
          <div class="mt-2"><a class="btn btn-sm btn-outline-primary" href="editar_os.php?id=${d.id}"><i class="bi bi-pencil-square"></i> Editar</a></div>
        <?php endif; ?>
      </div>
    `;

    marker.bindPopup(html);
    cluster.addLayer(marker);
    bounds.push([d.lat, d.lon]);
  });

  map.addLayer(cluster);

  if (bounds.length) {
    map.fitBounds(bounds, { padding: [20,20] });
  } else {
    // sem pontos: mantém visão geral do Brasil
    map.setView([-14.235, -51.925], 4);
  }
</script>

<?php include '../inc_footer.php'; ?>
