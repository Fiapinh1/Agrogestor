<?php
// os/export_relatorio_pilotos_csv.php
require_once '../auth.php';  requireLogin();
require_once '../conexao.php';

$u = user();
if (($u['perfil'] ?? '') !== 'admin') {
  http_response_code(403); echo "Acesso negado."; exit;
}

function brToIsoDate(?string $d){
  $d = trim((string)$d);
  if ($d==='') return null;
  if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/',$d)) { [$dd,$mm,$yy]=explode('/',$d); return "$yy-$mm-$dd"; }
  return $d;
}

$qDe      = $_GET['de'] ?? '';   $qAte = $_GET['ate'] ?? '';
$qStatus  = $_GET['status'] ?? ''; 
$qCliente = $_GET['cliente'] ?? '';
$qPiloto  = (int)($_GET['piloto_id'] ?? 0);

$isoDe=brToIsoDate($qDe); $isoAte=brToIsoDate($qAte);

$where=[]; $params=[];
if ($isoDe && $isoAte){ $where[]="o.criado_em BETWEEN :de AND :ate"; $params[':de']=$isoDe; $params[':ate']=$isoAte; }
elseif($isoDe){ $where[]="o.criado_em >= :de"; $params[':de']=$isoDe; }
elseif($isoAte){ $where[]="o.criado_em <= :ate"; $params[':ate']=$isoAte; }

if ($qStatus!==''){ $where[]="o.status = :st"; $params[':st']=$qStatus; }
if ($qPiloto>0)   { $where[]="o.piloto_id = :pid"; $params[':pid']=$qPiloto; }

$joinCliente='';
if (trim($qCliente)!==''){
  $joinCliente="JOIN clientes c ON c.id=o.cliente_id";
  $where[]="(c.nome_fantasia LIKE :cli OR c.razao_social LIKE :cli)";
  $params[':cli']="%$qCliente%";
}
$W = $where ? ('WHERE '.implode(' AND ',$where)) : '';

$sql = "SELECT 
   col.nome AS piloto,
   COUNT(*) AS total_os,
   SUM(o.area_ha) AS area_total,
   SUM(CASE WHEN o.status='concluido' THEN 1 ELSE 0 END) AS os_concluidas,
   SUM(CASE WHEN o.status='concluido' THEN o.area_ha ELSE 0 END) AS area_concluida,
   AVG(o.area_ha) AS media_area
FROM os o
LEFT JOIN colaboradores col ON col.id=o.piloto_id
$joinCliente
$W
GROUP BY col.nome
ORDER BY area_concluida DESC, area_total DESC";

$st = $pdo->prepare($sql); $st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ranking_pilotos.csv');
$fp = fopen('php://output', 'w');
fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
fputcsv($fp, ['Piloto','OS','OS concl.','Área total (ha)','Área concl. (ha)','Média área (ha)'], ';');

foreach($rows as $r){
  fputcsv($fp, [
    $r['piloto'] ?? '—',
    (int)$r['total_os'],
    (int)$r['os_concluidas'],
    number_format((float)$r['area_total'],2,',','.'),
    number_format((float)$r['area_concluida'],2,',','.'),
    number_format((float)$r['media_area'],2,',','.')
  ], ';');
}
fclose($fp);
