<?php 
require 'conexao.php';
require 'auth.php';
requireLogin();
require 'utils.php'; 
$title = 'Novo Colaborador | AgroGestor'; 
include 'inc_header.php'; 
?>

<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Novo Colaborador</h2>
  <a href="listar.php" class="btn btn-outline-secondary ms-auto">
    <i class="bi bi-arrow-left"></i> Voltar
  </a>
</div>

<form action="salvar.php" method="post" class="row g-3">

  <div class="col-md-6">
    <label class="form-label">Nome Completo *</label>
    <input type="text" name="nome" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Nome Social</label>
    <input type="text" name="nome_social" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">Data de Nascimento *</label>
    <input type="date" name="data_nascimento" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">CPF *</label>
    <input type="text" name="cpf" class="form-control" placeholder="000.000.000-00" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">RG</label>
    <input type="text" name="rg" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">CTPS</label>
    <input type="text" name="ctps" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">PIS</label>
    <input type="text" name="pis" class="form-control">
  </div>
  <div class="col-md-9">
    <label class="form-label">Endereço</label>
    <input type="text" name="endereco" class="form-control" placeholder="Rua, nº, bairro, cidade, UF, CEP">
  </div>

  <div class="col-md-4">
    <label class="form-label">Telefone</label>
    <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000">
  </div>
  <div class="col-md-4">
    <label class="form-label">E-mail</label>
    <input type="email" name="email" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Contato de Emergência</label>
    <input type="text" name="contato_emergencia" class="form-control" placeholder="Nome / Telefone">
  </div>

  <div class="col-md-4">
    <label class="form-label">Cargo *</label>
    <input type="text" name="cargo" class="form-control" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Setor</label>
    <input type="text" name="setor" class="form-control" placeholder="Operações, Geo, Oficina...">
  </div>
  <div class="col-md-4">
    <label class="form-label">Frente</label>
    <input type="text" name="frente" class="form-control" placeholder="Frente 01, Raízen, Delta VG...">
  </div>

  <div class="col-md-3">
    <label class="form-label">Regime</label>
    <select name="regime" class="form-select">
      <option value="">Selecione</option>
      <option>CLT</option>
      <option>PJ</option>
      <option>Estagiário</option>
      <option>Temporário</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Admissão</label>
    <input type="date" name="admissao" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Salário</label>
    <input type="number" step="0.01" name="salario" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Gestor Imediato</label>
    <input type="text" name="gestor_imediato" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">Situação</label>
    <select name="situacao" class="form-select">
      <option>Ativo</option>
      <option>Férias</option>
      <option>Afastado</option>
      <option>Desligado</option>
    </select>
  </div>

  <div class="col-md-3">
    <label class="form-label">CNH</label>
    <input type="text" name="cnh" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Validade CNH</label>
    <input type="date" name="validade_cnh" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">Certif. Piloto</label>
    <input type="text" name="certificado_piloto" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Validade Certificado</label>
    <input type="date" name="validade_certificado" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">ASO (data)</label>
    <input type="date" name="aso" class="form-control">
  </div>
  <div class="col-md-9">
    <label class="form-label">Alergias/Observações</label>
    <input type="text" name="alergias" class="form-control">
  </div>

  <div class="col-12">
    <label class="form-label">EPI/Uniforme</label>
    <textarea name="epi" class="form-control" rows="2"></textarea>
  </div>

  <div class="col-md-3">
    <label class="form-label">Tipo de Contrato</label>
    <select name="contrato_tipo" class="form-select">
      <option value="">Selecione</option>
      <option>Indeterminado</option>
      <option>Determinado</option>
      <option>Estágio</option>
      <option>Terceiro</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Término Contrato</label>
    <input type="date" name="contrato_termino" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">Banco</label>
    <input type="text" name="banco" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Agência</label>
    <input type="text" name="agencia" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">Conta</label>
    <input type="text" name="conta" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Chave PIX</label>
    <input type="text" name="chave_pix" class="form-control">
  </div>

  <div class="col-12">
    <button type="submit" class="btn btn-success">
      <i class="bi bi-save"></i> Salvar
    </button>
    <a href="listar.php" class="btn btn-secondary">
      <i class="bi bi-x-circle"></i> Cancelar
    </a>
  </div>
  <div class="form-check mt-2">
  <input class="form-check-input" type="checkbox" name="is_piloto" value="1" <?= !empty($col['is_piloto']) ? 'checked':'' ?>>
  <label class="form-check-label">É Piloto</label>
</div>


</form>

<?php include 'inc_footer.php'; ?>
