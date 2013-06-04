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
     * @param {} response
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
