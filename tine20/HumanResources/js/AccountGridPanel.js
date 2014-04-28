/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

/**
 * Account grid panel
 * 
 * @namespace   Tine.HumanResources
 * @class       Tine.HumanResources.AccountGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Account Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.HumanResources.AccountGridPanel
 */
Tine.HumanResources.AccountGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    initComponent: function() {
        this.actions_bookVacation = new Ext.Action({
            text: this.app.i18n._('Book remaining vacation'),
            iconCls: 'action_export',
            allowMultiple: true,
            handler: this.bookRemaining.createDelegate(this)
        });
        
        this.contextMenuItems = [this.actions_bookVacation];
        
        Tine.HumanResources.AccountGridPanel.superclass.initComponent.call(this);
        this.action_addInNewWindow.hide();
        this.action_deleteRecord.hide();
    },
    
    createMissingAccounts: function() {
        Ext.Msg.prompt(this.app.i18n._('Year'), this.app.i18n._('Please enter the year you want to create accounts for:'), function(btn, year) {
            if (btn == 'ok') {
                Ext.Ajax.request({
                    url : 'index.php',
                    timeout: 60000*5,
                    params : { method : 'HumanResources.createMissingAccounts', year: year},
                    success : function(_result, _request) {
                        this.onAccountCreateSuccess(Ext.decode(_result.responseText));
                    },
                    failure : function(exception) {
                        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                    },
                    scope: this
                });
            }
        }, this);
    },
    
    onAccountCreateSuccess: function(data) {
        Ext.MessageBox.show({
            buttons: Ext.Msg.OK,
            icon: Ext.MessageBox.INFO,
            title: this.app.i18n._('Accounts have been created'), 
            msg: String.format(this.app.i18n._('{0} accounts for the year {1} have been created successfully!'), data.totalcount, data.year)
        });
    },
    
    /**
     * returns additional toobar items
     * 
     * @return {Array} of Ext.Action
     */
    getActionToolbarItems: function() {
        this.actions_createAccounts = new Ext.Action({
            text: this.app.i18n._('Create new accounts'),
            iconCls: 'action_create_accounts',
            allowMultiple: true,
            handler: this.createMissingAccounts.createDelegate(this)
        });
        
        var button = Ext.apply(new Ext.Button(this.actions_createAccounts), {
            scale: 'medium',
            rowspan: 2,
            iconAlign: 'top'
        });
        
        var additionalActions = [this.actions_exportIPAggregate];
        this.actionUpdater.addActions(additionalActions);
        return [button];
    },
    
    /**
     * Books remaining vacation from an old year to the new year
     * 
     * @param {} action
     * @param {} event
     */
    bookRemaining: function(action, event) {
        var selections = this.getGrid().getSelectionModel().getSelections();
        var ids = [];
        
        Ext.each(selections, function(sel) {
            ids.push(sel.getId());
        });
        
        var req = Ext.Ajax.request({
            url : 'index.php',
            timeout: 60000*5,
            params : { method : 'HumanResources.bookRemaining', ids: ids},
            success : function(_result, _request) {
                this.onAfterBookRemaining(Ext.decode(_result.responseText));
            },
            failure : function(exception) {
                Tine.HumanResources.handleRequestException(exception);
            },
            scope: this
        });
    },
    
    /**
     * is called if booking remaining was successfull
     * 
     * @param {Object} response
     */
    onAfterBookRemaining: function(response) {
        Ext.MessageBox.show({
            buttons: Ext.Msg.OK,
            icon: Ext.MessageBox.INFO,
            title: this.app.i18n._('Booking has been successfull!'), 
            msg: this.app.i18n._('The remaining vacation days of the selected accounts have been booked successfully!')
        });
    }
});
