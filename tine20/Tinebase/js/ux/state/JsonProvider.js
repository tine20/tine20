/*
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Ext.ux', 'Ext.ux.state');

/**
 * @class Ext.ux.state.JsonProvider
 * @constructor
 */
Ext.ux.state.JsonProvider = function(config) {
    Ext.apply(this, config);
    
    if (! this.record) {
        this.record = Ext.data.Record.create([
            { name: 'name' },
            { name: 'value' }
        ]);
    }
    
    if (! this.store) {
        this.store = new Ext.data.SimpleStore({
            fields: this.record,
            id: 'name',
            data: []
        });
    }
    
    
}
 
Ext.extend(Ext.ux.state.JsonProvider, Ext.state.Provider, {
    
    /**
     * @property {Ext.data.Store}
     */
    store: null,
    /**
     * @property {Ext.data.Record}
     */
    record: null,
    
    /**
     * sets the states store
     */
    setStateStore: function(store) {
        this.store = store;
    },
    
    /**
     * returns the states store
     */
    getStateStore: function() {
        return this.store;
    },
    
    /**
     * Returns the current value for a key 
     */
    get: function(name, defaultValue) {
        if(name.match(/^ext\-comp/)) {
            return defaultValue;
        }
        
        var state = this.store.getById(name);
        
        return state ? state.get('value') : defaultValue;
    },
    
    /**
     * Sets the value for a key
     */
    set: function(name, value) {
        if(! name.match(/^ext\-comp/)) {
            var cmp = Ext.getCmp(name);
            if (cmp.stateful) {
                var state = this.store.getById(name);
                if (state) {
                    state.set('value', value);
                } else {
                    this.store.add(new this.record({
                        name: name,
                        value: value
                    }, name));
                }
            } else {
                 //console.info('Ext.ux.state.JsonProvider::set Attempt to set state of the non stateful component: "' + name + '"');
            }
        }
    },
    
    /**
     * Clears a value from the state
     */
    clear: function(name) {
        var state = this.store.getById(name);
        if (state) {
            this.store.remove(state);
        }
    }
});