!function(){var e={120:function(){},228:function(){},816:function(){},125:function(){},15:function(){},595:function(){},530:function(){},109:function(e,t,n){var o=n(120);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),(0,n(534).A)("3fce1e17",o,!0,{})},783:function(e,t,n){var o=n(228);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),(0,n(534).A)("502999b7",o,!0,{})},751:function(e,t,n){var o=n(816);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),(0,n(534).A)("62b0683c",o,!0,{})},734:function(e,t,n){var o=n(125);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),(0,n(534).A)("741a7146",o,!0,{})},900:function(e,t,n){var o=n(15);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),(0,n(534).A)("9200bb18",o,!0,{})},784:function(e,t,n){var o=n(595);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),(0,n(534).A)("0390cbdc",o,!0,{})},415:function(e,t,n){var o=n(530);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),(0,n(534).A)("01341db6",o,!0,{})},534:function(e,t,n){"use strict";function o(e,t){for(var n=[],o={},i=0;i<t.length;i++){var s=t[i],r=s[0],l={id:e+":"+i,css:s[1],media:s[2],sourceMap:s[3]};o[r]?o[r].parts.push(l):n.push(o[r]={id:r,parts:[l]})}return n}n.d(t,{A:function(){return f}});var i,s="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!s)throw Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var r={},l=s&&(document.head||document.getElementsByTagName("head")[0]),a=null,d=0,c=!1,u=function(){},p=null,m="data-vue-ssr-id",g="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function f(e,t,n,i){c=n,p=i||{};var s=o(e,t);return h(s),function(t){for(var n=[],i=0;i<s.length;i++){var l=r[s[i].id];l.refs--,n.push(l)}t?h(s=o(e,t)):s=[];for(var i=0;i<n.length;i++){var l=n[i];if(0===l.refs){for(var a=0;a<l.parts.length;a++)l.parts[a]();delete r[l.id]}}}}function h(e){for(var t=0;t<e.length;t++){var n=e[t],o=r[n.id];if(o){o.refs++;for(var i=0;i<o.parts.length;i++)o.parts[i](n.parts[i]);for(;i<n.parts.length;i++)o.parts.push(_(n.parts[i]));o.parts.length>n.parts.length&&(o.parts.length=n.parts.length)}else{for(var s=[],i=0;i<n.parts.length;i++)s.push(_(n.parts[i]));r[n.id]={id:n.id,refs:1,parts:s}}}}function v(){var e=document.createElement("style");return e.type="text/css",l.appendChild(e),e}function _(e){var t,n,o=document.querySelector("style["+m+'~="'+e.id+'"]');if(o){if(c)return u;o.parentNode.removeChild(o)}if(g){var i=d++;t=w.bind(null,o=a||(a=v()),i,!1),n=w.bind(null,o,i,!0)}else t=y.bind(null,o=v()),n=function(){o.parentNode.removeChild(o)};return t(e),function(o){o?(o.css!==e.css||o.media!==e.media||o.sourceMap!==e.sourceMap)&&t(e=o):n()}}var b=(i=[],function(e,t){return i[e]=t,i.filter(Boolean).join("\n")});function w(e,t,n,o){var i=n?"":o.css;if(e.styleSheet)e.styleSheet.cssText=b(t,i);else{var s=document.createTextNode(i),r=e.childNodes;r[t]&&e.removeChild(r[t]),r.length?e.insertBefore(s,r[t]):e.appendChild(s)}}function y(e,t){var n=t.css,o=t.media,i=t.sourceMap;if(o&&e.setAttribute("media",o),p.ssrId&&e.setAttribute(m,t.id),i&&(n+="\n/*# sourceURL="+i.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(i))))+" */"),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}}},t={};function n(o){var i=t[o];if(void 0!==i)return i.exports;var s=t[o]={id:o,exports:{}};return e[o](s,s.exports,n),s.exports}n.d=function(e,t){for(var o in t)n.o(t,o)&&!n.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:t[o]})},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="bundles/torqshopwarecommon/",window?.__sw__?.assetPath&&(n.p=window.__sw__.assetPath+"/bundles/torqshopwarecommon/"),function(){"use strict";n(783),Shopware.Component.register("sw-cms-block-route-pagelet",{template:'{% block sw_cms_block_route_pagelet %}\n	<div class="sw-cms-block-route-pagelet">\n		\n		<slot name="pagelet">\n			{% block sw_cms_block_route_pagelet_slot_pagelet %}\n			{% endblock %}\n		</slot>\n\n	</div>\n{% endblock %}\n'}),n(751),Shopware.Component.register("sw-cms-preview-route-pagelet",{template:'{% block sw_cms_block_route_pagelet_preview %}\n	<div class="sw-cms-preview-route-pagelet">\n        <div class="sw-cms-preview-route-pagelet__heading-container">\n			<h2>{{ $tc(\'sw-cms.elements.routePagelet.label\') }}</h2>\n		</div>\n	</div>\n{% endblock %}\n'}),Shopware.Service("cmsService").registerCmsBlock({name:"route-pagelet",category:"html",label:"sw-cms.blocks.html.routePagelet.label",component:"sw-cms-block-route-pagelet",previewComponent:"sw-cms-preview-route-pagelet",defaultConfig:{marginBottom:"20px",marginTop:"20px",marginLeft:"20px",marginRight:"20px",sizingMode:"full_width"},slots:{pagelet:{type:"route-pagelet"}}}),n(734),Shopware.Component.register("sw-cms-el-route-pagelet",{template:"{% block sw_cms_element_route_pagelet %}\n    <div>&nbsp;</div>\n    <div>\n        <p>{{ $tc('sw-cms.elements.routePagelet.labelPageletContentHere') }}</p>\n    </div>\n{% endblock %}\n",mixins:["cms-element"],created(){this.createdComponent()},methods:{createdComponent(){this.initElementConfig("route-pagelet")}}}),n(900);let{Mixin:e,Component:t}=Shopware;t.register("sw-cms-el-config-route-pagelet",{template:'{% block sw_cms_element_route_pagelet_config %}\n	<div class="sw-cms-el-config-route-pagelet">\n		{% block sw_cms_element_route_pagelet_config %}\n			<sw-tabs position-identifier="sw-cms-element-config-route_pagelet" class="sw-cms-el-config-text__tabs" default-item="settings">\n\n                <template\n					#default="{ active }">\n					{% block sw_cms_element_route_pagelet_config_tab_options %}\n						<sw-tabs-item :title="$tc(\'sw-cms.elements.general.config.tab.settings\')" name="settings" :active-tab="active">\n							{{ $tc(\'sw-cms.elements.general.config.tab.settings\') }}\n						</sw-tabs-item>\n					{% endblock %}\n				</template>\n\n                <template #content="{ active }">\n                    {% block sw_cms_element_route_pagelet_config_settings %}\n                        <sw-container v-if="active === \'settings\'" class="sw-cms-el-config-text__tab-settings">\n                            {% block sw_cms_el_text_config_settings_routename %}\n                                <sw-text-field  name="routeName" \n                                                v-model:value="element.config.routeName.value"\n                                                :label="$tc(\'sw-cms.elements.routePagelet.labelRouteName\')" \n                                                required />\n                            {% endblock %}\n\n                            {% block sw_cms_el_text_config_settings_param1 %}\n                                <sw-text-field  name="routeParams" \n                                                v-model:value="element.config.routeParam.value"\n                                                :label="$tc(\'sw-cms.elements.routePagelet.labelRouteParameters\')"/>  \n                            {% endblock %}\n\n                        </sw-container>\n                    {% endblock %}\n                </template>\n			</sw-tabs>\n		{% endblock %}\n	</div>\n{% endblock %}\n\n',inject:["repositoryFactory"],mixins:[e.getByName("cms-element")],created(){this.createdComponent()},methods:{createdComponent(){this.initElementConfig("route-pagelet"),this.initElementData("route-pagelet")}}}),n(784),Shopware.Component.register("sw-cms-el-preview-route-pagelet",{template:"{% block sw_cms_element_route_pagelet %}\n    <div class=\"\">\n        <div>\n            {{ $tc('sw-cms.elements.routePagelet.labelPageletContentHere') }}\n        </div>\n    </div>\n{% endblock %}\n"}),Shopware.Service("cmsService").registerCmsElement({name:"route-pagelet",label:"sw-cms.elements.routePagelet.label",component:"sw-cms-el-route-pagelet",configComponent:"sw-cms-el-config-route-pagelet",previewComponent:"sw-cms-el-preview-route-pagelet",removable:!1,hidden:!1,defaultConfig:{routeName:{source:"static",value:""},routeParam:{source:"static",value:""}}}),n(109);let{Component:o,Mixin:i}=Shopware,{mapPropertyErrors:s}=o.getComponentHelper(),{Criteria:r}=Shopware.Data;o.extend("torq-condition-customer-address-custom-field","sw-condition-base",{template:'{% block sw_condition_value_content %}\n<div class="torq-condition-customer-address-custom-field sw-condition__condition-value">\n    {% block torq_condition_customer_address_custom_field_field %}\n    <sw-entity-single-select\n        ref="selectedField"\n        v-model:value="selectedField"\n        entity="custom_field"\n        :criteria="customFieldCriteria"\n        :placeholder="$tc(\'global.sw-condition.condition.lineItemCustomField.field.customFieldSelect.placeholder\')"\n        :disabled="disabled || undefined"\n        size="medium"\n        show-clearable-button\n        @update:value="onFieldChange"\n    >\n        <template #selection-label-property="slotProps">\n            {{ getInlineSnippet(slotProps.item.config.label) || slotProps.item.name }}\n        </template>\n\n        <template #result-label-property="slotProps">\n            {{ getInlineSnippet(slotProps.item.config.label) || slotProps.item.name }}\n        </template>\n\n        <template #result-description-property="slotProps">\n            {% block torq_condition_customer_address_custom_field_field_description %}\n            {{ getInlineSnippet(slotProps.item.customFieldSet.config.label) || slotProps.item.customFieldSet.name }}\n            {% endblock %}\n        </template>\n    </sw-entity-single-select>\n    {% endblock %}\n\n    {% block torq_condition_customer_address_custom_field_operator %}\n    <sw-condition-operator-select\n        v-if="renderedField"\n        v-bind="{ operators, condition }"\n        :disabled="disabled || undefined"\n    />\n    {% endblock %}\n\n    {% block torq_condition_customer_address_custom_field_value %}\n    <sw-form-field-renderer\n        v-if="renderedField"\n        :value="renderedFieldValue"\n        :config="renderedField.config"\n        :disabled="disabled || undefined"\n        size="medium"\n        @update:value="renderedFieldValue = $event"\n    />\n    {% endblock %}\n</div>\n{% endblock %}\n',inject:["repositoryFactory","feature"],mixins:[i.getByName("sw-inline-snippet")],computed:{customFieldCriteria(){let e=new r(1,25);return e.addAssociation("customFieldSet"),e.addFilter(r.equals("customFieldSet.relations.entityName","customer_address")),e.addSorting(r.sort("customFieldSet.name","ASC")),e},operator:{get(){return this.ensureValueExist(),this.condition.value.operator},set(e){this.ensureValueExist(),this.condition.value={...this.condition.value,operator:e}}},renderedField:{get(){return this.ensureValueExist(),this.condition.value.renderedField},set(e){this.ensureValueExist(),this.condition.value={...this.condition.value,renderedField:e}}},selectedField:{get(){return this.ensureValueExist(),this.condition.value.selectedField},set(e){this.ensureValueExist(),this.condition.value={...this.condition.value,selectedField:e}}},selectedFieldSet:{get(){return this.ensureValueExist(),this.condition.value.selectedFieldSet},set(e){this.ensureValueExist(),this.condition.value={...this.condition.value,selectedFieldSet:e}}},renderedFieldValue:{get(){return this.ensureValueExist(),this.condition.value.renderedFieldValue},set(e){this.ensureValueExist(),this.condition.value={...this.condition.value,renderedFieldValue:e}}},operators(){return this.conditionDataProviderService.getOperatorSetByComponent(this.renderedField)},...s("condition",["value.renderedField","value.selectedField","value.selectedFieldSet","value.operator","value.renderedFieldValue"]),currentError(){return this.conditionValueRenderedFieldError||this.conditionValueSelectedFieldError||this.conditionValueSelectedFieldSetError||this.conditionValueOperatorError||this.conditionValueRenderedFieldValueError}},methods:{onFieldChange(e){this.$refs.selectedField.resultCollection.has(e)?(this.renderedField=this.$refs.selectedField.resultCollection.get(e),this.selectedFieldSet=this.renderedField.customFieldSetId):this.renderedField=null,this.operator=null,this.renderedFieldValue=null}}}),Shopware.Application.addServiceProviderDecorator("ruleConditionDataProviderService",e=>(e.addCondition("customerAddressCustomField",{component:"torq-condition-customer-address-custom-field",label:"Address with custom field",scopes:["global"],group:"customer"}),e)),n(415);let{ShopwareError:l}=Shopware.Classes,{Mixin:a,EntityDefinition:d}=Shopware,{Criteria:c}=Shopware.Data;Shopware.Component.override("sw-customer-employee-detail",{template:'\n{% block sw_customer_employee_create_content_card_first_name %}\n\n    <template>\n        <sw-container\n            class="sw-customer-employee-detail__login-as-employee-container"\n        >\n            <sw-button \n                @click="onImitateEmployee">\n                {{ $tc(\'employee.loginAsEmployee\') }}\n            </sw-button>\n\n        </sw-container>\n        \n    </template>\n    \n    {% parent %}\n{% endblock %}',inject:["contextStoreService"],data(){return{}},computed:{currentUser(){return Shopware.State.get("session").currentUser}},methods:{async onImitateEmployee(){this.contextStoreService.generateImitateCustomerToken(this.entity.businessPartnerCustomerId,"018f62369c3e708cba4ee6f85819a4af").then(e=>{let t=this.handleResponse(e),n=document.createElement("form");n.method="POST",n.action="http://wp.localhost.torq:9401/account/login/imitate-employee",n.target="_blank",document.body.appendChild(n),this.createHiddenInput(n,"token",t.token),this.createHiddenInput(n,"customerId",this.entity.businessPartnerCustomerId),this.createHiddenInput(n,"employeeId",this.entity.id),this.createHiddenInput(n,"userId",this.currentUser?.id),n.submit(),n.remove()}).catch(e=>{console.error(e)})},createHiddenInput(e,t,n){let o=document.createElement("input");o.type="hidden",o.name=t,o.value=n,e.appendChild(o)},handleResponse(e){if(null===e.data||void 0===e.data)return e;let t=e.headers;return"object"==typeof t&&null!==t&&"application/vnd.api+json"===t["content-type"]&&console.log(e.data),e.data}}})}()}();