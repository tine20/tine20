/**
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.SimpleFAQ','Tine.SimpleFAQ.Model');

/**
 * @namespace Tine.SimpleFAQ.Model
 * @class Tine.SimpleFAQ.Model.Faq
 * @extends Tine.Tinebase.data.Record
 *
 * Faq Record Definition
 */ 
Tine.SimpleFAQ.Model.Faq = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    {name: 'id',            type: 'string'},
    {name: 'faqstatus_id',  type: 'int'},
    {name: 'faqtype_id',    type: 'int'},
    {name: 'question'},
    {name: 'answer'},
    {name: 'notes'},
    {name: 'tags'},
    {name: 'relations'},
    
    {name: 'attachments', omitDuplicateResolving: true}
]), {
    appName: 'SimpleFAQ',
    modelName: 'Faq',
    idProperty: 'id',
    titleProperty: 'question',
    // ngettext('FAQ', 'FAQs', n);
    recordName: 'FAQ',
    recordsName: 'FAQs',
    containerProperty: 'container_id',
    // ngettext('FAQ List', 'FAQ Lists', n); gettext('FAQ Lists');
    containerName: 'FAQ List',
    containersName: 'FAQ Lists'
});

/**
 * get default data for a new Faq
 *
 * @return {Object} default data
 */
Tine.SimpleFAQ.Model.Faq.getDefaultData = function() {

    var defaults = Tine.SimpleFAQ.registry.get('defaults');
    var app = Tine.Tinebase.appMgr.get('SimpleFAQ');

    var data = {
        faqstatus_id: defaults.faqstatus_id,
        faqtype_id: defaults.faqtype_id,
        container_id: app.getMainScreen().getWestPanel().getContainerTreePanel().getSelectedContainer('addGrant', defaults.container_id)
    }
    return data;
};

/**
 * @namespace Tine.Sales.Model
 *
 * get product filter
 *
 * @return {Array} filter objects
 * @static
 */
Tine.SimpleFAQ.Model.Faq.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get("SimpleFAQ");
    
    return [
        {label: _('Quick Search'), field: 'query', operators: ['contains']},
        {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Tine.SimpleFAQ.Model.Faq},
        {filtertype: 'simplefaq.faqstatus', app: app},
        {filtertype: 'simplefaq.faqtype', app: app},
        {filtertype: 'tinebase.tag', app: app}
    ];
};

/**
 * @namespace Tine.SimpleFAQ.Model
 * @class Tine.SimpleFAQ.Model.Settings
 * @extends Tine.Tinebase.data.Record
 *
 * Settings Record Definition
 * 
 */
Tine.SimpleFAQ.Model.Settings = Tine.Tinebase.data.Record.create([
       {name: 'id'},
       {name: 'defaults'},
       {name: 'faqstatuses'},
       {name: 'faqtypes'},
       {name: 'default_faqstatus_id',   type: 'int'},
       {name: 'default_faqtype_id',     type: 'int'}
    ], {
    appName: 'SimpleFAQ',
    modelName: 'Settings',
    idProperty: 'id',
    titleProperty: 'title',
    recordName: 'Settings',
    recordsName: 'Settings',
    containerProperty: 'container_id',
    containerName: 'Settings',
    containersName: 'Settings',
    getTitle: function() {
        return this.recordName;
    }
});

Tine.SimpleFAQ.Model.getRandomUnusedId = function(store) {
    var result;
    do {
        result = Tine.Tinebase.common.getRandomNumber(0, 21474836);
    } while (store.getById(result) != undefined)

    return result;
};
