<?php
// os/minhas_os.php
require_once '../auth.php';  requireLogin();
require_once '../conexao.php';
require_once '../utils.php';

/* helpers locais, sem colidir com utils.php */
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
    $map=[
      'novo'=>'secondary','recebido'=>'info','planejado'=>'primary',
      'em_execucao'=>'warning','pausado'=>'dark','concluido'=>'success','cancelado'=>'danger'
    ];
    $cls = $map[$s] ?? 'secondary';
    return "<span class='badge bg-$cls'>".ucfirst(str_replace('_',' ',$s))."</span>";
  }
}

/* pega usuário e colaborador vinculado */
$u   = user();
$uid = (int)($u['id'] ?? 0);
$colabId = (int)($u['colaborador_id'] ?? 0);

/* fallback: busca no banco se necessário */
if (!$colabId && $uid) {
  $q = $pdo->prepare("SELECT colaborador_id FROM usuarios WHERE id = :id LIMIT 1");
  $q->execute([':id'=>$uid]);
  $colabId = (int)$q->fetchColumn();
}

/* valida piloto */
$pilotoNome = null; $isPiloto = false;
if ($colabId) {
  $q = $pdo->prepare("SELECT nome, COALESCE(is_piloto,0) AS is_piloto FROM colaboradores WHERE id = :id");
  $q->execute([':id'=>$colabId]);
  if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $pilotoNome = $row['nome'];
    $isPiloto   = ((int)$row['is_piloto'] === 1);
  }
}

$title = 'Minhas OS | AgroGestor';
include '../inc_header.php';

if (!$colabId) {
  echo '<div class="alert alert-warning">Seu usuário não está vinculado a um colaborador. Peça para um administrador vincular sua conta a um colaborador piloto.</div>';
  include '../inc_footer.php'; exit;
}
if (!$isPiloto) {
  echo '<div class="alert alert-warning">Seu colaborador não está marcado como <b>Piloto</b>. Peça para um administrador atualizar o cadastro do colaborador.</div>';
  include '../inc_footer.php'; exit;
}

/* ------------ filtros ------------ */
$qBusca  = trim($_GET['q'] ?? '');
$qStatus = trim($_GET['status'] ?? '');
$qDe     = trim($_GET['prazo_de'] ?? '');
$qAte    = trim($_GET['prazo_ate'] ?? '');
$isoDe   = brToIsoDate($qDe);
$isoAte  = brToIsoDate($qAte);

$where  = ['o.piloto_id = :pid'];
$params = [':pid'=>$colabId];

if ($qBusca !== '') {
  $where[] = "(o.numero_os LIKE :q
           OR o.fazenda LIKE :q
           OR o.fazenda_codigo LIKE :q
           OR c.razao_social LIKE :q
           OR c.nome_fantasia LIKE :q
           OR o.insumo_nome LIKE :q)";
  $params[':q'] = '%'.$qBusca.'%';
}
if ($qStatus !== '') {
  $where[] = "o.status = :status";
  $params[':status'] = $qStatus;
}
if ($isoDe && $isoAte) {
  $where[] = "o.prazo_final BETWEEN :d1 AND :d2";
  $params[':d1'] = $isoDe; $params[':d2'] = $isoAte;
} elseif ($isoDe) {
  $where[] = "o.prazo_final >= :d1";
  $params[':d1'] = $isoDe;
} elseif ($isoAte) {
  $where[] = "o.prazo_final <= :d2";
  $params[':d2'] = $isoAte;
}

$wsql = 'WHERE '.implode(' AND ', $where);

/* lista as OS do piloto */
$sql = "SELECT o.*,
               COALESCE(c.nome_fantasia, c.razao_social) AS cliente
        FROM os o
        JOIN clientes c ON c.id = o.cliente_id
        $wsql
        ORDER BY 
          CASE WHEN o.prazo_final IS NULL THEN 1 ELSE 0 END,
          o.prazo_final ASC,
          o.criado_em DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* botões de status que o piloto pode usar */
$pilotStatuses = [
  'recebido'     => ['label'=>'Recebido',   'btn'=>'btn-outline-info'],
  'em_execucao'  => ['label'=>'Execução',   'btn'=>'btn-outline-warning'],
  'pausado'      => ['label'=>'Pausado',    'btn'=>'btn-outline-secondary'],
  'concluido'    => ['label'=>'Concluído',  'btn'=>'btn-outline-success'],
];

/* querystring (se precisar manter filtros em links) */
$qsStr = http_build_query($_GET);
?>

<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Minhas OS</h2>
  <div class="ms-auto text-muted">
    Piloto: <strong><?= h($pilotoNome ?? '—') ?></strong>
  </div>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-md-5">
    <input class="form-control" name="q" placeholder="Buscar por nº OS, cliente, fazenda, cód. fazenda ou insumo" value="<?= h($qBusca) ?>">
  </div>
  <div class="col-md-3">
    <select name="status" class="form-select">
      <option value="">— Status —</option>
      <?php foreach (['novo','recebido','planejado','em_execucao','pausado','concluido','cancelado'] as $st): ?>
        <option value="<?= $st ?>" <?= $qStatus===$st?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$st)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <input class="form-control" name="prazo_de" placeholder="Prazo de (dd/mm/aaaa)" value="<?= h($qDe) ?>">
  </div>
  <div class="col-md-2">
    <input class="form-control" name="prazo_ate" placeholder="Prazo até (dd/mm/aaaa)" value="<?= h($qAte) ?>">
  </div>
  <div class="col-12 d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
    <a class="btn btn-outline-secondary" href="minhas_os.php"><i class="bi bi-x-circle"></i> Limpar</a>
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
        <th>Coordenada</th>
        <th>Prazo</th>
        <th>Status</th>
        <th class="text-end">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="12" class="text-muted">Você ainda não possui OS designadas (ou nenhum resultado com os filtros informados).</td></tr>
      <?php else: foreach ($rows as $r):
        $id      = (int)$r['id'];
        $prazo   = !empty($r['prazo_final']) ? date('d/m/Y', strtotime($r['prazo_final'])) : '—';
        $atrasada = ($r['status']!=='concluido' && $r['status']!=='cancelado'
                  && !empty($r['prazo_final']) && (new DateTime($r['prazo_final']) < new DateTime('today')));
        $classePrazo = $atrasada ? 'text-danger fw-bold' : 'text-muted';
        $coord   = (isset($r['lat']) && isset($r['lon'])) ? (h($r['lat']).', '.h($r['lon'])) : '—';
        $objetivo = match($r['objetivo']) {
          'aplicacao_total'      => 'Aplicação (Área Total)',
          'aplicacao_localizada' => 'Aplicação (Localizada)',
          'mapeamento'           => 'Mapeamento',
          'cotesia'              => 'Cotesia',
          default                => $r['objetivo']
        };
      ?>
      <tr>
        <td><?= h($r['numero_os']) ?></td>
        <td><?= h($r['cliente']) ?></td>
        <td><?= h($r['fazenda_codigo'] ?: '—') ?></td>
        <td><?= h($r['fazenda'] ?: '—') ?></td>
        <td><?= number_format((float)$r['area_ha'], 2, ',', '.') ?></td>
        <td><?= h($objetivo) ?></td>
        <td><?= ucfirst(h($r['produto_categoria'])) ?></td>
        <td><?= h($r['insumo_nome'] ?: '—') ?></td>
        <td><?= $coord ?></td>
        <td class="<?= $classePrazo ?>"><?= $prazo ?></td>
        <td><?= badge_status($r['status']) ?></td>
        <td class="text-end">
          <!-- Botões rápidos de status -->
          <form method="post" action="mudar_status.php" class="d-inline">
            <input type="hidden" name="os_id" value="<?= $id ?>">
            <div class="btn-group btn-group-sm" role="group" aria-label="Trocar status">
              <?php foreach ($pilotStatuses as $st => $meta):
                $active = ($r['status'] === $st) ? ' active' : '';
              ?>
                <button class="btn <?= $meta['btn'] . $active ?>"
                        name="status" value="<?= $st ?>" title="Mover para <?= h($meta['label']) ?>">
                  <?php if ($st==='em_execucao'): ?><i class="bi bi-play-fill"></i><?php endif; ?>
                  <?php if ($st==='pausado'): ?><i class="bi bi-pause-fill"></i><?php endif; ?>
                  <?php if ($st==='concluido'): ?><i class="bi bi-check2-circle"></i><?php endif; ?>
                  <?php if ($st==='recebido'): ?><i class="bi bi-inbox"></i><?php endif; ?>
                  <?= $meta['label'] ?>
                </button>
              <?php endforeach; ?>
            </div>
          </form>

          <!-- Ver detalhes numa linha colapsável -->
          <button class="btn btn-outline-secondary btn-sm ms-1" data-bs-toggle="collapse" data-bs-target="#det-<?= $id ?>">
            <i class="bi bi-eye"></i> Ver
          </button>
        </td>
      </tr>
      <tr class="collapse" id="det-<?= $id ?>">
        <td colspan="12">
          <div class="row g-2 small">
            <div class="col-md-3"><strong>Criado em:</strong> <?= !empty($r['criado_em']) ? date('d/m/Y H:i', strtotime($r['criado_em'])) : '—' ?></div>
            <div class="col-md-3"><strong>Atualizado em:</strong> <?= !empty($r['atualizado_em']) ? date('d/m/Y H:i', strtotime($r['atualizado_em'])) : '—' ?></div>
            <div class="col-md-3"><strong>Observações:</strong> <?= h($r['observacoes'] ?? '—') ?></div>
            <div class="col-md-3"><strong>Categoria/Objetivo:</strong> <?= ucfirst(h($r['produto_categoria'])) ?> / <?= h($objetivo) ?></div>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<div class="d-flex gap-2">
  <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Início</a>
</div>

<?php include '../inc_footer.php'; ?>
