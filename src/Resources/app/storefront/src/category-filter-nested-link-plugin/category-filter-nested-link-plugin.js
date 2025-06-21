const { PluginBaseClass } = window;
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
import Iterator from 'src/helper/iterator.helper';

export default class CategoryFilterNestedLinkPlugin extends PluginBaseClass {

    init() {
        this.initPlugin();
    }

    initPlugin() {

        const categoryLinks = this.el.querySelectorAll('.torq-category-filter-nested-link a');

        Iterator.iterate(categoryLinks, (item) => item.addEventListener('click', this.navigateToCategory));
        
    }

    navigateToCategory(e) {
        e.preventDefault();
        const currentQuery = window.location.search;
        const targetUrl = this.getAttribute('href');
        window.location.href = targetUrl + currentQuery;
    }
}