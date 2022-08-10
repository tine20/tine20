/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Tinebase');

/**
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.StateProvider
 * @extends     Ext.state.Provider
 */
Tine.Tinebase.StateProvider = function (config) {
    Tine.Tinebase.StateProvider.superclass.constructor.call(this);
    Ext.apply(this, config);
};

Ext.extend(Tine.Tinebase.StateProvider, Ext.state.Provider, {
    
    // private
    clear: async function (name) {
        // persistent clear
        await Tine.Tinebase.clearState(name);
        Tine.Tinebase.StateProvider.superclass.clear.call(this, name);
    },
    
    // private
    readRegistry: async function () {
        const states = {};
        await Tine.Tinebase.loadState().then((stateInfo) => {
            for (const name in stateInfo) {
                states[name] = this.decodeValue(stateInfo[name]);
            }
        }).catch((e) => {});
        
        this.state = states;
        return states;
    },
    
    // private
    set: async function (name, value) {
        if (typeof value == "undefined" || value === null) {
            await this.clear(name);
            return;
        }
        
        const encodedValue = this.encodeValue(value);
        const currentStateEncodedValue = this.encodeValue(this.state[name]);

        if (!this.state[name] || (currentStateEncodedValue !== encodedValue)) {
            // persistent save
            await Tine.Tinebase.setState(name, encodedValue);
        }

        Tine.Tinebase.StateProvider.superclass.set.call(this, name, value);
    }
});
