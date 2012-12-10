/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
Tine.Crm.Model.Lead = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
        {name: 'id',            type: 'string'},
        {name: 'lead_name',     type: 'string'},
        {name: 'leadstate_id',  type: 'int'},
        {name: 'leadtype_id',   type: 'int'},
        {name: 'leadsource_id', type: 'int'},
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
        {name: 'customfields', isMetaField: true}
    ]), {
    appName: 'Crm',
    modelName: 'Lead',
    idProperty: 'id',
    titleProperty: 'lead_name',
    // ngettext('Lead', 'Leads', n);
    recordName: 'Lead',
    recordsName: 'Leads',
    containerProperty: 'container_id',
    // ngettext('lead list', 'lead lists', n); gettext('lead lists');
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
    var app = Tine.Tinebase.appMgr.get('Crm');
    
    var data = {
        start: new Date().clearTime(),
        leadstate_id: defaults.leadstate_id,
        leadtype_id: defaults.leadtype_id,
        leadsource_id: defaults.leadsource_id,
        container_id: app.getMainScreen().getWestPanel().getContainerTreePanel().getSelectedContainer('addGrant', defaults.container_id),
        probability: 0,
        turnover: 0,
        relations: [{
            type: 'responsible',
            related_record: Tine.Tinebase.registry.get('userContact')
        }]
    };
    
    return data;
};

/**
 * get filtermodel of lead model
 * 
 * @namespace Tine.Crm.Model
 * @static
 * @return {Array} filterModel definition
 */ 
Tine.Crm.Model.Lead.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Crm'),
        filters = [
            {label: _('Quick search'),  field: 'query',    operators: ['contains']},
            {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Crm.Model.Lead},
            {label: app.i18n._('Lead name'),   field: 'lead_name' },
            {filtertype: 'crm.leadstate', app: app},
            {label: app.i18n._('Probability'), field: 'probability', valueType: 'percentage'},
            {label: app.i18n._('Turnover'),    field: 'turnover', valueType: 'number', defaultOperator: 'greater'},
            {filtertype: 'tinebase.tag', app: app},
            {label: _('Last Modified Time'),                                                field: 'last_modified_time', valueType: 'date'},
            {label: _('Last Modified By'),                                                  field: 'last_modified_by',   valueType: 'user'},
            {label: _('Creation Time'),                                                     field: 'creation_time',      valueType: 'date'},
            {label: _('Created By'),                                                        field: 'created_by',         valueType: 'user'},
            {filtertype: 'crm.contact'},
            {filtertype: 'foreignrecord', app: app, foreignRecordClass: Tine.Tasks.Model.Task, ownField: 'task'}
        ];
        
    if (Tine.Sales && Tine.Tinebase.common.hasRight('run', 'Sales')) {
        filters.push({filtertype: 'foreignrecord', 
            app: app,
            foreignRecordClass: Tine.Sales.Model.Product,
            ownField: 'product'
        });
    }
    
    return filters;
}

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
    // ngettext('record list', 'record lists', n);
    containerName: 'Settings',
    containersName: 'Settings',
    getTitle: function() {
        return this.recordName;
    }
});

Tine.Crm.Model.getRandomUnusedId = function(store) {
    var result;
    do {
        result = Tine.Tinebase.common.getRandomNumber(0, 21474836);
    } while (store.getById(result) != undefined)
    
    return result;
};
