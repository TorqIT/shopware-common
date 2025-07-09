const { PluginBaseClass } = window;
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
import Iterator from 'src/helper/iterator.helper';

export default class QuickAddAutocompletePlugin extends PluginBaseClass {

    static options = {
        urlPrefix : '/'
    };

    init() {
        this.client = new HttpClient();
        this._registerEvents();
        this.autocompleteActive = false;

        this._onDocumentClick = this._onDocumentClick.bind(this);
        document.addEventListener('click', this._onDocumentClick, { passive: true });
    }

    _registerEvents(){
        const form = DomAccess.querySelector(this.el, 'form', false);
        const searchBox = DomAccess.querySelector(form, '#addProductInput', false);
        const searchContainer = DomAccess.querySelector(this.el, '#quick-add-autocomplete-container', false);
        const parentRow = this.el.closest('.row');

        searchBox.addEventListener('keyup', s => {
            const term = s.target.value;

            if(term.length <= 2){
                searchContainer.innerHTML= '';
                return;
            }

            this.client.get(this.options.urlPrefix + 'checkout/cart/quickadd/autocomplete?term=' + term, c => {
                if(!this.autocompleteActive){
                    return;
                }
                searchContainer.innerHTML = c;

                const forms = DomAccess.querySelectorAll(searchContainer, 'form', false);
                if (forms) {
                    Iterator.iterate(
                        forms, 
                        qForm => {
                            qForm.addEventListener('submit', s => {
                                ElementLoadingIndicatorUtil.create(parentRow);
                            });
                        }
                    );
                }
            });
        });

        searchBox.addEventListener('focus', s => {
            this.autocompleteActive = true;
        });
    }

    _onDocumentClick(e) {
        if (this.el.contains(e.target)) {
            return;
        }

        const searchContainer = DomAccess.querySelector(this.el, '#quick-add-autocomplete-container', false);
        if (searchContainer) {
            searchContainer.innerHTML = '';
        }
        this.autocompleteActive = false;
    }
}