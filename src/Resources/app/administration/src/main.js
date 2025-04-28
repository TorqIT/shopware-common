import './module/sw-cms/blocks/html/route-pagelet';
import './module/sw-cms/elements/route-pagelet';

import './decorator/rule-condition-service-decoration';
import './decorator/product-stream-condition.decorator';

import './module/sw-customer/page/sw-customer-employee-detail';

import './module/sw-employee-imitate-modal';

// Import snippets
import enGB from './snippet/en-GB.json';

// Register snippets
Shopware.Locale.register('en-GB', enGB);