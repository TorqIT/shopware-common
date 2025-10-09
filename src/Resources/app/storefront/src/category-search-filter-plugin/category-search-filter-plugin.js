/*
 * @sw-package inventory
 */

import DomAccess from 'src/helper/dom-access.helper';
import Iterator from 'src/helper/iterator.helper';
import FilterBasePlugin from 'src/plugin/listing/filter-base.plugin';
import HttpClient from 'src/service/http-client.service';
import deepmerge from 'deepmerge';

export default class SearchCategoryFilterPlugin extends FilterBasePlugin {

    static options = deepmerge(FilterBasePlugin.options, {
        filterContainer: '.category-search-filter-container',
        categoryId: null,
        categoryName: null,
        aggregateCategoryIds: null,
        filterUrl: ''
    });

    init() {
        //this.selection = [];
        //this.counter = DomAccess.querySelector(this.el, this.options.countSelector);
        //this.mainFilterButton = DomAccess.querySelector(this.el, this.options.mainFilterButtonSelector, false);

        this._client = new HttpClient();
        this._registerEvents();
        this._loader = DomAccess.querySelector(this.el, '.loader');
    }

    /**
     * @private
     */
    _registerEvents() {
        const links = DomAccess.querySelectorAll(this.el, this.options.filterContainer + ' a');

        Iterator.iterate(links, (link) => {
            link.addEventListener('click', this._onChangeFilter.bind(this));
        });
    }

    /**
     * @return {Array}
     * @public
     */
    getValues() {
        const values = {};
        if (this.options.categoryId){
            values[this.options.name] = this.options.categoryId;
        }

        return values;
    }

    /**
     * @return {Array}
     * @public
     */
    getLabels() {

        let labels = [];

        if (this.options.categoryId) {
           labels.push({
                label: this.options.categoryName,
                id: this.options.categoryId,
           });
        }

        return labels;
    }

    setValuesFromUrl(params = {}) {
        const properties = params[this.options.name];

        const ids = properties ? properties.split('|') : [];

        if (ids.length > 0) {
            this.options.categoryId = ids[0];
        }

        const categoryLink = DomAccess.querySelector(this.el, `[data-category-id=\"${this.options.categoryId}\"]`, false);
       
        if (categoryLink) {
            this.options.categoryName = categoryLink.dataset.categoryName;
            return true;
        }

        return false;
    }

    

    /**
     * @param id
     * @public
     */
    reset(id) {
        this.options.categoryId = null;
        this._reloadFilterPane();
    }

    /**
     * @public
     */
    resetAll() {
        this.reset();
    }

    /**
     * @public
     */
    refreshDisabledState(filter) {
        var catFilter = filter[this.options.name];

        var catIds = [];

        catFilter.entities.forEach(entity => {
            catIds.push(entity.id);
        });

        if (this.options.aggregateCategoryIds && 
            this.options.aggregateCategoryIds.length === catIds.length &&
            this.options.aggregateCategoryIds.every((id, index) => id === catIds[index])) {
            return;
        }

        this.options.aggregateCategoryIds = catIds;

        this._reloadFilterPane();
    }


    /**
     * @public
     */
    disableOption(input){
    }

    /**
     * @public
     */
    enableOption(input) {
    }

    /**
     * @public
     */
    enableAllOptions() {

    }

    /**
     * @public
     */
    disableFilter() {
    }

    /**
     * @public
     */
    enableFilter() {
        console.log('refreshDisabledState');        
    }

    /**
     * @private
     */
    _onChangeFilter(event) {
        event.preventDefault();
        event.stopPropagation();

        const categoryId = event.target.dataset.categoryId;

        this.options.categoryId = categoryId;
        this.options.categoryName = event.target.dataset.categoryName;

        this._reloadFilterPane();

        this.listing.changeListing(true, { p: 1 });

    }

    async _reloadFilterPane(){
        const filterPane = DomAccess.querySelector(this.el, this.options.filterContainer);
        filterPane.innerHTML = '';
        const data = {
            categoryId: this.options.categoryId,
            categoryIds: this.options.aggregateCategoryIds
        };

        this._loader.style.display = 'block';
        this._client.post(
            this.options.filterUrl,
            JSON.stringify(data),
            (responseText, request) => {
                if (request.status >= 400) {
                    
                }

                try {
                    filterPane.innerHTML = responseText;
                    this._loader.style.display = 'none';
                    this._registerEvents();
                } catch (error) {
                    
                }
            },
        );
    }
}
