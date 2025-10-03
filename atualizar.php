<?php
// atualizar.php  (COLABORADORES)
require_once 'auth.php';
requireLogin();  requireRole('admin');                // só exige estar logado (sem requireRole)
require_once 'conexao.php';

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  if (function_exists('flash')) { flash('Método inválido.', 'error'); }
  header('Location: listar.php');
  exit;
}

// Helpers iguais ao salvar.php
function v(string $k): string { return trim($_POST[$k] ?? ''); }
function n(string $k): ?string { $x = trim($_POST[$k] ?? ''); return $x === '' ? null : $x; }
function dt(string $k): ?string {
  $x = trim($_POST[$k] ?? '');
  if ($x === '') return null;
  if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $x)) { [$d,$m,$y] = explode('/',$x); return "$y-$m-$d"; }
  return $x; // já veio ISO (YYYY-MM-DD)
}

// ID obrigatório
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  if (function_exists('flash')) { flash('ID inválido.', 'error'); }
  header('Location: listar.php'); exit;
}

// Piloto: checkbox + fallback por cargo conter "piloto"
$is_piloto = !empty($_POST['is_piloto']) ? 1 : 0;
if (!$is_piloto && stripos(v('cargo'), 'piloto') !== false) { $is_piloto = 1; }

// Valida mínimos
if (v('nome') === '' || v('cpf') === '' || v('cargo') === '') {
  if (function_exists('flash')) { flash('Preencha Nome, CPF e Cargo.', 'error'); }
  header('Location: editar.php?id='.$id); exit;
}

$sql = "UPDATE colaboradores SET
  nome                 = :nome,
  nome_social          = :nome_social,
  data_nascimento      = :data_nascimento,
  cpf                  = :cpf,
  rg                   = :rg,
  ctps                 = :ctps,
  pis                  = :pis,
  endereco             = :endereco,
  telefone             = :telefone,
  email                = :email,
  contato_emergencia   = :contato_emergencia,
  cargo                = :cargo,
  is_piloto            = :is_piloto,
  setor                = :setor,
  frente               = :frente,
  regime               = :regime,
  admissao             = :admissao,
  salario              = :salario,
  gestor_imediato      = :gestor_imediato,
  situacao             = :situacao,
  cnh                  = :cnh,
  validade_cnh         = :validade_cnh,
  certificado_piloto   = :certificado_piloto,
  validade_certificado = :validade_certificado,
  aso                  = :aso,
  alergias             = :alergias,
  epi                  = :epi,
  contrato_tipo        = :contrato_tipo,
  contrato_termino     = :contrato_termino,
  banco                = :banco,
  agencia              = :agencia,
  conta                = :conta,
  chave_pix            = :chave_pix
WHERE id = :id";

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':id'                   => $id,
    ':nome'                 => v('nome'),
    ':nome_social'          => n('nome_social'),
    ':data_nascimento'      => dt('data_nascimento'),
    ':cpf'                  => v('cpf'),
    ':rg'                   => n('rg'),
    ':ctps'                 => n('ctps'),
    ':pis'                  => n('pis'),
    ':endereco'             => n('endereco'),
    ':telefone'             => n('telefone'),
    ':email'                => n('email'),
    ':contato_emergencia'   => n('contato_emergencia'),
    ':cargo'                => v('cargo'),
    ':is_piloto'            => $is_piloto,                 // ⬅️ AQUI
    ':setor'                => n('setor'),
    ':frente'               => n('frente'),
    ':regime'               => n('regime'),
    ':admissao'             => dt('admissao'),
    ':salario'              => n('salario'),
    ':gestor_imediato'      => n('gestor_imediato'),
    ':situacao'             => n('situacao') ?: 'Ativo',
    ':cnh'                  => n('cnh'),
    ':validade_cnh'         => dt('validade_cnh'),
    ':certificado_piloto'   => n('certificado_piloto'),
    ':validade_certificado' => dt('validade_certificado'),
    ':aso'                  => n('aso'),                    // ajuste p/ DATE se necessário
    ':alergias'             => n('alergias'),
    ':epi'                  => n('epi'),
    ':contrato_tipo'        => n('contrato_tipo'),
    ':contrato_termino'     => dt('contrato_termino'),
    ':banco'                => n('banco'),
    ':agencia'              => n('agencia'),
    ':conta'                => n('conta'),
    ':chave_pix'            => n('chave_pix'),
  ]);

  if (function_exists('flash')) { flash('Colaborador atualizado com sucesso!', 'success'); }
  header('Location: listar.php'); exit;

} catch (PDOException $e) {
  // Em produção, logue o erro e mostre msg genérica
  if (function_exists('flash')) {
    flash('Erro ao atualizar: '.$e->getMessage(), 'error');
    header('Location: editar.php?id='.$id);
  } else {
    die('Erro ao atualizar: '.$e->getMessage());
  }
  exit;
}
