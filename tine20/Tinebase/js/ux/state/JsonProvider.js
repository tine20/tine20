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

Ext.ux.state.JsonProvider = function(config) {
    
}
 
Ext.extend(Ext.ux.state.JsonProvider, Ext.state.Provider, {

    /**
     * Returns the current value for a key 
     */
    get: function(name, defaultValue) {
        if(name.match(/^ext\-comp/)) {
            return defaultValue;
        }
        if (name == 'AddressbookEditRecordContainerSelector') {
            console.log('Ext.ux.state.JsonProvider.get');
            //console.log(name);
            //console.log(defaultValue);
            return [{
                id: '3434',
                name: 'mylovelycontaier',
                type: 'personalContainer'
            }];
        }
        
    },
    
    /**
     * Sets the value for a key
     */
    set: function(name, value) {
        if(! name.match(/^ext\-comp/)) {
            var cmp = Ext.getCmp(name);
            if (cmp.stateful) {
               console.log('Ext.ux.state.JsonProvider.set');
                console.log(name);
                console.log(value);
            } else {
                 console.info('Ext.ux.state.JsonProvider::set Attempt to set state of the non stateful component: "' + name + '"');
            }
        }
    },
    
    /**
     * Clears a value from the state
     */
    clear: function(name) {
        
    }
});