/*
 * Tine 2.0
 * 
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.RequestTracker');

Tine.RequestTracker.StatusCombo = Ext.extend(Ext.form.ComboBox, {
    /**
     * @property {Tine.Tinebase.Application}
     */
    app: null,
    
    mode: 'local',
    triggerAction: 'all',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('RequestTracker');
        
        this.store = [
            ['new',      this.app.i18n._('New')],
            ['open',     this.app.i18n._('Open')],
            ['stalled',  this.app.i18n._('Stalled')],
            ['waiting',  this.app.i18n._('Waiting')],
            ['pending',  this.app.i18n._('Pending')],
            ['resolved', this.app.i18n._('Resolved')],
            ['rejected', this.app.i18n._('Rejected')],
            ['deleted',  this.app.i18n._('Deleted')]
        ];
        
        Tine.RequestTracker.StatusCombo.superclass.initComponent.call(this);
    }
});

Tine.RequestTracker.TicketGridStatusFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    field: 'status',
    //valueType: 'timeaccount',    
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.tags.TagFilter.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get('RequestTracker');
        this.label = this.app.i18n._("Status");
        this.operators = ['equals', 'greater', 'less'];
    },
   
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        // value
        var value = new Tine.RequestTracker.StatusCombo({
            filter: filter,
            blurOnSelect: true,
            width: 200,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        });
        value.on('specialkey', function(field, e){
             if(e.getKey() == e.ENTER){
                 this.onFiltertrigger();
             }
        }, this);
        //value.on('select', this.onFiltertrigger, this);
        
        return value;
    }
});
