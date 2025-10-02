<?php
require_once '../auth.php'; requireLogin(); requireRole('admin');
require_once '../conexao.php'; require_once '../utils.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash('ID inválido.', 'error'); header('Location: listar_os.php'); exit; }

// OS
$stmt = $pdo->prepare("SELECT * FROM os WHERE id = :id");
$stmt->execute([':id' => $id]);
$os = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$os) { flash('OS não encontrada.', 'error'); header('Location: listar_os.php'); exit; }

// Carregar clientes ativos para o select
$clientes = $pdo->query("
  SELECT id, razao_social, nome_fantasia, cpf_cnpj, cidade, uf, status
  FROM clientes
  WHERE status = 'ativo'
  ORDER BY razao_social
")->fetchAll(PDO::FETCH_ASSOC);

// Pilotos
$pilotos = $pdo->query("
  SELECT id, nome FROM colaboradores
  WHERE is_piloto = 1 AND (situacao IS NULL OR situacao='Ativo')
  ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);

function sel($a,$b){ return (string)$a === (string)$b ? 'selected' : ''; }

$title = 'Editar OS | AgroGestor';
include '../inc_header.php';
?>
<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Editar OS #<?= h($os['numero_os']) ?></h2>
  <a href="listar_os.php" class="btn btn-outline-secondary ms-auto"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<form action="atualizar_os.php" method="post" class="row g-3" autocomplete="off">
  <input type="hidden" name="id" value="<?= (int)$os['id'] ?>">

  <div class="col-md-4">
    <label class="form-label">Cliente *</label>
    <select name="cliente_id" class="form-select" required>
      <option value="">— selecione —</option>
      <?php foreach ($clientes as $c): 
        $nome = $c['nome_fantasia'] ?: $c['razao_social'];
        $doc  = $c['cpf_cnpj'] ? ' • ' . $c['cpf_cnpj'] : '';
        $selected = sel($os['cliente_id'], $c['id']);
      ?>
        <option value="<?= (int)$c['id'] ?>" <?= $selected ?>>
          <?= h($nome . $doc) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-4">
    <label class="form-label">Número da OS *</label>
    <input type="text" name="numero_os" class="form-control" value="<?= h($os['numero_os']) ?>" required>
  </div>

  <div class="col-md-4">
    <label class="form-label">Código da Fazenda</label>
    <input type="text" name="fazenda_codigo" class="form-control" maxlength="50"
           value="<?= h($os['fazenda_codigo']) ?>" placeholder="Ex.: SJ-004, FZD-15, etc.">
  </div>

  <div class="col-md-4">
    <label class="form-label">Fazenda *</label>
    <input type="text" name="fazenda" class="form-control" value="<?= h($os['fazenda']) ?>" required>
  </div>

  <div class="col-md-3">
    <label class="form-label">Área da OS (ha) *</label>
    <input type="number" step="0.01" name="area_ha" class="form-control" value="<?= h($os['area_ha']) ?>" required>
  </div>

  <div class="col-md-3">
    <label class="form-label">Objetivo *</label>
    <select name="objetivo" class="form-select" required>
      <option value="aplicacao_total"     <?= sel($os['objetivo'],'aplicacao_total') ?>>Aplicação (Área Total)</option>
      <option value="aplicacao_localizada"<?= sel($os['objetivo'],'aplicacao_localizada') ?>>Aplicação (Catação/Localizada)</option>
      <option value="mapeamento"          <?= sel($os['objetivo'],'mapeamento') ?>>Mapeamento</option>
      <option value="cotesia"             <?= sel($os['objetivo'],'cotesia') ?>>Cotesia</option>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">Categoria do Produto *</label>
    <select name="produto_categoria" class="form-select" required>
      <option value="herbicida"    <?= sel($os['produto_categoria'],'herbicida') ?>>Herbicida</option>
      <option value="inseticida"   <?= sel($os['produto_categoria'],'inseticida') ?>>Inseticida</option>
      <option value="fungicida"    <?= sel($os['produto_categoria'],'fungicida') ?>>Fungicida</option>
      <option value="fertilizante" <?= sel($os['produto_categoria'],'fertilizante') ?>>Fertilizante</option>
      <option value="maturador"    <?= sel($os['produto_categoria'],'maturador') ?>>Maturador</option>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">Insumo Utilizado (nome comercial) *</label>
    <input type="text" name="insumo_nome" class="form-control" value="<?= h($os['insumo_nome']) ?>" required>
  </div>

  <div class="col-12"><hr><b>Coordenadas (Grau/Minuto/Segundo + Hemisfério)</b></div>

  <div class="col-md-6">
    <label class="form-label">Latitude *</label>
    <div class="row g-2">
      <div class="col-3"><input type="number" name="lat_grau" class="form-control" value="<?= h($os['lat_grau']) ?>" required></div>
      <div class="col-3"><input type="number" name="lat_min" class="form-control" value="<?= h($os['lat_min']) ?>" required></div>
      <div class="col-3"><input type="number" step="0.001" name="lat_seg" class="form-control" value="<?= h($os['lat_seg']) ?>" required></div>
      <div class="col-3">
        <select name="lat_hemi" class="form-select" required>
          <option value="N" <?= sel($os['lat_hemi'],'N') ?>>N</option>
          <option value="S" <?= sel($os['lat_hemi'],'S') ?>>S</option>
        </select>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label">Longitude *</label>
    <div class="row g-2">
      <div class="col-3"><input type="number" name="lon_grau" class="form-control" value="<?= h($os['lon_grau']) ?>" required></div>
      <div class="col-3"><input type="number" name="lon_min" class="form-control" value="<?= h($os['lon_min']) ?>" required></div>
      <div class="col-3"><input type="number" step="0.001" name="lon_seg" class="form-control" value="<?= h($os['lon_seg']) ?>" required></div>
      <div class="col-3">
        <select name="lon_hemi" class="form-select" required>
          <option value="E" <?= sel($os['lon_hemi'],'E') ?>>E</option>
          <option value="W" <?= sel($os['lon_hemi'],'W') ?>>W</option>
        </select>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Prazo final</label>
      <input type="date" name="prazo_final" class="form-control" value="<?= h($os['prazo_final'] ?? '') ?>">
    </div>

    <div class="col-md-4">
      <label class="form-label">Piloto responsável</label>
      <select name="piloto_id" class="form-select">
        <option value="">— Selecionar —</option>
        <?php
          foreach ($pilotos as $p) {
            $s = ($os['piloto_id'] ?? null) == $p['id'] ? 'selected':'';
            echo "<option value='{$p['id']}' $s>".h($p['nome'])."</option>";
          }
        ?>
      </select>
    </div>
  </div>

  <div class="col-12">
    <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Atualizar OS</button>
    <a href="listar_os.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancelar</a>
  </div>
</form>

<?php include '../inc_footer.php'; ?>
