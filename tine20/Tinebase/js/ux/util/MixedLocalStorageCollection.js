/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Ext.ux.util');

/**
 * @namespace   Ext.ux.util
 * @class       Ext.ux.util.MixedLocalStorageCollection
 * @extends     Ext.util.MixedCollection
 */
Ext.ux.util.MixedLocalStorageCollection = function(keyFn) {
    // localStorage can only store strings
    Ext.ux.util.MixedLocalStorageCollection.superclass.constructor.apply(this, false, keyFn);

    this.items = [];
    this.map = {};
    this.keys = [];
    this.length = 0;
}
Ext.extend(Ext.ux.util.MixedLocalStorageCollection, Ext.util.MixedCollection, {


});

