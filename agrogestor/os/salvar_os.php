<?php
require_once '../auth.php'; requireLogin();
require_once '../conexao.php'; require_once '../utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  flash('Método inválido.', 'error');
  header('Location: cadastrar_os.php'); exit;
}

/* ——— entradas ——— */
$cliente_id     = (int)($_POST['cliente_id'] ?? 0);
$numero_os      = trim($_POST['numero_os'] ?? '');
$fazenda_codigo = trim($_POST['fazenda_codigo'] ?? '');
$fazenda        = trim($_POST['fazenda'] ?? '');
$produto_cat    = trim($_POST['produto_categoria'] ?? '');
$insumo_nome    = trim($_POST['insumo_nome'] ?? '');
$prazo_final    = trim($_POST['prazo_final'] ?? '');   // dd/mm/aaaa
$piloto_id      = (int)($_POST['piloto_id'] ?? 0);

$area_ha        = (float)($_POST['area_ha'] ?? 0);
$objetivo       = trim($_POST['objetivo'] ?? '');

/* Coordenadas: você já tinha os campos em GMS — converta pra decimal */
$lat_grau = (int)($_POST['lat_grau'] ?? 0);
$lat_min  = (int)($_POST['lat_min'] ?? 0);
$lat_seg  = (float)($_POST['lat_seg'] ?? 0);
$lat_hemi = trim($_POST['lat_hemi'] ?? '');
$lon_grau = (int)($_POST['lon_grau'] ?? 0);
$lon_min  = (int)($_POST['lon_min'] ?? 0);
$lon_seg  = (float)($_POST['lon_seg'] ?? 0);
$lon_hemi = trim($_POST['lon_hemi'] ?? '');

/* Validações básicas */
if ($cliente_id <= 0) { flash('Selecione um cliente válido.', 'error'); header('Location: cadastrar_os.php'); exit; }
if ($numero_os === '') { flash('Informe o número da OS.', 'error'); header('Location: cadastrar_os.php'); exit; }

/* Cliente existe? */
$ck = $pdo->prepare("SELECT 1 FROM clientes WHERE id = :id LIMIT 1");
$ck->execute([':id'=>$cliente_id]);
if (!$ck->fetchColumn()) { flash('Cliente não encontrado. Cadastre-o antes.', 'error'); header('Location: cadastrar_os.php'); exit; }

/* Datas */
$prazo_iso = brToIsoDate($prazo_final);  // sua util: dd/mm/aaaa → yyyy-mm-dd

/* helpers locais (se não estiverem em utils.php) */
function dmsToDecimal($g,$m,$s,$hemi){
  $g=(float)strtr($g,',','.');
  $m=(float)strtr($m,',','.');
  $s=(float)strtr($s,',','.');
  $val = abs($g) + $m/60 + $s/3600;
  $hemi = strtoupper(trim((string)$hemi));
  if (in_array($hemi, ['S','W'])) $val = -$val;
  return $val ?: null;
}
function brToIsoDate($d){
  $d = trim((string)$d);
  if ($d === '') return null;
  if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/',$d)) { [$dd,$mm,$yy]=explode('/',$d); return "$yy-$mm-$dd"; }
  return $d; // já veio ISO
}

/* Calcule ANTES do INSERT! */
$lat = dmsToDecimal($lat_grau, $lat_min, $lat_seg, $lat_hemi);
$lon = dmsToDecimal($lon_grau, $lon_min, $lon_seg, $lon_hemi);

/* Agora sim, faça o INSERT usando $lat e $lon */
$sql = "INSERT INTO os
  (cliente_id, numero_os, fazenda_codigo, fazenda, area_ha, objetivo, produto_categoria, insumo_nome,
   lat_grau, lat_min, lat_seg, lat_hemi, lon_grau, lon_min, lon_seg, lon_hemi, lat, lon, prazo_final, piloto_id, criado_em)
VALUES
  (:cliente_id, :numero_os, :fazenda_codigo, :fazenda, :area_ha, :objetivo, :produto_categoria, :insumo_nome,
   :lat_grau, :lat_min, :lat_seg, :lat_hemi, :lon_grau, :lon_min, :lon_seg, :lon_hemi, :lat, :lon, :prazo_final, :piloto_id, NOW())";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':cliente_id'        => $cliente_id,
  ':numero_os'         => $numero_os,
  ':fazenda_codigo'    => $fazenda_codigo ?: null,
  ':fazenda'           => $fazenda ?: null,
  ':area_ha'           => $area_ha,
  ':objetivo'          => $objetivo,
  ':produto_categoria' => $produto_cat,
  ':insumo_nome'       => $insumo_nome,
  ':lat_grau'          => $lat_grau,
  ':lat_min'           => $lat_min,
  ':lat_seg'           => $lat_seg,
  ':lat_hemi'          => $lat_hemi,
  ':lon_grau'          => $lon_grau,
  ':lon_min'           => $lon_min,
  ':lon_seg'           => $lon_seg,
  ':lon_hemi'          => $lon_hemi,
  ':lat'               => $lat,
  ':lon'               => $lon,
  ':prazo_final'       => $prazo_iso,
  ':piloto_id'         => $piloto_id ?: null,
]);

flash('OS cadastrada com sucesso!', 'success');
header('Location: listar_os.php'); exit;
