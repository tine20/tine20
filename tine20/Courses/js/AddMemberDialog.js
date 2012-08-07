/**
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

Ext.namespace('Tine.Courses');

/**
 * Generic 'Credentials' dialog
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.Courses.AddMemberDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * @constructor
 * @param       {Object} config The configuration options.
 */
Tine.Courses.AddMemberDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'AddMemberWindow_',
    loadRecord: false,
    tbarItems: [],
    evalGrants: false,
    mode: 'local',
    recordClass: Tine.Admin.Model.User,
    
    /**
     * needed for request
     */
    courseData: null,
    
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
                fieldLabel: this.app.i18n._('First Name'), 
                name: 'accountFirstName',
                allowBlank: false
            },{
                fieldLabel: this.app.i18n._('Last Name'), 
                name: 'accountLastName',
                allowBlank: false
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
        
        var title = this.windowTitle || this.app.i18n._('Add new member to course');
        this.window.setTitle(title);
        
        this.getForm().loadRecord(this.record);
        
        this.loadMask.hide();
        
        this.getForm().findField('accountFirstName').focus(true, 100);
    },
    
    /**
     * generic apply changes handler
     */
    onApplyChanges: function() {
        this.onRecordUpdate();
        if (this.isValid()) {
            this.loadMask.show();
            Tine.Courses.addNewMember(this.record.data, this.courseData, function(response, exception) {
                Tine.log.debug('Tine.Courses.CourseEditDialog::onMembersImport');
                Tine.log.debug(arguments);
                
                this.loadMask.hide();
                
                if (response) {
                    this.fireEvent('update', response);
                    this.window.close();
                } else {
                    Tine.Tinebase.ExceptionHandler.handleRequestException((exception.error) ? exception.error : exception);
                }
            }, this);
            
        } else {
            Ext.MessageBox.alert(_('Errors'), _('Please fix the errors noted.'));
        }
    }
});

/**
 * credentials dialog popup / window
 */
Tine.Courses.AddMemberDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 260,
        height: 160,
        name: Tine.Courses.AddMemberDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Courses.AddMemberDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};
