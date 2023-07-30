var y=Object.defineProperty;var T=(e,n,t)=>n in e?y(e,n,{enumerable:!0,configurable:!0,writable:!0,value:t}):e[n]=t;var f=(e,n,t)=>(T(e,typeof n!="symbol"?n+"":n,t),t);function I(e){return new TextEncoder().encode(e)}function u(e){const n=new Uint8Array(e);let t="";for(const i of n)t+=String.fromCharCode(i);return btoa(t).replace(/\+/g,"-").replace(/\//g,"_").replace(/=/g,"")}function w(e){const n=e.replace(/-/g,"+").replace(/_/g,"/"),t=(4-n.length%4)%4,a=n.padEnd(n.length+t,"="),i=atob(a),r=new ArrayBuffer(i.length),s=new Uint8Array(r);for(let c=0;c<i.length;c++)s[c]=i.charCodeAt(c);return r}function g(){return(window==null?void 0:window.PublicKeyCredential)!==void 0&&typeof window.PublicKeyCredential=="function"}function p(e){const{id:n}=e;return{...e,id:w(n),transports:e.transports}}function A(e){return e==="localhost"||/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i.test(e)}class o extends Error{constructor({message:t,code:a,cause:i,name:r}){super(t,{cause:i});f(this,"code");this.name=r??i.name,this.code=a}}function C({error:e,options:n}){var a,i;const{publicKey:t}=n;if(!t)throw Error("options was missing required publicKey property");if(e.name==="AbortError"){if(n.signal instanceof AbortSignal)return new o({message:"Registration ceremony was sent an abort signal",code:"ERROR_CEREMONY_ABORTED",cause:e})}else if(e.name==="ConstraintError"){if(((a=t.authenticatorSelection)==null?void 0:a.requireResidentKey)===!0)return new o({message:"Discoverable credentials were required but no available authenticator supported it",code:"ERROR_AUTHENTICATOR_MISSING_DISCOVERABLE_CREDENTIAL_SUPPORT",cause:e});if(((i=t.authenticatorSelection)==null?void 0:i.userVerification)==="required")return new o({message:"User verification was required but no available authenticator supported it",code:"ERROR_AUTHENTICATOR_MISSING_USER_VERIFICATION_SUPPORT",cause:e})}else{if(e.name==="InvalidStateError")return new o({message:"The authenticator was previously registered",code:"ERROR_AUTHENTICATOR_PREVIOUSLY_REGISTERED",cause:e});if(e.name==="NotAllowedError")return new o({message:e.message,code:"ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY",cause:e});if(e.name==="NotSupportedError")return t.pubKeyCredParams.filter(s=>s.type==="public-key").length===0?new o({message:'No entry in pubKeyCredParams was of type "public-key"',code:"ERROR_MALFORMED_PUBKEYCREDPARAMS",cause:e}):new o({message:"No available authenticator supported any of the specified pubKeyCredParams algorithms",code:"ERROR_AUTHENTICATOR_NO_SUPPORTED_PUBKEYCREDPARAMS_ALG",cause:e});if(e.name==="SecurityError"){const r=window.location.hostname;if(A(r)){if(t.rp.id!==r)return new o({message:`The RP ID "${t.rp.id}" is invalid for this domain`,code:"ERROR_INVALID_RP_ID",cause:e})}else return new o({message:`${window.location.hostname} is an invalid domain`,code:"ERROR_INVALID_DOMAIN",cause:e})}else if(e.name==="TypeError"){if(t.user.id.byteLength<1||t.user.id.byteLength>64)return new o({message:"User ID was not between 1 and 64 characters",code:"ERROR_INVALID_USER_ID_LENGTH",cause:e})}else if(e.name==="UnknownError")return new o({message:"The authenticator was unable to process the specified options, or could not create a new credential",code:"ERROR_AUTHENTICATOR_GENERAL_ERROR",cause:e})}return e}class S{constructor(){f(this,"controller")}createNewAbortSignal(){if(this.controller){const t=new Error("Cancelling existing WebAuthn API call for new one");t.name="AbortError",this.controller.abort(t)}const n=new AbortController;return this.controller=n,n.signal}}const m=new S,O=["cross-platform","platform"];function _(e){if(e&&!(O.indexOf(e)<0))return e}async function v(e){var d;if(!g())throw new Error("WebAuthn is not supported in this browser");const t={publicKey:{...e,challenge:w(e.challenge),user:{...e.user,id:I(e.user.id)},excludeCredentials:(d=e.excludeCredentials)==null?void 0:d.map(p)}};t.signal=m.createNewAbortSignal();let a;try{a=await navigator.credentials.create(t)}catch(R){throw C({error:R,options:t})}if(!a)throw new Error("Registration was not completed");const{id:i,rawId:r,response:s,type:c}=a;let l;return typeof s.getTransports=="function"&&(l=s.getTransports()),{id:i,rawId:u(r),response:{attestationObject:u(s.attestationObject),clientDataJSON:u(s.clientDataJSON),transports:l},type:c,clientExtensionResults:a.getClientExtensionResults(),authenticatorAttachment:_(a.authenticatorAttachment)}}function D(e){return new TextDecoder("utf-8").decode(e)}async function P(){const e=window.PublicKeyCredential;return e.isConditionalMediationAvailable!==void 0&&e.isConditionalMediationAvailable()}function N({error:e,options:n}){const{publicKey:t}=n;if(!t)throw Error("options was missing required publicKey property");if(e.name==="AbortError"){if(n.signal instanceof AbortSignal)return new o({message:"Authentication ceremony was sent an abort signal",code:"ERROR_CEREMONY_ABORTED",cause:e})}else{if(e.name==="NotAllowedError")return new o({message:e.message,code:"ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY",cause:e});if(e.name==="SecurityError"){const a=window.location.hostname;if(A(a)){if(t.rpId!==a)return new o({message:`The RP ID "${t.rpId}" is invalid for this domain`,code:"ERROR_INVALID_RP_ID",cause:e})}else return new o({message:`${window.location.hostname} is an invalid domain`,code:"ERROR_INVALID_DOMAIN",cause:e})}else if(e.name==="UnknownError")return new o({message:"The authenticator was unable to process the specified options, or could not create a new assertion signature",code:"ERROR_AUTHENTICATOR_GENERAL_ERROR",cause:e})}return e}async function K(e,n=!1){var E,h;if(!g())throw new Error("WebAuthn is not supported in this browser");let t;((E=e.allowCredentials)==null?void 0:E.length)!==0&&(t=(h=e.allowCredentials)==null?void 0:h.map(p));const a={...e,challenge:w(e.challenge),allowCredentials:t},i={};if(n){if(!await P())throw Error("Browser does not support WebAuthn autofill");if(document.querySelectorAll("input[autocomplete*='webauthn']").length<1)throw Error('No <input> with `"webauthn"` in its `autocomplete` attribute was detected');i.mediation="conditional",a.allowCredentials=[]}i.publicKey=a,i.signal=m.createNewAbortSignal();let r;try{r=await navigator.credentials.get(i)}catch(b){throw N({error:b,options:i})}if(!r)throw new Error("Authentication was not completed");const{id:s,rawId:c,response:l,type:d}=r;let R;return l.userHandle&&(R=D(l.userHandle)),{id:s,rawId:u(c),response:{authenticatorData:u(l.authenticatorData),clientDataJSON:u(l.clientDataJSON),signature:u(l.signature),userHandle:R},type:d,clientExtensionResults:r.getClientExtensionResults(),authenticatorAttachment:_(r.authenticatorAttachment)}}export{P as a,g as b,K as c,v as s};
//# sourceMappingURL=index-5360b657.js.map