<?php
require_once 'auth.php';
requireLogin();

require_once 'conexao.php';
require_once 'utils.php';

$title = 'Início | AgroGestor';
include 'inc_header.php';

/* ----------------------------------------------------
   Identidade do usuário e papel (admin / piloto)
---------------------------------------------------- */
$u        = user() ?? [];
$isAdmin  = (($u['perfil'] ?? '') === 'admin');
$colabId  = (int)($u['colaborador_id'] ?? 0);

// Descobre colaborador_id caso não esteja no payload do usuário
if (!$colabId) {
  try {
    $st = $pdo->prepare("SELECT colaborador_id FROM usuarios WHERE id = :id LIMIT 1");
    $st->execute([':id' => (int)($u['id'] ?? 0)]);
    $colabId = (int)$st->fetchColumn();
  } catch (\Throwable $e) { /* silencioso */ }
}
$isPiloto = $colabId > 0;

/* Helpers locais */
function diasAte(?string $isoDate): ?int {
  if (!$isoDate) return null;
  try {
    $d = new DateTime($isoDate);
    $hoje = new DateTime('today');
    return (int)$hoje->diff($d)->format('%r%a'); // negativo se vencido
  } catch (\Throwable $e) { return null; }
}
function badgeByDias(?int $dias): string {
  if ($dias === null) return 'secondary';
  if ($dias < 0)       return 'danger';
  if ($dias <= 15)     return 'warning';
  if ($dias <= 45)     return 'info';
  return 'secondary';
}
function badge_status(string $s): string {
  $map=[
    'novo'=>'secondary','recebido'=>'info','planejado'=>'primary',
    'em_execucao'=>'warning','pausado'=>'dark','concluido'=>'success','cancelado'=>'danger'
  ];
  $cls = $map[$s] ?? 'secondary';
  return "<span class='badge bg-$cls'>".ucfirst(str_replace('_',' ',$s))."</span>";
}

/* ----------------------------------------------------
   BLOCO: KPIs ADMIN
---------------------------------------------------- */
$totColab = $totColabAtivos = $totClientes = $totUsuarios = 0;
$osTotal = 0; $haTotal = 0.0; $osAtrasadas = 0;
$osPorStatus = [];  // [status => ['cnt'=>x,'ha'=>y]]
$proxPrazos  = [];  // próximas OS
$rankPilotos = [];  // ranking por ha concluídos

if ($isAdmin) {
  try {
    $totColab = (int)$pdo->query("SELECT COUNT(*) FROM colaboradores")->fetchColumn();
    $totColabAtivos = (int)$pdo->query("SELECT COUNT(*) FROM colaboradores WHERE situacao='Ativo'")->fetchColumn();
  } catch (\Throwable $e) {}
  try {
    $totClientes = (int)$pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
  } catch (\Throwable $e) {}
  try {
    $totUsuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
  } catch (\Throwable $e) {}

  try {
    $osTotal = (int)$pdo->query("SELECT COUNT(*) FROM os")->fetchColumn();
    $haTotal = (float)$pdo->query("SELECT COALESCE(SUM(area_ha),0) FROM os")->fetchColumn();

    // por status
    $st = $pdo->query("SELECT status, COUNT(*) cnt, COALESCE(SUM(area_ha),0) ha
                       FROM os GROUP BY status");
    foreach ($st->fetchAll() as $r) {
      $osPorStatus[$r['status']] = ['cnt'=>(int)$r['cnt'], 'ha'=>(float)$r['ha']];
    }

    // atrasadas
    $osAtrasadas = (int)$pdo->query("
      SELECT COUNT(*) FROM os
      WHERE status NOT IN ('concluido','cancelado')
        AND prazo_final IS NOT NULL
        AND prazo_final < CURDATE()
    ")->fetchColumn();

    // próximas 10 por prazo
    $st = $pdo->query("
      SELECT id, numero_os, cliente_id, fazenda, fazenda_codigo, status, prazo_final, area_ha
      FROM os
      WHERE status NOT IN ('concluido','cancelado')
        AND prazo_final IS NOT NULL
      ORDER BY prazo_final ASC
      LIMIT 10
    ");
    $proxPrazos = $st->fetchAll();

    // ranking pilotos (concluídas)
    $st = $pdo->query("
      SELECT p.id, p.nome, COUNT(*) os_cnt, COALESCE(SUM(o.area_ha),0) ha
      FROM os o
      JOIN colaboradores p ON p.id = o.piloto_id
      WHERE o.status = 'concluido'
      GROUP BY p.id, p.nome
      ORDER BY ha DESC, os_cnt DESC
      LIMIT 5
    ");
    $rankPilotos = $st->fetchAll();
  } catch (\Throwable $e) {}
}

/* ----------------------------------------------------
   BLOCO: KPIs PILOTO
---------------------------------------------------- */
$meusContadores = ['total'=>0,'ha_concluida'=>0.0,'os_concluidas'=>0];
$meusPorStatus  = [];  // status => count
$meusPrazos     = [];  // próximas OS do piloto
$meusAlertas    = [];  // dados pessoais para validade

if ($isPiloto) {
  try {
    // total minhas OS
    $st = $pdo->prepare("SELECT COUNT(*) FROM os WHERE piloto_id = :pid");
    $st->execute([':pid'=>$colabId]); $meusContadores['total'] = (int)$st->fetchColumn();

    // área concluída + concluidas
    $st = $pdo->prepare("
      SELECT COALESCE(SUM(area_ha),0) ha, COUNT(*) cnt
      FROM os WHERE piloto_id=:pid AND status='concluido'
    ");
    $st->execute([':pid'=>$colabId]);
    $r = $st->fetch();
    $meusContadores['ha_concluida'] = (float)$r['ha'];
    $meusContadores['os_concluidas'] = (int)$r['cnt'];

    // por status
    $st = $pdo->prepare("
      SELECT status, COUNT(*) c FROM os
      WHERE piloto_id = :pid GROUP BY status
    ");
    $st->execute([':pid'=>$colabId]);
    foreach ($st->fetchAll() as $r) $meusPorStatus[$r['status']] = (int)$r['c'];

    // próximas 8 por prazo
    $st = $pdo->prepare("
      SELECT id, numero_os, cliente_id, fazenda, fazenda_codigo, status, prazo_final, area_ha
      FROM os
      WHERE piloto_id = :pid
        AND status NOT IN ('concluido','cancelado')
        AND prazo_final IS NOT NULL
      ORDER BY prazo_final ASC
      LIMIT 8
    ");
    $st->execute([':pid'=>$colabId]);
    $meusPrazos = $st->fetchAll();

    // meus alertas pessoais (CNH/Cert.)
    $st = $pdo->prepare("
      SELECT nome, cnh, validade_cnh, certificado_piloto, validade_certificado
      FROM colaboradores WHERE id=:id LIMIT 1
    ");
    $st->execute([':id'=>$colabId]);
    $meusAlertas = $st->fetch() ?: [];
  } catch (\Throwable $e) {}
}
?>

<?php if ($isAdmin): ?>
  <!-- =====================  DASHBOARD ADMIN  ===================== -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Colaboradores (Ativos/Total)</div>
            <div class="fs-3 fw-bold"><?= (int)$totColabAtivos ?> / <?= (int)$totColab ?></div>
          </div>
          <a class="btn btn-outline-primary" href="listar.php"><i class="bi bi-people"></i></a>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Clientes</div>
            <div class="fs-3 fw-bold"><?= (int)$totClientes ?></div>
          </div>
          <a class="btn btn-outline-primary" href="clientes/listar_clientes.php"><i class="bi bi-buildings"></i></a>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Usuários</div>
            <div class="fs-3 fw-bold"><?= (int)$totUsuarios ?></div>
          </div>
          <a class="btn btn-outline-primary" href="usuarios/listar_usuarios.php"><i class="bi bi-person-gear"></i></a>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="text-muted small d-flex justify-content-between">
            <span>OS Totais / Área</span>
            <span class="badge bg-danger">Atrasadas: <?= (int)$osAtrasadas ?></span>
          </div>
          <div class="fs-3 fw-bold"><?= (int)$osTotal ?> / <?= number_format($haTotal,2,',','.') ?> ha</div>
          <div class="mt-2 d-flex flex-wrap gap-1">
            <?php foreach (['novo','recebido','planejado','em_execucao','pausado','concluido','cancelado'] as $st): ?>
              <span class="badge bg-secondary">
                <?= ucfirst(str_replace('_',' ',$st)) ?>:
                <?= (int)($osPorStatus[$st]['cnt'] ?? 0) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Próximos prazos -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center mb-2">
        <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Próximos prazos</h5>
        <a href="os/listar_os.php" class="btn btn-sm btn-outline-secondary ms-auto">
          <i class="bi bi-diagram-3"></i> Ver OS
        </a>
      </div>
      <?php if (!$proxPrazos): ?>
        <div class="text-muted">Sem prazos próximos.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Nº OS</th><th>Cód. Faz.</th><th>Fazenda</th><th>Área</th><th>Status</th><th>Prazo</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($proxPrazos as $r):
                $dd = $r['prazo_final'] ?: null; $dias = diasAte($dd); ?>
                <tr>
                  <td><?= h($r['numero_os']) ?></td>
                  <td><?= h($r['fazenda_codigo'] ?: '—') ?></td>
                  <td><?= h($r['fazenda'] ?: '—') ?></td>
                  <td><?= number_format((float)$r['area_ha'],2,',','.') ?> ha</td>
                  <td><?= badge_status($r['status']) ?></td>
                  <td>
                    <?php if ($dd): ?>
                      <span class="badge bg-<?= badgeByDias($dias) ?>">
                        <?= h(brDate($dd)) ?> <?= $dias !== null ? '(' . ($dias<0 ? abs($dias).'d venc.' : $dias.'d') . ')' : '' ?>
                      </span>
                    <?php else: ?> — <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Ranking pilotos -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center mb-2">
        <h5 class="mb-0"><i class="bi bi-trophy"></i> Ranking de pilotos (ha concluídos)</h5>
      </div>
      <?php if (!$rankPilotos): ?>
        <div class="text-muted">Sem dados concluídos ainda.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Piloto</th><th>OS</th><th>Área concluída</th></tr></thead>
            <tbody>
              <?php foreach ($rankPilotos as $r): ?>
                <tr>
                  <td><?= h($r['nome']) ?></td>
                  <td><?= (int)$r['os_cnt'] ?></td>
                  <td><?= number_format((float)$r['ha'],2,',','.') ?> ha</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Alertas de validade (CNH / Cert.) -->
  <?php
  $alertas = [];
  try {
    $sqlAlert = "
      SELECT id, nome, cnh, validade_cnh, certificado_piloto, validade_certificado
      FROM colaboradores
      WHERE
        (validade_cnh IS NOT NULL AND validade_cnh <> '' AND validade_cnh <= DATE_ADD(CURDATE(), INTERVAL 45 DAY))
        OR
        (validade_certificado IS NOT NULL AND validade_certificado <> '' AND validade_certificado <= DATE_ADD(CURDATE(), INTERVAL 45 DAY))
      ORDER BY LEAST(
        IF(validade_cnh IS NULL OR validade_cnh='', '9999-12-31', validade_cnh),
        IF(validade_certificado IS NULL OR validade_certificado='', '9999-12-31', validade_certificado)
      ) ASC
      LIMIT 50
    ";
    $alertas = $pdo->query($sqlAlert)->fetchAll();
  } catch (\Throwable $e) { $alertas = []; }
  ?>
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center mb-2">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Alertas de Vencimento (CNH e Cert. Piloto)</h5>
        <span class="ms-2 badge bg-secondary"><?= count($alertas) ?></span>
        <a href="listar.php" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-people"></i> Ver colaboradores</a>
      </div>
      <?php if (!$alertas): ?>
        <div class="text-muted">Nenhum vencimento nos próximos 45 dias.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Colaborador</th><th>CNH</th><th>Validade CNH</th><th>Cert. Piloto</th><th>Validade Cert.</th></tr></thead>
            <tbody>
              <?php foreach ($alertas as $r):
                $dCNH  = $r['validade_cnh'] ?: null;
                $dCert = $r['validade_certificado'] ?: null;
                $diasCNH  = diasAte($dCNH);
                $diasCert = diasAte($dCert);
              ?>
              <tr>
                <td><?= h($r['nome']) ?></td>
                <td><?= h($r['cnh'] ?: '—') ?></td>
                <td>
                  <?php if ($dCNH): ?>
                    <span class="badge bg-<?= badgeByDias($diasCNH) ?>">
                      <?= h(brDate($dCNH)) ?> <?= $diasCNH !== null ? '(' . ($diasCNH<0 ? abs($diasCNH).'d venc.' : $diasCNH.'d') . ')' : '' ?>
                    </span>
                  <?php else: ?> — <?php endif; ?>
                </td>
                <td><?= h($r['certificado_piloto'] ?: '—') ?></td>
                <td>
                  <?php if ($dCert): ?>
                    <span class="badge bg-<?= badgeByDias($diasCert) ?>">
                      <?= h(brDate($dCert)) ?> <?= $diasCert !== null ? '(' . ($diasCert<0 ? abs($diasCert).'d venc.' : $diasCert.'d') . ')' : '' ?>
                    </span>
                  <?php else: ?> — <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-4">
      <a href="cadastrar.php" class="btn btn-success w-100 py-3">
        <i class="bi bi-person-plus"></i><br> Novo Colaborador
      </a>
    </div>
    <div class="col-md-4">
      <a href="clientes/cadastrar_cliente.php" class="btn btn-outline-success w-100 py-3">
        <i class="bi bi-building-add"></i><br> Novo Cliente
      </a>
    </div>
    <div class="col-md-4">
      <a href="os/cadastrar_os.php" class="btn btn-primary w-100 py-3">
        <i class="bi bi-plus-circle"></i><br> Nova OS
      </a>
    </div>
  </div>

<?php else: ?>
  <!-- =====================  DASHBOARD PILOTO  ===================== -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Minhas OS</div>
            <div class="fs-3 fw-bold"><?= (int)$meusContadores['total'] ?></div>
          </div>
          <a class="btn btn-outline-primary" href="os/minhas_os.php"><i class="bi bi-clipboard-check"></i></a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Área concluída</div>
            <div class="fs-3 fw-bold"><?= number_format($meusContadores['ha_concluida'],2,',','.') ?> ha</div>
          </div>
          <a class="btn btn-outline-primary" href="os/minhas_metricas.php"><i class="bi bi-speedometer2"></i></a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">OS concluídas</div>
            <div class="fs-3 fw-bold"><?= (int)$meusContadores['os_concluidas'] ?></div>
          </div>
          <a class="btn btn-outline-primary" href="os/minhas_os.php?status=concluido"><i class="bi bi-check2-circle"></i></a>
        </div>
      </div>
    </div>
  </div>

  <!-- Minhas por status -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <h5 class="mb-2"><i class="bi bi-bar-chart"></i> Minhas OS por status</h5>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach (['novo','recebido','planejado','em_execucao','pausado','concluido','cancelado'] as $st): ?>
          <span class="badge bg-secondary">
            <?= ucfirst(str_replace('_',' ',$st)) ?>:
            <?= (int)($meusPorStatus[$st] ?? 0) ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Meus próximos prazos -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center mb-2">
        <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Meus próximos prazos</h5>
        <a href="os/minhas_os.php" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-clipboard-check"></i> Ver todas</a>
      </div>
      <?php if (!$meusPrazos): ?>
        <div class="text-muted">Sem prazos próximos.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr><th>Nº OS</th><th>Cód. Faz.</th><th>Fazenda</th><th>Área</th><th>Status</th><th>Prazo</th></tr></thead>
            <tbody>
              <?php foreach ($meusPrazos as $r):
                $dd = $r['prazo_final'] ?: null; $dias = diasAte($dd); ?>
                <tr>
                  <td><?= h($r['numero_os']) ?></td>
                  <td><?= h($r['fazenda_codigo'] ?: '—') ?></td>
                  <td><?= h($r['fazenda'] ?: '—') ?></td>
                  <td><?= number_format((float)$r['area_ha'],2,',','.') ?> ha</td>
                  <td><?= badge_status($r['status']) ?></td>
                  <td>
                    <?php if ($dd): ?>
                      <span class="badge bg-<?= badgeByDias($dias) ?>">
                        <?= h(brDate($dd)) ?> <?= $dias !== null ? '(' . ($dias<0 ? abs($dias).'d venc.' : $dias.'d') . ')' : '' ?>
                      </span>
                    <?php else: ?> — <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Meus alertas pessoais (CNH/Cert.) -->
  <?php if ($meusAlertas): ?>
    <?php
      $dCNH  = $meusAlertas['validade_cnh'] ?: null;
      $dCert = $meusAlertas['validade_certificado'] ?: null;
      $diasCNH  = diasAte($dCNH);
      $diasCert = diasAte($dCert);
    ?>
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h5 class="mb-2"><i class="bi bi-shield-check"></i> Meus documentos</h5>
        <div class="row row-cols-1 row-cols-md-2 g-3">
          <div class="col">
            <div class="border rounded p-3">
              <div class="text-muted small">CNH</div>
              <div class="fw-semibold"><?= h($meusAlertas['cnh'] ?: '—') ?></div>
              <div class="mt-1">
                <?php if ($dCNH): ?>
                  <span class="badge bg-<?= badgeByDias($diasCNH) ?>">
                    <?= h(brDate($dCNH)) ?> <?= $diasCNH !== null ? '(' . ($diasCNH<0 ? abs($diasCNH).'d venc.' : $diasCNH.'d') . ')' : '' ?>
                  </span>
                <?php else: ?> <span class="text-muted">Sem data</span> <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="border rounded p-3">
              <div class="text-muted small">Certificado de Piloto</div>
              <div class="fw-semibold"><?= h($meusAlertas['certificado_piloto'] ?: '—') ?></div>
              <div class="mt-1">
                <?php if ($dCert): ?>
                  <span class="badge bg-<?= badgeByDias($diasCert) ?>">
                    <?= h(brDate($dCert)) ?> <?= $diasCert !== null ? '(' . ($diasCert<0 ? abs($diasCert).'d venc.' : $diasCert.'d') . ')' : '' ?>
                  </span>
                <?php else: ?> <span class="text-muted">Sem data</span> <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-md-6">
      <a href="os/minhas_os.php" class="btn btn-primary w-100 py-3">
        <i class="bi bi-clipboard-check"></i><br> Minhas OS
      </a>
    </div>
    <div class="col-md-6">
      <a href="os/minhas_metricas.php" class="btn btn-outline-primary w-100 py-3">
        <i class="bi bi-speedometer2"></i><br> Meu rendimento
      </a>
    </div>
  </div>
<?php endif; ?>

<?php include 'inc_footer.php'; ?>
