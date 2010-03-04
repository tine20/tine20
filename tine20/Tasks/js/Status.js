/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.Tasks.status');

/**
 * @namespace   Tine.Tasks
 * @class       Tine.Tasks.status.StatusFilter
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Tasks.status.StatusFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @property Tine.Tinebase.Application app
     */
    app: null,
    
    field: 'status_id',
    defaultOperator: 'notin',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.tags.TagFilter.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get('Tasks');
        
        this.operators = ['in', 'notin'];
        this.label = _('Status');
        
        this.defaultValue = Tine.Tasks.status.getClosedStatus();
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value = new Tine.Tasks.status.StatusFilterValueField({
            app: this.app,
            filter: filter,
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

Tine.widgets.grid.FilterToolbar.FILTERS['tasks.status'] = Tine.Tasks.status.StatusFilter;

/**
 * @namespace   Tine.Tasks
 * @class       Tine.Tasks.status.StatusFilterValueField
 * @extends     Ext.ux.form.LayerCombo
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Tasks.status.StatusFilterValueField = Ext.extend(Ext.ux.form.LayerCombo, {
    hideButtons: false,
    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },
    
    getFormValue: function() {
        var ids = [];
        var statusStore = Tine.Tasks.status.getStore();
        
        var formValues = this.getInnerForm().getForm().getValues();
        for (var id in formValues) {
            if (formValues[id] === 'on' && statusStore.getById(id)) {
                ids.push(id);
            }
        }
        
        return ids;
    },
    
    getItems: function() {
        var items = [];
        
        Tine.Tasks.status.getStore().each(function(status) {
            items.push({
                xtype: 'checkbox',
                boxLabel: status.get('status_name'),
                icon: status.get('status_icon'),
                name: status.get('id')
            });
        }, this);
        
        return items;
    },
    
    /**
     * @param {String} value
     * @return {Ext.form.Field} this
     */
    setValue: function(value) {
        value = Ext.isArray(value) ? value : [value];
        
        var statusStore = Tine.Tasks.status.getStore();
        var statusText = [];
        this.currentValue = [];
        
        Tine.Tasks.status.getStore().each(function(status) {
            var id = status.get('id');
            var name = status.get('status_name');
            if (value.indexOf(id) >= 0) {
                statusText.push(name);
                this.currentValue.push(id);
            }
        }, this);
        
        this.setRawValue(statusText.join(', '));
        
        return this;
    },
    
    /**
     * sets values to innerForm
     */
    setFormValue: function(value) {
        this.getInnerForm().getForm().items.each(function(item) {
            item.setValue(values.indexOf(item.name) >= 0 ? 'on' : 'off');
        }, this);
    }
});

/**
 * @namespace   Tine.Tasks
 * @class       Tine.Tasks.status.ComboBox
 * @extends     Ext.form.ComboBox
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Tasks.status.ComboBox = Ext.extend(Ext.form.ComboBox, {
	/**
     * @cfg {bool} autoExpand Autoexpand comboBox on focus.
     */
    autoExpand: false,
	/**
     * @cfg {bool} blurOnSelect blurs combobox when item gets selected
     */
    blurOnSelect: false,
    
	fieldLabel: 'status',
    name: 'status',
    displayField: 'status_name',
    valueField: 'id',
    mode: 'local',
    triggerAction: 'all',
    emptyText: 'Status...',
    typeAhead: true,
    selectOnFocus: true,
    editable: false,
    lazyInit: false,
    
    translation: null,
	
	//private
    initComponent: function(){
    	
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Tasks');
    	
		this.store = Tine.Tasks.status.getStore();
		if (!this.value) {
			this.value = Tine.Tasks.status.getIdentifier(this.translation._('IN-PROCESS'));
		}
		if (this.autoExpand) {
            this.lazyInit = false;
			this.on('focus', function(){
                this.onTriggerClick();
            });
		}
		if (this.blurOnSelect){
            this.on('select', function(){
                this.fireEvent('blur', this);
            }, this);
        }
		//this.on('select', function(){console.log(this.value)});
	    Tine.Tasks.status.ComboBox.superclass.initComponent.call(this);
	},
    
    setValue: function(value) {
        if(! value) {
            return;
        }
        Tine.Tasks.status.ComboBox.superclass.setValue.call(this, value);
    }
        
});
Ext.reg('tasksstatuscombo', Tine.Tasks.status.ComboBox);

Tine.Tasks.status.getStore = function() {
	if (!store) {
		var store = new Ext.data.JsonStore({
            fields: [ 
                { name: 'id'                                                },
                { name: 'created_by'                                        }, 
                { name: 'creation_time',      type: 'date', dateFormat: Date.patterns.ISO8601Long },
                { name: 'last_modified_by'                                  },
                { name: 'last_modified_time', type: 'date', dateFormat: Date.patterns.ISO8601Long },
                { name: 'is_deleted'                                        }, 
                { name: 'deleted_time',       type: 'date', dateFormat: Date.patterns.ISO8601Long }, 
                { name: 'deleted_by'                                        },
                { name: 'status_name'                                       },
                { name: 'status_is_open',      type: 'bool'                 },
                { name: 'status_icon'                                       }
           ],
		   // initial data from http request
           data: Tine.Tasks.registry.get('AllStatus'),
           autoLoad: true,
           id: 'id'
       });
	}
	return store;
};

Tine.Tasks.status.getClosedStatus = function() {
    var reqStatus = [];
        
    Tine.Tasks.status.getStore().each(function(status) {
        if (! status.get('status_is_open')) {
            reqStatus.push(status.get('id'));
        }
    }, this);
    
    return reqStatus;
};

Tine.Tasks.status.getIdentifier = function(statusName) {
	var index = Tine.Tasks.status.getStore().find('status_name', statusName);
	var status = Tine.Tasks.status.getStore().getAt(index);
	return status ? status.data.id : statusName;
};

Tine.Tasks.status.getStatus = function(id) {
	var status = Tine.Tasks.status.getStore().getById(id);
    return status ? status : id;
};

Tine.Tasks.status.getStatusIcon = function(id) {
    var status = Tine.Tasks.status.getStatus(id);
    if (!status) {
    	return;
    }
    return '<img class="TasksMainGridStatus" src="' + status.data.status_icon + '" ext:qtip="' + status.data.status_name + '">';
};
