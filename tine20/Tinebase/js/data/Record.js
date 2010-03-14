/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
Ext.ns('Tine.Tinebase', 'Tine.Tinebase.data');

Tine.Tinebase.data.Record = function(data, id){
    if (id || id === 0) {
        this.id = id;
    } else if (data[this.idProperty]) {
        this.id = data[this.idProperty];
    } else {
        this.id = ++Ext.data.Record.AUTO_ID;
    }
    this.data = data;
};

/**
 * @namespace Tine.Tinebase.data
 * @class     Tine.Tinebase.data.Record
 * @extends   Ext.data.Record
 * 
 * Baseclass of Tine 2.0 models
 */
Ext.extend(Tine.Tinebase.data.Record, Ext.data.Record, {
    /**
     * @cfg {String} appName
     * internal/untranslated app name (required)
     */
    appName: null,
    /**
     * @cfg {String} modelName
     * name of the model/record  (required)
     */
    modelName: null,
    /**
     * @cfg {String} idProperty
     * property of the id of the record
     */
    idProperty: 'id',
    /**
     * @cfg {String} titleProperty
     * property of the title attibute, used in generic getTitle function  (required)
     */
    titleProperty: null,
    /**
     * @cfg {String} recordName
     * untranslated record/item name
     */
    recordName: 'record',
    /**
     * @cfg {String} recordName
     * untranslated records/items (plural) name
     */
    recordsName: 'records',
    /**
     * @cfg {String} containerProperty
     * name of the container property
     */
    containerProperty: 'container_id',
    /**
     * @cfg {String} containerName
     * untranslated container name
     */
    containerName: 'container',
    /**
     * @cfg {string} containerName
     * untranslated name of container (plural)
     */
    containersName: 'containers',
    
    /**
     * returns title of this record
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.titleProperty ? this.get(this.titleProperty) : '';
    },
    
    /**
     * converts data to String
     * 
     * @return {String}
     */
    toString: function() {
        return Ext.encode(this.data);
    }
});

/**
 * Generate a constructor for a specific Record layout.
 * 
 * @param {Array} def see {@link Ext.data.Record#create}
 * @param {Object} meta information see {@link Tine.Tinebase.data.Record}
 * 
 * <br>usage:<br>
<b>IMPORTANT: the ngettext comments are required for the translation system!</b>
<pre><code>
var TopicRecord = Tine.Tinebase.data.Record.create([
    {name: 'summary', mapping: 'topic_title'},
    {name: 'details', mapping: 'username'}
], {
    appName: 'Tasks',
    modelName: 'Task',
    idProperty: 'id',
    titleProperty: 'summary',
    // ngettext('Task', 'Tasks', n);
    recordName: 'Task',
    recordsName: 'Tasks',
    containerProperty: 'container_id',
    // ngettext('to do list', 'to do lists', n);
    containerName: 'to do list',
    containesrName: 'to do lists'
});
</code></pre>
 * @static
 */
Tine.Tinebase.data.Record.create = function(o, meta) {
    var f = Ext.extend(Tine.Tinebase.data.Record, {});
    var p = f.prototype;
    Ext.apply(p, meta);
    p.fields = new Ext.util.MixedCollection(false, function(field){
        return field.name;
    });
    for(var i = 0, len = o.length; i < len; i++){
        p.fields.add(new Ext.data.Field(o[i]));
    }
    f.getField = function(name){
        return p.fields.get(name);
    };
    f.getMeta = function(name) {
        return p[name];
    };
    f.getDefaultData = function() {
        return {};
    };
    f.getFieldDefinitions = function() {
        return p.fields.items;
    };
    f.getFieldNames = function() {
        if (! p.fieldsarray) {
            var arr = p.fieldsarray = [];
            Ext.each(p.fields.items, function(item) {arr.push(item.name);});
        }
        return p.fieldsarray;
    };
    return f;
};