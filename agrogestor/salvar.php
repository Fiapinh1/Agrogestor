<?php
// salvar.php  (COLABORADORES)
require_once 'auth.php';
requireLogin();                    // só exige estar logado (sem requireRole)
require_once 'conexao.php';

// Bloqueia acesso direto por GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  if (function_exists('flash')) { flash('Método inválido.', 'error'); }
  header('Location: cadastrar.php');
  exit;
}

// Helpers
function v(string $k): string { return trim($_POST[$k] ?? ''); }
function n(string $k): ?string { $x = trim($_POST[$k] ?? ''); return $x === '' ? null : $x; }
function dt(string $k): ?string {
  $x = trim($_POST[$k] ?? '');
  if ($x === '') return null;
  if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $x)) { [$d,$m,$y] = explode('/',$x); return "$y-$m-$d"; }
  return $x; // já veio ISO
}

// Piloto: checkbox + fallback por cargo conter "piloto"
$is_piloto = !empty($_POST['is_piloto']) ? 1 : 0;
if (!$is_piloto && stripos(v('cargo'), 'piloto') !== false) { $is_piloto = 1; }

// Valida mínimos
if (v('nome') === '' || v('cpf') === '' || v('cargo') === '') {
  if (function_exists('flash')) { flash('Preencha Nome, CPF e Cargo.', 'error'); }
  header('Location: cadastrar.php'); exit;
}

$sql = "INSERT INTO colaboradores
(nome, nome_social, data_nascimento, cpf, rg, ctps, pis, endereco, telefone, email, contato_emergencia,
 cargo, is_piloto, setor, frente, regime, admissao, salario, gestor_imediato, situacao,
 cnh, validade_cnh, certificado_piloto, validade_certificado, aso, alergias, epi,
 contrato_tipo, contrato_termino, banco, agencia, conta, chave_pix)
VALUES
(:nome,:nome_social,:data_nascimento,:cpf,:rg,:ctps,:pis,:endereco,:telefone,:email,:contato_emergencia,
 :cargo,:is_piloto,:setor,:frente,:regime,:admissao,:salario,:gestor_imediato,:situacao,
 :cnh,:validade_cnh,:certificado_piloto,:validade_certificado,:aso,:alergias,:epi,
 :contrato_tipo,:contrato_termino,:banco,:agencia,:conta,:chave_pix)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
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

if (function_exists('flash')) { flash('Colaborador cadastrado com sucesso!', 'success'); }
header('Location: listar.php');
exit;
