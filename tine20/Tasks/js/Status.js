/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.Tasks', 'Tine.Tasks.status');

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
		//this.on('select', function(){console.log(this.value)});
	    Tine.Tasks.status.ComboBox.superclass.initComponent.call(this);
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
                { name: 'status_is_open'                                    },
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
