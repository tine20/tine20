/**
 * egroupware 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Egw.Tasks', 'Egw.Tasks.status');

Egw.Tasks.status.ComboBox = Ext.extend(Ext.form.ComboBox, {
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
		this.store = Egw.Tasks.status.getStore();
		if (!this.value) {
			this.value = Egw.Tasks.status.getIdentifier('IN-PROCESS');
		}
		if (this.autoExpand) {
			this.on('focus', function(){
				this.lazyInit = false;
                this.expand();
            });
		}
		//this.on('select', function(){console.log(this.value)});
	    Egw.Tasks.status.ComboBox.superclass.initComponent.call(this);
	}
        
});

Egw.Tasks.status.getStore = function() {
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
           data: Egw.Tasks.AllStati,
           autoLoad: true,
           id: 'identifier'
       });
	}
	return store;
};

Egw.Tasks.status.getIdentifier = function(statusName) {
	var index = Egw.Tasks.status.getStore().find('status_name', statusName);
	var status = Egw.Tasks.status.getStore().getAt(index);
	return status.data.identifier;
};

Egw.Tasks.status.getStatus = function(identifier) {
	var status = Egw.Tasks.status.getStore().getById(identifier);
    return status ? status : identifier;
};

Egw.Tasks.status.getStatusIcon = function(identifier) {
    var status = Egw.Tasks.status.getStatus(identifier);
    return '<img class="TasksMainGridStatus" src="' + status.data.status_icon + '" ext:qtip="' + status.data.status_name + '">';
};
