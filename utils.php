<?php
function h($str) {
return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}


function brDate($date) {
if (!$date) return '';
$p = explode('-', $date);
return count($p) === 3 ? "$p[2]/$p[1]/$p[0]" : $date;
}