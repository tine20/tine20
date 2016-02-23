/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.cweiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.path');

/**
 * paths block renderer
 *
 * @param {Array} paths
 * @param {String} queryString
 * @returns {string}
 */
Tine.widgets.path.pathsRenderer = function(paths, queryString) {
    var pathsString = '';

    if (Ext.isArray(paths)) {
        Ext.each(paths, function(path) {
            pathsString += Tine.widgets.path.pathRenderer(path, queryString);
        });
    }

    return pathsString ? '<div class="tb-widgets-path-pathsblock">' + pathsString + '</div>' : pathsString;
};

/**
 * single path renderer
 *
 * @param path
 * @param queryString
 * @returns {string}
 */
Tine.widgets.path.pathRenderer = function(path, queryString) {
    var pathName = String(path.path),
        queryParts = String(queryString).split(' ');

    pathName = pathName
        .replace(/^\//, '')
        .replace(/\//g, '\u0362');

    pathName = Ext.util.Format.htmlEncode(pathName);

    if (queryParts.length) {
        var hasQueryMatches = false,
            search = '';

        Ext.each(queryParts, function(queryPart, idx) {
            search += (search ? '|(' :'(') + Ext.util.Format.htmlEncode(queryPart) + ')';
        });

        pathName = pathName.replace(new RegExp(search,'gi'), function(match) {
            hasQueryMatches = true;
            return '<span class="tb-widgets-path-pathitem-match">' + match + '</span>';
        });

        // skip path if no token matched
        if (! hasQueryMatches) {
            pathName = '';
        }
    }

    var qtip = pathName.replace(/(?:{(.*)}){0,1}\u0362/g, function(all, type) {
        return "<br/>&nbsp;" + (type ? type + ' ' : '') + Ext.util.Format.htmlEncode('\u00BB') + '&nbsp;';
    });

    pathName = pathName.replace(/(?:{(.*)}){0,1}\u0362/g, function(all, type) {
        return "&nbsp;" + (type ? '<span class="tb-widgets-path-pathitem-type">' + type[0] + '</span>' : '') + Ext.util.Format.htmlEncode('\u00BB') + '&nbsp;';
    });


    return pathName ? '<div class="tb-widgets-path-pathitem" ext:qtip="' + Ext.util.Format.htmlEncode(qtip) + '">' + pathName + '</div>' : pathName;
}
