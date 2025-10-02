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

// Cores por categoria (KML usa aabbggrr em hex ARGB)
$kmlColors = [
  'herbicida'    => 'ff00a000', // verde
  'inseticida'   => 'ff0000a0', // vermelho
  'fungicida'    => 'ffa00000', // azul
  'fertilizante' => 'ffa0a000', // ciano-ish
  'maturador'    => 'ff00a0ff', // laranja
  'default'      => 'ffa000a0'
];

$fname = 'os_export_'.date('Ymd_His').'.kml';
header('Content-Type: application/vnd.google-earth.kml+xml; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$fname.'"');

echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<Document>
  <name>OS - AgroGestor</name>

  <!-- Estilos -->
  <?php foreach ($kmlColors as $key=>$color): ?>
  <Style id="s_<?= $key ?>">
    <IconStyle>
      <color><?= $color ?></color>
      <scale>1.1</scale>
      <Icon>
        <href>http://maps.google.com/mapfiles/kml/paddle/wht-blank.png</href>
      </Icon>
    </IconStyle>
  </Style>
  <?php endforeach; ?>

  <?php foreach ($rows as $r): 
    $cat = strtolower($r['produto_categoria']);
    $style = isset($kmlColors[$cat]) ? "s_$cat" : "s_default";
    $name = htmlspecialchars("OS {$r['numero_os']} - {$r['fazenda']}", ENT_QUOTES, 'UTF-8');
    $desc = htmlspecialchars(
      "Unidade: {$r['unidade']}\n".
      "Objetivo: {$r['objetivo']}\n".
      "Produto: {$r['produto_categoria']}\n".
      "Insumo: {$r['insumo_nome']}\n".
      "Área (ha): {$r['area_ha']}",
      ENT_QUOTES, 'UTF-8'
    );
    $lon = $r['lon']; $lat = $r['lat'];
  ?>
  <Placemark>
    <name><?= $name ?></name>
    <styleUrl>#<?= $style ?></styleUrl>
    <description><?= $desc ?></description>
    <Point><coordinates><?= $lon ?>,<?= $lat ?>,0</coordinates></Point>
  </Placemark>
  <?php endforeach; ?>
</Document>
</kml>
