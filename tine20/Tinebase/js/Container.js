/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Tine.Tinebase.container');

/**
 * Tinebase container class
 * 
 * @todo move container model here
 * 
 * @namespace   Tine.Tinebase.container
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */
Tine.Tinebase.container = {
    /**
     * type for internal contaier for example the internal addressbook
     * 
     * @constant TYPE_INTERNAL
     * @type String
     */
    TYPE_INTERNAL: 'internal',
    
    /**
     * type for personal containers
     * 
     * @constant TYPE_PERSONAL
     * @type String
     */
    TYPE_PERSONAL: 'personal',
    
    /**
     * type for shared container
     * 
     * @constant TYPE_SHARED
     * @type String
     */
    TYPE_SHARED: 'shared',
    
    /**
     * @private
     * 
     * @property isLeafRegExp
     * @type RegExp
     */
    isLeafRegExp: /^\/personal\/[0-9a-z_\-]+\/|^\/shared\/[a-f0-9]+|^\/internal/i,
    
    /**
     * @private
     * 
     * @property isPersonalNodeRegExp
     * @type RegExp
     */
    isPersonalNodeRegExp: /^\/personal\/([0-9a-z_\-]+)$/i,
    
    /**
     * @private
     * 
     * @property isInternalRegExp
     * @type RegExp
     */
    isInternalRegExp: /^\/internal/,
    
    /**
     * returns the path of the 'my ...' node
     * 
     * @return {String}
     */
    getMyNodePath: function() {
        return '/personal/' + Tine.Tinebase.registry.get('currentAccount').accountId
    },
    
    /**
     * returns true if given path represents a (single) container
     * 
     * NOTE: if path could only be undefined when server send container without path.
     *       This happens only in server json classes which only could return containers
     * 
     * @static
     * @param {String} path
     * @return {Boolean}
     */
    pathIsContainer: function(path) {
        return !Ext.isString(path) || !!path.match(Tine.Tinebase.container.isLeafRegExp);
    },
    
    /**
     * returns true if given path represents a personal container of current user
     * 
     * @param {String} path
     * @return {Boolean}
     */
    pathIsMyPersonalContainer: function(path) {
        var regExp = new RegExp('^' + Tine.Tinebase.container.getMyNodePath() + '\/([0-9a-z_\-]+)$');
        var matches = path.match(regExp);
        
        return !!matches;
    },
    
    /**
     * returns true if given path represents an (single) internal container
     * 
     * @static
     * @param {String} path
     * @return {Boolean}
     */
    pathIsInternalContainer: function(path) {
        return !Ext.isString(path) || path.match(Tine.Tinebase.container.isInternalRegExp);
    },
    
    /**
     * returns owner id if given path represents an personal _node_
     * 
     * @static
     * @param {String} path
     * @return {String/Boolean}
     */
    pathIsPersonalNode: function(path) {
        if (! Ext.isString(path)) {
            return false;
        }
        var matches = path.match(Tine.Tinebase.container.isPersonalNodeRegExp);
        
        return matches ? matches[1] : false;
    },
    
    /**
     * gets translated container name by path
     * 
     * @static
     * @param {String} path
     * @param {String} containerName
     * @param {String} containersName
     * @return {String}
     */
    path2name: function(path, containerName, containersName) {
        switch (path) {
            case '/':           return String.format(_('All {0}'), containersName);
            case '/shared':     return String.format(_('Shared {0}'), containersName);
            case '/personal':   return String.format(_('Other Users {0}'), containersName);
            case '/internal':   return String.format(_('Internal {0}'), containersName);
        }
        
        if (path === Tine.Tinebase.container.getMyNodePath()) {
            return String.format(_('My {0}'), containersName);
        }
        
        return path;
    },
    
    /**
     * returns container type (personal/shared) of given path
     * 
     * @static
     * @param {String} path
     * @return {String}
     */
    path2type: function(path) {
        var pathParts = Ext.isArray(path) ? path : path.split('/');
        
        return pathParts[1]; 
    }
    
};
