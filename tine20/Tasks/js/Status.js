/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine.Tasks', 'Tine.Tasks.status');

Tine.Tasks.status.ComboBox = Ext.extend(Ext.form.ComboBox, {
	/**
     * @cfs {bool} autoExpand
     * Autoexpand comboBox on focus.
     */
    autoExpand: false,
	
	fieldLabel: 'status',
    name: 'status',
    displayField: 'status_name',
    valueField: 'identifier',
    mode: 'local',
    triggerAction: 'all',
    emptyText: 'Status...',
    typeAhead: true,
    selectOnFocus: true,
    editable: false,
    lazyInit: false,
	
	//private
    initComponent: function(){
		this.store = Tine.Tasks.status.getStore();
		if (!this.value) {
			this.value = Tine.Tasks.status.getIdentifier('IN-PROCESS');
		}
		if (this.autoExpand) {
			this.on('focus', function(){
				this.lazyInit = false;
                this.expand();
            });
		}
		//this.on('select', function(){console.log(this.value)});
	    Tine.Tasks.status.ComboBox.superclass.initComponent.call(this);
	}
        
});

Tine.Tasks.status.getStore = function() {
	if (!store) {
		var store = new Ext.data.JsonStore({
            fields: [ 
                { name: 'identifier'                                        },
                { name: 'created_by'                                        }, 
                { name: 'creation_time',      type: 'date', dateFormat: 'c' },
                { name: 'last_modified_by'                                  },
                { name: 'last_modified_time', type: 'date', dateFormat: 'c' },
                { name: 'is_deleted'                                        }, 
                { name: 'deleted_time',       type: 'date', dateFormat: 'c' }, 
                { name: 'deleted_by'                                        },
                { name: 'status_name'                                       },
                { name: 'status_is_open'                                    },
                { name: 'status_icon'                                       }
           ],
		   // initial data from http request
           data: Tine.Tasks.AllStati,
           autoLoad: true,
           id: 'identifier'
       });
	}
	return store;
};

Tine.Tasks.status.getIdentifier = function(statusName) {
	var index = Tine.Tasks.status.getStore().find('status_name', statusName);
	var status = Tine.Tasks.status.getStore().getAt(index);
	return status.data.identifier;
};

Tine.Tasks.status.getStatus = function(identifier) {
	var status = Tine.Tasks.status.getStore().getById(identifier);
    return status ? status : identifier;
};

Tine.Tasks.status.getStatusIcon = function(identifier) {
    var status = Tine.Tasks.status.getStatus(identifier);
    return '<img class="TasksMainGridStatus" src="' + status.data.status_icon + '" ext:qtip="' + status.data.status_name + '">';
};
