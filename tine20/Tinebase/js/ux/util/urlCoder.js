/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Ext.ux.util.urlCoder');

/**
 * native encodeURIComponent converts space into %20 -> convert to modern +
 *
 * @param string
 * @returns {string}
 */
Ext.ux.util.urlCoder.encodeURIComponent = function(string) {
    return encodeURIComponent(string).replace(/\%20/gm,"+");
};

/**
 * native encodeURI converts space into %20 -> convert to modern +
 *
 * @param string
 * @returns {string}
 */
Ext.ux.util.urlCoder.encodeURI = function(string) {
    return encodeURI(string).replace(/\%20/gm,"+");
};

/**
 * native decodeURIComponent converts can't cope with modern + for spaces
 *
 * @param string
 * @returns {string}
 */
Ext.ux.util.urlCoder.decodeURIComponent = function(string) {
    return decodeURIComponent((string+'').replace(/\+/gm,"%20"));
};

/**
 * native decodeURI converts can't cope with modern + for spaces
 *
 * @param string
 * @returns {string}
 */
Ext.ux.util.urlCoder.decodeURI = function(string) {
    return decodeURI((string+'').replace(/\+/gm,"%20"));
};