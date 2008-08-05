/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.widgets');

Ext.namespace('Tine.widgets.Priority');
Tine.widgets.Priority.store = new Ext.data.SimpleStore({
    storeId: 'Priorities',
    id: 'key',
    fields: ['key','value', 'icon'],
    data: [
            ['0', 'low',    '' ],
            ['1', 'normal', '' ],
            ['2', 'high',   '' ],
            ['3', 'urgent', '' ]
        ]
});

Tine.widgets.Priority.Combo = Ext.extend(Ext.form.ComboBox, {
    /**
     * @cfg {bool} autoExpand Autoexpand comboBox on focus.
     */
    autoExpand: false,
    /**
     * @cfg {bool} blurOnSelect blurs combobox when item gets selected
     */
    blurOnSelect: false,
    
    displayField: 'value',
    valueField: 'key',
    mode: 'local',
    triggerAction: 'all',
    //selectOnFocus: true,
    editable: false,
    lazyInit: false,
    
    //private
    initComponent: function(){
        Tine.widgets.Priority.Combo.superclass.initComponent.call(this);
        // allways set a default
        if(!this.value) {
            this.value = 1;
        }
            
        this.store = Tine.widgets.Priority.store;
        
        if (this.autoExpand) {
            this.on('focus', function(){
                this.lazyInit = false;
                this.expand();
            });
        }
        if (this.blurOnSelect){
            this.on('select', function(){
                this.fireEvent('blur', this);
            }, this);
        }
    }
});

Ext.reg('tineprioritycombo', Tine.widgets.Priority.Combo);

Tine.widgets.Priority.renderer = function(priority) {
    var s = Tine.widgets.Priority.store;
    var idx = s.find('key', priority);
    return (idx !== undefined && idx >= 0) ? s.getAt(idx).data.value : priority;
};
