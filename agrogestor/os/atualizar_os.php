<?php
require_once '../auth.php'; requireLogin(); requireRole('admin');
require_once '../conexao.php'; require_once '../utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  flash('Método inválido.', 'error');
  header('Location: listar_os.php'); exit;
}

$id             = (int)($_POST['id'] ?? 0);
$cliente_id     = (int)($_POST['cliente_id'] ?? 0);
$numero_os      = trim($_POST['numero_os'] ?? '');
$fazenda_codigo = trim($_POST['fazenda_codigo'] ?? '');
$fazenda        = trim($_POST['fazenda'] ?? '');
$area_ha        = (float)($_POST['area_ha'] ?? 0);
$objetivo       = trim($_POST['objetivo'] ?? '');
$produto_cat    = trim($_POST['produto_categoria'] ?? '');
$insumo_nome    = trim($_POST['insumo_nome'] ?? '');
$prazo_final    = trim($_POST['prazo_final'] ?? '');
$piloto_id      = (int)($_POST['piloto_id'] ?? 0);

// Coordenadas
$lat_grau = (int)($_POST['lat_grau'] ?? 0);
$lat_min  = (int)($_POST['lat_min'] ?? 0);
$lat_seg  = (float)($_POST['lat_seg'] ?? 0);
$lat_hemi = trim($_POST['lat_hemi'] ?? '');
$lon_grau = (int)($_POST['lon_grau'] ?? 0);
$lon_min  = (int)($_POST['lon_min'] ?? 0);
$lon_seg  = (float)($_POST['lon_seg'] ?? 0);
$lon_hemi = trim($_POST['lon_hemi'] ?? '');

$lat = dmsToDecimal($lat_grau, $lat_min, $lat_seg, $lat_hemi);
$lon = dmsToDecimal($lon_grau, $lon_min, $lon_seg, $lon_hemi);

// Validações (adicione as que precisar)
if ($id <= 0) { flash('ID inválido.', 'error'); header('Location: listar_os.php'); exit; }
if ($cliente_id <= 0) { flash('Selecione um cliente válido.', 'error'); header('Location: editar_os.php?id='.$id); exit; }
if ($numero_os === '') { flash('Informe o número da OS.', 'error'); header('Location: editar_os.php?id='.$id); exit; }

// Data
function brToIsoDate($d){
  $d = trim((string)$d);
  if ($d === '') return null;
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/',$d)) return $d;
  if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/',$d)) { [$dd,$mm,$yy]=explode('/',$d); return "$yy-$mm-$dd"; }
  return null;
}
$prazo_iso = brToIsoDate($prazo_final);

// UPDATE
$sql = "UPDATE os SET
  cliente_id = :cliente_id,
  numero_os = :numero_os,
  fazenda_codigo = :fazenda_codigo,
  fazenda = :fazenda,
  area_ha = :area_ha,
  objetivo = :objetivo,
  produto_categoria = :produto_categoria,
  insumo_nome = :insumo_nome,
  lat_grau = :lat_grau, lat_min = :lat_min, lat_seg = :lat_seg, lat_hemi = :lat_hemi,
  lon_grau = :lon_grau, lon_min = :lon_min, lon_seg = :lon_seg, lon_hemi = :lon_hemi,
  lat = :lat, lon = :lon,
  prazo_final = :prazo_final,
  piloto_id = :piloto_id
WHERE id = :id";

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
  ':id'                => $id,
]);

flash('OS atualizada com sucesso!', 'success');
header('Location: listar_os.php'); exit;

// Helper
function dmsToDecimal($g,$m,$s,$hemi){
  $g=(float)strtr($g,',','.');
  $m=(float)strtr($m,',','.');
  $s=(float)strtr($s,',','.');
  $val = abs($g) + $m/60 + $s/3600;
  $hemi = strtoupper(trim((string)$hemi));
  if (in_array($hemi, ['S','W'])) $val = -$val;
  return $val ?: null;
}
