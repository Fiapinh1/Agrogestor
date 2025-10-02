<?php
require_once '../auth.php'; requireLogin();
require_once '../conexao.php';

$qCat = trim($_GET['produto_categoria'] ?? '');
$qIns = trim($_GET['insumo'] ?? '');

$where = []; $params = [];
if ($qCat !== '') { $where[] = 'o.produto_categoria = :cat'; $params[':cat'] = $qCat; }
if ($qIns !== '') { $where[] = 'o.insumo_nome LIKE :ins'; $params[':ins'] = "%$qIns%"; }
$wsql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

$sql = "SELECT o.*, u.nome AS unidade FROM os o
        JOIN unidades u ON u.id = o.unidade_id
        $wsql
        ORDER BY o.criado_em DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fname = 'os_export_'.date('Ymd_His').'.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$fname.'"');

$out = fopen('php://output', 'w');
// Cabeçalho
fputcsv($out, [
  'id','numero_os','unidade','fazenda','area_ha','objetivo','produto_categoria','insumo_nome',
  'lat','lon','lat_grau','lat_min','lat_seg','lat_hemi','lon_grau','lon_min','lon_seg','lon_hemi','criado_em'
], ';');
// Linhas
foreach ($rows as $r) {
  fputcsv($out, [
    $r['id'], $r['numero_os'], $r['unidade'], $r['fazenda'], $r['area_ha'],
    $r['objetivo'], $r['produto_categoria'], $r['insumo_nome'],
    $r['lat'], $r['lon'],
    $r['lat_grau'], $r['lat_min'], $r['lat_seg'], $r['lat_hemi'],
    $r['lon_grau'], $r['lon_min'], $r['lon_seg'], $r['lon_hemi'],
    $r['criado_em']
  ], ';');
}
fclose($out);
exit;
