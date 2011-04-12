/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.data', 'Tine.Tinebase.data.schemaProc');

/**
 * functions to read a tine schema xml definition
 * <p>Usage: var schema = Tine.Tinebase.data.schemaProc.xmlReader.getSchema(schemaXML); </p>
 */
Tine.Tinebase.data.schemaProc.xmlReader = {
    /**
     * @property {Function} qs
     * shortcut to Ext.DomQuery.select
     */
    qs: Ext.DomQuery.select,
    /**
     * @property {Function} qsn
     * shortcut to Ext.DomQuery.selectNode
     */
    qsn: Ext.DomQuery.selectNode,
    /**
     * @property {Function} qv
     * shortcut to Ext.DomQuery.selectValue
     */
    qv: Ext.DomQuery.selectValue,
    /**
     * @property {Function} qn
     * shortcut to Ext.DomQuery.selectNumber
     */
    qn: Ext.DomQuery.selectNumber,
    
    /**
     * returns schema object from a given schema xml
     * 
     * @param {Document} xml document
     * @retrun {Array} array of app schema definitions
     */
    getSchema: function (xml) {
        var i, x, schema = [], apps = this.qs('application', xml);
        for (i=0; i<apps.length; i++) {
            x = apps[i];
            schema.push({
                name: this.qv('name', x),
                version: this.qn('version', x),
                tables: this.getTableDefinitions(x),
                defaultRecords: this.getDefaultRecords(x)
            });
        }
        return schema;
    },
    
    /**
     * @param {Element} xml application defintion node
     * @return {Array} array of table definition objects
     */
    getTableDefinitions: function(xml) {
        var tes = this.qs('tables > table', xml), tables = [];
        for (var i=0; i<tes.length; i++) {
            tables.push(this.getTableDefinition(tes[i]));
        }
        return tables;
    },
    
    /**
     * returns a table definition object for a single table
     * 
     * @param {Element} xml elemnt of a talbe
     * @return {Object}
     */
    getTableDefinition: function(xml) {
        var table = {
            name: this.qv('name', xml),
            version: this.qn('version', xml),
            declaration: {
                fields: this.getFieldDefinitions(this.qs('declaration > field', xml)),
                indices: this.getIndicesDefinitions(this.qs('declaration > index', xml))
            }
        };
        return table;
    },
    
    /**
     * returns a set of table field definitions
     * 
     * @param {Array} xml array of xml field definitions
     * @return {Array} array of field definition objects
     */
    getFieldDefinitions: function(xml) {
        var d, x, ds = [];
        for (var i=0; i<xml.length; i++) {
            x = xml[i];
            d = {
                name:          this.qv('name', x),
                type:          this.qv('type', x),
                autoincrement: this.qv('autoincrement', x, false),
                length:        this.qn('length', x, null),
                notnull:       this.qv('notnull', x, false)
            };
            
            if (this.qv('default', x)) {
                d['default'] = this.qv('default', x);
            }
            
            if (d.type === 'enum') {
                d.values = this.getValues('value', x);
            }
            
            ds.push(d);
        }
        return ds;
    },
    
    getIndicesDefinitions: function(xml) {
        var idx, x, idxs = [];
        for (var i=0; i<xml.length; i++) {
            x = xml[i];
            idx = {
                name:    this.qv('name', x),
                primary: this.qv('primary', x, false),
                unique:  this.qv('unique', x, false),
                foreign: this.qv('foreign', x, false),
                fields:  this.getValues('name', this.qs('field:has(name)', x))
            }
            
            x = this.qsn('reference', x);
            if (x) {
                idx.reference = {
                    table: this.qv('table', x),
                    field: this.qv('field', x),
                    ondelete: this.qv('ondelete', x, null)
                }
            }
            
            idxs.push(idx);
        }
        
        return idxs;
    },
    
    /**
     * We don't do with default records client side
     */
    getDefaultRecords: function(xml) {
        return [];
    },
    
    /**
     * returns values from a simple 1:n xml node
     * 
     * @param {String} name name of param to fetch
     * @param {Array}  xml  array of simple value nodes
     * @param {String} type (optional) string/number  (defaults to string)
     * 
     * @return {Array} array of values
     */
    getValues: function(name, xml, type) {
        var v = [];
        for(var i=0; i<xml.length; i++) {
            v.push(type == 'number' ? this.qn(name, xml[i]) : this.qv(name, xml[i]));
        }
        
        return v;
    }
}
