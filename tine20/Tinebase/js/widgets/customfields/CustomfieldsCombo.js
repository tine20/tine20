/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         this is only used in the phone dial dialog, do we need that?
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.customfields');

/**
 * CustomfieldsCombo
 * 
 * @namespace   Tine.widgets.customfields
 * @class       Tine.widgets.customfields.CustomfieldsCombo
 * @extends     Ext.form.ComboBox
 * 
 */
Tine.widgets.customfields.CustomfieldsCombo = Ext.extend(Ext.form.ComboBox, {
    
    typeAhead: false,
    forceSelection: true,
    mode: 'local',
    triggerAction: 'all',    
    
    
    initComponent: function() {
        
        Tine.widgets.customfields.CustomfieldsCombo.superclass.initComponent.call(this);

    },
    
    
       stateEvents: ['select'],
     getState: function() { return this.getValue(); },
    applyState: function(state) { this.setValue(state); }
});


