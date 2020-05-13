/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail.sieve');

 Tine.Felamimail.sieve.NotificationDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'NotificationWindow_',
    appName: 'Felamimail',
    loadRecord: true,
    tbarItems: [],
    evalGrants: false,
    readonlyReason: false,
    
    initComponent: function() {
        this.recordClass = Tine.Felamimail.Model.Account;
        this.recordProxy = Tine.Felamimail.accountBackend;
        
        Tine.Felamimail.sieve.NotificationDialog.superclass.initComponent.call(this);
    }, 
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * 
     * @private
     */
    updateToolbars: function() {
    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }

        this.getForm().loadRecord(this.record);

        var title = String.format(this.app.i18n._('Notification for {0}'), this.record.get('name'));
        this.window.setTitle(title);

        this.loadMask.hide();
    },
        
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     * 
     */
    getFormItems: function() {

        return {
            xtype: 'tabpanel',
            deferredRender: false,
            border: false,
            activeTab: 0,
            items: [{
                title: this.app.i18n._('Notifications'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 1,
                },
                items: [[{
                    maxLength: 256,
                    fieldLabel: this.app.i18n._('Notification Email'),
                    name: 'sieve_notification_email',
                    vtype: 'email'
                }]]
            }]
        };
    }
});

/**
 * Felamimail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.sieve.NotificationDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 320,
        height: 200,
        name: Tine.Felamimail.sieve.NotificationDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.sieve.NotificationDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
