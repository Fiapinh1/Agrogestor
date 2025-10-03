<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'AgroGestor') ?></title>

<?php
/* ---------------- Sessão / usuário ---------------- */
require_once __DIR__ . '/auth.php';

$path = $_SERVER['PHP_SELF'] ?? '';
$ROOT = (str_contains($path, '/usuarios/')
      || str_contains($path, '/clientes/')
      || str_contains($path, '/os/'))
      ? '../' : '';

/* helpers */
if (!function_exists('nav_active')) {
  function nav_active(string $needle): string {
    $p = $_SERVER['SCRIPT_NAME'] ?? '';
    return (strpos($p, $needle) !== false) ? 'active' : '';
  }
}
?>

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- CSS do projeto -->
  <link rel="stylesheet" href="<?= $ROOT ?>assets/css/custom.css">

  <style>
    /* Aparência do item ativo no menu lateral */
    .ag-sidebar .list-group-item.active {
      background: #0d6efd;
      border-color: #0d6efd;
      color: #fff;
    }
    .ag-sidebar .list-group-item i { width: 1.25rem; }
    /* Botão flutuante (admin) */
    .ag-fab {
      position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 1040;
    }
  </style>
</head>
<body class="bg-light">

<?php
/* ----------------- Quem é o usuário ----------------- */
$u       = user() ?? [];
$isAdmin = (($u['perfil'] ?? '') === 'admin');
$nome    = $u['nome'] ?? 'Usuário';
$perfil  = $u['perfil'] ?? 'usuario';

/* tenta descobrir se é piloto */
$colaboradorId = (int)($u['colaborador_id'] ?? 0);
if (!$colaboradorId) {
  try {
    require_once __DIR__ . '/conexao.php';
    $st = $pdo->prepare("SELECT colaborador_id FROM usuarios WHERE id = :id LIMIT 1");
    $st->execute([':id'=>(int)($u['id'] ?? 0)]);
    $colaboradorId = (int)$st->fetchColumn();
  } catch (\Throwable $e) {}
}
$isPiloto = $colaboradorId > 0;
?>

<!-- ============ TOPBAR mínima ============ -->
<nav class="navbar bg-white border-bottom sticky-top">
  <div class="container d-flex align-items-center">
    <!-- menu (offcanvas) -->
    <button class="btn btn-outline-secondary me-2" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar">
      <i class="bi bi-list"></i>
    </button>

    <!-- logo -->
    <a class="navbar-brand fw-semibold me-auto" href="<?= $ROOT ?>index.php">
      <i class="bi bi-hexagon"></i> AgroGestor
    </a>

    <!-- usuário -->
    <div class="dropdown">
      <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($nome) ?>
        <span class="text-muted small">(<?= htmlspecialchars($perfil) ?>)</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <?php if ($isPiloto): ?>
          <li><a class="dropdown-item" href="<?= $ROOT ?>os/minhas_os.php"><i class="bi bi-clipboard-check me-2"></i>Minhas OS</a></li>
          <li><a class="dropdown-item" href="<?= $ROOT ?>os/minhas_metricas.php"><i class="bi bi-speedometer2 me-2"></i>Meu rendimento</a></li>
          <li><hr class="dropdown-divider"></li>
        <?php endif; ?>
        <li><a class="dropdown-item" href="<?= $ROOT ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- ============ SIDEBAR Offcanvas ============ -->
<div class="offcanvas offcanvas-start ag-sidebar" tabindex="-1" id="appSidebar" aria-labelledby="appSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="appSidebarLabel"><i class="bi bi-hexagon"></i> AgroGestor</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div class="px-3 pb-2 text-muted small">Navegação</div>

    <div class="list-group list-group-flush">
      <a class="list-group-item list-group-item-action <?= nav_active('/index.php') ?>"
         href="<?= $ROOT ?>index.php">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
      </a>

      <!-- Cadastros / Gestão -->
      <?php if ($isAdmin): ?>
        <div class="px-3 pt-3 text-muted small">Cadastros</div>
        <a class="list-group-item list-group-item-action <?= nav_active('/listar.php') ?>"
           href="<?= $ROOT ?>listar.php"><i class="bi bi-people me-2"></i> Colaboradores</a>
        <a class="list-group-item list-group-item-action <?= nav_active('/clientes/') ?>"
           href="<?= $ROOT ?>clientes/listar_clientes.php"><i class="bi bi-buildings me-2"></i> Clientes</a>
        <a class="list-group-item list-group-item-action <?= nav_active('/usuarios/') ?>"
           href="<?= $ROOT ?>usuarios/listar_usuarios.php"><i class="bi bi-person-gear me-2"></i> Usuários</a>
      <?php endif; ?>

      <!-- Operação (OS) -->
      <div class="px-3 pt-3 text-muted small">Operação</div>
      <a class="list-group-item list-group-item-action <?= nav_active('/os/listar_os.php') ?>"
         href="<?= $ROOT ?>os/listar_os.php"><i class="bi bi-diagram-3 me-2"></i> OS</a>
      <a class="list-group-item list-group-item-action <?= nav_active('/os/mapa_os.php') ?>"
         href="<?= $ROOT ?>os/mapa_os.php"><i class="bi bi-map me-2"></i> Mapa OS</a>

      <?php if ($isPiloto): ?>
        <a class="list-group-item list-group-item-action <?= nav_active('/os/minhas_os.php') ?>"
           href="<?= $ROOT ?>os/minhas_os.php"><i class="bi bi-person-workspace me-2"></i> Minhas OS</a>
        <a class="list-group-item list-group-item-action <?= nav_active('/os/minhas_metricas.php') ?>"
           href="<?= $ROOT ?>os/minhas_metricas.php"><i class="bi bi-clipboard-data me-2"></i> Meu rendimento</a>
      <?php endif; ?>

      <!-- Relatórios (apenas admin por enquanto) -->
      <?php if ($isAdmin): ?>
        <div class="px-3 pt-3 text-muted small">Relatórios</div>
        <a class="list-group-item list-group-item-action <?= nav_active('/relatorios/') ?>"
           href="<?= $ROOT ?>relatorios/index.php"><i class="bi bi-graph-up-arrow me-2"></i> Relatórios</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
// Flash messages (se definidas)
if (function_exists('get_flashes')) {
  echo '<div class="container pt-3">';
  foreach (get_flashes() as $f) {
    $map = ['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info'];
    $type = $map[$f['type']] ?? 'secondary';
    echo '<div class="alert alert-'.$type.' alert-dismissible fade show" role="alert">'
       . htmlspecialchars($f['msg'])
       . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
       . '</div>';
  }
  echo '</div>';
}
?>

<!-- Conteúdo das páginas -->
<div class="container py-4">

<?php
/* ======================= FAB (apenas admin) ======================= */
if ($isAdmin): ?>
  <div class="dropup ag-fab">
    <button class="btn btn-primary btn-lg dropdown-toggle rounded-circle shadow" data-bs-toggle="dropdown" aria-expanded="false" title="Criar">
      <i class="bi bi-plus-lg"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow">
      <li><a class="dropdown-item" href="<?= $ROOT ?>os/cadastrar_os.php"><i class="bi bi-diagram-3 me-2"></i> Nova OS</a></li>
      <li><a class="dropdown-item" href="<?= $ROOT ?>clientes/cadastrar_cliente.php"><i class="bi bi-building-add me-2"></i> Novo Cliente</a></li>
      <li><a class="dropdown-item" href="<?= $ROOT ?>cadastrar.php"><i class="bi bi-person-plus me-2"></i> Novo Colaborador</a></li>
    </ul>
  </div>
<?php endif; ?>
