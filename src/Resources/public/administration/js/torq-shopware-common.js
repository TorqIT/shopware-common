(function(){var e={318:function(){},485:function(){},529:function(){},274:function(){},31:function(){},849:function(e,t,n){var s=n(318);s.__esModule&&(s=s.default),"string"==typeof s&&(s=[[e.id,s,""]]),s.locals&&(e.exports=s.locals),n(346).Z("163128c7",s,!0,{})},66:function(e,t,n){var s=n(485);s.__esModule&&(s=s.default),"string"==typeof s&&(s=[[e.id,s,""]]),s.locals&&(e.exports=s.locals),n(346).Z("ec7d901c",s,!0,{})},526:function(e,t,n){var s=n(529);s.__esModule&&(s=s.default),"string"==typeof s&&(s=[[e.id,s,""]]),s.locals&&(e.exports=s.locals),n(346).Z("796bc9c7",s,!0,{})},16:function(e,t,n){var s=n(274);s.__esModule&&(s=s.default),"string"==typeof s&&(s=[[e.id,s,""]]),s.locals&&(e.exports=s.locals),n(346).Z("79382924",s,!0,{})},161:function(e,t,n){var s=n(31);s.__esModule&&(s=s.default),"string"==typeof s&&(s=[[e.id,s,""]]),s.locals&&(e.exports=s.locals),n(346).Z("3064c07c",s,!0,{})},346:function(e,t,n){"use strict";function s(e,t){for(var n=[],s={},o=0;o<t.length;o++){var a=t[o],c=a[0],i={id:e+":"+o,css:a[1],media:a[2],sourceMap:a[3]};s[c]?s[c].parts.push(i):n.push(s[c]={id:c,parts:[i]})}return n}n.d(t,{Z:function(){return g}});var o="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!o)throw Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var a={},c=o&&(document.head||document.getElementsByTagName("head")[0]),i=null,r=0,l=!1,m=function(){},u=null,p="data-vue-ssr-id",d="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function g(e,t,n,o){l=n,u=o||{};var c=s(e,t);return f(c),function(t){for(var n=[],o=0;o<c.length;o++){var i=a[c[o].id];i.refs--,n.push(i)}t?f(c=s(e,t)):c=[];for(var o=0;o<n.length;o++){var i=n[o];if(0===i.refs){for(var r=0;r<i.parts.length;r++)i.parts[r]();delete a[i.id]}}}}function f(e){for(var t=0;t<e.length;t++){var n=e[t],s=a[n.id];if(s){s.refs++;for(var o=0;o<s.parts.length;o++)s.parts[o](n.parts[o]);for(;o<n.parts.length;o++)s.parts.push(v(n.parts[o]));s.parts.length>n.parts.length&&(s.parts.length=n.parts.length)}else{for(var c=[],o=0;o<n.parts.length;o++)c.push(v(n.parts[o]));a[n.id]={id:n.id,refs:1,parts:c}}}}function _(){var e=document.createElement("style");return e.type="text/css",c.appendChild(e),e}function v(e){var t,n,s=document.querySelector("style["+p+'~="'+e.id+'"]');if(s){if(l)return m;s.parentNode.removeChild(s)}if(d){var o=r++;t=w.bind(null,s=i||(i=_()),o,!1),n=w.bind(null,s,o,!0)}else t=h.bind(null,s=_()),n=function(){s.parentNode.removeChild(s)};return t(e),function(s){s?(s.css!==e.css||s.media!==e.media||s.sourceMap!==e.sourceMap)&&t(e=s):n()}}var b=function(){var e=[];return function(t,n){return e[t]=n,e.filter(Boolean).join("\n")}}();function w(e,t,n,s){var o=n?"":s.css;if(e.styleSheet)e.styleSheet.cssText=b(t,o);else{var a=document.createTextNode(o),c=e.childNodes;c[t]&&e.removeChild(c[t]),c.length?e.insertBefore(a,c[t]):e.appendChild(a)}}function h(e,t){var n=t.css,s=t.media,o=t.sourceMap;if(s&&e.setAttribute("media",s),u.ssrId&&e.setAttribute(p,t.id),o&&(n+="\n/*# sourceURL="+o.sources[0]+" */\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(o))))+" */"),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}}},t={};function n(s){var o=t[s];if(void 0!==o)return o.exports;var a=t[s]={id:s,exports:{}};return e[s](a,a.exports,n),a.exports}n.d=function(e,t){for(var s in t)n.o(t,s)&&!n.o(e,s)&&Object.defineProperty(e,s,{enumerable:!0,get:t[s]})},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="bundles/torqshopwarecommon/",window?.__sw__?.assetPath&&(n.p=window.__sw__.assetPath+"/bundles/torqshopwarecommon/"),function(){"use strict";n(849),Shopware.Component.register("sw-cms-block-custom-page",{template:'{% block sw_cms_block_custom_page %}\n	<div class="sw-cms-block-custom-page">\n\n		<slot name="heading">\n			{% block sw_cms_block_custom_page_slot_heading %}\n            {% endblock %}\n		</slot>\n		\n		<slot name="datatable">\n			{% block sw_cms_block_custom_page_slot_datatable %}\n			{% endblock %}\n		</slot>\n\n	</div>\n{% endblock %}\n'}),n(66),Shopware.Component.register("sw-cms-preview-custom-page",{template:'{% block sw_cms_block_custom_page_preview %}\n	<div class="sw-cms-preview-custom-page">\n        <div class="sw-cms-preview-custom-page__heading-container">\n			<h2>Custom Page</h2>\n		</div>\n	</div>\n{% endblock %}\n'}),Shopware.Service("cmsService").registerCmsBlock({name:"custom-page",category:"html",label:"sw-cms.blocks.html.customPage.label",component:"sw-cms-block-custom-page",previewComponent:"sw-cms-preview-custom-page",defaultConfig:{marginBottom:"20px",marginTop:"20px",marginLeft:"20px",marginRight:"20px",sizingMode:"full_width"},slots:{heading:{type:"text"},datatable:{type:"custom-page"}}}),n(526),Shopware.Component.register("sw-cms-el-custom-page",{template:"{% block sw_cms_element_custom_page %}\n    <div>&nbsp;</div>\n    <div>\n        <p>Custom Page content...</p>\n    </div>\n{% endblock %}\n",mixins:["cms-element"],created(){this.createdComponent()},methods:{createdComponent(){this.initElementConfig("custom-page")}}}),n(16);let{Mixin:e,Component:t}=Shopware;t.register("sw-cms-el-config-custom-page",{template:'{% block sw_cms_element_custom_page_config %}\n	<div class="sw-cms-el-config-custom-page">\n		{% block sw_cms_element_custom_page_config %}\n			<sw-tabs position-identifier="sw-cms-element-config-custom_page" class="sw-cms-el-config-text__tabs" default-item="settings">\n\n                <template\n					#default="{ active }">\n					{% block sw_cms_element_custom_page_config_tab_options %}\n						<sw-tabs-item :title="$tc(\'sw-cms.elements.general.config.tab.settings\')" name="settings" :active-tab="active">\n							{{ $tc(\'sw-cms.elements.general.config.tab.settings\') }}\n						</sw-tabs-item>\n					{% endblock %}\n				</template>\n\n                <template #content="{ active }">\n                    {% block sw_cms_element_custom_page_config_settings %}\n                        <sw-container v-if="active === \'settings\'" class="sw-cms-el-config-text__tab-settings">\n                            {% block sw_cms_el_text_config_settings_routename %}\n                                <sw-text-field  name="routeName" \n                                                v-model:value="element.config.routeName.value"\n                                                :label="$tc(\'sw-cms.elements.customPage.labelRouteName\')" \n                                                required />\n                            {% endblock %}\n\n                            {% block sw_cms_el_text_config_settings_param1 %}\n                                <sw-text-field  name="routeParams" \n                                                v-model:value="element.config.routeParam.value"\n                                                :label="$tc(\'sw-cms.elements.customPage.labelRouteParameters\')"/>  \n                            {% endblock %}\n\n\n                        </sw-container>\n                    {% endblock %}\n                </template>\n			</sw-tabs>\n		{% endblock %}\n	</div>\n{% endblock %}\n\n',inject:["repositoryFactory"],mixins:[e.getByName("cms-element")],created(){this.createdComponent()},methods:{createdComponent(){this.initElementConfig("custom-page"),this.initElementData("custom-page")}}}),n(161),Shopware.Component.register("sw-cms-el-preview-custom-page",{template:'{% block sw_cms_element_custom_page %}\n    <div class="">\n        <div>\n            Custom Page content here...\n        </div>\n    </div>\n{% endblock %}\n'}),Shopware.Service("cmsService").registerCmsElement({name:"custom-page",label:"sw-cms.elements.customPage.label",component:"sw-cms-el-custom-page",configComponent:"sw-cms-el-config-custom-page",previewComponent:"sw-cms-el-preview-custom-page",removable:!1,hidden:!1,defaultConfig:{routeName:{source:"static",value:""},routeParam:{source:"static",value:""}}})}()})();