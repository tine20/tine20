/**
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * TODO         add general local + modal dialog (can be used here, in Tine.Courses.AddMemberDialog and ...)
 */

Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * Generic 'Credentials' dialog
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.CredentialsDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * @constructor
 * @param       {Object} config The configuration options.
 */
Tine.widgets.dialog.CredentialsDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    credentialsId: null,
    
    /**
     * @private
     */
    windowNamePrefix: 'CredentialsWindow_',
    loadRecord: false,
    tbarItems: [],
    evalGrants: false,
    sendRequest: true,
    mode: 'local',
    
    /**
     * returns dialog
     */
    getFormItems: function() {
        return {
            bodyStyle: 'padding:5px;',
            buttonAlign: 'right',
            labelAlign: 'top',
            border: false,
            layout: 'form',
            defaults: {
                xtype: 'textfield',
                anchor: '90%',
                listeners: {
                    scope: this,
                    specialkey: function(field, event) {
                        if (event.getKey() == event.ENTER) {
                            this.onApplyChanges();
                        }
                    }
                }
            },
            items: [{
                fieldLabel: _('Username'), 
                name: 'username',
                allowBlank: false
            },{
                fieldLabel: _('Password'), 
                name: 'password',
                inputType: 'password'
            }]
        };
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
        
        var title = this.windowTitle || this.app.i18n._('Please enter your credentials');
        this.window.setTitle(title);
        
        this.getForm().loadRecord(this.record);
        
        this.loadMask.hide();
    },
    
    /**
     * generic apply changes handler
     */
    onApplyChanges: function() {
        var form = this.getForm();
        if(form.isValid()) {
            var values = form.getValues();
            
            if (this.sendRequest) {
                this.loadMask.show();
                
                var params = {
                    method: this.appName + '.changeCredentials',
                    password: values.password,
                    username: values.username,
                    id: this.credentialsId
                };
                
                Ext.Ajax.request({
                    params: params,
                    scope: this,
                    success: function(_result, _request){
                        this.loadMask.hide();
                        this.fireEvent('update', _result);
                        this.purgeListeners();
                        this.window.close();
                    }
                });
            } else {
                this.fireEvent('update', values);
                this.window.close();
            }
            
        } else {
            Ext.MessageBox.alert(_('Errors'), _('Please fix the errors noted.'));
        }
    }
});

/**
 * credentials dialog popup / window
 */
Tine.widgets.dialog.CredentialsDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 240,
        height: 180,
        name: Tine.widgets.dialog.CredentialsDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.CredentialsDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};
