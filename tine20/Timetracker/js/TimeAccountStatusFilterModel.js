/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Timetracker');

Tine.Timetracker.TimeAccountStatusFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    field: 'is_open',
    valueType: 'bool',
    defaultValue: 1,
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.Timetracker.TimeAccountStatusFilterModel.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get('Timetracker');
        this.label = this.label ? this.label : this.app.i18n._("Time Account - Status");
        this.operators = ['equals'];
    },
   
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        // value
        var value = new Ext.form.ComboBox({
            filter: filter,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el,
            mode: 'local',
            forceSelection: true,
            blurOnSelect: true,
            triggerAction: 'all',
            store: [[0, this.app.i18n._('closed')], [1, this.app.i18n._('open')]]
        });
        value.on('specialkey', function(field, e){
             if(e.getKey() == e.ENTER){
                 this.onFiltertrigger();
             }
        }, this);
        
        return value;
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['timetracker.timeaccountstatus'] = Tine.Timetracker.TimeAccountStatusFilterModel;
