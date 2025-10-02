<?php
require '../auth.php'; requireLogin(); requireRole('admin');
require '../conexao.php'; require '../utils.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
$stmt->execute([':id'=>$id]);
$c = $stmt->fetch();
if (!$c) die('Cliente não encontrado.');

$title = 'Editar Cliente | AgroGestor';
include '../inc_header.php';
?>

<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Editar Cliente</h2>
  <a href="listar_clientes.php" class="btn btn-outline-secondary ms-auto"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<form action="atualizar_cliente.php" method="post" class="row g-3" autocomplete="off">
  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">

  <div class="col-md-3">
    <label class="form-label">Tipo de Cliente *</label>
    <select name="tipo_cliente" class="form-select" required>
      <?php foreach(['usina'=>'Usina','produtor'=>'Produtor','cliente'=>'Cliente'] as $k=>$v): ?>
        <option value="<?= $k ?>" <?= $c['tipo_cliente']===$k?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">Pessoa *</label>
    <select name="tipo_pessoa" class="form-select" required>
      <option value="juridica" <?= $c['tipo_pessoa']==='juridica'?'selected':'' ?>>Jurídica (PJ)</option>
      <option value="fisica"   <?= $c['tipo_pessoa']==='fisica'?'selected':'' ?>>Física (PF)</option>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <?php foreach(['ativo'=>'Ativo','suspenso'=>'Suspenso','inativo'=>'Inativo'] as $k=>$v): ?>
        <option value="<?= $k ?>" <?= $c['status']===$k?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Razão Social / Nome *</label>
    <input type="text" name="razao_social" value="<?= h($c['razao_social']) ?>" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Nome Fantasia</label>
    <input type="text" name="nome_fantasia" value="<?= h($c['nome_fantasia']) ?>" class="form-control">
  </div>

  <div class="col-md-4">
    <label class="form-label">CNPJ/CPF *</label>
    <input type="text" name="cpf_cnpj" value="<?= h($c['cpf_cnpj']) ?>" class="form-control" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">IE/RG</label>
    <input type="text" name="ie_rg" value="<?= h($c['ie_rg']) ?>" class="form-control">
  </div>
  <div class="col-md-4 form-check mt-4">
    <input class="form-check-input" type="checkbox" name="ie_isento" value="1" id="ie_isento" <?= $c['ie_isento']?'checked':'' ?>>
    <label class="form-check-label" for="ie_isento">IE Isento</label>
  </div>

  <div class="col-md-4">
    <label class="form-label">Responsável</label>
    <input type="text" name="responsavel" value="<?= h($c['responsavel']) ?>" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Telefone</label>
    <input type="text" name="telefone" value="<?= h($c['telefone']) ?>" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">WhatsApp</label>
    <input type="text" name="whatsapp" value="<?= h($c['whatsapp']) ?>" class="form-control">
  </div>

  <div class="col-md-6">
    <label class="form-label">E-mail</label>
    <input type="email" name="email" value="<?= h($c['email']) ?>" class="form-control">
  </div>
  <div class="col-md-6">
    <label class="form-label">Site</label>
    <input type="text" name="site" value="<?= h($c['site']) ?>" class="form-control">
  </div>

  <div class="col-md-2">
    <label class="form-label">CEP</label>
    <input type="text" name="cep" value="<?= h($c['cep']) ?>" class="form-control">
  </div>
  <div class="col-md-6">
    <label class="form-label">Endereço</label>
    <input type="text" name="endereco" value="<?= h($c['endereco']) ?>" class="form-control">
  </div>
  <div class="col-md-2">
    <label class="form-label">Número</label>
    <input type="text" name="numero" value="<?= h($c['numero']) ?>" class="form-control">
  </div>
  <div class="col-md-2">
    <label class="form-label">Compl.</label>
    <input type="text" name="complemento" value="<?= h($c['complemento']) ?>" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Bairro</label>
    <input type="text" name="bairro" value="<?= h($c['bairro']) ?>" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Cidade</label>
    <input type="text" name="cidade" value="<?= h($c['cidade']) ?>" class="form-control">
  </div>
  <div class="col-md-2">
    <label class="form-label">UF</label>
    <input type="text" name="uf" value="<?= h($c['uf']) ?>" class="form-control" maxlength="2">
  </div>

  <div class="col-md-3">
    <label class="form-label">Área total (ha)</label>
    <input type="number" step="0.01" name="area_total_ha" value="<?= h($c['area_total_ha']) ?>" class="form-control">
  </div>
  <div class="col-md-6">
    <label class="form-label">Culturas</label>
    <input type="text" name="culturas" value="<?= h($c['culturas']) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Safra</label>
    <input type="text" name="safra" value="<?= h($c['safra']) ?>" class="form-control">
  </div>

  <div class="col-md-4">
    <label class="form-label">Condição de Pagto</label>
    <input type="text" name="cond_pagto" value="<?= h($c['cond_pagto']) ?>" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Limite de Crédito</label>
    <input type="number" step="0.01" name="limite_credito" value="<?= h($c['limite_credito']) ?>" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">E-mail NFe</label>
    <input type="email" name="email_nfe" value="<?= h($c['email_nfe']) ?>" class="form-control">
  </div>

  <div class="col-12">
    <label class="form-label">Observações</label>
    <textarea name="obs" rows="2" class="form-control"><?= h($c['obs']) ?></textarea>
  </div>

  <div class="col-12">
    <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-repeat"></i> Atualizar</button>
    <a href="listar_clientes.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancelar</a>
  </div>
</form>

<?php include '../inc_footer.php'; ?>
