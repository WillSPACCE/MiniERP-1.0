(()=>{
  const normalize=value=>String(value??'').replace(/\D/g,'');
  const isValid=value=>{const cpf=normalize(value);if(cpf.length!==11||/^(\d)\1{10}$/.test(cpf))return false;for(let digit=9;digit<11;digit++){let sum=0;for(let i=0;i<digit;i++)sum+=Number(cpf[i])*((digit+1)-i);let check=(10*sum)%11;if(check===10)check=0;if(Number(cpf[digit])!==check)return false}return true};
  globalThis.MiniErpCpfValidator=Object.freeze({normalize,isValid});
})();
