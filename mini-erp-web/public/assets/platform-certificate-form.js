document.addEventListener('DOMContentLoaded',()=>{
 const panel=document.querySelector('#certificate');if(!panel)return;
 const upload=panel.querySelector('form[enctype="multipart/form-data"]');
 const input=upload?.querySelector('input[type="file"][name="certificate"]');
 if(input){
  const label=input.closest('label');label?.classList.add('platform-certificate-file');
  const hint=document.createElement('span');hint.className='platform-certificate-file__hint';hint.innerHTML='<b>Selecionar certificado</b><small>Clique aqui para escolher um arquivo .PFX ou .P12</small>';
  input.before(hint);
  input.addEventListener('change',()=>{const file=input.files?.[0];hint.querySelector('b').textContent=file?'Arquivo selecionado':'Selecionar certificado';hint.querySelector('small').textContent=file?file.name:'Clique aqui para escolher um arquivo .PFX ou .P12'});
 }
 const link=document.querySelector('#saved-certificate-download');
 if(link&&upload){const actions=upload.querySelector('.form-actions');actions?.querySelector('button[disabled]')?.remove();link.hidden=false;actions?.append(link);}
});
