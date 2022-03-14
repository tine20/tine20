/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

class FreeTimeGridPanel extends Tine.widgets.grid.GridPanel {
    initComponent() {
        // this.evalGrants = false;
        super.initComponent();
        this.action_editInNewWindow.actionUpdater = (action, grants, records, isFilterSelect, filteredContainers) => {
            let enabled = records.length === 1
            action.setDisabled(!enabled)
        }
        this.action_deleteRecord.actionUpdater = (action, grants, records, isFilterSelect, filteredContainers) => {
            let enabled = records.length >= 1
            //@TODO ...
            action.setDisabled(!enabled)
        }
    }
}

Tine.HumanResources.FreeTimeGridPanel = FreeTimeGridPanel;
export default FreeTimeGridPanel;
