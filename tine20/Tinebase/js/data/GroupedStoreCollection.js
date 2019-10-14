/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.data');

/**
 * @namespace   Tine.Tinebase.data
 * @class       Tine.Tinebase.data.GroupedStoreCollection
 * @extends     Ext.util.MixedCollection
 *
 * grouping store collection
 *
 * automatically manages group stores
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config
 */
Tine.Tinebase.data.GroupedStoreCollection = function(config) {
    this.fixedGroups = [];
    Ext.apply(this, config);
    Tine.Tinebase.data.GroupedStoreCollection.superclass.constructor.call(this);

    this.store.on('beforeload', this.onStoreBeforeLoad, this);
    this.store.on('load', this.onStoreLoad, this);
    this.store.on('add', this.onStoreAdd, this);
    this.store.on('update', this.onStoreUpdate, this);
    this.store.on('remove', this.onStoreRemove, this);

    if (this.group) {
        this.applyGrouping();
    }
};

Ext.extend(Tine.Tinebase.data.GroupedStoreCollection, Ext.util.MixedCollection, {
    /**
     * @cfg {Ext.data.Store} store
     */
    store: null,

    /**
     * @cfg {String|Function} group
     */
    group: '',

    /**
     * @cfg {Array} fixedGroups
     * if present, these is the fixed set of groups
     */
    fixedGroups: null,

    /**
     * @cfg {Bool} groupOnLoad
     * apply grouping when store is loaded
     * NOTE: when disabled, grouping must be triggered manually
     */
    groupOnLoad: true,

    applyGrouping: function() {
        if (this.fixedGroups.length) {
            this.setFixedGroups(this.fixedGroups)
        }

        this.groupRecords(this.store.getRange(), false);
    },

    groupBy: function(group) {
        this.group = group;
        this.applyGrouping();
    },

    groupRecords: function(rs, append) {
        // put data into groups
        var groups = [];
        var records = [];
        Ext.each(rs, function(r) {
            var groupNames = this.getGroupNames(r);

            Ext.each(groupNames, function(groupName) {
                var idx = groups.indexOf(groupName);
                if (idx < 0) {
                    groups.push(groupName);
                    records.push([r.copy()]);
                } else {
                    records[idx].push(r.copy());
                }
            });
        }, this);

        // collection housekeeping
        if (! this.fixedGroups.length) {
            Ext.each(this.keys.concat(), function (groupName) {
                if (groups.indexOf(groupName) < 0) {
                    this.removeKey(groupName);
                }
            }, this);
        }

        if (! append) {
            // clear stores which have no longer data
            this.eachKey(function (groupName) {
                if (groups.indexOf(groupName) < 0) {
                    var store = this.getCloneStore(groupName);
                    store.loadRecords({records: []}, {add: append}, true);
                }
            }, this);
        }

        Ext.each(groups, function(groupName, idx) {
            var store = this.getCloneStore(groupName);
            // do we need a beforeload event here?
            store.loadRecords({records: records[idx]}, {add: append}, true);
        }, this);
    },

    setFixedGroups: function(groupNames) {
        this.fixedGroups = groupNames;
        if (groupNames.length) {
            this.setGroups(groupNames);
        }
        this.groupRecords(this.store.getRange(), false);
    },

    // add/delete cloneStores
    setGroups: function(groupNames) {
        Ext.each(this.keys.concat(), function(groupName) {
            if (groupNames.indexOf(groupName) < 0) {
                this.removeKey(groupName);
            }
        }, this);

        Ext.each(groupNames, function(groupName, idx) {
            var store = this.get(groupName);
            if (! store) {
                store = this.createCloneStore();
                this.addSorted(groupName, store);
            }
        }, this);
    },

    /**
     * gets groups of given record
     *
     * @param {Ext.data.record} record
     * @returns {Array}
     */
    getGroupNames: function(record) {
        var _ = window.lodash,
            groupNames = Ext.isFunction(this.group) ? this.group(record) : record.get(this.group);

        if (! Ext.isArray(groupNames)) {
            groupNames = [groupNames];
        }

        if (this.fixedGroups.length) {
            groupNames = _.intersection(groupNames, this.fixedGroups);
        }

        return groupNames;
    },

    onStoreBeforeLoad: function(store, options) {
        var ret = true;
        this.eachKey(function(groupName) {
            var store = this.get(groupName);
            ret = ret && store.fireEvent('beforeload', store, options);
        }, this);

        return ret;
    },

    onStoreLoad: function() {
        if (this.groupOnLoad) {
            this.applyGrouping();
        }
    },

    onStoreAdd: function (store, records, index) {
        this.suspendCloneStoreEvents = true;

        Ext.each(records, function (record) {
            Ext.each(this.getGroupNames(record), function (groupName) {
                var store = this.get(groupName),
                    existingRecord = record.id != 0 ? store.getById(record.id) : null;

                // NOTE: record might be existing as it was added to a cloneStore
                if (existingRecord) {
                    this.getCloneStore(groupName).replace(existingRecord, record.copy());
                } else {
                    this.getCloneStore(groupName).add([record.copy()]);
                }
            }, this);

        }, this);

        this.suspendCloneStoreEvents = false;
    },

    onStoreUpdate: function(store, record, operation) {
        this.suspendCloneStoreEvents = true;

        var groupNames = this.getGroupNames(record);
        this.eachKey(function(groupName) {
            var store = this.get(groupName),
                existingRecord = store.getById(record.id);

            if (existingRecord) {
                if (groupNames.indexOf(groupName) < 0) {
                    store.remove(existingRecord);
                } else {
                    store.replaceRecord(existingRecord, record.copy());
                }

            } else {
                store.add([record.copy()]);
            }
            groupNames.remove(groupName);
        }, this);

        Ext.each(groupNames, function(groupName) {
            var store = this.getCloneStore(groupName);
            store.add(record.copy());
        }, this);

        this.suspendCloneStoreEvents = false;
    },

    onStoreRemove: function(store, record, index) {
        this.suspendCloneStoreEvents = true;

        this.eachKey(function(groupName) {
            var store = this.get(groupName),
                existingRecord = store.getById(record.id);

            if (existingRecord) {
                store.remove(existingRecord);
            }

        }, this);

        this.suspendCloneStoreEvents = false;
    },

    getCloneStore: function(groupName) {
        var store = this.get(groupName);
        if (! store && !this.fixedGroups.length) {
            store = this.createCloneStore();
            this.addSorted(groupName, store);
        }

        return store;
    },

    addSorted: function(groupName, store) {
        var idx = this.length;

        if (this.sortFn) {
            var items = [store].concat(this.items);
            items.sort(this.sortFn);
            idx = items.indexOf(store);
        }

        this.insert(idx, groupName, store);
    },

    createCloneStore: function() {
        var clone = new Ext.data.Store({
            fields: this.store.fields,
            // load: this.mainStore.load.createDelegate(this.mainStore),
            // proxy: this.store.proxy,
            replaceRecord: function(o, n) {
                var idx = this.indexOf(o);
                this.remove(o);
                this.insert(idx, n);
            }
        });

        clone.on('add', this.onCloneStoreAdd, this);
        clone.on('update', this.onCloneStoreUpdate, this);
        clone.on('remove', this.onCloneStoreRemove, this);
        return clone;
    },

    onCloneStoreAdd: function(eventStore, rs) {
        if (this.suspendCloneStoreEvents) return;

        Ext.each(rs, function(r) {
            this.store.add(r.copy());
        }, this);
    },

    onCloneStoreUpdate: function(eventStore, r) {
        if (this.suspendCloneStoreEvents) return;

        var existingRecord = this.store.getById(r.id);

        if (existingRecord) {
            this.store.replaceRecord(existingRecord, r.copy());
        }
    },

    onCloneStoreRemove: function(store, r) {
        if (this.suspendCloneStoreEvents) return;

        var existingRecord = this.store.getById(r.id);

        if (existingRecord) {
            this.store.remove(existingRecord);
        }
    }
});