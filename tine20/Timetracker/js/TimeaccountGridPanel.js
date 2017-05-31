/*
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Timetracker');

/**
 * Timeaccount grid panel
 * 
 * @namespace   Tine.Timetracker
 * @class       Tine.Timetracker.TimeaccountGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Timeaccount Grid Panel</p>
 * <p><pre>
 * TODO         copy action needs to copy the acl too
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Timetracker.TimeaccountGridPanel
 */
Tine.Timetracker.TimeaccountGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    defaultSortInfo: {field: 'creation_time', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'title'
    },
    copyEditAction: true,
    defaultFilters: [{
        field: 'query',
        operator: 'contains',
        value: ''
    }, {
        field: 'is_open',
        operator: 'equals',
        value: true
    }],
    
    initComponent: function() {
        Tine.Timetracker.TimeaccountGridPanel.superclass.initComponent.call(this);
        
        this.action_addInNewWindow.setDisabled(! Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts'));
        this.action_editInNewWindow.requiredGrant = 'editGrant';
    },

    /**
     * add custom items to context menu
     *
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-', {
                text: Tine.Tinebase.appMgr.get('Timetracker').i18n._('Close Timeaccount'),
                iconCls: 'action_edit',
                scope: this,
                disabled: !Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts'),
                itemId: 'closeAccount',
                handler: this.onCloseTimeaccount.createDelegate(this)
            }
        ];

        return items;
    },

    /**
     * Closes selected timeaccount
     */
    onCloseTimeaccount: function () {
        var grid = this,
            recordProxy = this.recordProxy,
            selectionModel = grid.selectionModel;

        Ext.each(selectionModel.getSelections(), function (record) {
            recordProxy.loadRecord(record, {
                success: function (record) {
                    record.set('is_open', false);
                    recordProxy.saveRecord(record, {
                        success: function () {
                            grid.store.reload();
                            grid.store.remove(record);
                        }
                    });
                }
            });
        });
    }
});
