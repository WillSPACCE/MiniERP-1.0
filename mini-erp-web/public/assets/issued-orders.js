(() => {
  const root = document.querySelector('[data-issued-orders]');
  if (!root) return;
  const csrf = root.dataset.csrf || '';
  let deleteId = 0;

  const toast = (message, error = false) => {
    const node = document.createElement('div');
    node.className = `issued-toast${error ? ' error' : ''}`;
    node.setAttribute('role', 'status');
    node.textContent = message;
    document.body.append(node);
    setTimeout(() => node.remove(), 4500);
  };
  const request = async (action, orderId, extra = {}) => {
    const body = new URLSearchParams({csrf, order_action: action, order_id: String(orderId), ...extra});
    const response = await fetch('/issued_order_action.php', {method: 'POST', body, credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}});
    const data = await response.json().catch(() => ({success: false, message: 'Resposta inválida do servidor.'}));
    if (!response.ok || !data.success) throw Object.assign(new Error(data.message || 'Não foi possível concluir.'), {data});
    return data;
  };
  const openModal = dialog => window.AppModal?.open ? window.AppModal.open(dialog) : dialog.showModal();
  const closeModal = dialog => window.AppModal?.close ? window.AppModal.close(dialog) : dialog.close();

  root.addEventListener('click', async event => {
    const action = event.target.closest('[data-issued-action]');
    if (!action) return;
    event.preventDefault(); event.stopPropagation();
    if (action.disabled) return;
    const type = action.dataset.issuedAction;
    const orderId = Number(action.dataset.orderId);
    if (type === 'delete') {
      deleteId = orderId;
      const dialog = document.querySelector('#issued-delete-modal');
      dialog.querySelector('[data-issued-delete-label]').textContent = action.dataset.orderLabel || `Pedido #${orderId}`;
      openModal(dialog); return;
    }
    if (type === 'clone' && !confirm(`Duplicar o pedido #${orderId}?`)) return;
    if (type === 'issue' && !confirm('Preparar este pedido para emissão fiscal? Nenhum envio à SEFAZ será feito nesta etapa.')) return;
    const previewWindow = type === 'preview' ? window.open('', '_blank') : null;
    if (previewWindow) previewWindow.document.write('<!doctype html><meta charset="utf-8"><title>Prévia fiscal</title><p style="font:16px system-ui;padding:28px">Gerando prévia fiscal…</p>');
    action.disabled = true; action.classList.add('loading');
    try {
      const data = await request(type, orderId, type === 'issue' ? {idempotency_key: action.dataset.idempotency || ''} : {});
      toast(data.message);
      if (previewWindow && data.preview_url) previewWindow.location.href = data.preview_url;
      if (data.redirect) setTimeout(() => { window.location.href = data.redirect; }, type === 'preview' ? 250 : 500);
    } catch (error) {
      toast(error.message, true);
      if (previewWindow) { previewWindow.document.body.innerHTML = `<p style="font:16px system-ui;padding:28px">${error.message}</p>`; }
      if (error.data?.redirect) setTimeout(() => { window.location.href = error.data.redirect; }, 900);
    } finally { action.disabled = false; action.classList.remove('loading'); }
  });
  root.querySelectorAll('.issued-row').forEach(row => {
    const navigate = event => { if (!event.target.closest('a,button,details,summary') && (!event.type.includes('key') || ['Enter', ' '].includes(event.key))) window.location.href = row.dataset.editUrl; };
    row.addEventListener('click', navigate); row.addEventListener('keydown', navigate);
  });
  document.querySelector('[data-confirm-issued-delete]')?.addEventListener('click', async event => {
    if (!deleteId) return;
    event.currentTarget.disabled = true;
    try { const data = await request('delete', deleteId); closeModal(document.querySelector('#issued-delete-modal')); toast(data.message); setTimeout(() => window.location.href = data.redirect, 450); }
    catch (error) { toast(error.message, true); }
    finally { event.currentTarget.disabled = false; }
  });
})();
