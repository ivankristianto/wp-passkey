import{b as o,s as a}from"./index-5360b657.js";async function n(){let t;try{const e=await wp.apiFetch({path:"/wp-passkey/v1/register-request",method:"POST"});t=await a(e)}catch(e){throw e}try{(await wp.apiFetch({path:"/wp-passkey/v1/register-response",method:"POST",data:t})).status==="verified"&&window.location.reload()}catch(e){throw e}}wp.domReady(()=>{const t=document.querySelector(".wp-register-new-passkey"),e=document.querySelector(".wp-register-passkey--message");if(!(!t||!e)){if(!o()){t.style.display="none";return}t.addEventListener("click",async()=>{try{await n()}catch(r){r.name==="InvalidStateError"?e.innerText=wp.i18n.__("Error: Authenticator was probably already registered by you","wp-passkey"):e.innerText=`Error: ${r.message}`,e.classList.add("error")}})}});async function i(t){if(t.preventDefault(),!window.confirm(wp.i18n.__("Are you sure you want to revoke this passkey? This action cannot be undone.","wp-passkey")))return;const r=t.target.dataset.id;try{(await wp.apiFetch({path:"/wp-passkey/v1/revoke",method:"POST",data:{fingerprint:r}})).status==="success"&&window.location.reload()}catch(s){throw s}}wp.domReady(()=>{const t=document.querySelectorAll(".wp-passkey-list-table button.delete");t&&t.forEach(e=>{e.addEventListener("click",i)})});
//# sourceMappingURL=user-profile-b76582dd.js.map
