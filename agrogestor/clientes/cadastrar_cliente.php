<?php
require '../auth.php'; requireLogin(); requireRole('admin');
require '../utils.php';
$title = 'Novo Cliente | AgroGestor';
include '../inc_header.php';
?>

<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Novo Cliente</h2>
  <a href="listar_clientes.php" class="btn btn-outline-secondary ms-auto"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<form action="salvar_cliente.php" method="post" class="row g-3" autocomplete="off">

  <div class="col-md-3">
    <label class="form-label">Tipo de Cliente *</label>
    <select name="tipo_cliente" class="form-select" required>
      <option value="usina">Usina</option>
      <option value="produtor">Produtor</option>
      <option value="cliente">Cliente</option>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">Pessoa *</label>
    <select name="tipo_pessoa" class="form-select" required>
      <option value="juridica">Jurídica (PJ)</option>
      <option value="fisica">Física (PF)</option>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option value="ativo" selected>Ativo</option>
      <option value="suspenso">Suspenso</option>
      <option value="inativo">Inativo</option>
    </select>
  </div>

  <div class="col-md-6">
    <label class="form-label">Razão Social / Nome *</label>
    <input type="text" name="razao_social" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Nome Fantasia</label>
    <input type="text" name="nome_fantasia" class="form-control">
  </div>

  <div class="col-md-4">
    <label class="form-label">CNPJ/CPF *</label>
    <input type="text" name="cpf_cnpj" class="form-control" required placeholder="00.000.000/0000-00 ou 000.000.000-00">
  </div>
  <div class="col-md-4">
    <label class="form-label">IE/RG</label>
    <input type="text" name="ie_rg" class="form-control">
  </div>
  <div class="col-md-4 form-check mt-4">
    <input class="form-check-input" type="checkbox" name="ie_isento" value="1" id="ie_isento">
    <label class="form-check-label" for="ie_isento">IE Isento</label>
  </div>

  <div class="col-md-4">
    <label class="form-label">Responsável</label>
    <input type="text" name="responsavel" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Telefone</label>
    <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000">
  </div>
  <div class="col-md-4">
    <label class="form-label">WhatsApp</label>
    <input type="text" name="whatsapp" class="form-control" placeholder="(00) 00000-0000">
  </div>

  <div class="col-md-6">
    <label class="form-label">E-mail</label>
    <input type="email" name="email" class="form-control">
  </div>
  <div class="col-md-6">
    <label class="form-label">Site</label>
    <input type="text" name="site" class="form-control" placeholder="https://">
  </div>

  <div class="col-md-2">
    <label class="form-label">CEP</label>
    <input type="text" name="cep" class="form-control" placeholder="00000-000">
  </div>
  <div class="col-md-6">
    <label class="form-label">Endereço</label>
    <input type="text" name="endereco" class="form-control">
  </div>
  <div class="col-md-2">
    <label class="form-label">Número</label>
    <input type="text" name="numero" class="form-control">
  </div>
  <div class="col-md-2">
    <label class="form-label">Compl.</label>
    <input type="text" name="complemento" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Bairro</label>
    <input type="text" name="bairro" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Cidade</label>
    <input type="text" name="cidade" class="form-control">
  </div>
  <div class="col-md-2">
    <label class="form-label">UF</label>
    <input type="text" name="uf" class="form-control" maxlength="2">
  </div>

  <div class="col-md-3">
    <label class="form-label">Área total (ha)</label>
    <input type="number" step="0.01" name="area_total_ha" class="form-control">
  </div>
  <div class="col-md-6">
    <label class="form-label">Culturas</label>
    <input type="text" name="culturas" class="form-control" placeholder="Soja, Milho, Cana...">
  </div>
  <div class="col-md-3">
    <label class="form-label">Safra</label>
    <input type="text" name="safra" class="form-control" placeholder="24/25">
  </div>

  <div class="col-md-4">
    <label class="form-label">Condição de Pagto</label>
    <input type="text" name="cond_pagto" class="form-control" placeholder="14d, 30/60...">
  </div>
  <div class="col-md-4">
    <label class="form-label">Limite de Crédito</label>
    <input type="number" step="0.01" name="limite_credito" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">E-mail NFe</label>
    <input type="email" name="email_nfe" class="form-control">
  </div>

  <div class="col-12">
    <label class="form-label">Observações</label>
    <textarea name="obs" rows="2" class="form-control"></textarea>
  </div>

  <div class="col-12">
    <button class="btn btn-success" type="submit"><i class="bi bi-save"></i> Salvar</button>
    <a href="listar_clientes.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancelar</a>
  </div>
</form>


<?php include '../inc_footer.php'; ?>
