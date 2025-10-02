<?php
require_once '../auth.php';  requireLogin();  requireRole('admin');
require_once '../conexao.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
  $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
  $stmt->execute([':id'=>$id]);
  flash('Cliente excluído com sucesso!', 'warning');
}
header('Location: listar_clientes.php'); exit;
