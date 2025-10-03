</div> <!-- fecha .container aberta no header -->

<footer class="bg-white border-top py-3 mt-4">
  <div class="container d-flex justify-content-between align-items-center">
    <span class="text-muted small">
      &copy; <?= date('Y') ?> AgroGestor — Sistema de Gestão
    </span>
    <span class="text-muted small">
      <i class="bi bi-code-slash"></i> Desenvolvido por Pulvion Tech
    </span>
  </div>
</footer>

<!-- Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Seu JS (respeitando a pasta atual) -->
<?php
// reutiliza a mesma detecção do header:
$path = $_SERVER['PHP_SELF'] ?? '';
$ROOT = str_contains($path, '/usuarios/') ? '../' : '';
?>
<script src="/agrogestor/assets/js/app.js"></script>

<?php
// fallback para evitar warnings se a página ainda não definiu $isAdmin
$isAdmin = $isAdmin ?? ((function_exists('user') ? (user()['perfil'] ?? '') : '') === 'admin');
?>

<?php if (!$isAdmin): ?>
<!-- Modal: Ver Colaborador -->
<div class="modal fade" id="modalVerColab" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Colaborador</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <dt class="col-sm-3">Nome</dt>       <dd class="col-sm-9" id="c-nome"></dd>
          <dt class="col-sm-3">Cargo</dt>      <dd class="col-sm-9" id="c-cargo"></dd>
          <dt class="col-sm-3">E-mail</dt>     <dd class="col-sm-9" id="c-email"></dd>
          <dt class="col-sm-3">Telefone</dt>   <dd class="col-sm-9" id="c-telefone"></dd>
          <dt class="col-sm-3">Setor</dt>      <dd class="col-sm-9" id="c-setor"></dd>
          <dt class="col-sm-3">Frente</dt>     <dd class="col-sm-9" id="c-frente"></dd>
          <dt class="col-sm-3">Regime</dt>     <dd class="col-sm-9" id="c-regime"></dd>
          <dt class="col-sm-3">Admissão</dt>   <dd class="col-sm-9" id="c-admissao"></dd>
          <dt class="col-sm-3">Situação</dt>   <dd class="col-sm-9" id="c-situacao"></dd>
        </dl>
        <small class="text-muted">* Dados sigilosos (CPF, salário, banco, etc.) não são exibidos.</small>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.ver-colab').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const id = btn.dataset.id;
    try{
      const r = await fetch('ver_colaborador.php?id='+id, {headers:{'X-Requested-With':'fetch'}});
      if(!r.ok) throw new Error('Falha ao carregar');
      const d = await r.json();

      const set = (sel, val) => {
        const el = document.querySelector(sel);
        el.textContent = val ?? '—';
      };
      set('#c-nome', d.nome);
      set('#c-cargo', d.cargo);
      set('#c-email', d.email);
      set('#c-telefone', d.telefone);
      set('#c-setor', d.setor);
      set('#c-frente', d.frente);
      set('#c-regime', d.regime);
      set('#c-admissao', d.admissao_br);
      set('#c-situacao', d.situacao);

      new bootstrap.Modal(document.getElementById('modalVerColab')).show();
    }catch(e){
      alert('Não foi possível abrir os dados.');
    }
  });
});
</script>
<?php endif; ?>
</body>
</html>
