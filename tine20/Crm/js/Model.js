/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Crm', 'Tine.Crm.Model');

/**
 * @namespace Tine.Crm.Model
 * @class Tine.Crm.Model.Lead
 * @extends Tine.Tinebase.data.Record
 * 
 * Lead Record Definition
 */ 
Tine.Crm.Model.Lead = Tine.Tinebase.data.Record.create([
        {name: 'id',            type: 'int'},
        {name: 'lead_name',     type: 'string'},
        {name: 'leadstate_id',  type: 'int'},
        {name: 'leadtype_id',   type: 'int'},
        {name: 'leadsource_id', type: 'int'},
        {name: 'container_id'              },
        {name: 'start',         type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'description',   type: 'string'},
        {name: 'end',           type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'turnover',      type: 'int'},
        {name: 'probability',   type: 'int'},
        {name: 'probableTurnover',   type: 'int'},
        {name: 'end_scheduled', type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'lastread'},
        {name: 'lastreader'},
        {name: 'responsible'},
        {name: 'customer'},
        {name: 'partner'},
        {name: 'tasks'},
        {name: 'relations'},
        {name: 'products'},
        {name: 'tags'},
        {name: 'notes'},
        {name: 'creation_time',      type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'created_by',         type: 'int'                  },
        {name: 'last_modified_time', type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'last_modified_by',   type: 'int'                  },
        {name: 'is_deleted',         type: 'boolean'              },
        {name: 'deleted_time',       type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'deleted_by',         type: 'int'                  }
    ], {
    appName: 'Crm',
    modelName: 'Lead',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Lead', 'Leads', n);
    recordName: 'Lead',
    recordsName: 'Leads',
    containerProperty: 'container_id',
    // ngettext('lead list', 'lead lists', n);
    containerName: 'lead list',
    containersName: 'lead lists',
    getTitle: function() {
        return this.get('lead_name') ? this.get('lead_name') : false;
    }
});

/**
 * @namespace Tine.Crm.Model
 * 
 * get default data for a new lead
 *  
 * @return {Object} default data
 * @static
 * 
 * TODO generalize default container id handling?
 */ 
Tine.Crm.Model.Lead.getDefaultData = function() {
    
    var defaults = Tine.Crm.registry.get('defaults');
    
    var data = {
        start: new Date().clearTime(),
        leadstate_id: defaults.leadstate_id,
        leadtype_id: defaults.leadtype_id,
        leadsource_id: defaults.leadsource_id,
        probability: 0,
        turnover: 0,
        relations: [{
            type: 'responsible',
            related_record: Tine.Tinebase.registry.get('userContact')
        }]
    };
    
    // add default container
    var app = Tine.Tinebase.appMgr.get('Crm');
    if (app.getMainScreen().treePanel) {
        var treeNode = app.getMainScreen().treePanel.getSelectionModel().getSelectedNode();
        if (treeNode && treeNode.attributes && treeNode.attributes.containerType == 'singleContainer') {
            data.container_id = treeNode.attributes.container;
        } else {
            data.container_id = defaults.container_id;
        }
    }

    return data;
};

/**
 * @namespace Tine.Crm.Model
 * @class Tine.Crm.Model.Settings
 * @extends Tine.Tinebase.data.Record
 * 
 * Settings Record Definition
 * 
 * TODO         generalize this
 */ 
Tine.Crm.Model.Settings = Tine.Tinebase.data.Record.create([
        {name: 'id'},
        {name: 'defaults'},
        {name: 'leadstates'},
        {name: 'leadtypes'},
        {name: 'leadsources'},
        {name: 'default_leadstate_id',  type: 'int'},
        {name: 'default_leadtype_id',   type: 'int'},
        {name: 'default_leadsource_id', type: 'int'}
    ], {
    appName: 'Crm',
    modelName: 'Settings',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Settings', 'Settings', n);
    recordName: 'Settings',
    recordsName: 'Settingss',
    containerProperty: 'container_id',
    // ngettext('record list', 'record lists', n);
    containerName: 'Settings',
    containersName: 'Settings',
    getTitle: function() {
        return this.recordName;
    }
});
