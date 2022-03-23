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
    recordClass: 'Tine.Timetracker.Model.Timeaccount',
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
    action_bookmark: null,

    initComponent: function() {
        Tine.Timetracker.TimeaccountGridPanel.superclass.initComponent.call(this);

        this.action_addInNewWindow.setDisabled(! Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts'));
        this.action_editInNewWindow.requiredGrant = 'editGrant';
    },

    /**
     * @private
     */
    initActions: function() {
        Tine.Timetracker.TimesheetGridPanel.superclass.initActions.call(this);

        this.action_bookmark = new Ext.Action({
            text: i18n._('Bookmark Timeaccount'),
            iconCls: 'action_add',
            requiredGrant: 'readGrant',
            scope: this,
            disabled: true,
            allowMultiple: false,
            visible: false,
            handler: this.addBookmark
        });

        // register actions in updater
        this.actionUpdater.addActions([
            this.action_bookmark
        ]);
    },

    addBookmark: function () {
        var grid = this,
            app = this.app,
            selectionModel = grid.selectionModel;

        Ext.each(selectionModel.getSelections(), function (record) {
            Ext.Ajax.request({
                url: 'index.php',
                scope: this,
                params: {
                    method: 'Timetracker.addTimeAccountFavorite',
                    timeaccountId: record.get('id')
                },
                success : function(_result, _request) {
                    if (_result.responseText) {
                        Tine.Timetracker.registry.set('timeaccountFavorites', JSON.parse(_result.responseText).timeaccountFavorites);

                        var rootNode = Ext.getCmp('TimeaccountFavoritesPanel').getRootNode();

                        if (!rootNode.hasChildNodes()) {
                            rootNode.removeAll();
                        }

                        rootNode.removeAll();
                        rootNode.appendChild(Tine.Timetracker.registry.get('timeaccountFavorites'));

                        rootNode.expand();
                    }
                },
                failure: function(result) {
                    Tine.Tinebase.ExceptionHandler.handleRequestException(result);
                }
            });
        });
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
                iconCls: 'action_close',
                scope: this,
                disabled: !Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts'),
                itemId: 'closeAccount',
                handler: this.onCloseTimeaccount.createDelegate(this)
            }
        ];

        if (this.app.featureEnabled('featureTimeaccountBookmark')) {
            items.push(this.action_bookmark);
        }

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
