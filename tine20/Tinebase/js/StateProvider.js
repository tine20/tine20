/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Tinebase');

Tine.Tinebase.StateProvider = function(config) {
    Tine.Tinebase.StateProvider.superclass.constructor.call(this);
    Ext.apply(this, config);
    this.state = this.readRegistry();
};

Ext.extend(Tine.Tinebase.StateProvider, Ext.state.Provider, {
    
    // private
    clear: function(name){
        Tine.Tinebase.clearState(name);
        Tine.Tinebase.StateProvider.superclass.clear.call(this, name);
    },
    
    // private
    readRegistry: function() {
        var states = {};
        var stateInfo = Tine.Tinebase.registry.get('stateInfo'); 
        for (name in stateInfo) {
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
        Tine.Tinebase.setState(name, this.encodeValue(value));
        Tine.Tinebase.StateProvider.superclass.set.call(this, name, value);
    }
});