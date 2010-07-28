/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * TODO         add pref description to input fields
 * TODO         add icons from apps
 */

Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * preferences card panel
 * -> this panel is filled with the preferences subpanels containing the pref stores for the apps
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.PreferencesCardPanel
 * @extends     Ext.Panel
 */
Tine.widgets.dialog.PreferencesCardPanel = Ext.extend(Ext.Panel, {
    
    //private
    layout: 'card',
    border: false,
    frame: true,
    labelAlign: 'top',
    autoScroll: true,
    defaults: {
        anchor: '100%'
    },
    
    initComponent: function() {
        this.title = _('Preferences');
        Tine.widgets.dialog.PreferencesCardPanel.superclass.initComponent.call(this);
    }
});

/**
 * preferences panel with the preference input fields for an application
 * 
 * @todo add checkbox type
 */
Tine.widgets.dialog.PreferencesPanel = Ext.extend(Ext.Panel, {
    
	/**
	 * the prefs store
	 * @cfg {Ext.data.Store}
	 */
	prefStore: null,
	
    /**
     * @cfg {String} appName
     */
    appName: 'Tinebase',

    /**
     * @cfg {Boolean} adminMode activated?
     */
    adminMode: false,
    
    //private
    layout: 'form',
    border: true,
    labelAlign: 'top',
    autoScroll: true,
    defaults: {
        anchor: '95%',
        labelSeparator: ''
    },
    bodyStyle: 'padding:5px',
    
    initComponent: function() {
    	
        this.addEvents(
            /**
             * @event change
             * @param appName
             * Fired when a value is changed
             */
            'change'
        );    	
    	
        if (this.prefStore && this.prefStore.getCount() > 0) {
            
            this.items = [];
            this.prefStore.each(function(pref) {
                
        	    // check if options available -> use combobox or textfield
                var fieldDef = {
                    fieldLabel: pref.get('label'),
                    name: pref.get('name'),
                    value: pref.get('value'),
                    listeners: {
                    	scope: this,
                    	change: function(field, newValue, oldValue) {
                    		// fire change event
                    		this.fireEvent('change', this.appName);
                    	}
                    },
                    prefId: pref.id,
                    description: pref.get('description')
                };
                
                // evaluate xtype
                var xtype = (pref.get('options') && pref.get('options').length > 0) ? 'combo' : 'textfield';
                if (xtype == 'combo' && this.adminMode) {
                    xtype = 'lockCombo';
                } else if (xtype == 'textfield' && this.adminMode) {
                    xtype = 'lockTextfield';
                }
                fieldDef.xtype = xtype;
                
                if (pref.get('options') && pref.get('options').length > 0) {
                	// add additional combobox config
                	fieldDef.store = pref.get('options');
                	fieldDef.mode = 'local';
                    fieldDef.forceSelection = true;
                    fieldDef.triggerAction = 'all';
                }
                
                if (this.adminMode) {
                    Tine.log.debug(pref);
                	// set lock (value forced => hiddenFieldData = '0')
                	fieldDef.hiddenFieldData = (pref.get('type') == 'default') ? '1' : '0';
                	fieldDef.hiddenFieldId = pref.get('name') + '_writable';
                    // disable personal only fields (not quite sure why we get a string here in personal_only field)
                    fieldDef.disabled = (pref.get('personal_only') === '1' || pref.get('personal_only') === true);
                } else {
                	fieldDef.disabled = (pref.get('type') == 'forced');
                }
                
                //console.log(fieldDef);
                try {
                    var fieldObj = Ext.ComponentMgr.create(fieldDef);
                    this.items.push(fieldObj);

                    // ugh a bit ugly
                    // what does that do??
                    pref.fieldObj = fieldObj;
                } catch (e) {
                	//console.log(e);
                    console.error('Unable to create preference field "' + pref.get('name') + '". Check definition!');
                    this.prefStore.remove(pref);
                }
            }, this);

        } else {
            this.html = '<div class="x-grid-empty">' + _('There are no preferences for this application.') + "</div>";
        }
        
        Ext.QuickTips.init();

        Tine.widgets.dialog.PreferencesPanel.superclass.initComponent.call(this);
    },
    
    /**
     * afterRender -> adds qtips to all elements
     * 
     * @private
     * 
     * @todo add qtip to label as well
     */
    afterRender: function() {
        Tine.widgets.dialog.PreferencesPanel.superclass.afterRender.call(this);
        
        if (this.items && this.items.items) {
            for (var i=0; i < this.items.items.length; i++) {
            	var field = this.items.items[i];
                Ext.QuickTips.register({
                    target: field,
                    title: field.fieldLabel,
                    text: field.description,
                    width: 200
                });        	
            }
        }
    }
});
