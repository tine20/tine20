/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         add pref description to input fields
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
    
    /**
     * init component
     * @private
     */
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
            Tine.log.debug('Tine.widgets.dialog.PreferencesPanel::initComponent() -> Adding pref items from store:');
            Tine.log.debug(this.prefStore);
            
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
                
                var options = pref.get('options');
                // NOTE: some prefs have no default and only one option (e.g. std email account)
                if (options.length > 1 || (options.length == 1 && options[0][0] !== '_default_')) {
                    Ext.apply(fieldDef, {
                        xtype: (this.adminMode ? 'lockCombo' : 'combo'),
                        store: pref.get('options'),
                        mode: 'local',
                        forceSelection: true,
                        allowBlank: false,
                        triggerAction: 'all'
                    });
                } else {
                    Ext.apply(fieldDef, {
                        xtype: (this.adminMode ? 'lockTextfield' : 'textfield'),
                        defaultValue: (options[0] && options[0][1]) ? options[0][1] : '',
                        isValid: function() {
                            // crude hack to guess type (prefs need DTD)
                            var type = this.defaultValue.match(/\(([0-9]*)\)$/) ? 'int' : 'string',
                                value = this.getValue();
                            
                            // default is always valid
                            if (value == '_default_') {
                                return true;
                            }
                            
                            if (type == 'int') {
                                return !! String(value).match(/\d+/);
                            }
                            
                            return true;
                        },
                        setValue: function(v) {
                            v = v == '_default_' ? this.defaultValue : v;
                            Ext.form.TextField.prototype.setValue.call(this, v);
                        },
                        getValue: function() {
                            var value = Ext.form.TextField.prototype.getValue.call(this);
                            return value == this.defaultValue ? '_default_' : value;
                        },
                        postBlur: function() {
                            var value = this.getValue();
                            if (value === '') {
                                this.setValue('_default_');
                            }
                        }
                    });
                }
                
                if (this.adminMode) {
                    // set lock (value forced => hiddenFieldData = '0')
                    fieldDef.hiddenFieldData = (pref.get('type') == 'forced') ? '0' : '1';
                    fieldDef.hiddenFieldId = pref.get('name') + '_writable';
                    // disable personal only fields (not quite sure why we get a string here in personal_only field)
                    fieldDef.disabled = (pref.get('personal_only') === '1' || pref.get('personal_only') === true);
                } else {
                    fieldDef.disabled = (pref.get('type') == 'forced');
                }
                
                try {
                    var fieldObj = Ext.ComponentMgr.create(fieldDef);
                    this.items.push(fieldObj);

                    // ugh a bit ugly
                    // what does that do??
                    pref.fieldObj = fieldObj;
                } catch (e) {
                    Tine.log.debug(e);
                    Tine.log.err('Unable to create preference field "' + pref.get('name') + '". Check definition!');
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
        
        // NOTE: server side translations have problems with quotes. Preferences with quotes
        //       in their description don't get translated. Thus we (re) translate them here
        //       as the js translations are much better
        var app = Tine.Tinebase.appMgr.get(this.appName),
            gt  = app ? app.i18n._.createDelegate(app.i18n) : _;
        
        if (this.items && this.items.items) {
            for (var i=0; i < this.items.items.length; i++) {
                var field = this.items.items[i];
                Ext.QuickTips.register({
                    target: field,
                    dismissDelay: 30000,
                    title: Ext.util.Format.htmlEncode(gt(field.fieldLabel)),
                    text: Ext.util.Format.htmlEncode(gt(field.description)),
                    width: 200
                });
            }
        }
    },
    
    /**
     * check validity for all panel items
     * 
     * @return {Boolean}
     */
    isValid: function() {
        var isValid = true;
        
        if (this.items && this.items.items) {
            var field;
            for (var i=0; i < this.items.items.length; i++) {
                field = this.items.items[i];
                if (! field.isValid()) {
                    field.markInvalid();
                    isValid = false;
                }
            }
        }
        
        return isValid;
    }
});
