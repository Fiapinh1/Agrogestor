<?php
require_once '../auth.php'; requireLogin(); requireRole('admin');
require_once '../conexao.php';

function gen_pass($len=10){
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$%';
  $s=''; for($i=0;$i<$len;$i++){ $s .= $chars[random_int(0, strlen($chars)-1)]; }
  return $s;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  flash('Método inválido.', 'danger');
  header('Location: cadastrar_usuario.php'); exit;
}

$colabId = (int)($_POST['colaborador_id'] ?? 0);
$perfil  = ($_POST['perfil'] ?? 'usuario');
$senhaIn = trim($_POST['senha'] ?? '');

if ($colabId <= 0) { flash('Selecione um colaborador.', 'danger'); header('Location: cadastrar_usuario.php'); exit; }

// Confere colaborador existe e ainda não tem usuário
$stmt = $pdo->prepare("SELECT c.id, c.nome, c.email
                         FROM colaboradores c
                    LEFT JOIN usuarios u ON u.colaborador_id = c.id
                        WHERE c.id = :id AND u.id IS NULL
                        LIMIT 1");
$stmt->execute([':id'=>$colabId]);
$colab = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$colab) {
  flash('Colaborador inválido ou já possui usuário.', 'danger');
  header('Location: cadastrar_usuario.php'); exit;
}

$email = trim($colab['email'] ?? '');
if ($email === '') {
  flash('O colaborador não possui e-mail cadastrado. Atualize o cadastro do colaborador primeiro.', 'danger');
  header('Location: ../colaboradores/editar.php?id='.$colabId); exit;
}

// Se senha em branco, gera uma temporária
if ($senhaIn === '') { $senhaIn = gen_pass(10); $temp = true; } else { $temp = false; }

$hash = password_hash($senhaIn, PASSWORD_DEFAULT);

// Cria usuário
try {
  $stmt = $pdo->prepare("INSERT INTO usuarios (colaborador_id, nome, email, senha, perfil, ativo, criado_em)
                         VALUES (:cid, :nome, :email, :senha, :perfil, 1, NOW())");
  $stmt->execute([
    ':cid'   => $colabId,
    ':nome'  => $colab['nome'],
    ':email' => $email,
    ':senha' => $hash,
    ':perfil'=> $perfil
  ]);

  $msg = 'Usuário criado com sucesso!';
  if ($temp) { $msg .= ' Senha temporária: <b>'.$senhaIn.'</b>'; }
  flash($msg, 'success');

  header('Location: listar_usuarios.php'); exit;

} catch (PDOException $e) {
  // colisão de e-mail/unique
  flash('Erro ao criar usuário: '.$e->getMessage(), 'danger');
  header('Location: cadastrar_usuario.php'); exit;
}
