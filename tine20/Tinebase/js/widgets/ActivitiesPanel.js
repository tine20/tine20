/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * TODO         add to extdoc
 */
 
Ext.namespace('Tine.widgets', 'Tine.widgets.activities');

/************************* panel *********************************/

/**
 * Class for a single activities panel
 */
Tine.widgets.activities.ActivitiesPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {String} app Application which uses this panel
     */
    app: '',
    
    /**
     * @cfg
     */
    showAddNoteForm: true,
    
    /**
     * @cfg {Array} notes Initial notes
     */
    notes: [],
    
    /**
     * the translation object
     */
    translation: null,

    /**
     * @var {Ext.data.JsonStore}
     * Holds activities of the record this panel is displayed for
     */
    recordNotesStore: null,
    
    title: null,
    iconCls: 'notes_noteIcon',
    layout: 'hfit',
    bodyStyle: 'padding: 2px 2px 2px 2px',
    autoScroll: true,
    
    /**
     * event handler
     */
    handlers: {
    	
    	/**
    	 * add a new note
    	 * 
    	 */
    	addNote: function(_button, _event) { 
            //var note_type_id = Ext.getCmp('note_type_combo').getValue();
    		var note_type_id = _button.typeId;
            var noteTextarea = Ext.getCmp('note_textarea');
            var note = noteTextarea.getValue();
            
            if (note_type_id && note) {
                notesStore = Ext.StoreMgr.lookup('NotesStore');
                var newNote = new Tine.Tinebase.Model.Note({note_type_id: note_type_id, note: note});
                notesStore.insert(0, newNote);
                
                // clear textarea
                noteTextarea.setValue('');
                noteTextarea.emptyText = noteTextarea.emptyText;
            }
        }    	
    },
    
    /**
     * init activities data view
     */
    initActivitiesDataView: function()
    {
        var ActivitiesTpl = new Ext.XTemplate(
            '<tpl for=".">',
               '<div class="x-widget-activities-activitiesitem" id="{id}">',
                    '<div class="x-widget-activities-activitiesitem-text"',
                    '   ext:qtip="{[this.encode(values.note)]} - {[this.render(values.creation_time, "timefull")]} - {[this.render(values.created_by, "user")]}" >', 
                        '{[this.render(values.note_type_id, "icon")]}&nbsp;{[this.render(values.creation_time, "timefull")]}<br/>',
                        '{[this.encode(values.note, true)]}<hr color="#aaaaaa">',
                    '</div>',
                '</div>',
            '</tpl>' ,{
                encode: function(value, ellipsis) {
                    var result = Ext.util.Format.nl2br(Ext.util.Format.htmlEncode(value)); 
                    return (ellipsis) ? Ext.util.Format.ellipsis(result, 300) : result;
                },
                render: function(value, type) {
                    switch (type) {
                        case 'icon':
                            return Tine.widgets.activities.getTypeIcon(value);
                        case 'user':
                            if (!value) {
                                value = Tine.Tinebase.registry.map.currentAccount.accountDisplayName;
                            }
                            var username = value;
                            return '<i>' + username + '</i>';
                        case 'time':
                            if (!value) {
                                return '';
                            }
                            return value.format(Locale.getTranslationData('Date', 'medium'));
                        case 'timefull':
                            if (!value) {
                                return '';
                            }
                            return value.format(Locale.getTranslationData('Date', 'medium')) + ' ' +
                                value.format(Locale.getTranslationData('Time', 'medium'));
                    }
                }
            }
        );
        
        this.activities = new Ext.DataView({
            tpl: ActivitiesTpl,       
            id: 'grid_activities_limited',
            store: this.recordNotesStore,
            overClass: 'x-view-over',
            itemSelector: 'activities-item-small'
        }); 
    },
    
    /**
     * init note form
     * 
     */
    initNoteForm: function()
    {
        var noteTextarea =  new Ext.form.TextArea({
            id:'note_textarea',
            emptyText: this.translation._('Add a Note...'),
            grow: false,
            preventScrollbars:false,
            anchor:'100%',
            height: 55,
            hideLabel: true
        });
        
        var subMenu = [];
        var typesStore = Tine.widgets.activities.getTypesStore();
        var defaultTypeRecord = typesStore.getAt(typesStore.find('is_user_type', '1')); 
        
        typesStore.each(function(record){
        	if (record.data.is_user_type == 1) {
            	var action = new Ext.Action({
                    text: this.translation._('Add') + ' ' + this.translation._(record.data.name) + ' ' + this.translation._('Note'),
                    tooltip: this.translation._(record.data.description),
                    handler: this.handlers.addNote,
                    iconCls: 'notes_' + record.data.name + 'Icon',
                    typeId: record.data.id,
                    scope: this
                });            
                subMenu.push(action);
        	}
        }, this);
        
        var addButton = new Ext.SplitButton({
            text: this.translation._('Add'),
            tooltip: this.translation._('Add new note'),
            iconCls: 'action_saveAndClose',
            menu: {
                items: subMenu
            },
            handler: this.handlers.addNote,
            typeId: defaultTypeRecord.data.id
        });

        this.formFields = {
            layout: 'form',
            items: [
                noteTextarea
            ],
            bbar: [
                '->',
                addButton
            ]
        };
    },

    /**
     * @private
     */
    initComponent: function(){
    	
        // get translations
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Tinebase');
        
        // translate / update title
        this.title = this.translation._('Notes');
        
        // init recordNotesStore
        this.notes = [];
        this.recordNotesStore = new Ext.data.JsonStore({
            id: 'id',
            fields: Tine.Tinebase.Model.Note,
            data: this.notes,
            sortInfo: {
            	field: 'creation_time',
            	direction: 'DESC'
            }
        });
        
        Ext.StoreMgr.add('NotesStore', this.recordNotesStore);        
        
        // set data view with activities
        this.initActivitiesDataView();
        
        if (this.showAddNoteForm) {
            // set add new note form
            this.initNoteForm();
                
            this.items = [
                this.formFields,
                // this form field is only for fetching and saving notes in the record
                new Tine.widgets.activities.NotesFormField({                    
                    recordNotesStore: this.recordNotesStore
                }),                 
                this.activities
            ];
        } else {
            this.items = [
                new Tine.widgets.activities.NotesFormField({                    
                    recordNotesStore: this.recordNotesStore
                }),                 
                this.activities
            ];        	
        }
        
        Tine.widgets.activities.ActivitiesPanel.superclass.initComponent.call(this);
    }      
});

/************************* add note button ***************************/

/**
 * button for adding notes
 * 
 */
Tine.widgets.activities.ActivitiesAddButton = Ext.extend(Ext.SplitButton, {

    iconCls: 'notes_noteIcon',

    /**
     * event handler
     */
    handlers: {
        
        /**
         * add a new note (show prompt)
         */
        addNote: function(_button, _event) {
            Ext.Msg.prompt(
                this.translation._('Add Note'),
                this.translation._('Enter new note:'), 
                function(btn, text) {
                    if (btn == 'ok'){
                        this.handlers.onNoteAdd(text, _button.typeId);
                    }
                }, 
                this,
                40 // height of input area
            );            
        },       
        
        /**
         * on add note
         * - add note to activities panel
         */
        onNoteAdd: function(_text, _typeId) {
            if (_text && _typeId) {
                notesStore = Ext.StoreMgr.lookup('NotesStore');
                var newNote = new Tine.Tinebase.Model.Note({note_type_id: _typeId, note: _text});
                notesStore.insert(0, newNote);     
            }
        }
    },
    
    /**
     * @private
     */
    initComponent: function(){

        // get translations
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Tinebase');

        // get types for split button
        var subMenu = [];
        var typesStore = Tine.widgets.activities.getTypesStore();
        var defaultTypeRecord = typesStore.getAt(typesStore.find('is_user_type', '1')); 
        
        typesStore.each(function(record){
            if (record.data.is_user_type == 1) {
            	
                var action = new Ext.Action({
                    requiredGrant: 'editGrant',
                    text: String.format(this.translation._('Add a {0} Note'), record.data.name),
                    tooltip: this.translation._(record.data.description),
                    handler: this.handlers.addNote,
                    //iconCls: 'notes_' + record.data.name + 'Icon',
                    iconCls: record.data.icon_class,
                    typeId: record.data.id,
                    scope: this
                });            
                subMenu.push(action);
            }
        }, this);
        
        this.requiredGrant = 'editGrant';
        this.text = this.translation._('Add Note');
        this.tooltip = this.translation._('Add new note');
        this.menu = {
            items: subMenu
        };
        this.handler = this.handlers.addNote;
        this.typeId = defaultTypeRecord.data.id;
        
        Tine.widgets.activities.ActivitiesAddButton.superclass.initComponent.call(this);
    }
});
Ext.reg('widget-activitiesaddbutton', Tine.widgets.activities.ActivitiesAddButton);

/************************* tab panel *********************************/

/**
 * Class for a activities tab with notes/activities grid
 * 
 * TODO add more filters to filter toolbar
 */
Tine.widgets.activities.ActivitiesTabPanel = Ext.extend(Ext.Panel, {

    /**
     * @cfg {String} app Application which uses this panel
     */
    app: '',
    
    /**
     * @var {Ext.data.JsonStore}
     * Holds activities of the record this panel is displayed for
     */
    store: null,
    
    /**
     * the translation object
     */
    translation: null,

    /**
     * @cfg {Object} paging defaults
     */
    paging: {
        start: 0,
        limit: 20,
        sort: 'creation_time',
        dir: 'DESC'
    },

    /**
     * the record id
     */
    record_id: null,
    
    /**
     * the record model
     */
    record_model: null,
    
    /**
     * other config options
     */
	title: null,
	layout: 'fit',
    
    getActivitiesGrid: function() 
    {
        // @todo add row expander on select ?
    	// @todo add context menu ?
    	// @todo add buttons ?    	
    	// @todo add more renderers ?
    	
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'note_type_id', header: this.translation._('Type'), dataIndex: 'note_type_id', width: 15, 
                renderer: Tine.widgets.activities.getTypeIcon },
            { resizable: true, id: 'note', header: this.translation._('Note'), dataIndex: 'note'},
            { resizable: true, id: 'created_by', header: this.translation._('Created By'), dataIndex: 'created_by', width: 70},
            { resizable: true, id: 'creation_time', header: this.translation._('Timestamp'), dataIndex: 'creation_time', width: 50, 
                renderer: Tine.Tinebase.common.dateTimeRenderer }
        ]);

        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 20,
            store: this.store,
            displayInfo: true,
            displayMsg: this.translation._('Displaying history records {0} - {1} of {2}'),
            emptyMsg: this.translation._("No history to display")
        }); 

        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Activities_Grid',
            store: this.store,
            cm: columnModel,
            tbar: pagingToolbar,     
            selModel: rowSelectionModel,
            border: false,                  
            //autoExpandColumn: 'note',
            //enableColLock:false,
            //autoHeight: true,
            //loadMask: true,            
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                autoScroll: true
            })            
        });
        
        return gridPanel;    	
    },
    
    /**
     * init the contacts json grid store
     */
    initStore: function(){

        this.store = new Ext.data.JsonStore({
            id: 'id',
            autoLoad: false,
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Tinebase.Model.Note,
            remoteSort: true,
            baseParams: {
                method: 'Tinebase.searchNotes'
            },
            sortInfo: {
                field: this.paging.sort,
                direction: this.paging.dir
            }
        });
        
        // register store
        Ext.StoreMgr.add('NotesGridStore', this.store);
        
        // prepare filter
        this.store.on('beforeload', function(store, options){
            if (!options.params) {
                options.params = {};
            }
            
            // paging toolbar only works with this properties in the options!
            options.params.sort  = store.getSortState() ? store.getSortState().field : this.paging.sort;
            options.params.dir   = store.getSortState() ? store.getSortState().direction : this.paging.dir;
            options.params.start = options.params.start ? options.params.start : this.paging.start;
            options.params.limit = options.params.limit ? options.params.limit : this.paging.limit;
            
            options.params.paging = Ext.copyTo({}, options.params, 'sort,dir,start,limit');
            
            var filterToolbar = Ext.getCmp('activitiesFilterToolbar');
            var filter = filterToolbar ? filterToolbar.getValue() : [];
            filter.push(
                {field: 'record_model', operator: 'equals', value: this.record_model },
                {field: 'record_id', operator: 'equals', value: (this.record_id) ? this.record_id : 0 },
                {field: 'record_backend', operator: 'equals', value: 'Sql' }
            );
                        
            options.params.filter = filter;
        }, this);
        
        // add new notes from notes store
        this.store.on('load', function(store, operation) {
        	notesStore = Ext.StoreMgr.lookup('NotesStore');
        	notesStore.each(function(note){
        		if (!note.data.creation_time) {
                    store.insert(0, note);   
        		}
            });        	
        }, this);
                        
        //this.store.load({});
    },

    /**
     * @private
     */
    initComponent: function(){
    	
    	// get translations
    	this.translation = new Locale.Gettext();
        this.translation.textdomain('Tinebase');
        
        // translate / update title
        this.title = this.translation._('History');
        
    	// get store
        this.initStore();

        // get grid
        this.activitiesGrid = this.getActivitiesGrid();
        
        // the filter toolbar
        var filterToolbar = new Tine.widgets.grid.FilterToolbar({
            id : 'activitiesFilterToolbar',
            filterModels: [
                {label: _('Quick search'), field: 'query',         operators: ['contains']},
                //{label: this.translation._('Time'), field: 'creation_time', operators: ['contains']}
                {label: this.translation._('Time'), field: 'creation_time', valueType: 'date', pastOnly: true}
                // user search is note working yet -> see NoteFilter.php
                //{label: this.translation._('User'), field: 'created_by', defaultOperator: 'contains'},
                // type search isn't implemented yet
                //{label: this.translation._('Type'), field: 'note_type_id', defaultOperator: 'contains'}
             ],
             defaultFilter: 'query',
             filters: []
        });
        
        filterToolbar.on('change', function() {
            this.store.load({});
        }, this);
                                                
        this.items = [        
            new Ext.Panel({
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'panel',
                    layout: 'fit',
                    border: false,
                    items: this.activitiesGrid
                }, {
                    region: 'north',
                    border: false,
                    items: filterToolbar,
                    listeners: {
                        scope: this,
                        afterlayout: function(ct) {
                            ct.setHeight(filterToolbar.getHeight());
                            ct.ownerCt.layout.layout();
                        }
                    }
                }]
            })
        ];
                
        // load store on activate
        this.on('activate', function(panel){
            panel.store.load({});
        });
        
        Tine.widgets.activities.ActivitiesTabPanel.superclass.initComponent.call(this);
    }
});

/************************* helper *********************************/

/**
 * @private Helper class to have activities processing in the standard form/record cycle
 */
Tine.widgets.activities.NotesFormField = Ext.extend(Ext.form.Field, {
    /**
     * @cfg {Ext.data.JsonStore} recordNotesStore a store where the record notes are in.
     */
    recordNotesStore: null,
    
    name: 'notes',
    hidden: true,
    hideLabel: true,
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.activities.NotesFormField.superclass.initComponent.call(this);
        this.hide();
    },
    /**
     * returns notes data of the current record
     */
    getValue: function() {
        var value = [];
        this.recordNotesStore.each(function(note){
        	value.push(note.data);
        });
        return value;
    },
    /**
     * sets notes from an array of note data objects (not records)
     */
    setValue: function(value){
        this.recordNotesStore.loadData(value);
    }

});

/**
 * get note / activities types store
 * if available, load data from initial data
 * 
 * @return Ext.data.JsonStore with activities types
 * 
 * @todo translate type names / descriptions
 */
Tine.widgets.activities.getTypesStore = function() {
    var store = Ext.StoreMgr.get('noteTypesStore');
    if (!store) {
        store = new Ext.data.JsonStore({
            fields: Tine.Tinebase.Model.NoteType,
            baseParams: {
                method: 'Tinebase.getNoteTypes'
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        /*if (Tine.Tinebase.registry.get('NoteTypes')) {
            store.loadData(Tine.Tinebase.registry.get('NoteTypes'));
        } else*/ if (Tine.Tinebase.registry.get('NoteTypes')) {
            store.loadData(Tine.Tinebase.registry.get('NoteTypes'));
        }
        Ext.StoreMgr.add('noteTypesStore', store);
    }
    
    return store;
};

/**
 * get type icon
 * 
 * @param   id of the note type record
 * @returns img tag with icon source
 * 
 * @todo use icon_class here
 */
Tine.widgets.activities.getTypeIcon = function(id) {	
    var typesStore = Tine.widgets.activities.getTypesStore();
    var typeRecord = typesStore.getById(id);
    if (typeRecord) {
        return '<img src="' + typeRecord.data.icon + '" ext:qtip="' + typeRecord.data.description + '"/>';
    } else {
    	return '';
    }
};
