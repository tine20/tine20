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

Tine.Calendar.CalendarSelectWidget = function(config) {
    Ext.apply(this, config);
    Tine.Calendar.CalendarSelectWidget.superclass.constructor.call(this);
    
    this.app = Tine.Tinebase.appMgr.get('Calendar');
    this.recordClass = Tine.Calendar.Model.Event;
    
    this.physCalCombo = new Tine.widgets.container.selectionComboBox({
        id: this.app.appName + 'EditDialogPhysCalSelector',
        fieldLabel: Tine.Tinebase.tranlation._hidden('Saved in'),
        name: 'container_id',
        width: 300,
        name: this.recordClass.getMeta('containerProperty'),
        containerName: this.app.i18n._hidden(this.recordClass.getMeta('containerName')),
        containersName: this.app.i18n._hidden(this.recordClass.getMeta('containersName')),
        appName: this.app.appName,
        hideTrigger1: false,
        trigger1Class: 'cal-invitation-trigger',
        onTrigger1Click: this.toggle.createDelegate(this),
        
        allowBlank: true // for the moment
    });
    
    this.dispCalCombo = new Tine.widgets.container.selectionComboBox({
        id: this.app.appName + 'EditDialogDispCalSelector',
        fieldLabel: this.app.i18n._('Show in'),
        width: 300,
        name: this.recordClass.getMeta('containerProperty'),
        containerName: this.app.i18n._hidden(this.recordClass.getMeta('containerName')),
        containersName: this.app.i18n._hidden(this.recordClass.getMeta('containersName')),
        appName: this.app.appName,
        hideTrigger1: false,
        trigger1Class: 'cal-invitation-trigger',
        onTrigger1Click: this.toggle.createDelegate(this),
        
        allowBlank: true // for the moment
    });
},

Ext.extend(Tine.Calendar.CalendarSelectWidget, Ext.util.Observable, {
    /**
     * @cfg String activeSelector
     */
    activeSelector: 'disp',
    
    /**
     * @property {Tine.widgets.container.selectionComboBox} physCalCombo
     */
    physCalCombo: null,
    /**
     * @property {Tine.widgets.container.selectionComboBox} dispCalCombo
     */
    dispCalCombo: null,
    /**
     * @property {Tine.Calendar.Model.Event}
     */
    record: null,

    
    
    getFormItems: function() {
        return [
            this.physCalCombo,
            this.dispCalCombo
        ];
    },
    
    insertEl: function() {
        return this.el.insertFirst({tag: 'div', style: {'position': 'absolute', 'top': '0px', 'left': '0px'}});
    },
    
    onRecordLoad: function(record) {
        this.record = record;
        
    },
    
    onRecordUpdate: function(record) {
        
    },
    
    render: function(el) {
        this.el = el;
        
        this.physEl = this.insertEl();
        new Ext.Panel({
            layout: 'form',
            border: false,
            renderTo: this.physEl,
            bodyStyle: {'background-color': '#F0F0F0'},
            items: this.physCalCombo
        });
        
        this.dispEl = this.insertEl();
        new Ext.Panel({
            layout: 'form',
            border: false,
            renderTo: this.dispEl,
            bodyStyle: {'background-color': '#F0F0F0'},
            items: this.dispCalCombo
        });
        
        var toDeactivate = this.activeSelector == 'disp' ? 'phys' : 'disp';
        this[toDeactivate + 'El'].hide();
    },
    
    showDisp: function(anim) {
        if (this.activeSelector != 'disp') {
            this.toggle(anim);
        }
    },
    
    showPhys: function(anim) {
        if (this.activeSelector != 'phys') {
            this.toggle(anim);
        }
    },
    
    toggle: function(anim) {
        this[this.activeSelector + 'El'].hide(anim);
        var toActivate = this.activeSelector == 'disp' ? 'phys' : 'disp';
        
        this[toActivate + 'El'].show(anim);
        this.activeSelector = toActivate;
    }
    
});
