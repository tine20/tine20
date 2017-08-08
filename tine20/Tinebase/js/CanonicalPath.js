/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase.CanonicalPath');

/**
 * @static
 * @type {string}
 */
Tine.Tinebase.CanonicalPath.separator = '/';

/**
 * get full canonical path of given component
 *
 * $static
 * @param {Ext.Component} component
 */
Tine.Tinebase.CanonicalPath.getPath = function(component) {
    var _ = window.lodash,
        pathParts = [],
        pathPart;

    while (component) {
        pathPart = Tine.Tinebase.CanonicalPath.getPathPart(component);

        if (pathPart && !pathParts.join(Tine.Tinebase.CanonicalPath.separator).match(new RegExp('^' + _.escapeRegExp(pathPart)))) {
            pathParts.unshift(pathPart);
        }

        // stop if pathPart starts with a separator -> counts as root
        component = String(pathPart).charAt(0) != Tine.Tinebase.CanonicalPath.separator && Ext.isFunction(component.findParentBy) ?
            component.findParentBy(Tine.Tinebase.CanonicalPath.hasPathPart) : false;
    }

    return pathParts.join(Tine.Tinebase.CanonicalPath.separator);
};

/**
 * return canonical path part
 *
 * @static
 * @param {Ext.Component} component
 * @returns {String}
 */
Tine.Tinebase.CanonicalPath.getPathPart = function(component) {
    return Tine.Tinebase.CanonicalPath.hasPathPart(component) ?
        component.getCanonicalPathSegment() : '';
};

/**
 * checks if component has canonical path part
 *
 * @static
 * @param {Ext.Component} component
 * @returns {Bool}
 */
Tine.Tinebase.CanonicalPath.hasPathPart = function(component) {
    return Ext.isFunction(component.getCanonicalPathSegment);
};

/**
 * activate  as much as possible :)
 *
 * @TODO
 *  - activate views/ranges (e.g. calendar)
 *  - activate filtertoolbar
 *  - activate and find paths in windows
 * @TODO
 * - rethink forward resolution/activation of paths, we need this for tests to stear UI
 *
 * @private
 * @static
 */
Tine.Tinebase.CanonicalPath.activateAll = function() {
    Tine.Tinebase.appMgr.apps.each(function (app) {
        Tine.Tinebase.MainScreen.activate(app);
        var mainScreen = app.getMainScreen(),
            westPanel = mainScreen ? mainScreen.getWestPanel() : null,
            moduleTree = mainScreen && mainScreen.useModuleTreePanel && Ext.isFunction(mainScreen.getModuleTreePanel) ?
                mainScreen.getModuleTreePanel() : null;

        // activate mainscreens of all contenttypes
        if (moduleTree && Ext.isFunction(moduleTree.getRootNode)) {
            Ext.each(moduleTree.getRootNode().childNodes, function (node) {
                mainScreen.setActiveContentType(node.attributes.contentType);
            });
        } else if (westPanel && Ext.isFunction(westPanel.getRootNode)) {
            Ext.each(westPanel.getRootNode().childNodes, function (node) {
                // Admin, but not Voipmanager :)
                if (! node.childNodes.length) {
                    node.fireEvent('click', node, {});
                }
            });
        }
    });

    // query for all openWindow methods
    var openers = {};
    var rfn = function(o) {
        lodash.forEach(o, function(value, key) {
            if (lodash.isObject(value) && lodash.isFunction(value.openWindow)) {
                openers[key] = value.openWindow;
            } else if (lodash.isPlainObject(value)) {
                rfn(value);
            }
        });
    }
    rfn(Tine);

    Tine.WindowFactory.windowType = 'Ext';
    lodash.forEach(openers, function(opnr, name) {
        try {
            console.info(name);
            opnr({});
        } catch(e) {}

    });
};

/**
 * gets canonical pathes of all created components
 *
 * @private
 * @static
 * @returns {Array}
 */
Tine.Tinebase.CanonicalPath.getAllPaths = function() {
    var paths = [];
    Ext.ComponentMgr.all.each(function(component) {
        var canonicalPath = Tine.Tinebase.CanonicalPath.getPath(component);
        if (canonicalPath) {
            paths.push(canonicalPath);
        }
    });

    return paths.unique().sort();
};

/**
 * generic canonical name support for components
 */
Ext.override(Ext.Component, {
    /**
     * canonical name
     * @cfg {String} canonicalName
     */
    canonicalName: null,

    /**
     * returns canonical name / path segment
     * @returns {string}
     */
    getCanonicalPathSegment: function() {
        return this.canonicalName
    }
});
