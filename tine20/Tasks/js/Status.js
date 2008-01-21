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
Ext.namespace('Egw.Tasks', 'Egw.Tasks.Status');

Egw.Tasks.Status = function() {
	
	_init = function() {
	    // initial data from http request
	    Egw.Tasks.Status.Store = new Ext.data.JsonStore({
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
	       data: Egw.Tasks.InitialData.Status,
		   autoLoad: true,
	       id: 'identifier'
	    });
	};
	
	_getStatusName = function(identifier) {
	    var status = Egw.Tasks.Status.Store.getById(identifier);
	    return status.data.status;
	};
	
	_getStatusIcon = function(identifier) {
	    var name = _getStatusName(identifier);
	    return '<div class="TasksMainGridStatus-' + name + '" ext:qtip="' + name + '"></div>';
	};
	
	return{
		init          : _init,
		getStatusName : _getStatusName,
		getStatusIcon : _getStatusIcon
	};
}();
