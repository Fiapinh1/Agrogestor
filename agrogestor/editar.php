<?php 
require 'conexao.php';
require 'auth.php'; 
requireLogin(); requireRole('admin');
require 'utils.php'; 

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('ID inválido.');

$stmt = $pdo->prepare("SELECT * FROM colaboradores WHERE id = :id");
$stmt->execute([':id' => $id]);
$col = $stmt->fetch();
if (!$col) die('Colaborador não encontrado.');

$title = "Editar Colaborador | AgroGestor"; 
include 'inc_header.php'; 
?>

<div class="d-flex align-items-center mb-3">
  <h2 class="mb-0">Editar: <?= h($col['nome']) ?></h2>
  <a href="listar.php" class="btn btn-outline-secondary ms-auto">
    <i class="bi bi-arrow-left"></i> Voltar
  </a>
</div>

<form action="atualizar.php" method="post" class="row g-3">
  <input type="hidden" name="id" value="<?= (int)$col['id'] ?>">

  <div class="col-md-6">
    <label class="form-label">Nome Completo *</label>
    <input type="text" name="nome" value="<?= h($col['nome']) ?>" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Nome Social</label>
    <input type="text" name="nome_social" value="<?= h($col['nome_social']) ?>" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">Data de Nascimento *</label>
    <input type="date" name="data_nascimento" value="<?= h($col['data_nascimento']) ?>" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">CPF *</label>
    <input type="text" name="cpf" value="<?= h($col['cpf']) ?>" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">RG</label>
    <input type="text" name="rg" value="<?= h($col['rg']) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">CTPS</label>
    <input type="text" name="ctps" value="<?= h($col['ctps']) ?>" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">PIS</label>
    <input type="text" name="pis" value="<?= h($col['pis']) ?>" class="form-control">
  </div>
  <div class="col-md-9">
    <label class="form-label">Endereço</label>
    <input type="text" name="endereco" value="<?= h($col['endereco']) ?>" class="form-control">
  </div>

  <div class="col-md-4">
    <label class="form-label">Telefone</label>
    <input type="text" name="telefone" value="<?= h($col['telefone']) ?>" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">E-mail</label>
    <input type="email" name="email" value="<?= h($col['email']) ?>" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Contato de Emergência</label>
    <input type="text" name="contato_emergencia" value="<?= h($col['contato_emergencia']) ?>" class="form-control">
  </div>

  <div class="col-md-4">
    <label class="form-label">Cargo *</label>
    <input type="text" name="cargo" value="<?= h($col['cargo']) ?>" class="form-control" required>
  </div>
  <div class="col-md-4">
    <label class="form-label">Setor</label>
    <input type="text" name="setor" value="<?= h($col['setor']) ?>" class="form-control">
  </div>
  <div class="col-md-4">
    <label class="form-label">Frente</label>
    <input type="text" name="frente" value="<?= h($col['frente']) ?>" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">Regime</label>
    <input type="text" name="regime" value="<?= h($col['regime']) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Admissão</label>
    <input type="date" name="admissao" value="<?= h($col['admissao']) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Salário</label>
    <input type="number" step="0.01" name="salario" value="<?= h($col['salario']) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Gestor Imediato</label>
    <input type="text" name="gestor_imediato" value="<?= h($col['gestor_imediato']) ?>" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">Situação</label>
    <input type="text" name="situacao" value="<?= h($col['situacao']) ?>" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">CNH</label>
    <input type="text" name="cnh" value="<?= h($col['cnh']) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Validade CNH</label>
    <input type="date" name="validade_cnh" value="<?= h($col['validade_cnh']) ?>" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">Certif. Piloto</label>
    <input type="text" name="certificado_piloto" value="<?= h($col['certificado_piloto']) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Validade Certificado</label>
    <input type="date" name="validade_certificado" value="<?= h($col['validade_certificado']) ?>" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">ASO (data)</label>
    <input type="date" name="aso" value="<?= h($col['aso']) ?>" class="form-control">
  </div>
  <div class="col-md-9">
    <label class="form-label">Alergias/Observações</label>
    <input type="text" name="alergias" value="<?= h($col['alergias']) ?>" class="form-control">
  </div>

  <div class="col-12">
    <label class="form-label">EPI/Uniforme</label>
    <textarea name="epi" class="form-control" rows="2"><?= h($col['epi']) ?></textarea>
  </div>

  <div class="col-md-3">
    <label class="form-label">Tipo de Contrato</label>
    <input type="text" name="contrato_tipo" value="<?= h($col['contrato_tipo']) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Término Contrato</label>
    <input type="date" name="contrato_termino" value="<?= h($col['contrato_termino']) ?>" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">Banco</label>
    <input type="text" name="banco" value="<?= h($col['banco']) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Agência</label>
    <input type="text" name="agencia" value="<?= h($col['agencia']) ?>" class="form-control">
  </div>

  <div class="col-md-3">
    <label class="form-label">Conta</label>
    <input type="text" name="conta" value="<?= h($col['conta']) ?>" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Chave PIX</label>
    <input type="text" name="chave_pix" value="<?= h($col['chave_pix']) ?>" class="form-control">
  </div>

    <div class="form-check mt-2">
  <input class="form-check-input" type="checkbox" name="is_piloto" value="1"
         <?= !empty($col['is_piloto']) ? 'checked' : '' ?>>
    <label class="form-check-label">É Piloto</label>
    </div>


  <div class="col-12">
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-arrow-repeat"></i> Atualizar
    </button>
    <a href="listar.php" class="btn btn-secondary">
      <i class="bi bi-x-circle"></i> Cancelar
    </a>
  </div>
</form>

<?php include 'inc_footer.php'; ?>
