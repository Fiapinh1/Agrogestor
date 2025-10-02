<?php
require '../auth.php';
requireLogin();
requireRole('admin');
require '../conexao.php';

$id = (int)($_POST['id'] ?? 0);
$nome  = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';
$perfil = $_POST['perfil'] ?? 'usuario';
$ativo  = (int)($_POST['ativo'] ?? 1);

if ($id > 0 && $nome && $email) {
  if ($senha) {
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE usuarios SET nome=:n,email=:e,senha_hash=:s,perfil=:p,ativo=:a WHERE id=:id");
    $stmt->execute([':n'=>$nome,':e'=>$email,':s'=>$hash,':p'=>$perfil,':a'=>$ativo,':id'=>$id]);
  } else {
    $stmt = $pdo->prepare("UPDATE usuarios SET nome=:n,email=:e,perfil=:p,ativo=:a WHERE id=:id");
    $stmt->execute([':n'=>$nome,':e'=>$email,':p'=>$perfil,':a'=>$ativo,':id'=>$id]);
  }
}

flash('Usuário atualizado com sucesso!', 'success');
header('Location: listar_usuarios.php');
exit;
