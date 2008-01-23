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
	fieldLabel: 'status',
    name: 'status',
    displayField: 'status',
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
		if (this.autoExpand) {
			this.on('focus', function(){
				this.lazyInit = false;
                this.expand();
            });
		}
	    Egw.widgets.Percent.ComboBox.superclass.initComponent.call(this);
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
                { name: 'status'                                            }
           ],
		   // initial data from http request
           data: Egw.Tasks.InitialData.Status,
           autoLoad: true,
           id: 'identifier'
       });
	}
	return store;
}

Egw.Tasks.status.getStatusName = function(identifier) {
	var status = Egw.Tasks.status.getStore().getById(identifier);
    return status.data.status;
};

Egw.Tasks.status.getStatusIcon = function(identifier) {
    var name = Egw.Tasks.status.getStatusName(identifier);
    return '<div class="TasksMainGridStatus-' + name + '" ext:qtip="' + name + '"></div>';
};
