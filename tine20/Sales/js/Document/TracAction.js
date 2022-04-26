/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './TracDialog'

Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    const getAction = (type, config) => {
        const recordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${type}`)
        return new Ext.Action(Object.assign({
            text: config.text || app.formatMessage('Track Document'),
            iconCls: `action_track`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length === 1
                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler(cmp) {
                let record = this.initialConfig.selections[0]
                Tine.Sales.Document.TracDialog.openWindow({
                    documentModel: recordClass.getPhpClassName(),
                    document: {... record.data},
                })
            }

        }, config))
    }

    ['Offer', 'Order', 'Delivery', 'Invoice'].forEach((type) => {
        const action = getAction(type, {})
        const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-GridPanel-ContextMenu`, action, 2)
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), medBtnStyle), 30)
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 10)
    })
})

