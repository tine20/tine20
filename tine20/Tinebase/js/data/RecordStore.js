/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
 
/**
 * @namespace Tine.Tinebase.data
 * @class     Tine.Tinebase.data.RecordStore
 * @extends   Ext.data.Store
 * @author    Cornelius Weiss <c.weiss@metaways.de>
 * @version   $Id$
 * 
 * Small helper class to create an {@link Ext.data.Store} configured with an
 * {@link Ext.data.DirectProxy} and {@link Ext.data.JsonReader} to make interacting
 * with an {@link Ext.Direct} Server-side {@link Ext.ux.direct.ZendFrameworkProvider} easier.
 * 
 * @xtype     tinerecordstore
 * @constructor
 * @param {Object} config config object
 */
Tine.Tinebase.data.RecordStore = function(c){
    /**
     * @cfg {bool} readOnly
     * set to true to skip creation of a writer
     */
    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     * model of this proxy (required)
     */
    
    c.batchTransactions = false;
    
    c.appName = c.recordClass.getMeta('appName');
    c.modelName = c.recordClass.getMeta('modelName');
    
    if (typeof(c.reader) == 'undefined') {
        c.reader = new Ext.data.JsonReader({
            root: c.root || 'results',
            totalProperty: c.totalProperty || 'totalcount',
            id: c.recordClass.getMeta('idProperty')
        }, c.recordClass);
    }
    
    if (typeof(c.writer) == 'undefined' && ! c.readOnly) {
        c.writer = new Ext.data.JsonWriter({
        });
    }
    
    if (typeof(c.proxy) == 'undefined') {
        c.proxy = new Tine.Tinebase.data.RecordProxy({
            recordClass: c.recordClass
        });
    }
    
    Tine.Tinebase.data.RecordStore.superclass.constructor.call(this, c);
};
Ext.extend(Tine.Tinebase.data.RecordStore, Ext.data.Store, {});
Ext.reg('tinerecordstore', Tine.Tinebase.data.RecordStore);
