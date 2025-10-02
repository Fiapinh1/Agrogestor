<?php
require_once '../auth.php'; requireLogin(); requireRole('admin');
require_once '../conexao.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { flash('ID inválido.', 'error'); header('Location: listar_os.php'); exit; }

try {
  $stmt = $pdo->prepare("DELETE FROM os WHERE id = :id");
  $stmt->execute([':id'=>$id]);
  flash('OS excluída com sucesso!', 'warning');
} catch (PDOException $e) {
  flash('Não foi possível excluir: '.$e->getMessage(), 'error');
}
header('Location: listar_os.php'); exit;
