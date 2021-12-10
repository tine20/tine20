/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

import { BoilerplatePanel } from './BoilerplatePanel'

Tine.Sales.Document_AbstractEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    initComponent() {
        Tine.Sales.Document_AbstractEditDialog.superclass.initComponent.call(this);

        this.items.get(0).insert(1, new BoilerplatePanel({}));
    }
});
