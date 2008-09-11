/**
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.widgets');

/**
 * lang chooser widget
 */
Tine.widgets.TimezoneChooser = Ext.extend(Ext.form.ComboBox, {
    
    /**
     * @cfg {Sring}
     */
    fieldLabel: null,
    
    //displayField: 'timezone',
    //valueField: 'timezone',
    triggerAction: 'all',
    width: 100,
    listWidth: 200,
    
    initComponent: function() {
        this.value = Tine.Tinebase.Registry.get('timezone');
        this.fieldLabel = this.fieldLabel ? this.fieldLabel : _('Timezone');
        
        this.store = new Ext.data.JsonStore({
            id: 'timezone',
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Tinebase.Model.Timezone,
            baseParams: {
                method: 'Tinebase.getAvailableTimezones',
            }
        });
        Tine.widgets.TimezoneChooser.superclass.initComponent.call(this);
        
        this.on('select', this.onTimezoneSelect, this);
    },
    
    /**
     * timezone selection ajax call
     */
    onTimezoneSelect: function(combo, record, idx) {
        var currentTimezone = Tine.Tinebase.Registry.get('locale').locale;
        var newTimezone = record.get('timezone');
        
        if (newTimezone != currentTimezone) {
            Ext.MessageBox.wait(_('setting new timezone...'), _('Please Wait'));
            
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Tinebase.setTimezone',
                    timezoneString: newTimezone,
                    saveaspreference: true
                },
                success: function(result, request){
                    var responseData = Ext.util.JSON.decode(result.responseText);
                    window.location = window.location;
                }
            });
        }
    }
});
