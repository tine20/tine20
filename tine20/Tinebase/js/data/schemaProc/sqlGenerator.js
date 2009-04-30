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

Ext.ns('Tine.Tinebase.data', 'Tine.Tinebase.data.schemaProc');

/**
 * Ext.onReady(function() {
    Ext.Ajax.request({
        url: '/tt/tine20/Tasks/Setup/setup.xml',
        success: function(response) {
            var xml = response.responseXML;
            var schema = Tine.Tinebase.data.schemaProc.xmlReader.getSchema(xml);
            var stmts = Tine.Tinebase.data.schemaProc.sqlGenerator.getCreateStmts(schema)
            //console.log(stmts);
        }
    });
});
 */

Tine.Tinebase.data.schemaProc.sqlGenerator = {
    
    /**
     * returns an array of CREATE TABLE statments
     * @param {Object} schema
     * @return {Array} array of strings
     */
    getCreateStmts: function(schema) {
        var tableDef, stmt, stmts=[];
        for (var appIdx=0; appIdx<schema.length; appIdx++) {
            for(var j=0; j<schema[appIdx].tables.length; j++) {
                tableDef = schema[appIdx].tables[j];
                stmt = this.generateCreateStmt(tableDef);
                console.log(stmt);
                stmts.push(stmt);
            }
        }
        return stmts;
    },
    
    /**
     * generates a CREATE TABLE statement
     * @param {Object} tableDef
     * @return {String}
     */
    generateCreateStmt: function(tableDef) {
        var body = [];
        for(var i=0; i<tableDef.declaration.fields.length; i++) {
            body.push(this.generateFieldStmt(tableDef.declaration.fields[i]));
        }
        for(var i=0; i<tableDef.declaration.indices.length; i++) {
            body.push(this.generateContrainStmt(tableDef.declaration.indices[i]));
        }
        
        var stmt = "CREATE TABLE " + this.qi(tableDef.name) + " ( \n";
        stmt += body.join(", \n");       
        stmt +=  ")";

        // charset is alwyas utf-16 according to html5 spec
        // at least gears can't deal with comments
        //stmt +=  " DEFAULT CHARSET=utf8 COMMENT='' \n";
        return stmt;
    },
    
    /**
     * returns a field portion for a CREATE TABLE stmt
     * @link {http://www.sqlite.org/datatype3.html}
     * 
     * @param {Object} field definition object
     * @return {String}
     */
    generateFieldStmt: function(d) {
        var f = this.qi(d.name);
        switch (d.type) {
            case 'text':
                // maybe next sqlite version brings VARCHAR, so we define it here
                f += d.length > 255 ? " TEXT" : " VARCHAR(" + d.length + ")";
                break;
            case 'datetime':
                // we need to define TEXT here, otherwise sqlite would take an INTEGER
                f += " TEXT";
                break;
            case 'tinyint':
            case 'boolean':
                // we represent a bool as int yet
                f += " INTEGER";
                break;
            default:
                f += " " + d.type.toUpperCase()
                break;
        }
        
        if (d.autoincrement) {
            f += " AUTOINCREMENT";
        }
        
        if (d['default']) {
            f += " DEFAULT " + d['default'];
        }
        
        if (d.notnull) {
            f += " NOT NULL";
        }
        
        return f;
    },
    
    generateContrainStmt: function(d) {
        var c = "CONSTRAINT " + this.qi(d.name);
        
        if (d.primary) {
            c += " PRIMARY KEY (";
        } else if (d.unique) {
            c += " UNIQUE (";
        } else if (d.foreign) {
            c += " FOREIGN KEY (";
        } else {
            // sqlite does not support indices
            return '';
        }
        //c += d.fields.concat() + ')';
        
        console.log(d);
        return c;
    },
    
    /**
     * simple quote identifier function
     * 
     * @param {String} identifier
     * @return {String} quoted identifier
     */
    qi: function(identifier) {
        return "'" + identifier + "'";
    }
    
}
