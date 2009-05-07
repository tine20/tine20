/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * @todo        use it for reply/reply to all/forward
 * @todo        add buttons for add cc/ add bcc
 * @todo        add contact search combo for to/cc/bcc
 * @todo        add signature
 * @todo        add attachments
 */
 
Ext.namespace('Tine.Felamimail');

Tine.Felamimail.MessageEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'MessageEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Message,
    recordProxy: Tine.Felamimail.messageBackend,
    loadRecord: false,
    tbarItems: [/*{xtype: 'widget-activitiesaddbutton'}*/],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function() {

    },
    
    onRecordLoad: function() {
    	// you can do something here

    	Tine.Felamimail.MessageEditDialog.superclass.onRecordLoad.call(this);
        
        this.window.setTitle(_('Write New Mail'));
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        return {
            //title: this.app.i18n._('Message'),
            autoScroll: true,
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'center',
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype:'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 1
                },
                items: [[{
                        fieldLabel: this.app.i18n._('To'),
                        name: 'to',
                        allowBlank: false
                    }/*, {
                        fieldLabel: this.app.i18n._('Cc'),
                        name: 'cc',
                        allowBlank: true
                    }, {
                        fieldLabel: this.app.i18n._('Bcc'),
                        name: 'bcc',
                        allowBlank: false
                    }*/, {
                        fieldLabel: this.app.i18n._('Subject'),
                        name: 'subject',
                        allowBlank: false
                    }, {
                        fieldLabel: this.app.i18n._('Body'),
                        name: 'body',
                        allowBlank: true,
                        xtype:'htmleditor',
                        height: 280
                    }
                ]] 
            }]
        };
    }
});

/**
 * Felamimail Edit Popup
 */
Tine.Felamimail.MessageEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Felamimail.MessageEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Felamimail.MessageEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
