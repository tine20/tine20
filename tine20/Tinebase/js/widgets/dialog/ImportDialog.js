/**
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

Ext.namespace('Tine.widgets', 'Tine.widgets.dialog');

/**
 * Generic 'Import' dialog
 */
/**
 * @class Tine.widgets.dialog.ImportPanel
 * @extends Tine.widgets.dialog.EditDialog
 * @constructor
 * @param {Object} config The configuration options.
 * 
 * TODO add form fields
 * TODO use import model (with import definition, file, container id, dry run)
 * TODO add file upload grid
 * TODO add app grid to show results when dry run is selected
 */
Tine.widgets.dialog.ImportDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @cfg {String} title of window
     */
    windowTitle: '',
    
    credentialsId: null,
    
    /**
     * @private
     */
    windowNamePrefix: 'ImportWindow_',
    loadRecord: false,
    tbarItems: [],
    evalGrants: false,
    sendRequest: true,
    
    /**
     * init record to edit
     * 
     * - overwritten: we don't have a record here 
     */
    initRecord: function() {
    },
    
    onRender: function() {
        this.supr().onRender.apply(this, arguments);
        this.window.setTitle(this.windowTitle);
    },
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
                anchor: '90%'/*,
                listeners: {
                    scope: this,
                    specialkey: function(field, event) {
                        if (event.getKey() == event.ENTER) {
                            this.onApplyChanges({}, event, true);
                        }
                    }
                }*/
            },
            items: [/*{
                fieldLabel: _('Username'), 
                name: 'username',
                allowBlank: false
            },{
                fieldLabel: _('Password'), 
                name: 'password',
                inputType: 'password'
            }*/]
        };
    }
    
    /**
     * generic apply changes handler
     */
    /*
    onApplyChanges: function(button, event, closeWindow) {
        var form = this.getForm();
        if(form.isValid()) {
            var values = form.getValues();
            
            if (this.sendRequest) {
                this.loadMask.show();
                
                var params = {
                    method: this.appName + '.changeImport',
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
                        
                        if (closeWindow) {
                            this.purgeListeners();
                            this.window.close();
                        }
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
    */
});

/**
 * credentials dialog popup / window
 */
Tine.widgets.dialog.ImportDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 240,
        height: 180,
        name: Tine.widgets.dialog.ImportDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.ImportDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};
