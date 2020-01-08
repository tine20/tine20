/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase.Model');

/**
 * @type {Array}
 * 
 * modlog Fields
 */

Tine.Tinebase.Model.modlogFields = [
    { name: 'creation_time',      type: 'date', dateFormat: Date.patterns.ISO8601Long, omitDuplicateResolving: true },
    { name: 'created_by',                                                              omitDuplicateResolving: true },
    { name: 'last_modified_time', type: 'date', dateFormat: Date.patterns.ISO8601Long, omitDuplicateResolving: true },
    { name: 'last_modified_by',                                                        omitDuplicateResolving: true },
    { name: 'is_deleted',         type: 'boolean',                                     omitDuplicateResolving: true },
    { name: 'deleted_time',       type: 'date', dateFormat: Date.patterns.ISO8601Long, omitDuplicateResolving: true },
    { name: 'deleted_by',                                                              omitDuplicateResolving: true },
    { name: 'seq',                                                                     omitDuplicateResolving: true }
];

/**
 * @type {Array}
 * generic Record fields
 */
Tine.Tinebase.Model.genericFields = Tine.Tinebase.Model.modlogFields.concat([
    { name: 'container_id', header: 'Container',                                       omitDuplicateResolving: false}
]);
    
/**
 * Model of a language
 */
Tine.Tinebase.Model.Language = Ext.data.Record.create([
    { name: 'locale' },
    { name: 'language' },
    { name: 'region' }
]);

/**
 * Model of a timezone
 */
Tine.Tinebase.Model.Timezone = Ext.data.Record.create([
    { name: 'timezone' },
    { name: 'timezoneTranslation' }
]);

/**
 * Model of a role
 */
Tine.Tinebase.Model.Role = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
], {
    appName: 'Tinebase',
    modelName: 'Role',
    idProperty: 'id',
    titleProperty: 'name',
    recordName: 'Role',
    recordsName: 'Roles'
});

/**
 * Model of a generalised account (user or group)
 */
Tine.Tinebase.Model.Account = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'type'},
    {name: 'name'},
    {name: 'data'} // todo: throw away data
], {
    appName: 'Tinebase',
    modelName: 'Account',
    idProperty: 'id',
    titleProperty: 'name'
});

/**
 * Model of a container
 */
Tine.Tinebase.Model.Container = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.modlogFields.concat([
    {name: 'id'},
    {name: 'name'},
    {name: 'hierarchy'},
    {name: 'type'},
    {name: 'backend'},
    {name: 'order'},
    {name: 'color'},
    {name: 'application_id'},
    {name: 'owner_id'},
    {name: 'model'},
    {name: 'uuid'},
    {name: 'content_seq'},
    {name: 'account_grants'},
    {name: 'path'},
    {name: 'xprops'},
    // virtual
    {name: 'ownerContact'},
    {name: 'is_container_node', type: 'boolean'},
    {name: 'dtselect', type: 'number'},
]), {
    appName: 'Tinebase',
    modelName: 'Container',
    idProperty: 'id',
    titleProperty: 'name'
});

/**
 * Model of a grant
 */
Tine.Tinebase.Model.Grant = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'record_id'},
    {name: 'account_id'},
    {name: 'account_type'},
    {name: 'account_name', sortType: Tine.Tinebase.common.accountSortType},
    {name: 'freebusyGrant',type: 'boolean'},
    {name: 'readGrant',    type: 'boolean'},
    {name: 'addGrant',     type: 'boolean'},
    {name: 'editGrant',    type: 'boolean'},
    {name: 'deleteGrant',  type: 'boolean'},
    {name: 'privateGrant', type: 'boolean'},
    // TODO app specific (ADB) grant definition is currently needed here - make it work without
    {name: 'privateDataGrant', type: 'boolean'},
    {name: 'exportGrant',  type: 'boolean'},
    {name: 'syncGrant',    type: 'boolean'},
    {name: 'downloadGrant',type: 'boolean'},
    {name: 'publishGrant', type: 'boolean'},
    {name: 'adminGrant',   type: 'boolean'}
], {
    appName: 'Tinebase',
    modelName: 'Grant',
    idProperty: 'id',
    titleProperty: 'account_name',
    // ngettext('Grant', 'Grants', n); gettext('Grant');
    recordName: 'Grant',
    recordsName: 'Grants'
});

/**
 * Model of a tag
 * 
 * @constructor {Tine.Tinebase.data.Record}
 */
Tine.Tinebase.Model.Tag = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.modlogFields.concat([
    {name: 'id'         },
    {name: 'app'        },
    {name: 'owner'      },
    {name: 'name'       },
    {name: 'type'       },
    {name: 'description'},
    {name: 'color'      },
    {name: 'occurrence' },
    {name: 'rights'     },
    {name: 'contexts'   },
    {name: 'selection_occurrence', type: 'number'}
]), {
    appName: 'Tinebase',
    modelName: 'Tag',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Tag', 'Tags', n); gettext('Tag');
    recordName: 'Tag',
    recordsName: 'Tags'
});

/**
 * replace template fields with data
 * @static
 */
Tine.Tinebase.Model.Tag.replaceTemplateField = function(tagData) {
    if (Ext.isArray(tagData)) {
        return Ext.each(tagData, Tine.Tinebase.Model.Tag.replaceTemplateField);
    }
    
    if (Ext.isFunction(tagData.beginEdit)) {
        tagData = tagData.data;
    }
    
    var replace = {
        'CURRENTDATE': Tine.Tinebase.common.dateRenderer(new Date()),
        'CURRENTTIME': Tine.Tinebase.common.timeRenderer(new Date()),
        'USERFULLNAME': Tine.Tinebase.registry.get('currentAccount').accountDisplayName
    };
    
    Ext.each(['name', 'description'], function(field) {
        for(var token in replace) {
            if (replace.hasOwnProperty(token) && Ext.isString(tagData[field])) {
                tagData[field] = tagData[field].replace(new RegExp('###' + token + '###', 'g'), replace[token]);
            }
        }
    }, this);
    
};

/**
 * Model of a PickerRecord
 * 
 * @constructor {Ext.data.Record}
 * 
 * @deprecated
 */
Tine.Tinebase.PickerRecord = Ext.data.Record.create([
    {name: 'id'}, 
    {name: 'name'}, 
    {name: 'data'}
]);

/**
 * Model of a note
 * 
 * @constructor {Ext.data.Record}
 */
Tine.Tinebase.Model.Note = Ext.data.Record.create([
    {name: 'id'             },
    {name: 'note_type_id'   },
    {name: 'note'           },
    {name: 'creation_time', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    {name: 'created_by'     }
]);

/**
 * Model of a note type
 * 
 * @constructor {Ext.data.Record}
 */
Tine.Tinebase.Model.NoteType = Ext.data.Record.create([
    {name: 'id'             },
    {name: 'name'           },
    {name: 'icon'           },
    {name: 'icon_class'     },
    {name: 'description'    },
    {name: 'is_user_type'   }
]);

/**
 * Model of a customfield definition
 */
Tine.Tinebase.Model.Customfield = Ext.data.Record.create([
    { name: 'id'             },
    { name: 'application_id' },
    { name: 'model'          },
    { name: 'name'           },
    { name: 'definition'     },
    { name: 'account_grants' }
]);

/**
 * Model of a customfield value
 */
Tine.Tinebase.Model.CustomfieldValue = Ext.data.Record.create([
    { name: 'record_id'      },
    { name: 'customfield_id' },
    { name: 'value'          }
]);

/**
 * Model of a preference
 * 
 * @constructor {Ext.data.Record}
 */
Tine.Tinebase.Model.Preference = Ext.data.Record.create([
    {name: 'id'             },
    {name: 'name'           },
    {name: 'value'          },
    {name: 'type'           },
    {name: 'label'          },
    {name: 'description'    },
    {name: 'personal_only',         type: 'boolean' },
    {name: 'locked',                type: 'boolean' },
    {name: 'uiconfig'       },
    {name: 'options'        }
]);

/**
 * Model of an alarm
 * 
 * @constructor {Ext.data.Record}
 */
Tine.Tinebase.Model.Alarm = Tine.Tinebase.data.Record.create([
    {name: 'id'             },
    {name: 'record_id'      },
    {name: 'model'          },
    {name: 'alarm_time',      type: 'date', dateFormat: Date.patterns.ISO8601Long },
    {name: 'minutes_before',  sortType: Ext.data.SortTypes.asInt},
    {name: 'sent_time',       type: 'date', dateFormat: Date.patterns.ISO8601Long },
    {name: 'sent_status'    },
    {name: 'sent_message'   },
    {name: 'options'        }
], {
    appName: 'Tinebase',
    modelName: 'Alarm',
    idProperty: 'id',
    titleProperty: 'minutes_before',
    // ngettext('Alarm', 'Alarms', n); gettext('Alarm');
    recordName: 'Alarm',
    recordsName: 'Alarms',
    getOption: function(name) {
        var encodedOptions = this.get('options'),
            options = encodedOptions ? Ext.decode(encodedOptions) : {};
        
        return options[name];
    },
    setOption: function(name, value) {
        var encodedOptions = this.get('options'),
            options = encodedOptions ? Ext.decode(encodedOptions) : {};
        
        options[name] = value;
        this.set('options', Ext.encode(options));
    }
});

/**
 * @namespace Tine.Tinebase.Model
 * @class     Tine.Tinebase.Model.ImportJob
 * @extends   Tine.Tinebase.data.Record
 * 
 * Model of an import job
 */
Tine.Tinebase.Model.ImportJob = Tine.Tinebase.data.Record.create([
    {name: 'files'                  },
    {name: 'import_definition_id'   },
    {name: 'model'                  },
    {name: 'import_function'        },
    {name: 'container_id'           },
    {name: 'dry_run'                },
    {name: 'options'                }
], {
    appName: 'Tinebase',
    modelName: 'Import',
    idProperty: 'id',
    titleProperty: 'model',
    // ngettext('Import', 'Imports', n); gettext('Import');
    recordName: 'Import',
    recordsName: 'Imports'
});

/**
 * @namespace Tine.Tinebase.Model
 * @class     Tine.Tinebase.Model.ExportJob
 * @extends   Tine.Tinebase.data.Record
 * 
 * Model of an export job
 */
Tine.Tinebase.Model.ExportJob = Tine.Tinebase.data.Record.create([
    {name: 'scope'                  },
    {name: 'filter'                 },
    {name: 'export_definition_id'   },
    {name: 'format'                 },
    {name: 'exportFunction'         },
    {name: 'recordsName'            },
    {name: 'model'                  },
    {name: 'count', type: 'int'     },
    {name: 'options'                }
], {
    appName: 'Tinebase',
    modelName: 'Export',
    idProperty: 'id',
    titleProperty: 'model',
    // ngettext('Export', 'Export', n); gettext('Export');
    recordName: 'Export',
    recordsName: 'Exports'
});

/**
 * @namespace Tine.Tinebase.Model
 * @class     Tine.Tinebase.Model.Credentials
 * @extends   Tine.Tinebase.data.Record
 * 
 * Model of user credentials
 */
Tine.Tinebase.Model.Credentials = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'username'},
    {name: 'password'}
], {
    appName: 'Tinebase',
    modelName: 'Credentials',
    idProperty: 'id',
    titleProperty: 'username',
    // ngettext('Credentials', 'Credentials', n); gettext('Credentials');
    recordName: 'Credentials',
    recordsName: 'Credentials'
});

/**
 * @namespace Tine.Tinebase.Model
 * @class     Tine.Tinebase.Model.Relation
 * @extends   Tine.Tinebase.data.Record
 * 
 * Model of a Relation
 */
Tine.Tinebase.Model.Relation = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'own_model'},
    {name: 'own_id'},
    {name: 'related_model'},
    {name: 'related_id'},
    {name: 'type'},
    {name: 'remark'},
    {name: 'related_record', sortType: Tine.Tinebase.common.recordSortType},
    {name: 'creation_time'}
], {
    appName: 'Tinebase',
    modelName: 'Relation',
    idProperty: 'id',
    titleProperty: 'related_model',
    // ngettext('Relation', 'Relations', n); gettext('Relation');
    recordName: 'Relation',
    recordsName: 'Relations'
});

/**
 * find duplicate relation in store
 *
 * @param store
 * @param record
 * @return int index in store
 */
Tine.Tinebase.Model.Relation.findDuplicate = function(store, record) {
    return store.findBy(function(r) {
        return r.get('related_model') == record.get('related_model') &&
            r.get('related_id') == record.get('related_id') &&
            r.get('type') == record.get('type');
    }, this);
};

/**
 * @namespace Tine.Tinebase.Model
 * @class     Tine.Tinebase.Model.Department
 * @extends   Tine.Tinebase.data.Record
 * 
 * Model of a Department
 */
Tine.Tinebase.Model.Department = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
], {
    appName: 'Tinebase',
    modelName: 'Department',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Department', 'Departments', n); gettext('Department');
    recordName: 'Department',
    recordsName: 'Departments'
});

Tine.Tinebase.Model.Department.getFilterModel = function() {
    return [
        {label: i18n._('Name'),          field: 'name',       operators: ['contains']}
    ];
};

/**
 * @namespace Tine.Tinebase.Model
 * @class     Tine.Tinebase.Model.Config
 * @extends   Tine.Tinebase.data.Record
 * 
 * Model of a application config settings
 */
Tine.Tinebase.Model.Config = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'application_id'},
    {name: 'name'},
    {name: 'value'},
    {name: 'label'},
    {name: 'description'},
    {name: 'type'},
    {name: 'clientRegistryInclude'},
    {name: 'setByAdminModule'},
    {name: 'setBySetupModule'},
    {name: 'default'},
    {name: 'source'}
    //{name: 'settings'}
], {
    appName: 'Tinebase',
    modelName: 'Config',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Config', 'Configs', n); gettext('Configs');
    recordName: 'Config',
    recordsName: 'Configs'
});

Tine.Tinebase.Model.Tree_NodeArray = Tine.Tinebase.Model.modlogFields.concat([
    { name: 'id' },
    { name: 'name', label: 'Name' }, // _('Name')
    { name: 'path', label: 'Path' }, // _('Path')
    { name: 'size', label: 'Size' }, // _('Size')
    { name: 'revision', label: 'Revision' }, // _('Revision')
    { name: 'available_revisions', label: 'Available Revision' }, // _('Available Revision')
    { name: 'type', label: 'Type' }, // _('Type')
    { name: 'contenttype', label: 'Content Type' }, // _('Content Type')
    { name: 'description', label: 'Description' }, // _('Description')
    { name: 'account_grants' },
    { name: 'grants', label: 'Grants' }, // _('Grants')
    { name: 'acl_node', label: 'Grants Folder' }, // _('Grants Folder')
    { name: 'object_id'},
    { name: 'hash', label: 'MD5 Hash' }, // _('MD5 Hash')
    { name: 'revision_size', label: 'Revision Size' }, // _('Revision Size')
    { name: 'preview_count', label: 'Preview Count', type: 'int' }, // _('Preview Count')
    { name: 'isIndexed', label: 'Indexed' }, // _('Indexed')
    { name: 'pin_protected_node', label: 'Pin Protected' }, // _('Pin Protected')
    { name: 'quota', label: 'Quota'}, // _('Quota')
    { name: 'effectiveAndLocalQuota', label: 'Effective Quota' }, // _('Effective Quota')
    { name: 'is_quarantined', label: 'Is Quarantined' }, // _('Is Quarantined') // TODO add type bool/int?

    { name: 'relations' },
    { name: 'customfields' },
    { name: 'notes' },
    { name: 'tags' },

    { name: 'revisionProps' },
    { name: 'notificationProps' },
    { name: 'is_quarantined', type: 'boolean'}
]);
/**
 * @namespace   Tine.Tinebase.Model
 * @class       Tine.Tinebase.Model.Tree_Node
 * @extends     Tine.Tinebase.data.Record
 */
Tine.Tinebase.Model.Tree_Node = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.Tree_NodeArray, {
    appName: 'Tinebase',
    modelName: 'Tree_Node',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('File', 'Files', n); gettext('File');
    recordName: 'File',
    recordsName: 'Files'
});

Tine.widgets.grid.RendererManager.register('Tinebase', 'Tree_Node', 'size', Tine.Tinebase.common.byteRenderer);
Tine.widgets.grid.RendererManager.register('Tinebase', 'Tree_Node', 'revision_size', Tine.Tinebase.common.byteRenderer);

Tine.Tinebase.Model.KeyFieldRecord = Tine.Tinebase.data.Record.create([
    { name: 'id' },
    { name: 'value' },
    { name: 'i18nValue' },
    { name: 'icon' },
    { name: 'color' },
    { name: 'system' }
], {
    appName: 'Tinebase',
    modelName: 'KeyFieldRecord',
    idProperty: 'id',
    titleProperty: 'value'
});

Tine.Tinebase.Model.Path = Tine.Tinebase.data.Record.create([
    { name: 'id' },
    { name: 'path' },
    { name: 'shadow_path' },
    { name: 'creation_time' }
], {
    appName: 'Tinebase',
    modelName: 'Path',
    idProperty: 'id',
    titleProperty: 'path',
    // defaultFilter: 'path'
});

Tine.Tinebase.Model.Path.getFilterModel = function() {
    return [
        {label: i18n._('Query'),         field: 'query',       operators: ['contains']},
        {label: i18n._('Path'),          field: 'path',       operators: ['contains']}
    ];
};