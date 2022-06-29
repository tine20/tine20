/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import {avatarRenderer} from "../../Addressbook/js/renderers";

const nodeGridPanelPlugin = {
    init(nodeGridPanel) {
        const app = Tine.Tinebase.appMgr.get('Filemanager');

        nodeGridPanel.grid.colModel.columns.splice(1, 0, {
            id: 'ooi_editors', dataIndex: 'ooi_editors', header: app.i18n._('Editing'), tooltip: app.i18n._('Who is currently editing this document'), hidden: false, width: 50, renderer: (value) => {
                return _.compact([].concat(value)).map((contactData) => {
                    const contact = Tine.Tinebase.data.Record.setFromJson(contactData, Tine.Addressbook.Model.Contact);
                    return avatarRenderer(contactData.n_short, {}, contact);
                }).join();
            }
        })
    }
};

Ext.ux.pluginRegistry.register('Filemanager-Node-GridPanel', nodeGridPanelPlugin);

export default nodeGridPanelPlugin;
