(()=>{
 document.querySelector('[data-stock-panel="lots"]')?.prepend(document.querySelector('#stock-pending-lots'));
 const tabs=[...document.querySelectorAll('[data-stock-tab]')],panels=[...document.querySelectorAll('[data-stock-panel]')];
 const modal=document.querySelector('[data-stock-modal]'),form=modal?.querySelector('[data-stock-form]'),fields=modal?.querySelector('[data-stock-fields]'),title=modal?.querySelector('[data-stock-modal-title]');
 let allProducts=[];try{allProducts=JSON.parse(document.querySelector('#stock-product-options')?.textContent||'[]')}catch{}
 const show=key=>{tabs.forEach(tab=>tab.classList.toggle('active',tab.dataset.stockTab===key));panels.forEach(panel=>panel.hidden=panel.dataset.stockPanel!==key)};
 show(new URLSearchParams(location.search).get('stock_tab')||'products');
 tabs.forEach(tab=>tab.onclick=()=>show(tab.dataset.stockTab));
 document.querySelectorAll('[data-stock-filter-product]').forEach(button=>button.onclick=()=>{show('lots');document.querySelectorAll('[data-stock-product]').forEach(row=>row.hidden=row.dataset.stockProduct!==button.dataset.stockFilterProduct)});
 const open=(action,button=null)=>{
  form.reset();form.querySelector('[name=stock_action]').value=action==='new-lot'?'create_lot':action;form.querySelector('[name=lot_id]').value=button?.dataset.lotId||'';form.querySelector('[name=blocked]').value=button?.dataset.blocked||'';
  const template=document.querySelector(action==='receive'?'#stock-new-lot-fields':'#stock-'+action+'-fields');fields.replaceChildren(template.content.cloneNode(true));
  if(action==='new-lot'||action==='receive'){const select=fields.querySelector('[name=product_id]');if(select){select.replaceChildren(new Option('Selecione',''));allProducts.forEach(product=>select.add(new Option(product.name,String(product.id))))}}
  if(action==='receive'){fields.querySelector('[name=manufactured_at]')?.closest('label')?.remove();fields.querySelector('[name=expires_at]')?.closest('label')?.remove();const quantity=fields.querySelector('[name=quantity]');if(quantity){quantity.min='0.0001';quantity.closest('label').firstChild.textContent='Quantidade da entrada';}const reason=fields.querySelector('[name=reason]');if(reason){reason.value='';reason.placeholder='Ex.: NF de entrada 1234';}}
  fields.querySelector('[data-stock-lot-label]')?.replaceChildren(document.createTextNode('Lote: '+(button?.dataset.lotCode||'')));
  const names={'new-lot':'Novo lote','receive':'Entrada de estoque','block':button?.dataset.blocked==='1'?'Bloquear lote para venda':'Liberar lote para venda','transfer':'Transferir lote','adjust':'Registrar perda ou rendimento'};title.textContent=names[action]||'Estoque';modal.showModal();document.body.classList.add('app-modal-open');
 };
 document.querySelectorAll('[data-stock-open]').forEach(button=>button.onclick=()=>open(button.dataset.stockOpen,button));document.querySelectorAll('[data-stock-operation]').forEach(button=>button.onclick=()=>open(button.dataset.stockOperation,button));modal?.querySelectorAll('[data-stock-close]').forEach(button=>button.onclick=()=>modal.close());modal?.addEventListener('close',()=>document.body.classList.remove('app-modal-open'));
})();
