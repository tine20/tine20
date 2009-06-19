/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: AttendeeGridPanel.js 8754 2009-06-18 08:50:02Z c.weiss@metaways.de $
 *
 */
 
Ext.ns('Tine.Calendar');


Tine.Calendar.CalendarSelectWidget = function(EventEditDialog) {
    this.EventEditDialog = EventEditDialog;
    
    this.app = Tine.Tinebase.appMgr.get('Calendar');
    this.recordClass = Tine.Calendar.Model.Event;
    
    this.initTpl();
    
    this.fakeCombo = new Ext.form.ComboBox({
        typeAhead     : false,
        triggerAction : 'all',
        editable      : false,
        mode          : 'local',
        value         : null,
        forceSelection: true,
        width         : 450,
        store         : EventEditDialog.attendeeStore,
        //itemSelector  : 'div.list-item',
        tpl           : this.attendeeListTpl
    });
    
    this.calCombo = new Tine.widgets.container.selectionComboBox({
        //id: this.app.appName + 'EditDialogPhysCalSelector',
        fieldLabel: Tine.Tinebase.tranlation._hidden('Saved in'),
        width: 450,
        containerName: this.app.i18n._hidden(this.recordClass.getMeta('containerName')),
        containersName: this.app.i18n._hidden(this.recordClass.getMeta('containersName')),
        appName: this.app.appName,
        hideTrigger2: false,
        trigger2Class: 'cal-invitation-trigger',
        onTrigger2Click: this.fakeCombo.onTriggerClick.createDelegate(this.fakeCombo),
        allowBlank: true
    });
    
    
    
};

Ext.extend(Tine.Calendar.CalendarSelectWidget, Ext.form.ComboBox, {
    
    /**
     * @property {Tine.Calendar.EventEditDialog}
     */
    EventEditDialog: null,
    /**
     * @property {Tine.widgets.container.selectionComboBox} calCombo
     */
    calCombo: null,
    
    initTpl: function() {
        //this.attendeeListTpl = '<tpl for="."><div class="x-combo-list-item">Hier{' + 'type' + '}</div></tpl>';
        var getContainerName = this.EventEditDialog.attendeeGridPanel.renderAttenderDispContainer.createDelegate(this);
        var getAccountName = this.EventEditDialog.attendeeGridPanel.renderAttenderName.createDelegate(this);
        
        this.attendeeListTpl = new Ext.XTemplate(
            '<tpl for=".">' +
                '<div class="'+'x-combo-list'+'-item">' +
                        '<div style="width: 290px; float: left;">{[this.getContainerName(values.displaycontainer_id, {})]}</div><div>{[this.getAccountName(values.user_id, {})]}</div>' +
                '</div>' +
            '</tpl>', {
                /**
                 * encode
                 */
                encode: function(value) {
                    return value ? Ext.util.Format.htmlEncode(value) : '';
                },
                getContainerName: getContainerName,
                getAccountName: getAccountName
            }
        );
    },
    
    onRecordLoad: function(record) {
        this.record = record;
        this.calCombo.setValue(record.get('container_id'));
        this.calCombo.setTrigger2Text('fuer Cornelius Weiss');
        
    },
    
    onRecordUpdate: function(record) {
        console.log('onRecordUpdate');
    },
    
    render: function(el) {
        this.el = el;
        
        new Ext.Panel({
            layout: 'form',
            border: false,
            renderTo: el,
            bodyStyle: {'background-color': '#F0F0F0'},
            items: this.calCombo
        });
        
        this.fakeCombo.render(this.el.insertFirst({tag: 'div', style: {'position': 'absolute', 'top': '0px', 'right': '0px'}}));
    }
});
