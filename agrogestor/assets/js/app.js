// Máscaras simples de exemplo (opcional)
document.addEventListener('input', (e) => {
  const el = e.target;
  if (el.name === 'cpf') {
    el.value = el.value.replace(/\D/g,'')
      .replace(/(\d{3})(\d)/,'$1.$2')
      .replace(/(\d{3})(\d)/,'$1.$2')
      .replace(/(\d{3})(\d{1,2})$/,'$1-$2')
      .slice(0,14);
  }
  if (el.name === 'telefone') {
    el.value = el.value.replace(/\D/g,'')
      .replace(/^(\d{2})(\d)/,'($1) $2')
      .replace(/(\d{5})(\d{4}).*/,'$1-$2');
  }
});
