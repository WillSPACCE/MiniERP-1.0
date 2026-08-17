document.addEventListener('DOMContentLoaded', function(){
  // foco automático no campo email
  var email = document.querySelector('input[name="email"]');
  if(email){ email.focus(); email.select(); }

  // pequeno efeito no título da página
  document.title = 'Mini ERP — Acesso';

  // opcional: animação do favicon alternando entre cores (se desejar)
  try{
    const icons = [
      '/assets/images/Favicon/favicon-32x32.png',
      '/assets/images/Favicon/favicon-16x16.png'
    ];
    let idx = 0;
    const link = document.querySelector('link[rel="icon"]') || document.createElement('link');
    link.rel = 'icon';
    document.head.appendChild(link);
    setInterval(()=>{
      link.href = icons[idx % icons.length];
      idx++;
    }, 2500);
  }catch(e){ /* ignore */ }
  
  // feedback no submit: desabilitar botão e mostrar spinner
  var form = document.querySelector('form[action="/?page=login"]') || document.querySelector('form');
  var btn = document.getElementById('loginBtn');
  if(form && btn){
    form.addEventListener('submit', function(){
      btn.disabled = true;
      btn.classList.add('loading');
      btn.textContent = 'Entrando...';
    });
  }

  // toggle de visibilidade da senha
  var toggle = document.getElementById('togglePassword');
  if(toggle){
    toggle.addEventListener('click', function(){
      var pwd = document.querySelector('input[name="senha"]');
      if(!pwd) return;
      if(pwd.type === 'password'){
        pwd.type = 'text';
        toggle.setAttribute('aria-label', 'Ocultar senha');
      } else {
        pwd.type = 'password';
        toggle.setAttribute('aria-label', 'Mostrar senha');
      }
    });
  }

  // slider sign in / sign up
  var signUpButton = document.getElementById('signUp');
  var signInButton = document.getElementById('signIn');
  var container = document.getElementById('container');
  if(signUpButton && signInButton && container){
    signUpButton.addEventListener('click', function(){ container.classList.add('right-panel-active'); });
    signInButton.addEventListener('click', function(){ container.classList.remove('right-panel-active'); });
  }

  // form loading state for signup as well
  var signupForm = document.querySelector('form[action="/?page=save_usuario"]');
  var signupBtn = document.getElementById('signupBtnForm');
  if(signupForm && signupBtn){
    signupForm.addEventListener('submit', function(){
      signupBtn.disabled = true;
      signupBtn.classList.add('loading');
      signupBtn.textContent = 'Registrando...';
    });
  }
});
