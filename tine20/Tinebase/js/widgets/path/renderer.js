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
        .replace(/\//g, ' ###SEPARATOR### ');

    pathName = Ext.util.Format.htmlEncode(pathName);

    if (queryParts.length) {
        var hasQueryMatches = false;
        Ext.each(queryParts, function(queryPart) {
            queryPart = Ext.util.Format.htmlEncode(queryPart);

            var queryPartRe = new RegExp(queryPart, 'i'),
                matches = pathName.match(queryPartRe);

            if (matches) {
                hasQueryMatches = true;
                pathName = pathName.replace(queryPartRe, '<span class="tb-widgets-path-pathitem-match">' + matches[0] + '</span>');
            }
        });

        // skip path if no token matched
        if (! hasQueryMatches) {
            pathName = '';
        }

    }

    var qtip = pathName.replace(/###SEPARATOR###/g, "<br/>&nbsp;" + Ext.util.Format.htmlEncode('>'));
    pathName = pathName.replace(/###SEPARATOR###/g, Ext.util.Format.htmlEncode('>'));


    return pathName ? '<div class="tb-widgets-path-pathitem" ext:qtip="' + Ext.util.Format.htmlEncode(qtip) + '">' + pathName + '</div>' : pathName;
}
