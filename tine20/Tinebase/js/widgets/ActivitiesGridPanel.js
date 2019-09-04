/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.activities');

/**
 * @namespace   Tine.widgets.activities
 * @class       Tine.widgets.activities.ActivitiesGridPanel
 * @extends     Ext.grid.GridPanel
 * @author      Michael Spahn <m.spahn@metaways.de>
 */
Tine.widgets.activities.ActivitiesGridPanel = Ext.extend(Ext.grid.GridPanel, {
    /**
     * The calling EditDialog
     * @type Tine.widgets.activities.EditDialog
     */
    editDialog: null,

    /**
     * title
     *
     * @type String
     */
    title: null,

    /**
     * the record
     * @type Record
     */
    record: null,

    /**
     * @type Tinebase.Application
     */
    app: null,

    /**
     * @cfg {String} requiredGrant to make actions
     */
    requiredGrant: 'editGrant',

    /**
     * @cfg {Number} pos
     * position 200 = 100 + 100*1 -> means first one after app specific tabs
     */
    pos: 200,

    /* config */
    frame: true,
    border: true,
    autoScroll: true,
    layout: 'fit',
    canonicalName: 'NotesGrid',

    store: null,

    viewConfig: {},

    /**
     * initializes the component
     */
    initComponent: function () {
        this.record = this.editDialog.record;
        this.app = this.editDialog.app;
        this.title = this.i18nTitle = i18n._('Notes');

        // init actions
        this.actionUpdater = new Tine.widgets.ActionUpdater({
            evalGrants: false
        });

        this.initToolbarAndContextMenu();
        this.initStore();
        this.initColumnModel();
        this.initSelectionModel();

        //Tine.widgets.dialog.MultipleEditDialogPlugin.prototype.registerSkipItem(this);
        this.editDialog.on('load', this.onLoadRecord, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        Tine.widgets.activities.ActivitiesGridPanel.superclass.initComponent.call(this);

        this.on('rowcontextmenu', function (grid, row, e) {
            e.stopEvent();
            var selModel = grid.getSelectionModel();
            if (!selModel.isSelected(row)) {
                selModel.selectRow(row);
            }
            this.contextMenu.showAt(e.getXY());
        }, this);
    },

    /**
     * get columns
     * @return Array
     */
    getColumns: function () {
        var columns = [
            {
                id: 'note_type_id',
                header: i18n._('Type'),
                dataIndex: 'note_type_id',
                renderer: Tine.widgets.activities.getTypeIcon,
                width: 30
            },
            {
                id: 'note',
                dataIndex: 'note',
                width: 500,
                header: i18n._('Note'),
                sortable: true,
                renderer: this.renderMultipleLines
            },
            {
                id: 'created_by',
                header: i18n._('Created By'),
                dataIndex: 'created_by'
            },
            {
                id: 'creation_time',
                header: i18n._('Creation time'),
                dataIndex: 'creation_time',
                width: 120,
                renderer: Tine.Tinebase.common.dateTimeRenderer
            }
        ];

        return columns;
    },

    /**
     * There is no option to display multiple lines in extjs 3.* this renderer replaces newlines with html breaks
     *
     * @param value
     * @returns {string}
     */
    renderMultipleLines: function (value) {
        return '<div style="white-space:normal !important;">' + Ext.util.Format.nl2br(Ext.util.Format.htmlEncode(value)) + '</div>';
    },

    /**
     * Setup column model for grid
     */
    initColumnModel: function () {
        this.cm = new Ext.grid.ColumnModel(this.getColumns());
        this.cm.defaultSortable = true; // by default columns are sortable
    },

    /**
     * init store
     * @private
     */
    initStore: function () {
        this.store = new Ext.data.SimpleStore({
            fields: Tine.Tinebase.Model.Note
        });
    },

    /**
     * updates the title ot the tab
     * @param {Integer} count
     */
    updateTitle: function (count) {
        count = Ext.isNumber(count) ? count : this.store.getCount();
        this.setTitle(this.i18nTitle + ' (' + count + ')');
    },

    /**
     * is called from onApplyChanges of the edit dialog per save event
     *
     * @param {Tine.widgets.dialog.EditDialog} dialog
     * @param {Tine.Tinebase.data.Record} record
     * @return {Boolean}
     */
    onRecordUpdate: function(dialog, record) {
        if (record.data.hasOwnProperty('notes')) {
            Tine.Tinebase.common.assertComparable(record.data.notes);
        }

        record.set('notes', Tine.Tinebase.common.assertComparable(this.getData()));
    },

    /**
     * populate store
     *
     * @param {EditDialog} editDialog
     * @param {Record} record
     * @param {Function} ticketFn
     */
    onLoadRecord: function (editDialog, record, ticketFn) {
        var _ = window.lodash,
            interceptor = ticketFn(),
            notes = record.get('notes'),
            evalGrants = editDialog.evalGrants,
            hasRequiredGrant = !evalGrants || _.get(record, record.constructor.getMeta('grantsPath') + '.' + this.requiredGrant);

        this.store.purgeListeners();
        this.store.removeAll();

        if (notes && notes.length > 0) {
            this.updateTitle(notes.length);
            var notesRecords = [];

            Ext.each(notes, function (notes) {
                notesRecords.push(new Tine.Tinebase.Model.Tree_Node(notes, notes.id));
            }, this);
            this.store.add(notesRecords);
        } else {
            this.updateTitle(0);
        }

        // add other listeners after population
        if (this.store) {
            this.store.on('update', this.updateTitle, this);
            this.store.on('add', this.updateTitle, this);
            this.store.on('remove', this.updateTitle, this);
        }
        interceptor();

        this.setReadOnly(! hasRequiredGrant);
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        this.action_add.setDisabled(readOnly);
        this.action_remove.setDisabled(readOnly);
    },

    /**
     * init sel model
     * @private
     */
    initSelectionModel: function () {
        this.selModel = new Ext.grid.RowSelectionModel({multiSelect: true});

        this.selModel.on('selectionchange', function (selModel) {
            var rowCount = selModel.getCount();
            this.actionUpdater.updateActions(selModel);
            this.action_remove.setDisabled(this.readOnly);

            // The tine server can't handle edits for notes at the moment
            // @todo make this work
            //this.action_edit.setDisabled(this.readOnly);
        }, this);
    },

    /**
     * init toolbar and context menu
     * @private
     */
    initToolbarAndContextMenu: function () {
        this.action_add = new Ext.Action({
            text: String.format(i18n._('Add {0}'), this.i18nTitle),
            iconCls: 'action_add',
            scope: this,
            handler: this.addNote
        });

        this.action_edit = new Ext.Action({
            text: String.format(i18n._('Edit {0}'), this.i18nTitle),
            iconCls: 'action_edit',
            scope: this,
            disabled: true,
            visible: false,
            handler: this.addNote
        });

        this.action_remove = new Ext.Action({
            text: String.format(i18n._('Remove {0}'), this.i18nTitle),
            iconCls: 'action_remove',
            scope: this,
            disabled: true,
            handler: this.removeNote
        });

        //@todo make tine edit notes if client sents modified data
        this.tbar = [
            this.action_add,
            //this.action_edit,
            this.action_remove
        ];

        //@todo make tine edit notes if client sents modified data
        this.contextMenu = new Ext.menu.Menu({
            plugins: [{
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }],
            items: [
                //this.action_edit,
                this.action_remove
            ]
        });
    },

    /**
     * Handler: Remove every selected note, 1 or more
     * @param sel
     */
    removeNote: function (sel) {
        Ext.each(this.getSelectionModel().getSelections(), function (record) {
            this.store.remove(this.store.getById(record.id));
        });
    },

    /**
     * Handler: Opens a dialog and for note creation and saves to store
     * @param action
     * @param event
     */
    addNote: function (button, event) {
        var typesStore = Tine.widgets.activities.getTypesStore();
        var data = [];


        // if is edit
        var selectedRecord = this.getSelectionModel().getSelected();
        if (selectedRecord && button.iconCls == 'action_edit') {
            var typeId = selectedRecord.get('type_id');
            var note = selectedRecord.get('note');
            var recordId = selectedRecord.id;
        }

        typesStore.each(function (record) {
            if (record.data.is_user_type == 1) {
                data.push({'type_id': record.data.id,'name': record.data.name});
            }
        }, this);

        this.filteredTypeStore =  new Ext.data.JsonStore({
            fields: [
                'type_id',
                'name'
            ],
            data: data,
            idIndex: 0
        });

        this.typeComboBox = new Ext.form.ComboBox({
            store: this.filteredTypeStore,
            valueField: 'type_id',
            mode: 'local',
            displayField: 'name',
            fieldLabel: i18n._('Type'),
            forceSelection: true,
            allowEmpty: false,
            editable: false,
            anchor: '100% 100%'
        });

        this.typeComboBox.setValue(typeId || 1);

        this.onClose = function () {
            this.window.close();
        };

        this.onCancel = function () {
            this.onClose();
        };

        this.onOk = function () {
            if (this.formPanel.getForm().findField('notification').validate()) {
                var text = this.formPanel.getForm().findField('notification').getValue();
                this.onNoteAdd(text, this.typeComboBox.getValue(), recordId);
                this.onClose();
            }
        };

        this.cancelAction = new Ext.Action({
            text: i18n._('Cancel'),
            iconCls: 'action_cancel',
            minWidth: 70,
            handler: this.onCancel,
            scope: this
        });

        this.okAction = new Ext.Action({
            text: i18n._('Ok'),
            iconCls: 'action_saveAndClose',
            minWidth: 70,
            handler: this.onOk,
            scope: this
        });

        var buttons = [];
        if (Tine.Tinebase.registry && Tine.Tinebase.registry.get('preferences') && Tine.Tinebase.registry.get('preferences').get('dialogactionsOrderStyle') === 'Windows') {
            buttons.push(this.okAction, this.cancelAction);
        }
        else {
            buttons.push(this.cancelAction, this.okAction);
        }

        this.formPanel = new Ext.FormPanel({
            labelAlign: 'top',
            border: false,
            frame: true,
            buttons: buttons,
            items: [
                this.typeComboBox,
                {
                    xtype: 'textarea',
                    name: 'notification',
                    fieldLabel: i18n._('Enter new note:'),
                    labelSeparator: '',
                    allowBlank: false,
                    value: note || '',
                    anchor: '100% 100%'
                }
            ]
        });

        this.window = Tine.WindowFactory.getWindow({
            title: i18n._('Add Note'),
            width: 500,
            height: 450,
            modal: true,
            actionAlign: 'right',
            border: false,
            items: this.formPanel
        });
    },

    /**
     * on add note
     * - add note to activities panel
     */
    onNoteAdd: function (text, typeId, recordId) {
        if (text && typeId  && recordId == undefined) {
            var newNote = new Tine.Tinebase.Model.Note({note_type_id: typeId, note: text});
            this.store.add(newNote);
        } else if (text, typeId, recordId) {
            this.store.getById(recordId).set('note_type_id', typeId);
            this.store.getById(recordId).set('note', text);
        }
    },

    /**
     * get notes data as array
     *
     * @return {Array}
     */
    getData: function () {
        var notes = [];

        this.store.each(function (record) {
            notes.push(record.data);
        });

        return notes;
    }
});
