/*
 * Tine 2.0
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.namespace('Tine.HumanResources.Model');

Tine.HumanResources.Model.WorkingTimeArray = [
    { name: 'id',            type: 'string'},
    { name: 'title',         type: 'string' },
    { name: 'json',          type: 'string'},
    { name: 'working_hours', type: 'float'}
];

Tine.HumanResources.Model.WorkingTime = Tine.Tinebase.data.Record.create(Tine.HumanResources.Model.WorkingTimeArray, {
    appName: 'HumanResources',
    modelName: 'WorkingTime',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('Working Time', 'Working Times', n);
    recordName: 'Working Time',
    recordsName: 'Working Times',
    // ngettext('Working Times', 'Working Times', n);
    containerName: 'Working Times',
    containersName: 'Working Times',
    getTitle: function() {
        return this.get('title') ? this.get('title') : false;
    }
});

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get default data for a new WorkingTime
 *  
 * @return {Object} default data
 * @static
 */ 
Tine.HumanResources.Model.WorkingTime.getDefaultData = function() {
    var data = {};
    return data;
};

/**
 * @namespace Tine.HumanResources.Model
 * 
 * get WorkingTime filter
 *  
 * @return {Array} filter objects
 * @static
 */ 
Tine.HumanResources.Model.WorkingTime.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('HumanResources');
    
    return [
        {label: _('Quick Search'), field: 'query', operators: ['contains']}
    ];
};

/**
 * @namespace Tine.HumanResources
 * @class Tine.HumanResources.workingtimeBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Employee Backend
 */ 
Tine.HumanResources.workingtimeBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'HumanResources',
    modelName: 'WorkingTime',
    recordClass: Tine.HumanResources.Model.WorkingTime
});
