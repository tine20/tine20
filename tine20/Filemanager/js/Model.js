/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager.Model');


/**
 * @namespace   Tine.Filemanager.Model
 * @class       Tine.Filemanager.Model.File
 * @extends     Tine.Tinebase.data.Record
 * Example record definition
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Filemanager.Model.File = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat([
    { name: 'id' },
    { name: 'name' },
    // TODO add more record fields here
    // tine 2.0 notes + tags
    { name: 'size' },
    { name: 'revision' },
    { name: 'type' },
    { name: 'contenttype' },
    { name: 'description' }
]), {
    appName: 'Filemanager',
    modelName: 'File',
    idProperty: 'id',
    titleProperty: 'title',
    // ngettext('example record', 'example records', n);
    recordName: 'user file',
    recordsName: 'user files',
    containerProperty: 'container_id',
    // ngettext('example record list', 'example record lists', n);
    containerName: 'user file folder',
    containersName: 'user file folders'
});


/**
 * get filtermodel of contact model
 * 
 * @namespace Tine.Filemanager.Model
 * @static
 * @return {Object} filterModel definition
 */ 
Tine.Filemanager.Model.File.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Filemanager');
       
	return [ 	
	    {label : _('Quick search'), field : 'query', operators : [ 'contains' ]}, 
	    {filtertype : 'tine.widget.container.filtermodel', app : app, recordClass : Tine.Filemanager.Model.File}, 
	    {filtertype : 'tinebase.tag', app : app} 
	];
};