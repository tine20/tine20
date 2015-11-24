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
        {name: 'turnover',      type: 'float'},
        {name: 'probability',   type: 'int'},
        {name: 'probableTurnover',   type: 'int'},
        {name: 'end_scheduled', type: 'date', dateFormat: Date.patterns.ISO8601Long},
        {name: 'resubmission_date', type: 'date', dateFormat: Date.patterns.ISO8601Long},

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
        {name: 'customfields', omitDuplicateResolving: true},
        {name: 'attachments'}
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
            {label: _('Quick Search'),  field: 'query',    operators: ['contains']},
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
            {label: app.i18n._('Turnover'),    field: 'turnover', valueType: 'number', defaultOperator: 'greater'},
            {filtertype: 'tinebase.tag', app: app},
            {label: _('Last Modified Time'),                                                field: 'last_modified_time', valueType: 'date'},
            {label: _('Last Modified By'),                                                  field: 'last_modified_by',   valueType: 'user'},
            {label: _('Creation Time'),                                                     field: 'creation_time',      valueType: 'date'},
            {label: _('Created By'),                                                        field: 'created_by',         valueType: 'user'},
            
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
    { name: 'probability', label: 'Probability', type: 'percentage' }, // _('Probability')
    { name: 'endslead', label: 'X Lead', type: 'bool'} // _('X Lead')
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
    { name: 'archived', label: 'Archived', type: 'bool' } // _('Archived')
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
