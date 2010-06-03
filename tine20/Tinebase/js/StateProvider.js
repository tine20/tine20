/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.Tinebase');

/**
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.StateProvider
 * @extends     Ext.state.Provider
 */
Tine.Tinebase.StateProvider = function(config) {
    Tine.Tinebase.StateProvider.superclass.constructor.call(this);
    Ext.apply(this, config);
    this.state = this.readRegistry();
};

Ext.extend(Tine.Tinebase.StateProvider, Ext.state.Provider, {
    
    // private
    clear: function(name){
        // persistent clear
        Tine.Tinebase.clearState(name);
        
        // store back in registry (as needed for popups)
        var stateInfo = Tine.Tinebase.registry.get('stateInfo');
        delete stateInfo[name];
        
        Tine.Tinebase.StateProvider.superclass.clear.call(this, name);
    },
    
    // private
    readRegistry: function() {
        var states = {};
        var stateInfo = Tine.Tinebase.registry.get('stateInfo');
        for (var name in stateInfo) {
            states[name] = this.decodeValue(stateInfo[name]);
        };
        
        return states;
    },
    
    // private
    set: function(name, value) {
        if(typeof value == "undefined" || value === null){
            this.clear(name);
            return;
        }
        
        var encodedValue = this.encodeValue(value);
        // persistent save
        Tine.Tinebase.setState(name, encodedValue);
        
        // store back in registry (as needed for popups)
        var stateInfo = Tine.Tinebase.registry.get('stateInfo');
        stateInfo[name] = encodedValue;
        
        Tine.Tinebase.StateProvider.superclass.set.call(this, name, value);
    }
});