<?php
require_once '../auth.php'; requireLogin();
require_once '../conexao.php';

function change_os_status(PDO $pdo, int $osId, string $newStatus, ?int $userId, string $note = '') {
  $valid = ['novo','recebido','planejado','em_execucao','pausado','concluido','cancelado'];
  if (!in_array($newStatus, $valid, true)) {
    throw new RuntimeException('Status inválido');
  }

  // pega status atual
  $stmt = $pdo->prepare("SELECT status FROM os WHERE id=:id");
  $stmt->execute([':id'=>$osId]);
  $cur = $stmt->fetchColumn();
  if (!$cur) throw new RuntimeException('OS não encontrada');

  // atualiza status e datas chave
  $sets = "status=:s";
  $params = [':s'=>$newStatus, ':id'=>$osId];

  if ($newStatus === 'recebido')     { $sets .= ", recebido_em=IFNULL(recebido_em,NOW())"; }
  if ($newStatus === 'em_execucao')  { $sets .= ", iniciado_em=IFNULL(iniciado_em,NOW())"; }
  if ($newStatus === 'concluido')    { $sets .= ", finalizado_em=IFNULL(finalizado_em,NOW())"; }

  $pdo->prepare("UPDATE os SET $sets WHERE id=:id")->execute($params);

  // log
  $pdo->prepare("INSERT INTO os_status_log (os_id,status,user_id,note) VALUES (:o,:s,:u,:n)")
      ->execute([':o'=>$osId, ':s'=>$newStatus, ':u'=>$userId, ':n'=>$note]);
}

try {
  $osId = (int)($_POST['os_id'] ?? 0);
  $st   = trim($_POST['status'] ?? '');
  $note = trim($_POST['note'] ?? '');

  if ($osId <= 0) throw new RuntimeException('OS inválida');

  // permissões
  $u = user();
  $isAdmin  = ($u['perfil'] ?? '') === 'admin';
  $isPiloto = ($u['perfil'] ?? '') === 'usuario'; // ajuste se tiver perfis distintos

  // piloto só pode esses status:
  if ($isPiloto && !in_array($st, ['em_execucao','pausado','concluido'], true)) {
    throw new RuntimeException('Ação não permitida ao piloto');
  }

  change_os_status($pdo, $osId, $st, ($u['id'] ?? null), $note);
  flash('Status atualizado!', 'success');

} catch (Throwable $e) {
  flash('Erro: '.$e->getMessage(), 'danger');
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'listar_os.php'));
