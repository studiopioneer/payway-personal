import{v as j,r as h,o as c,c as p,a as e,b as f,au as K,n as k,d as m,t as y,f as _,B as Y,y as z,s as D,w as G,aE as H,A as w,m as R,h as J,g as S,F as $,p as B}from"../index-DNz3K7NR.js";import{a as A}from"./index-HW7Yk54d.js";import{s as Q,a as O,b as X}from"./index-ClyZ4Ber.js";import{s as Z}from"./index-3LIy-uA0.js";import"./index-qFyJwsfU.js";function N(t){var a,n,s;const i=(a=t==null?void 0:t.response)==null?void 0:a.status;if(i===429)return"Слишком много запросов. Подождите минуту и попробуйте снова.";if(i===402)return"Недостаточно средств на балансе.";if(i===401)return"Сессия истекла. Пожалуйста, войдите снова.";if(i===503||i===502)return"Сервис временно недоступен. Попробуйте позже.";const l=(s=(n=t==null?void 0:t.response)==null?void 0:n.data)==null?void 0:s.message;return l||t.message||"Произошла ошибка. Попробуйте позже."}const T=j("audit",()=>{const t=h(null),i=h("idle"),l=h(null),a=h(null),n=h(!1),s=h(null);let r=null;async function o(d){t.value=null,l.value=null,a.value=null,n.value=!1,s.value=null,i.value="pending";try{const x=await A.post("/audit/start",{channel_url:d});t.value=x.data.audit_id,u()}catch(x){i.value="error",a.value=N(x)}}function u(){r=setTimeout(v,3e3)}async function v(){if(t.value)try{const d=await A.get("/audit/"+t.value+"/status"),x=d.data.status;x==="done"?(i.value="done",l.value=d.data.report||null,n.value=!!d.data.is_paid):x==="error"?(i.value="error",a.value=d.data.message||"Ошибка анализа канала."):u()}catch(d){i.value="error",a.value=N(d)}}async function b(){if(!t.value)return;const d=await A.post("/audit/"+t.value+"/unlock");return d.data.unlocked&&(n.value=!0,s.value=d.data,l.value=d.data.report||l.value),d.data}function g(){r&&clearTimeout(r),t.value=null,i.value="idle",l.value=null,a.value=null,n.value=!1,s.value=null}return{auditId:t,status:i,report:l,error:a,isPaid:n,unlockInfo:s,startAudit:o,pollStatus:v,unlockReport:b,reset:g}}),ee={class:"surface-card border-round-xl shadow-1 p-4",style:{"max-width":"560px"}},te={class:"flex flex-column gap-2 mb-3"},se={key:0,class:"p-error"},ne={__name:"AuditInputForm",props:{loading:{type:Boolean,default:!1}},emits:["submit"],setup(t,{emit:i}){const l=t,a=i,n=h(""),s=h("");function r(){s.value="";const o=n.value.trim();if(!o){s.value="Введите URL канала";return}if(!o.includes("youtube.com")&&!o.includes("youtu.be")){s.value="Укажите корректный URL YouTube-канала";return}a("submit",o)}return(o,u)=>(c(),p("div",ee,[u[2]||(u[2]=e("p",{class:"text-600 mt-0 mb-4"},"Введите URL YouTube-канала для анализа. Результат будет готов через 1–2 минуты.",-1)),e("div",te,[u[1]||(u[1]=e("label",{class:"font-medium text-900"},"URL канала",-1)),f(m(Q),{modelValue:n.value,"onUpdate:modelValue":u[0]||(u[0]=v=>n.value=v),placeholder:"https://www.youtube.com/@channel",class:k([{"p-invalid":s.value},"w-full"]),onKeyup:K(r,["enter"])},null,8,["modelValue","class"]),s.value?(c(),p("small",se,y(s.value),1)):_("",!0)]),f(m(O),{label:"Начать аудит",icon:"pi pi-search",class:"w-full",loading:l.loading,onClick:r},null,8,["loading"])]))}};var re=`
    .p-progressspinner {
        position: relative;
        margin: 0 auto;
        width: 100px;
        height: 100px;
        display: inline-block;
    }

    .p-progressspinner::before {
        content: '';
        display: block;
        padding-top: 100%;
    }

    .p-progressspinner-spin {
        height: 100%;
        transform-origin: center center;
        width: 100%;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        margin: auto;
        animation: p-progressspinner-rotate 2s linear infinite;
    }

    .p-progressspinner-circle {
        stroke-dasharray: 89, 200;
        stroke-dashoffset: 0;
        stroke: dt('progressspinner.colorOne');
        animation:
            p-progressspinner-dash 1.5s ease-in-out infinite,
            p-progressspinner-color 6s ease-in-out infinite;
        stroke-linecap: round;
    }

    @keyframes p-progressspinner-rotate {
        100% {
            transform: rotate(360deg);
        }
    }
    @keyframes p-progressspinner-dash {
        0% {
            stroke-dasharray: 1, 200;
            stroke-dashoffset: 0;
        }
        50% {
            stroke-dasharray: 89, 200;
            stroke-dashoffset: -35px;
        }
        100% {
            stroke-dasharray: 89, 200;
            stroke-dashoffset: -124px;
        }
    }
    @keyframes p-progressspinner-color {
        100%,
        0% {
            stroke: dt('progressspinner.color.one');
        }
        40% {
            stroke: dt('progressspinner.color.two');
        }
        66% {
            stroke: dt('progressspinner.color.three');
        }
        80%,
        90% {
            stroke: dt('progressspinner.color.four');
        }
    }
`,oe={root:"p-progressspinner",spin:"p-progressspinner-spin",circle:"p-progressspinner-circle"},ie=Y.extend({name:"progressspinner",style:re,classes:oe}),le={name:"BaseProgressSpinner",extends:X,props:{strokeWidth:{type:String,default:"2"},fill:{type:String,default:"none"},animationDuration:{type:String,default:"2s"}},style:ie,provide:function(){return{$pcProgressSpinner:this,$parentInstance:this}}},W={name:"ProgressSpinner",extends:le,inheritAttrs:!1,computed:{svgStyle:function(){return{"animation-duration":this.animationDuration}}}},ae=["fill","stroke-width"];function ue(t,i,l,a,n,s){return c(),p("div",z({class:t.cx("root"),role:"progressbar"},t.ptmi("root")),[(c(),p("svg",z({class:t.cx("spin"),viewBox:"25 25 50 50",style:s.svgStyle},t.ptm("spin")),[e("circle",z({class:t.cx("circle"),cx:"50",cy:"50",r:"20",fill:t.fill,"stroke-width":t.strokeWidth,strokeMiterlimit:"10"},t.ptm("circle")),null,16,ae)],16))],16)}W.render=ue;const ce={class:"surface-card border-round-xl shadow-1 p-4",style:{"max-width":"560px"}},de={key:0,class:"flex flex-column align-items-center gap-3 py-3"},pe={key:1,class:"flex flex-column align-items-center gap-3 py-3"},me={class:"text-900 font-semibold m-0"},fe={__name:"AuditProgress",emits:["retry"],setup(t,{emit:i}){const l=i,a=T(),{status:n,error:s}=D(a),r=["Загружаем данные канала…","Анализируем видео…","Строим отчёт…","Финализируем…"],o=h(r[0]);let u=null;function v(){let g=0;u=setInterval(()=>{g=(g+1)%r.length,o.value=r[g]},2500)}function b(){u&&(clearInterval(u),u=null)}return G(n,g=>{g==="pending"?v():b()},{immediate:!0}),H(b),(g,d)=>(c(),p("div",ce,[m(n)==="pending"?(c(),p("div",de,[f(m(W),{style:{width:"56px",height:"56px"},strokeWidth:"4"}),d[1]||(d[1]=e("p",{class:"text-700 font-medium m-0"},"Анализируем канал…",-1)),f(m(Z),{value:o.value,severity:"info"},null,8,["value"])])):m(n)==="error"?(c(),p("div",pe,[d[2]||(d[2]=e("i",{class:"pi pi-times-circle text-red-500",style:{"font-size":"3rem"}},null,-1)),e("p",me,y(m(s)||"Произошла ошибка"),1),f(m(O),{label:"Попробовать снова",icon:"pi pi-refresh",severity:"secondary",onClick:d[0]||(d[0]=x=>l("retry"))})])):_("",!0)]))}},ve={class:"audit-result"},ge={class:"grid"},he={class:"col-12"},ye={class:"surface-card border-round-xl shadow-1 p-4 mb-3"},xe={class:"text-700 m-0 line-height-3"},be={class:"col-12 md:col-4"},ke={class:"flex align-items-center gap-2 mb-3"},_e={class:"text-700 m-0 line-height-3 text-sm"},we={class:"col-12 md:col-4"},$e={class:"flex align-items-center gap-2 mb-3"},Se={class:"text-700 m-0 line-height-3 text-sm"},ze={class:"col-12 md:col-4"},Be={class:"flex align-items-center gap-2 mb-3"},Ae={class:"text-700 m-0 line-height-3 text-sm"},q={__name:"AuditResult",props:{report:{type:Object,required:!0}},setup(t){function i(s){return s==="low"?"pi pi-check-circle text-green-500":s==="medium"?"pi pi-exclamation-triangle text-yellow-500":s==="high"?"pi pi-times-circle text-red-500":"pi pi-question-circle text-500"}function l(s){return s==="low"?"Низкий":s==="medium"?"Средний":s==="high"?"Высокий":"Неизвестно"}function a(s){return s==="low"?"success":s==="medium"?"warning":s==="high"?"danger":"secondary"}function n(s){return s==="high"?"border-left-3 border-red-400":s==="medium"?"border-left-3 border-yellow-400":s==="low"?"border-left-3 border-green-400":""}return(s,r)=>{var u,v,b,g,d,x,C,I,P,U,V,L,M,E,F;const o=w("Tag");return c(),p("div",ve,[e("div",ge,[e("div",he,[e("div",ye,[r[0]||(r[0]=e("div",{class:"flex align-items-center gap-2 mb-2"},[e("i",{class:"pi pi-info-circle text-primary"}),e("span",{class:"font-semibold text-900"},"Итог аудита")],-1)),e("p",xe,y(t.report.summary),1)])]),e("div",be,[e("div",{class:k(["surface-card border-round-xl shadow-1 p-4 h-full",n((u=t.report.admission)==null?void 0:u.risk)])},[e("div",ke,[e("i",{class:k(i((v=t.report.admission)==null?void 0:v.risk)),style:{"font-size":"1.4rem"}},null,2),r[1]||(r[1]=e("span",{class:"font-bold text-900"},"Допуск",-1)),f(o,{value:l((b=t.report.admission)==null?void 0:b.risk),severity:a((g=t.report.admission)==null?void 0:g.risk),class:"ml-auto"},null,8,["value","severity"])]),e("p",_e,y(((d=t.report.admission)==null?void 0:d.details)||"—"),1)],2)]),e("div",we,[e("div",{class:k(["surface-card border-round-xl shadow-1 p-4 h-full",n((x=t.report.demonetization)==null?void 0:x.risk)])},[e("div",$e,[e("i",{class:k(i((C=t.report.demonetization)==null?void 0:C.risk)),style:{"font-size":"1.4rem"}},null,2),r[2]||(r[2]=e("span",{class:"font-bold text-900"},"Монетизация",-1)),f(o,{value:l((I=t.report.demonetization)==null?void 0:I.risk),severity:a((P=t.report.demonetization)==null?void 0:P.risk),class:"ml-auto"},null,8,["value","severity"])]),e("p",Se,y(((U=t.report.demonetization)==null?void 0:U.details)||"—"),1)],2)]),e("div",ze,[e("div",{class:k(["surface-card border-round-xl shadow-1 p-4 h-full",n((V=t.report.copyright)==null?void 0:V.risk)])},[e("div",Be,[e("i",{class:k(i((L=t.report.copyright)==null?void 0:L.risk)),style:{"font-size":"1.4rem"}},null,2),r[3]||(r[3]=e("span",{class:"font-bold text-900"},"Авторские права",-1)),f(o,{value:l((M=t.report.copyright)==null?void 0:M.risk),severity:a((E=t.report.copyright)==null?void 0:E.risk),class:"ml-auto"},null,8,["value","severity"])]),e("p",Ae,y(((F=t.report.copyright)==null?void 0:F.details)||"—"),1)],2)])])])}}},Re={class:"audit-unlock-button"},Te={class:"surface-card border-round-xl shadow-1 p-4",style:{"max-width":"480px"}},Ce={class:"flex align-items-center gap-3 flex-wrap"},Ie={__name:"AuditUnlockButton",setup(t){const i=T(),l=h(!1),a=h(null);async function n(){var s,r;l.value=!0,a.value=null;try{await i.unlockReport()}catch(o){((s=o==null?void 0:o.response)==null?void 0:s.status)===402?a.value="Недостаточно средств на балансе. Пополните счёт и попробуйте снова.":((r=o==null?void 0:o.response)==null?void 0:r.status)===429?a.value="Слишком много запросов. Подождите минуту и попробуйте снова.":a.value=i.error||"Произошла ошибка. Попробуйте позже."}finally{l.value=!1}}return(s,r)=>{const o=w("Message"),u=w("Button");return c(),p("div",Re,[e("div",Te,[r[1]||(r[1]=e("div",{class:"flex align-items-center gap-2 mb-3"},[e("i",{class:"pi pi-lock text-orange-400",style:{"font-size":"1.5rem"}}),e("span",{class:"font-bold text-xl text-900"},"Полный отчёт заблокирован")],-1)),r[2]||(r[2]=e("p",{class:"text-600 mb-3 line-height-3"}," Разблокируйте полный AI-анализ канала: детальные рекомендации, список проблемных видео и план по устранению нарушений. ",-1)),a.value?(c(),R(o,{key:0,severity:"error",closable:!1,class:"mb-3"},{default:J(()=>[S(y(a.value),1)]),_:1})):_("",!0),e("div",Ce,[f(u,{label:"Разблокировать за $1.00",icon:"pi pi-unlock",loading:l.value,onClick:n,class:"p-button-warning"},null,8,["loading"]),r[0]||(r[0]=e("span",{class:"text-500 text-sm"},"Списание с баланса аккаунта",-1))])])])}}},Pe={class:"audit-full-report"},Ue={class:"surface-card border-round-xl shadow-1 p-4 mb-3"},Ve={class:"flex align-items-center gap-2 mb-4"},Le={key:0,class:"mb-4"},Me={class:"m-0 pl-4 text-700 line-height-3"},Ee={key:1,class:"mb-4"},Fe={class:"font-medium text-900 text-sm"},Ne={class:"text-500 text-xs mt-1"},qe={key:2,class:"mb-3"},De={class:"m-0 pl-4 text-700 line-height-3"},Oe={__name:"AuditFullReport",props:{report:{type:Object,required:!0}},emits:["reset"],setup(t,{emit:i}){const l=i;return(a,n)=>{const s=w("Tag"),r=w("Button");return c(),p("div",Pe,[e("div",Ue,[e("div",Ve,[n[1]||(n[1]=e("i",{class:"pi pi-file-edit text-primary",style:{"font-size":"1.4rem"}},null,-1)),n[2]||(n[2]=e("span",{class:"font-bold text-xl text-900"},"Полный AI-анализ",-1)),f(s,{value:"Разблокирован",severity:"success",class:"ml-auto"})]),t.report.recommendations?(c(),p("div",Le,[n[3]||(n[3]=e("h3",{class:"text-900 font-semibold mt-0 mb-2",style:{"font-size":"1rem"}},[e("i",{class:"pi pi-lightbulb text-yellow-500 mr-2"}),S("Рекомендации ")],-1)),e("ul",Me,[(c(!0),p($,null,B(t.report.recommendations,(o,u)=>(c(),p("li",{key:u,class:"mb-1"},y(o),1))),128))])])):_("",!0),t.report.problematic_videos&&t.report.problematic_videos.length?(c(),p("div",Ee,[n[5]||(n[5]=e("h3",{class:"text-900 font-semibold mt-0 mb-2",style:{"font-size":"1rem"}},[e("i",{class:"pi pi-exclamation-triangle text-orange-400 mr-2"}),S("Проблемные видео ")],-1)),(c(!0),p($,null,B(t.report.problematic_videos,(o,u)=>(c(),p("div",{key:u,class:"surface-50 border-round p-3 mb-2 flex align-items-start gap-3"},[n[4]||(n[4]=e("i",{class:"pi pi-video text-500 mt-1"},null,-1)),e("div",null,[e("div",Fe,y(o.title),1),e("div",Ne,y(o.issue),1)])]))),128))])):_("",!0),t.report.action_plan?(c(),p("div",qe,[n[6]||(n[6]=e("h3",{class:"text-900 font-semibold mt-0 mb-2",style:{"font-size":"1rem"}},[e("i",{class:"pi pi-list-check text-green-500 mr-2"}),S("План устранения нарушений ")],-1)),e("ol",De,[(c(!0),p($,null,B(t.report.action_plan,(o,u)=>(c(),p("li",{key:u,class:"mb-1"},y(o),1))),128))])])):_("",!0)]),f(r,{label:"Новый аудит",icon:"pi pi-plus",class:"p-button-outlined",onClick:n[0]||(n[0]=o=>l("reset"))})])}}},We={class:"p-3 md:p-5"},je={class:"mt-3"},Qe={__name:"AuditView",setup(t){const i=T(),{status:l,isPaid:a,report:n}=D(i);function s(o){i.startAudit(o)}function r(){i.reset()}return(o,u)=>{const v=w("Button");return c(),p("div",We,[u[0]||(u[0]=e("div",{class:"flex align-items-center gap-2 mb-4"},[e("i",{class:"pi pi-search text-primary",style:{"font-size":"1.4rem"}}),e("h1",{class:"text-900 font-bold m-0",style:{"font-size":"1.5rem"}},"Аудит канала")],-1)),m(l)==="idle"?(c(),R(ne,{key:0,onSubmit:s})):m(l)==="pending"||m(l)==="error"?(c(),R(fe,{key:1,onRetry:r})):m(l)==="done"&&!m(a)?(c(),p($,{key:2},[f(q,{report:m(n),class:"mb-4"},null,8,["report"]),f(Ie),e("div",je,[f(v,{label:"Новый аудит",icon:"pi pi-plus",class:"p-button-text p-button-sm",onClick:r})])],64)):m(l)==="done"&&m(a)?(c(),p($,{key:3},[f(q,{report:m(n),class:"mb-4"},null,8,["report"]),f(Oe,{report:m(n),onReset:r},null,8,["report"])],64)):_("",!0)])}}};export{Qe as default};
