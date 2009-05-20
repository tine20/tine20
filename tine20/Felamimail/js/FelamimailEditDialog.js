/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:MessageEditDialog.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * @ŧodo        make account combo work when loading from json
 * @todo        add buttons for add cc/ add bcc
 * @todo        add contact search combo for to/cc/bcc
 * @todo        add signature
 * @todo        add attachments
 * @todo        window title = subject?
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
    //layout: 'form',
    
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
    
    onRender : function(ct, position){
        Tine.Felamimail.MessageEditDialog.superclass.onRender.call(this, ct, position);
        
        //this.window.setTitle(this.record.get('subject'));
    },
        
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * TODO add recipient grid
     */
    getFormItems: function() {
        
        this.recipientGrid = new Tine.Felamimail.RecipientGrid({
            fieldLabel: _('Recipients'),
            record: this.record
        });
        
        this.htmlEditor = new Ext.form.HtmlEditor({
            fieldLabel: this.app.i18n._('Body'),
            name: 'body',
            allowBlank: true,
            height: 280,
            // TODO add signature style
            // TODO move css definitions to extern stylesheet?
            getDocMarkup: function(){
                var markup = '<html>'
                    + '<head>'
                    + '<META http-equiv="Content-Type" content="text/html; charset=UTF-8">'
                    + '<title></title>'
                    + '<style type="text/css">'
                        + 'blockquote {'
                            + 'margin: 5px 10px 0 3px;'
                            + 'padding-left: 10px;'
                            + 'border-left: 5px solid #000066;'
                        + '} '
                    + '</style>'
                    + '</head>'
                    + '<body class="com-conjoon-groupware-email-EmailForm-htmlEditor-body">'
                    + '</body></html>';
        
                return markup;
            }
        });
        
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
                        xtype:'reccombo',
                        name: 'from',
                        fieldLabel: this.app.i18n._('From'),
                        displayField: 'user',
                        store: new Ext.data.Store({
                            fields: Tine.Felamimail.Model.Account,
                            proxy: Tine.Felamimail.accountBackend,
                            reader: Tine.Felamimail.accountBackend.getReader(),
                            remoteSort: true,
                            sortInfo: {field: 'user', dir: 'ASC'}
                        })
                    }, this.recipientGrid
                    /*{
                        fieldLabel: this.app.i18n._('To'),
                        name: 'to',
                        allowBlank: false
                    }, {
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
                    }, 
                    this.htmlEditor
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
    //config.title = _('Write New Mail');
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 470,
        name: Tine.Felamimail.MessageEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Felamimail.MessageEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
