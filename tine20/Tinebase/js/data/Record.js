/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */

// @see https://github.com/ericmorand/twing/issues/332
// #if process.env.NODE_ENV !== 'unittest'
import getTwingEnv from "twingEnv";
// #endif

Ext.ns('Tine.Tinebase', 'Tine.Tinebase.data');

Tine.Tinebase.data.Record = function(data, id) {
    if (id || id === 0) {
        this.id = id;
    } else if (data[this.idProperty]) {
        this.id = data[this.idProperty];
    } else {
        this.id = ++Ext.data.Record.AUTO_ID;
    }
    this.data = data;
    this.initData();
    this.ctime = new Date().getTime();
};

/**
 * @namespace Tine.Tinebase.data
 * @class     Tine.Tinebase.data.Record
 * @extends   Ext.data.Record
 * 
 * Baseclass of Tine 2.0 models
 */
Ext.extend(Tine.Tinebase.data.Record, Ext.data.Record, {
    /**
     * @cfg {String} appName
     * internal/untranslated app name (required)
     */
    appName: null,
    /**
     * @cfg {String} modelName
     * name of the model/record  (required)
     */
    modelName: null,
    /**
     * @cfg {String} idProperty
     * property of the id of the record
     */
    idProperty: 'id',
    /**
     * @cfg {String} titleProperty
     * property of the title attibute, used in generic getTitle function  (required)
     */
    titleProperty: null,
    /**
     * @cfg {String} recordName
     * untranslated record/item name
     */
    recordName: 'record',
    /**
     * @cfg {String} recordName
     * untranslated records/items (plural) name
     */
    recordsName: 'records',
    /**
     * @cfg {String} grantsPath
     * path (see _.get() to find grants
     */
    grantsPath: null,
    /**
     * @cfg {String} containerProperty
     * name of the container property
     */
    containerProperty: null,
    /**
     * @cfg {String} containerName
     * untranslated container name
     */
    containerName: 'container',
    /**
     * @cfg {string} containerName
     * untranslated name of container (plural)
     */
    containersName: 'containers',
    /**
     * @cfg: {bool} copyNoAppendTitle
     * don't append "(copy)" string to title when record is copied
     */
    copyNoAppendTitle: null,
    /**
     * default filter
     * @type {string}
     */
    defaultFilter: null,
    
    cfExp: /^#(.+)/,

    /**
     * template fn called when record is instanciated
     */
    initData: Ext.emptyFn,

    /**
     * Get the value of the {@link Ext.data.Field#name named field}.
     * @param {String} name The {@link Ext.data.Field#name name of the field} to get the value of.
     * @return {Object} The value of the field.
     */
    get: function(name) {
        var cfName = String(name).match(this.cfExp);
        
        if (cfName) {
            return this.data.customfields ? this.data.customfields[cfName[1]] : null;
        }
        
        return this.data[name];
    },
    
    /**
     * Set the value of the {@link Ext.data.Field#name named field}.
     * @param {String} name The {@link Ext.data.Field#name name of the field} to get the value of.
     * @return {Object} The value of the field.
     */
    set : function(name, value) {
        var encode = Ext.isPrimitive(value) ? String : Ext.encode,
            current = this.get(name),
            cfName;
            
        if (current !== null && encode(current) == encode(value)) {
            return;
        }
        this.dirty = true;
        if (!this.modified) {
            this.modified = {};
        }
        if (this.modified[name] === undefined) {
            this.modified[name] = current;
        }
        if (encode(value) == encode(this.modified[name])) {
            delete this.modified[name];
        }
        if (Object.keys(this.modified).length === 0) {
            this.dirty = false;
        }
        if (cfName = String(name).match(this.cfExp)) {
            var oldValueJSON = JSON.stringify(this.get('customfields') || {}),
                valueObject = JSON.parse(oldValueJSON);

            Tine.Tinebase.common.assertComparable(valueObject);
            valueObject[cfName[1]] = value;

            if (JSON.stringify(valueObject) != oldValueJSON) {
                this.set('customfields', valueObject);
            }
        } else {
            this.data[name] = value;
        }
        
        if (!this.editing) {
            this.afterEdit();
        }
    },
    
    /**
     * returns title of this record
     * 
     * @return {String}
     */
    getTitle: function() {
        var _ = window.lodash,
            me = this;

        if (Tine.Tinebase.data.TitleRendererManager.has(this.appName, this.modelName)) {
            return Tine.Tinebase.data.TitleRendererManager.get(this.appName, this.modelName)(this);
        } else if (String(this.titleProperty).match(/{/)) {
            if (! this.constructor.titleTwing) {
                var twingEnv = getTwingEnv();
                var loader = twingEnv.getLoader();

                loader.setTemplate(
                    this.constructor.getPhpClassName() + 'Title',
                    Tine.Tinebase.appMgr.get(this.appName).i18n._hidden(this.titleProperty)
                );

                this.constructor.titleTwing = twingEnv;
            }

            return this.constructor.titleTwing.render(this.constructor.getPhpClassName() + 'Title', this.data);
        } else {
            var s = this.titleProperty ? this.titleProperty.split('.') : [null];
            return (s.length > 0 && this.get(s[0]) && this.get(s[0])[s[1]]) ? this.get(s[0])[s[1]] : s[0] ? this.get(this.titleProperty) : '';
        }
    },
    /**
     * returns the id of the record
     */
    getId: function() {
        return this.get(this.idProperty ? this.idProperty : 'id');
    },

    /**
     * sets the id of the record
     *
     * @param {String} id
     */
    setId: function(id) {
        return this.set(this.idProperty ? this.idProperty : 'id', id);
    },

    /**
     * converts data to String
     * 
     * @return {String}
     */
    toString: function() {
        return Ext.encode(this.data);
    },
    
    /**
     * returns true if given record obsoletes this one
     * 
     * - returns false if record has no modlog properties to make 
     *   handling of updated records work in the grid panel
     * @see 0009464: user grid does not refresh after ctx menu action
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @return {Boolean}
     */
    isObsoletedBy: function(record) {
        if (record.modelName !== this.modelName || record.getId() !== this.getId()) {
            throw new Ext.Error('Records could not be compared');
        }
        
        if (this.constructor.hasField('seq') && record.get('seq') != this.get('seq')) {
            return record.get('seq') > this.get('seq');
        }
        
        return (this.constructor.hasField('last_modified_time')) ? record.get('last_modified_time') > this.get('last_modified_time') : false;
    },

    /**
     * update complete record with data from given record
     *
     * @param record
     */
    update: function(record) {
        record = _.get(record, 'data', false) ? record :
            Tine.Tinebase.data.Record.setFromJson(record, this.constructor);

        this.beginEdit();
        this.fields.each((field) => {
            let newValue = Tine.Tinebase.common.assertComparable(record.get(field.name));
            Tine.Tinebase.common.assertComparable(_.get(this, 'data.' + field.name));
            this.set(field.name, newValue);
        }, this);
        this.endEdit();
    }
});

/**
 * Generate a constructor for a specific Record layout.
 * 
 * @param {Array} def see {@link Ext.data.Record#create}
 * @param {Object} meta information see {@link Tine.Tinebase.data.Record}
 * 
 * <br>usage:<br>
<b>IMPORTANT: the ngettext comments are required for the translation system!</b>
<pre><code>
var TopicRecord = Tine.Tinebase.data.Record.create([
    {name: 'summary', mapping: 'topic_title'},
    {name: 'details', mapping: 'username'}
], {
    appName: 'Tasks',
    modelName: 'Task',
    idProperty: 'id',
    titleProperty: 'summary',
    // ngettext('Task', 'Tasks', n);
    recordName: 'Task',
    recordsName: 'Tasks',
    containerProperty: 'container_id',
    // ngettext('to do list', 'to do lists', n);
    containerName: 'to do list',
    containesrName: 'to do lists'
});
</code></pre>
 * @static
 */
Tine.Tinebase.data.Record.create = function(o, meta) {
    var f = Ext.extend(Tine.Tinebase.data.Record, {});
    var p = f.prototype;
    Ext.apply(p, meta);
    p.fields = new Ext.util.MixedCollection(false, function(field) {
        return field.name;
    });
    for(var i = 0, len = o.length; i < len; i++) {
        if (o[i]['name'] == meta.containerProperty && meta.allowBlankContainer === false) {
            o[i]['allowBlank'] = false;
        }
        p.fields.add(new Ext.data.Field(o[i]));
    }
    f.getField = function(name) {
        return p.fields.get(name);
    };
    f.getMeta = function(name) {
        var value = null;
        switch (name) {
            case ('phpClassName'):
                value = p.appName + '_Model_' + p.modelName;
                break;
            default:
                value = p[name];
        }
        return value;
    };
    f.getDefaultData = function() {
        return {};
    };
    f.getFieldDefinitions = function() {
        return p.fields.items;
    };
    f.getFieldNames = function() {
        if (! p.fieldsarray) {
            var arr = p.fieldsarray = [];
            Ext.each(p.fields.items, function(item) {arr.push(item.name);});
        }
        return p.fieldsarray;
    };
    f.hasField = function(n) {
        return p.fields.indexOfKey(n) >= 0;
    };
    f.getRecordName = function() {
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n;

        return i18n.n_(p.recordName, p.recordsName, 1);
    };
    f.getModuleName = function () {
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n,
            moduleName;

        if (p.modelConfiguration && p.modelConfiguration.moduleName) {
            return i18n._(p.modelConfiguration.moduleName);
        }

        return p.moduleName ? i18n._(p.moduleName) : f.getRecordsName();
    };
    f.getRecordsName = function() {
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n;

        return i18n.n_(p.recordName, p.recordsName, 50);
    };
    f.getContainerName = function() {
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n;

        return i18n.n_(p.containerName, p.containersName, 1);
    };
    f.getContainersName = function() {
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n;

        return i18n.n_(p.containerName, p.containersName, 50);
    };
    f.getAppName = function() {
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n;

        return i18n._(p.appName);
    };
    f.getIconCls = function() {
        return 'ApplicationIconCls ' + p.appName + 'IconCls ' + p.appName + p.recordName;
    };
    /**
     * returns the php class name of the record itself or by the application(name) and model(name)
     * @param {mixed} app       the application instance or the application name or the record class
     * @param {mixed} model     the model name
     * @return {String} php class name
     */
    f.getPhpClassName = function(app, model) {
        // without arguments the php class name of the this is returned
        if (!app && !model) {
            return f.getMeta('phpClassName');
        }
        // if var app is a record class, the getMeta method is called
        if (Ext.isFunction(app.getMeta)) {
            return app.getMeta('phpClassName');
        }

        var appName = (Ext.isObject(app) && app.hasOwnProperty('name')) ? app.name : app;
        return appName + '_Model_' + model;
    };
    f.getModelConfiguration = function() {
        return p.modelConfiguration;
    };

    // sanitize containerProperty label
    var containerProperty = f.getMeta('containerProperty');
    if (containerProperty) {
        var field = p.fields.get(containerProperty);
        if (field) {
            field.label = p.containerName;
        }
    }
    if (!p.grantsPath) {
        p.grantsPath = 'data' + (containerProperty ? ('.' + containerProperty) : '') + '.account_grants';
    }
    Tine.Tinebase.data.RecordMgr.add(f);
    return f;
};

Tine.Tinebase.data.Record.generateUID = function(length) {
    length = length || 40;
        
    var s = '0123456789abcdef',
        uuid = new Array(length);
    for(var i=0; i<length; i++) {
        uuid[i] = s.charAt(Math.ceil(Math.random() *15));
    }
    return uuid.join('');
};

Tine.Tinebase.data.RecordManager = Ext.extend(Ext.util.MixedCollection, {
    add: function(record) {
        if (! Ext.isFunction(record.getMeta)) {
            throw new Ext.Error('only records of type Tinebase.data.Record could be added');
        }
        var appName = record.getMeta('appName'),
            modelName = record.getMeta('modelName');
            
        if (! appName && modelName) {
            throw new Ext.Error('appName and modelName must be in the metadatas');
        }
        
//        console.log('register model "' + appName + '.' + modelName + '"');
        Tine.Tinebase.data.RecordManager.superclass.add.call(this, appName + '.' + modelName, record);
    },
    
    get: function(appName, modelName) {
        if (! appName) return;
        if (Ext.isFunction(appName.getMeta)) {
            return appName;
        }
        if (! modelName && appName.modelName) {
            modelName = appName.modelName;
        }
        if (appName.appName) {
            appName = appName.appName;
        }

        if (_.isString(appName) && !modelName) {
            appName = appName.replace(/^Tine[._]/, '')
                .replace(/[._]Model[._]/, '.');

            let appPart = appName.match(/^.+\./);
            if (appPart) {
                modelName = appName.replace(appPart[0], '')
                appName = appPart[0].replace(/\.$/, '');
            }
        }

        if (! Ext.isString(appName)) {
            throw new Ext.Error('appName must be a string');
        }
        
        Ext.each([appName, modelName], function(what) {
            if (! Ext.isString(what)) return;
            var parts = what.split(/(?:_Model_)|(?:\.)/);
            if (parts.length > 1) {
                appName = parts[0];
                modelName = parts[1];
            }
        });
        
        return Tine.Tinebase.data.RecordManager.superclass.get.call(this, appName + '.' + modelName);
    }
});
Tine.Tinebase.data.RecordMgr = new Tine.Tinebase.data.RecordManager(true);

/**
 * create record from json string
 *
 * @param {String} json
 * @param {Tine.Tinebase.data.Record} recordClass
 * @returns {Tine.Tinebase.data.Record}
 */
Tine.Tinebase.data.Record.setFromJson = function(json, recordClass) {
    var jsonReader = new Ext.data.JsonReader({
        id: recordClass.idProperty,
        root: 'results',
        totalProperty: 'totalcount'
    }, recordClass);

    var recordData = {results: [
            Ext.isString(json) ? Ext.decode(json) : json
        ]},
        data = jsonReader.readRecords(recordData),
        record = data.records[0],
        recordId = record.get(record.idProperty);

    record.id = recordId ? recordId : Tine.Tinebase.data.Record.generateUID();

    return record;
};
