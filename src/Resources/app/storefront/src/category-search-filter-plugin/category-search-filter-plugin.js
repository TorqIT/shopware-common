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
        aggregateCategoryIds: null,
        filterUrl: ''
    });

    init() {
        //this.selection = [];
        //this.counter = DomAccess.querySelector(this.el, this.options.countSelector);
        //this.mainFilterButton = DomAccess.querySelector(this.el, this.options.mainFilterButtonSelector, false);

        this._client = new HttpClient();
        this._registerEvents();
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
        values[this.options.name] = this.options.categoryId;

        return values;
    }

    /**
     * @return {Array}
     * @public
     */
    getLabels() {
        const activeCheckboxes =
            DomAccess.querySelectorAll(this.el, `${this.options.checkboxSelector}:checked`, false);

        let labels = [];

        if (activeCheckboxes) {
            Iterator.iterate(activeCheckboxes, (checkbox) => {
                labels.push({
                    label: checkbox.dataset.label,
                    id: checkbox.id,
                });
            });
        } else {
            labels = [];
        }

        return labels;
    }

    setValuesFromUrl(params = {}) {
        let stateChanged = false;

        // const properties = params[this.options.name];

        // const ids = properties ? properties.split('|') : [];

        // const uncheckItems = this.selection.filter(x => !ids.includes(x));
        // const checkItems = ids.filter(x => !this.selection.includes(x));

        // if (uncheckItems.length > 0 || checkItems.length > 0) {
        //     stateChanged = true;
        // }

        // checkItems.forEach(id => {
        //     const checkboxEl = DomAccess.querySelector(this.el, `[id="${id}"]`, false);

        //     if (checkboxEl) {
        //         checkboxEl.checked = true;
        //         this.selection.push(checkboxEl.id);
        //     }
        // });

        // uncheckItems.forEach(id => {
        //     this.reset(id);

        //     this.selection = this.selection.filter(item => item !== id);
        // });

        // this._updateCount();

        return stateChanged;
    }

    /**
     * @private
     */
    _onChangeFilter(event) {
        event.preventDefault();
        event.stopPropagation();

        const categoryId = event.target.dataset.categoryId;

        this.options.categoryId = categoryId;

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

        this._client.post(
            this.options.filterUrl,
            JSON.stringify(data),
            (responseText, request) => {
                if (request.status >= 400) {
                    
                }

                try {
                    filterPane.innerHTML = responseText;
                    this._registerEvents();
                } catch (error) {
                    
                }
            },
        );

        this._registerEvents();
    }

    /**
     * @param id
     * @public
     */
    reset(id) {
        const checkboxEl = DomAccess.querySelector(this.el, `[id="${id}"]`, false);

        if (checkboxEl) {
            checkboxEl.checked = false;
        }
    }

    /**
     * @public
     */
    resetAll() {
        this.selection.filter = [];

        const checkedCheckboxes =
            DomAccess.querySelectorAll(this.el, `${this.options.checkboxSelector}:checked`, false);

        if (checkedCheckboxes) {
            Iterator.iterate(checkedCheckboxes, (checkbox) => {
                checkbox.checked = false;
            });
        }
    }

    /**
     * @public
     */
    refreshDisabledState(filter) {
        const disabledFilter = filter[this.options.name];

        if (!disabledFilter.entities || disabledFilter.entities.length < 1) {
            this.disableFilter();
            return;
        }

        this.enableFilter();

        this._disableInactiveFilterOptions(disabledFilter.entities.map(entity => entity.id));
    }

    /**
     * @private
     */
    _disableInactiveFilterOptions(activeItemIds) {
        const checkboxes = DomAccess.querySelectorAll(this.el, this.options.checkboxSelector);
        Iterator.iterate(checkboxes, (checkbox) => {
            if (checkbox.checked === true) {
                return;
            }

            if (activeItemIds.includes(checkbox.id)) {
                this.enableOption(checkbox);
            } else {
                this.disableOption(checkbox);
            }
        });
    }

    /**
     * @public
     */
    disableOption(input){
        const listItem = input.closest(this.options.listItemSelector);
        listItem.classList.add('disabled');
        listItem.setAttribute('title', this.options.snippets.disabledFilterText);
        input.disabled = true;
    }

    /**
     * @public
     */
    enableOption(input) {
        const listItem = input.closest(this.options.listItemSelector);
        listItem.removeAttribute('title');
        listItem.classList.remove('disabled');
        input.disabled = false;
    }

    /**
     * @public
     */
    enableAllOptions() {
        const checkboxes = DomAccess.querySelectorAll(this.el, this.options.checkboxSelector);
        Iterator.iterate(checkboxes, (checkbox) => {
            this.enableOption(checkbox);
        });
    }

    /**
     * @public
     */
    disableFilter() {
        const mainFilterButton = DomAccess.querySelector(this.el, this.options.mainFilterButtonSelector);
        mainFilterButton.classList.add('disabled');
        mainFilterButton.setAttribute('disabled', 'disabled');
        mainFilterButton.setAttribute('title', this.options.snippets.disabledFilterText);
    }

    /**
     * @public
     */
    enableFilter() {
        const mainFilterButton = DomAccess.querySelector(this.el, this.options.mainFilterButtonSelector);
        mainFilterButton.classList.remove('disabled');
        mainFilterButton.removeAttribute('disabled');
        mainFilterButton.removeAttribute('title');
    }

    /**
     * @private
     */
    _updateCount() {
        this.counter.textContent = this.selection.length ? `(${this.selection.length})` : '';

        this._updateAriaLabel();
    }

    /**
     * Update the aria-label for the filter toggle button to reflect the number of already selected items.
     * @private
     */
    _updateAriaLabel() {
        if (!this.options.snippets.ariaLabel) {
            return;
        }

        if (this.selection.length === 0) {
            this.mainFilterButton.setAttribute('aria-label', this.options.snippets.ariaLabel);
            return;
        }

        this.mainFilterButton.setAttribute(
            'aria-label',
            `${this.options.snippets.ariaLabel} (${this.options.snippets.ariaLabelCount.replace('%count%', this.selection.length.toString())})`
        );
    }
}
