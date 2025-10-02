<?php
require_once '../auth.php';  requireLogin();  requireRole('admin');
require_once '../conexao.php';

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { flash('ID inválido.', 'error'); header('Location: listar_clientes.php'); exit; }

function v($k){ return trim($_POST[$k] ?? ''); }
function n($k){ $x = trim($_POST[$k] ?? ''); return ($x === '' ? null : $x); }

$sql = "UPDATE clientes SET
  tipo_cliente=:tipo_cliente, tipo_pessoa=:tipo_pessoa, razao_social=:razao_social,
  nome_fantasia=:nome_fantasia, cpf_cnpj=:cpf_cnpj, ie_rg=:ie_rg, ie_isento=:ie_isento,
  responsavel=:responsavel, telefone=:telefone, whatsapp=:whatsapp, email=:email, site=:site,
  cep=:cep, endereco=:endereco, numero=:numero, complemento=:complemento, bairro=:bairro,
  cidade=:cidade, uf=:uf, area_total_ha=:area_total_ha, culturas=:culturas, safra=:safra,
  cond_pagto=:cond_pagto, limite_credito=:limite_credito, email_nfe=:email_nfe, obs=:obs, status=:status
 WHERE id=:id";

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':id'=>$id,
    ':tipo_cliente'=>v('tipo_cliente'),
    ':tipo_pessoa'=>v('tipo_pessoa'),
    ':razao_social'=>v('razao_social'),
    ':nome_fantasia'=>n('nome_fantasia'),
    ':cpf_cnpj'=>v('cpf_cnpj'),
    ':ie_rg'=>n('ie_rg'),
    ':ie_isento'=> isset($_POST['ie_isento']) ? 1 : 0,
    ':responsavel'=>n('responsavel'),
    ':telefone'=>n('telefone'),
    ':whatsapp'=>n('whatsapp'),
    ':email'=>n('email'),
    ':site'=>n('site'),
    ':cep'=>n('cep'),
    ':endereco'=>n('endereco'),
    ':numero'=>n('numero'),
    ':complemento'=>n('complemento'),
    ':bairro'=>n('bairro'),
    ':cidade'=>n('cidade'),
    ':uf'=>n('uf'),
    ':area_total_ha'=>n('area_total_ha'),
    ':culturas'=>n('culturas'),
    ':safra'=>n('safra'),
    ':cond_pagto'=>n('cond_pagto'),
    ':limite_credito'=>n('limite_credito'),
    ':email_nfe'=>n('email_nfe'),
    ':obs'=>n('obs'),
    ':status'=>v('status') ?: 'ativo'
  ]);

  flash('Cliente atualizado com sucesso!', 'success');
  header('Location: listar_clientes.php'); exit;

} catch (PDOException $e) {
  flash('Erro ao atualizar: '.$e->getMessage(), 'error');
  header('Location: editar_cliente.php?id='.$id); exit;
}
