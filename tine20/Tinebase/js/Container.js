/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.Tinebase.container');

/**
 * Tinebase container class
 * 
 * @todo add generic container model
 * @todo internal cache (store)
 */
Tine.Tinebase.container = {
    /** 
     * type for internal contaier
     * for example the internal addressbook
     */
    TYPE_INTERNAL: 'internal',
    /**
     * type for personal containers
     */
    TYPE_PERSONAL: 'personal',
    /**
     * type for shared container
     */
    TYPE_SHARED: 'shared'
};

/**
 * gets translated container name by path
 * 
 * @static
 * @param {String} path
 * @param {String} containerName
 * @param {String} containersName
 * @return {String}
 */
Tine.Tinebase.container.path2name = function(path, containerName, containersName) {
    switch (path) {
        case '/':           return String.format(_('All {0}'), containersName);
        case '/shared':     return String.format(_('Shared {0}'), containersName);
        case '/personal':   return String.format(_('Other Users {0}'), containersName);
        case '/internal':   return String.format(_('Internal {0}'), containersName);
    }
    
    if (path === '/personal/' + Tine.Tinebase.registry.get('currentAccount').accountId) {
        return String.format(_('My {0}'), containersName);
    }
    
    return path;
};

/**
 * gets container type by path
 * 
 * @static
 * @param {String} path
 * @return {String}
 */
Tine.Tinebase.container.path2type = function(path) {
    var pathParts = Ext.isArray(path) ? path : path.split('/');
    
    return pathParts[1] === Tine.Tinebase.container.TYPE_PERSONAL && pathParts.length === 2 ? 'otherUsers' : pathParts[1]; 
};