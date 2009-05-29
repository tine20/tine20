/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * TODO         show account name instead of id in account combo
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
    tbarItems: [],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function() {

    },
    
    /**
     * init record to edit
     * 
     * - overwritten to allow initialization from grid/onEditInNewWindow 
     */
    initRecord: function() {
        this.onRecordLoad();
    },
    
    /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        var title = this.app.i18n._('Compose email:');
        if (this.record.get('subject')) {
            title = title + ' ' + this.record.get('subject');
        }
        this.window.setTitle(title);
        
        this.getForm().loadRecord(this.record);
        //this.updateToolbars(this.record, this.recordClass.getMeta('containerProperty'));
        
        //console.log(this.record);
        
        this.loadMask.hide();
    },
        
    /**
     * execuded when record gets updated from form
     * - add attachments to record here
     * 
     * TODO add recipients here as well?
     */
    onRecordUpdate: function() {

        console.log(this.record);
        
        this.record.data.attachments = [];
        this.attachmentGrid.store.each(function(record) {
            this.record.data.attachments.push(record.data);
        }, this);
        
        Tine.Felamimail.MessageEditDialog.superclass.onRecordUpdate.call(this);
    },
    
    /**
     * show error if request fails
     * 
     * @param {} response
     * @param {} request
     * 
     * TODO mark field(s) invalid if for example email is incorrect
     * TODO add exception dialog on critical errors?
     */
    onRequestFailed: function(response, request) {
        var responseText = Ext.util.JSON.decode(response.responseText);
        //console.log(responseText);
        Ext.MessageBox.alert(
            this.app.i18n._('Failed'), 
            String.format(this.app.i18n._('Could not send {0}.'), this.i18nRecordName) 
                + ' ( ' + this.app.i18n._('Error:') + ' ' + responseText.msg + ')'
        ); 
        this.loadMask.hide();
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * TODO get css definitions from extern stylesheet?
     */
    getFormItems: function() {
        
        this.recipientGrid = new Tine.Felamimail.RecipientGrid({
            fieldLabel: this.app.i18n._('Recipients'),
            record: this.record,
            il8n: this.app.i18n,
            anchor: '100% 90%'
        });
        
        this.attachmentGrid = new Tine.Felamimail.AttachmentGrid({
            fieldLabel: this.app.i18n._('Attachments'),
            record: this.record,
            il8n: this.app.i18n,
            anchor: '100% 90%'
        });
        
        this.htmlEditor = new Ext.form.HtmlEditor({
            fieldLabel: this.app.i18n._('Body'),
            name: 'body',
            allowBlank: true,
            anchor: '100% 90%',
            //height: 200,
            getDocMarkup: function(){
                var markup = '<html>'
                    + '<head>'
                    + '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
                    + '<title></title>'
                    + '<style type="text/css">'
                        + '.felamimail-body-blockquote {'
                            + 'margin: 5px 10px 0 3px;'
                            + 'padding-left: 10px;'
                            + 'border-left: 2px solid #000088;'
                        + '} '
                        + '.felamimail-body-signature {'
                            + 'font-size: 9px;'
                            + 'color: #bbbbbb;'
                        + '} '
                    + '</style>'
                    + '</head>'
                    + '<body>'
                    + '</body></html>';
        
                return markup;
            }
        });
        
        var accountStore = Tine.Felamimail.loadAccountStore();
        
        return {
            autoScroll: true,
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'north',
                height: 160,
                resizable: true,
                layout: 'border',
                split: true,
                collapseMode: 'mini',
                collapsible: true,
                items: [{
                    region: 'north',
                    height: 40,
                    layout: 'form',
                    labelAlign: 'top',
                    items: [{
                        //xtype:'reccombo',
                        xtype:'combo',
                        name: 'from',
                        fieldLabel: this.app.i18n._('From'),
                        displayField: 'name',
                        valueField: 'id',
                        editable: false,
                        triggerAction: 'all',
                        anchor: '100%',
                        store: accountStore
                        /*
                        store: new Ext.data.Store({
                            fields: Tine.Felamimail.Model.Account,
                            proxy: Tine.Felamimail.accountBackend,
                            reader: Tine.Felamimail.accountBackend.getReader(),
                            remoteSort: true,
                            sortInfo: {field: 'user', dir: 'ASC'}
                        })
                        */
                    }]
                }, {
                    region: 'center',
                    layout: 'form',
                    items: [this.recipientGrid]
                }, {
                    region: 'south',
                    layout: 'form',
                    height: 40,
                    labelAlign: 'top',
                    items: [{
                        xtype:'textfield',
                        fieldLabel: this.app.i18n._('Subject'),
                        name: 'subject',
                        allowBlank: false,
                        enableKeyEvents: true,
                        anchor: '100%',
                        listeners: {
                            scope: this,
                            // update title on keyup event
                            'keyup': function(field, e) {
                                if (! e.isSpecialKey()) {
                                    this.window.setTitle(
                                        this.app.i18n._('Compose email:') + ' ' 
                                        + field.getValue()
                                    );
                                }
                            }
                        }
                    }]
                }]
            }, {
                region: 'center',
                layout: 'form',
                labelAlign: 'top',
                items: [this.htmlEditor]
            }, {
                region: 'south',
                layout: 'form',
                height: 80,
                split: true,
                collapseMode: 'mini',
                collapsible: true,
                items: [this.attachmentGrid]
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
        height: 600,
        name: Tine.Felamimail.MessageEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Felamimail.MessageEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
