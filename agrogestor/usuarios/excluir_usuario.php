<?php
require '../auth.php';
requireLogin();
requireRole('admin');
require '../conexao.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
  $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
  $stmt->execute([':id'=>$id]);
}

flash('Usuário excluído com sucesso!', 'success');
header('Location: listar_usuarios.php');
exit;
