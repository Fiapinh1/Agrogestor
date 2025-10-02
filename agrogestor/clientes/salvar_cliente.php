<?php
require_once '../auth.php';  requireLogin();  requireRole('admin');
require_once '../conexao.php';

function v($k){ return trim($_POST[$k] ?? ''); }
function n($k){ $x = trim($_POST[$k] ?? ''); return ($x === '' ? null : $x); }

$sql = "INSERT INTO clientes
 (tipo_cliente,tipo_pessoa,razao_social,nome_fantasia,cpf_cnpj,ie_rg,ie_isento,
  responsavel,telefone,whatsapp,email,site,
  cep,endereco,numero,complemento,bairro,cidade,uf,
  area_total_ha,culturas,safra,cond_pagto,limite_credito,email_nfe,obs,status)
 VALUES
 (:tipo_cliente,:tipo_pessoa,:razao_social,:nome_fantasia,:cpf_cnpj,:ie_rg,:ie_isento,
  :responsavel,:telefone,:whatsapp,:email,:site,
  :cep,:endereco,:numero,:complemento,:bairro,:cidade,:uf,
  :area_total_ha,:culturas,:safra,:cond_pagto,:limite_credito,:email_nfe,:obs,:status)";

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
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

  flash('Cliente cadastrado com sucesso!', 'success');
  header('Location: listar_clientes.php'); exit;

} catch (PDOException $e) {
  flash('Erro ao salvar: '.$e->getMessage(), 'error');
  header('Location: cadastrar_cliente.php'); exit;
}
