<?php
require_once '../auth.php'; requireLogin(); requireRole('admin');
require_once '../conexao.php'; require_once '../utils.php';

// Carregar clientes ativos para o select
$clientes = $pdo->query("
  SELECT id, razao_social, nome_fantasia, cpf_cnpj, cidade, uf, status
  FROM clientes
  WHERE status = 'ativo'
  ORDER BY razao_social
")->fetchAll(PDO::FETCH_ASSOC);

// PILOTOS (mantém como já estava)
$pilotos = $pdo->query("
  SELECT id, nome FROM colaboradores
  WHERE is_piloto = 1 AND (situacao IS NULL OR situacao='Ativo')
  ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);

$title = 'Cadastrar OS | AgroGestor';
include '../inc_header.php';

// Remova o select de unidades, pois agora o campo será Cliente
function opt($val,$label){ echo "<option value=\"".h($val)."\">".h($label)."</option>"; }
?>
<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Cadastrar OS</h2>
  <a href="listar_os.php" class="btn btn-outline-secondary ms-auto"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<form action="salvar_os.php" method="post" class="row g-3" autocomplete="off">
  <div class="col-md-4">
    <label class="form-label">Cliente *</label>
    <select name="cliente_id" class="form-select" required>
      <option value="">— selecione —</option>
      <?php foreach ($clientes as $c): 
        $nome = $c['nome_fantasia'] ?: $c['razao_social'];
        $doc  = $c['cpf_cnpj'] ? ' • ' . $c['cpf_cnpj'] : '';
      ?>
        <option value="<?= (int)$c['id'] ?>">
          <?= h($nome . $doc) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-4">
    <label class="form-label">Número da OS *</label>
    <input type="text" name="numero_os" class="form-control" required>
  </div>

  <div class="col-md-4">
    <label class="form-label">Código da Fazenda</label>
    <input type="text" name="fazenda_codigo" class="form-control" maxlength="50"
           placeholder="Ex.: SJ-004, FZD-15, etc.">
  </div>

  <div class="col-md-4">
    <label class="form-label">Fazenda *</label>
    <input type="text" name="fazenda" class="form-control" required>
  </div>

  <div class="col-md-3">
    <label class="form-label">Área da OS (ha) *</label>
    <input type="number" step="0.01" name="area_ha" class="form-control" required>
  </div>

  <div class="col-md-3">
    <label class="form-label">Objetivo *</label>
    <select name="objetivo" class="form-select" required>
      <?php
        opt('aplicacao_total','Aplicação (Área Total)');
        opt('aplicacao_localizada','Aplicação (Catação/Localizada)');
        opt('mapeamento','Mapeamento');
        opt('cotesia','Cotesia');
      ?>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">Categoria do Produto *</label>
    <select name="produto_categoria" class="form-select" required>
      <?php
        opt('herbicida','Herbicida');
        opt('inseticida','Inseticida');
        opt('fungicida','Fungicida');
        opt('fertilizante','Fertilizante');
        opt('maturador','Maturador');
      ?>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">Insumo Utilizado (nome comercial) *</label>
    <input type="text" name="insumo_nome" class="form-control" placeholder="Ex.: Glifosato 480" required>
  </div>

  <div class="col-12"><hr><b>Coordenadas (Grau/Minuto/Segundo + Hemisfério)</b></div>

  <div class="col-md-6">
    <label class="form-label">Latitude *</label>
    <div class="row g-2">
      <div class="col-3"><input type="number" name="lat_grau" class="form-control" placeholder="grau" required></div>
      <div class="col-3"><input type="number" name="lat_min" class="form-control" placeholder="min" required></div>
      <div class="col-3"><input type="number" step="0.001" name="lat_seg" class="form-control" placeholder="seg" required></div>
      <div class="col-3">
        <select name="lat_hemi" class="form-select" required>
          <option value="N">N</option>
          <option value="S" selected>S</option>
        </select>
      </div>
    </div>
    <small class="text-muted">Ex.: 15° 46' 48" S</small>
  </div>

  <div class="col-md-6">
    <label class="form-label">Longitude *</label>
    <div class="row g-2">
      <div class="col-3"><input type="number" name="lon_grau" class="form-control" placeholder="grau" required></div>
      <div class="col-3"><input type="number" name="lon_min" class="form-control" placeholder="min" required></div>
      <div class="col-3"><input type="number" step="0.001" name="lon_seg" class="form-control" placeholder="seg" required></div>
      <div class="col-3">
        <select name="lon_hemi" class="form-select" required>
          <option value="E">E</option>
          <option value="W" selected>W</option>
        </select>
      </div>
    </div>
    <small class="text-muted">Ex.: 47° 55' 45" W</small>
  </div>

  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Prazo final</label>
      <input type="date" name="prazo_final" class="form-control" value="">
    </div>

    <div class="col-md-4">
      <label class="form-label">Piloto responsável</label>
      <select name="piloto_id" class="form-select">
        <option value="">— Selecionar —</option>
        <?php
          foreach ($pilotos as $p) {
            echo "<option value='{$p['id']}'>".h($p['nome'])."</option>";
          }
        ?>
      </select>
    </div>
  </div>

  <div class="col-12">
    <button class="btn btn-success" type="submit"><i class="bi bi-save"></i> Salvar OS</button>
    <a href="listar_os.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancelar</a>
  </div>
</form>

<?php include '../inc_footer.php'; ?>
