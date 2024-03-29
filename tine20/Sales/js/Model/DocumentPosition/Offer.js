/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import AbstractMixin from './AbstractMixin'

Ext.ns('Tine.Sales.Model')

Tine.Sales.Model.DocumentPosition_OfferMixin = {
    parent() {
        console.error('child');
    },
    statics: {
        parent() {
            console.error('childStatic');
        },
    }
};

// @TODO this should be done by modelConfig!
_.defaultsDeep(Tine.Sales.Model.DocumentPosition_OfferMixin, AbstractMixin);
