/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales.Document');

import './AbstractEditDialog'

Tine.Sales.Document_OfferEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {


    initComponent () {
        this.supr().initComponent.call(this)
    }
});
