
/**
 * filter own records
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.OwnRecordFilter
 * @extends     Tine.widgets.grid.ForeignRecordFilter
 * 
 * @constructor
 */
Tine.widgets.grid.OwnRecordFilter = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {
    
    isGeneric: false,

    initComponent: function() {
        this.label = this.ftb.generateTitle();
        this.field = this.ownRecordClass.getMeta('idProperty');
        this.defaultOperator = 'definedBy';
        
        this.foreignRecordClass = this.ownRecordClass;
        
        Tine.widgets.grid.OwnRecordFilter.superclass.initComponent.call(this);
    },
    
    getFilterData: function(filterRecord) {
        var data = {
            field: this.field,
            id: filterRecord.id,
            label: filterRecord.toolbar ? filterRecord.toolbar.title : null
        };
        
        if (filterRecord.get('operator') == 'equals') {
            Ext.apply(data, {
                operator: 'equals',
                value: filterRecord.formFields.value.getValue()[0].value
            });
        } else {
            Ext.apply(data, {
                condition: filterRecord.condition || 'AND', 
                filters: this.getRelatedRecordValue(filterRecord)
            });
        }
        
        return data;
    },
    
    setFilterData: function(filterRecord, filterData) {
        // work around store compare bug
        if (Ext.isObject(filterData.filters)) {
            filterData.filters.toString = this.objectToString
        }
        
        if (filterData.operator == 'equals') {
            filterData.operator = 'AND';
            
            filterData.value = [{
                field: ':' + this.field,
                operator: 'equals',
                value: filterData.value
            }];
            filterData.value.toString = this.objectToString;
            
            filterRecord.set('operator', 'AND');
            filterRecord.set('value', filterData.value);
        } else {
            filterRecord.condition = filterData.condition || 'AND';
        }
        
        //filterRecord.set('value', filterData.filters);
        this.setRelatedRecordValue(filterRecord);
    },
    
    setRelatedRecordValue: function(filterRecord) {
        
        if (filterRecord.data.filters) {
            filterRecord.set('value', filterRecord.data.filters);
            delete filterRecord.data.filters;
        }
        
        Tine.widgets.grid.OwnRecordFilter.superclass.setRelatedRecordValue.apply(this, arguments);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['ownrecord'] = Tine.widgets.grid.OwnRecordFilter;