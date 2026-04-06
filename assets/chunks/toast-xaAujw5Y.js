import{b as v,f as u}from"./index-DowbgmiA.js";import{B as y,o as l,c as s,y as p,C as f,f as b,v as g,r as c}from"../index-DIZUxXWS.js";var h=`
    .p-divider-horizontal {
        display: flex;
        width: 100%;
        position: relative;
        align-items: center;
        margin: dt('divider.horizontal.margin');
        padding: dt('divider.horizontal.padding');
    }

    .p-divider-horizontal:before {
        position: absolute;
        display: block;
        inset-block-start: 50%;
        inset-inline-start: 0;
        width: 100%;
        content: '';
        border-block-start: 1px solid dt('divider.border.color');
    }

    .p-divider-horizontal .p-divider-content {
        padding: dt('divider.horizontal.content.padding');
    }

    .p-divider-vertical {
        min-height: 100%;
        display: flex;
        position: relative;
        justify-content: center;
        margin: dt('divider.vertical.margin');
        padding: dt('divider.vertical.padding');
    }

    .p-divider-vertical:before {
        position: absolute;
        display: block;
        inset-block-start: 0;
        inset-inline-start: 50%;
        height: 100%;
        content: '';
        border-inline-start: 1px solid dt('divider.border.color');
    }

    .p-divider.p-divider-vertical .p-divider-content {
        padding: dt('divider.vertical.content.padding');
    }

    .p-divider-content {
        z-index: 1;
        background: dt('divider.content.background');
        color: dt('divider.content.color');
    }

    .p-divider-solid.p-divider-horizontal:before {
        border-block-start-style: solid;
    }

    .p-divider-solid.p-divider-vertical:before {
        border-inline-start-style: solid;
    }

    .p-divider-dashed.p-divider-horizontal:before {
        border-block-start-style: dashed;
    }

    .p-divider-dashed.p-divider-vertical:before {
        border-inline-start-style: dashed;
    }

    .p-divider-dotted.p-divider-horizontal:before {
        border-block-start-style: dotted;
    }

    .p-divider-dotted.p-divider-vertical:before {
        border-inline-start-style: dotted;
    }

    .p-divider-left:dir(rtl),
    .p-divider-right:dir(rtl) {
        flex-direction: row-reverse;
    }
`,m={root:function(n){var e=n.props;return{justifyContent:e.layout==="horizontal"?e.align==="center"||e.align===null?"center":e.align==="left"?"flex-start":e.align==="right"?"flex-end":null:null,alignItems:e.layout==="vertical"?e.align==="center"||e.align===null?"center":e.align==="top"?"flex-start":e.align==="bottom"?"flex-end":null:null}}},S={root:function(n){var e=n.props;return["p-divider p-component","p-divider-"+e.layout,"p-divider-"+e.type,{"p-divider-left":e.layout==="horizontal"&&(!e.align||e.align==="left")},{"p-divider-center":e.layout==="horizontal"&&e.align==="center"},{"p-divider-right":e.layout==="horizontal"&&e.align==="right"},{"p-divider-top":e.layout==="vertical"&&e.align==="top"},{"p-divider-center":e.layout==="vertical"&&(!e.align||e.align==="center")},{"p-divider-bottom":e.layout==="vertical"&&e.align==="bottom"}]},content:"p-divider-content"},z=y.extend({name:"divider",style:h,classes:S,inlineStyles:m}),k={name:"BaseDivider",extends:v,props:{align:{type:String,default:null},layout:{type:String,default:"horizontal"},type:{type:String,default:"solid"}},style:z,provide:function(){return{$pcDivider:this,$parentInstance:this}}};function r(t){"@babel/helpers - typeof";return r=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n},r(t)}function d(t,n,e){return(n=P(n))in t?Object.defineProperty(t,n,{value:e,enumerable:!0,configurable:!0,writable:!0}):t[n]=e,t}function P(t){var n=x(t,"string");return r(n)=="symbol"?n:n+""}function x(t,n){if(r(t)!="object"||!t)return t;var e=t[Symbol.toPrimitive];if(e!==void 0){var i=e.call(t,n);if(r(i)!="object")return i;throw new TypeError("@@toPrimitive must return a primitive value.")}return(n==="string"?String:Number)(t)}var $={name:"Divider",extends:k,inheritAttrs:!1,computed:{dataP:function(){return u(d(d(d({},this.align,this.align),this.layout,this.layout),this.type,this.type))}}},w=["aria-orientation","data-p"],j=["data-p"];function B(t,n,e,i,a,o){return l(),s("div",p({class:t.cx("root"),style:t.sx("root"),role:"separator","aria-orientation":t.layout,"data-p":o.dataP},t.ptmi("root")),[t.$slots.default?(l(),s("div",p({key:0,class:t.cx("content"),"data-p":o.dataP},t.ptm("content")),[f(t.$slots,"default")],16,j)):b("",!0)],16,w)}$.render=B;const C=g("toast",()=>{const t=c(""),n=c("success");function e(a,o="success"){t.value=a,n.value=o}function i(){t.value="",n.value="success"}return{message:t,severity:n,showToast:e,clearToast:i}});export{$ as s,C as u};
