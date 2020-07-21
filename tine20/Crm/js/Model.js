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
        {name: 'id',            type: 'string', omitDuplicateResolving: true},
        {name: 'lead_name',     type: 'string', label: 'Lead name', group: 'Lead'},
        {name: 'leadstate_id',  type: 'int', omitDuplicateResolving: true},
        {name: 'leadtype_id',   type: 'int', omitDuplicateResolving: true},
        {name: 'leadsource_id', type: 'int', omitDuplicateResolving: true},
        {name: 'start',         type: 'date', dateFormat: Date.patterns.ISO8601Long, label: 'Start', group: 'Lead'},
        {name: 'description',   type: 'string', label: 'Description', group: 'Lead'},
        {name: 'end',           type: 'date', dateFormat: Date.patterns.ISO8601Long, label: 'End', group: 'Lead'},
        {name: 'turnover',      type: 'float', omitDuplicateResolving: true},
        {name: 'probability',   type: 'int', omitDuplicateResolving: true},
        {name: 'probableTurnover',   type: 'int', omitDuplicateResolving: true},
        {name: 'end_scheduled', type: 'date', dateFormat: Date.patterns.ISO8601Long, omitDuplicateResolving: true},
        {name: 'resubmission_date', type: 'date', dateFormat: Date.patterns.ISO8601Long, omitDuplicateResolving: true},
        {name: 'mute'},
        {name: 'lastread', omitDuplicateResolving: true},
        {name: 'lastreader', omitDuplicateResolving: true},
        {name: 'responsible', omitDuplicateResolving: true},
        {name: 'customer', omitDuplicateResolving: true, label: 'Customer', group: 'Relationen'},
        {name: 'partner', omitDuplicateResolving: true, label: 'Partner', group: 'Relationen'},
        {name: 'tasks', omitDuplicateResolving: true},
        {name: 'relations', label: 'Relationen', group: 'Relationen'},
        {name: 'products', omitDuplicateResolving: true, label: 'Products', group: 'Relationen'},
        {name: 'tags', label: 'Tags'},
        {name: 'notes', omitDuplicateResolving: true},
        {name: 'customfields', omitDuplicateResolving: true},
        {name: 'attachments', omitDuplicateResolving: true}
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
 */
Tine.Crm.Model.Lead.getDefaultData = function() {
    
    var app = Tine.Tinebase.appMgr.get('Crm');
    
    var data = {
        start: new Date().clearTime(),
        container_id: app.getMainScreen().getWestPanel().getContainerTreePanel().getDefaultContainer(),
        probability: 0,
        turnover: 0,
        mute: true, // @todo use the user/config ?
        relations: [{
            type: 'RESPONSIBLE',
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
            {label: i18n._('Quick Search'),  field: 'query',    operators: ['contains']},
            {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.Crm.Model.Lead},
            {label: app.i18n._('Lead name'),   field: 'lead_name' },
            {
                label: app.i18n._('Leadstate'),
                field: 'leadstate_id',
                filtertype: 'tine.widget.keyfield.filter',
                app: app,
                keyfieldName: 'leadstates'
            },
            {label: app.i18n._('Probability'), field: 'probability', valueType: 'percentage'},
            {
                label: app.i18n._('Leadsource'),
                field: 'leadsource_id',
                filtertype: 'tine.widget.keyfield.filter',
                app: app,
                keyfieldName: 'leadsources'
            },
            {
                label: app.i18n._('Leadtype'),
                field: 'leadtype_id',
                filtertype: 'tine.widget.keyfield.filter',
                app: app,
                keyfieldName: 'leadtypes'
            },
            {label: app.i18n._('Turnover'),    field: 'turnover', valueType: 'number', defaultOperator: 'greater'},
            {filtertype: 'tinebase.tag', app: app},
            {label: i18n._('Last Modified Time'),                                                field: 'last_modified_time', valueType: 'date'},
            {label: i18n._('Last Modified By'),                                                  field: 'last_modified_by',   valueType: 'user'},
            {label: i18n._('Creation Time'),                                                     field: 'creation_time',      valueType: 'date'},
            {label: i18n._('Created By'),                                                        field: 'created_by',         valueType: 'user'},
            
            {label: app.i18n._('Estimated end'), field: 'end_scheduled', valueType: 'date'},
            {label: app.i18n._('Resubmission Date'), field: 'resubmission_date', valueType: 'date'},
            
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

// custom keyFieldRecord
Tine.Crm.Model.LeadState = Tine.Tinebase.data.Record.create([
    { name: 'id' },
    { name: 'value' },
    { name: 'system' },
    { name: 'probability', label: 'Probability', type: 'percentage' }, // i18n._('Probability')
    { name: 'endslead', label: 'X Lead', type: 'bool'} // i18n._('X Lead')
], {
    appName: 'Crm',
    modelName: 'LeadState',
    idProperty: 'id',
    titleProperty: 'value'
});

// custom keyFieldRecord
Tine.Crm.Model.LeadSource = Tine.Tinebase.data.Record.create([
    { name: 'id' },
    { name: 'value' },
    { name: 'system' },
    { name: 'archived', label: 'Archived', type: 'bool' } // i18n._('Archived')
], {
    appName: 'Crm',
    modelName: 'LeadSource',
    idProperty: 'id',
    titleProperty: 'value'
});

Tine.Crm.Model.getRandomUnusedId = function(store) {
    var result;
    do {
        result = Tine.Tinebase.common.getRandomNumber(0, 21474836);
    } while (store.getById(result) != undefined)
    
    return result;
};
