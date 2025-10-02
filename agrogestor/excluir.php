<?php
// excluir.php (COLABORADORES) — somente ADMIN pode excluir
require_once 'auth.php';
requireLogin();
requireRole('admin'); // ⬅️ restringe a exclusão a administradores
require_once 'conexao.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  if (function_exists('flash')) { flash('ID inválido.', 'error'); }
  header('Location: listar.php');
  exit;
}

try {
  $stmt = $pdo->prepare('DELETE FROM colaboradores WHERE id = :id');
  $stmt->execute([':id' => $id]);

  if (function_exists('flash')) { flash('Colaborador excluído com sucesso!', 'warning'); }
  header('Location: listar.php');
  exit;

} catch (PDOException $e) {
  if (function_exists('flash')) { flash('Não foi possível excluir: '.$e->getMessage(), 'error'); }
  header('Location: listar.php');
  exit;
}
