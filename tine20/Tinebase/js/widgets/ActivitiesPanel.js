/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo add layout to the template / tooltip
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
    iconCls: 'notes_defaultIcon',
    layout: 'hfit',
    bodyStyle: 'padding: 2px 2px 2px 2px',
    collapsible: true,
    
    /**
     * @private
     */
    initComponent: function(){
    	
        // get translations
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Tinebase');
        
        // translate / update title
        this.title = this.translation._('Recent Activities');
        
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
        
        var ActivitiesTpl = new Ext.XTemplate(
            '<tpl for=".">',
               '<div class="x-widget-activities-activitiesitem" id="{id}">',
                    '<div class="x-widget-activities-activitiesitem-text"',
                    '   ext:qtip="{[this.encode(values.note)]}" >', 
                        '{[this.render(values.note_type_id, "icon")]}&nbsp;{[this.render(values.created_by, "user")]}&nbsp;{[this.render(values.creation_time, "time")]}<br/>',
                        '{[this.encode(values.note)]}<hr>',
                    '</div>',
                '</div>',
            '</tpl>' ,{
                encode: function(value) {
                    return Ext.util.Format.htmlEncode(value);
                },
                render: function(value, type) {
                	switch (type) {
                		case 'icon':
                		    return Tine.widgets.activities.getTypeIcon(value);
                        case 'user':
                            if (!value) {
                            	value = Tine.Tinebase.Registry.map.currentAccount.accountDisplayName;
                            }
                            var username = Ext.util.Format.ellipsis(value, 19);
                            return '<i>' + username + '</i>';
                        case 'time':
                            if (!value) {
                            	value = Tine.Tinebase.Registry.map.currentAccount.accountLastLogin;
                            }
                        	var dt = Date.parseDate(value, 'c'); 
                        	return dt.format(Locale.getTranslationData('Date', 'medium'));
                	}
                }
            }
        );

        this.activities = new Ext.DataView({
            tpl: ActivitiesTpl,       
            id: 'grid_activities_limited',
            store: this.recordNotesStore,
            overClass: 'x-view-over',
            itemSelector: 'activities-item-small',
            style: 'overflow:auto'
        })        
        
        var noteTextarea =  new Ext.form.TextArea({
        	id:'note_textarea',
            emptyText: this.translation._('Add a Note...'),
            grow: false,
            preventScrollbars:false,
            anchor:'100%',
            height: 55,
            hideLabel: true
        }) 

        var noteTypeCombo = new Ext.form.ComboBox({
            //emptyText: this.translation._('Note Type...'),
            hideLabel: true,
            id:'note_type_combo',
            store: Tine.widgets.activities.getTypesStore(),
            displayField:'name',
            valueField:'id',
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            editable: false,
            forceSelection: true,
            value: 1,
            width: 100
        });
              
        var bbar = [
            noteTypeCombo,
            '->',
            new Ext.Button({
                text: this.translation._('Add'),
                tooltip: this.translation._('Add new note'),
                iconCls: 'action_saveAndClose',
                handler: function(_button, _event) { 
                    var note_type_id = Ext.getCmp('note_type_combo').getValue();
                    var note = Ext.getCmp('note_textarea').getValue();
                    notesStore = Ext.StoreMgr.lookup('NotesStore');
                    
                    if (note_type_id && note) {
                        var newNote = new Tine.Tinebase.Model.Note({note_type_id: note_type_id, note: note});
                        notesStore.insert(0, newNote);
                        
                        // clear textarea
                        noteTextarea.setValue('');
                        noteTextarea.emptyText = noteTextarea.emptyText;
                    }
                }                
            })
        ];
        
        this.formFields = {
            layout: 'form',
            items: [
                noteTextarea,                
            ],
            bbar: bbar
        };
        
        this.items = [
            this.formFields,
            // this form field is only for fetching and saving notes in the record
            new Tine.widgets.activities.NotesFormField({                    
                recordNotesStore: this.recordNotesStore
            }),                 
            this.activities
        ];
        
        Tine.widgets.activities.ActivitiesPanel.superclass.initComponent.call(this);
    }      
});

/************************* tab panel *********************************/

/**
 * Class for a activities tab with notes/activities grid
 * 
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
    
	title: null,
    layout: 'hfit',
    
    getActivitiesGrid: function() 
    {
        // @todo add row expander on select ?
    	// @todo add context menu ?
    	// @todo add buttons ?
    	
    	// @todo add more renderers
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'note_type_id', header: this.translation._('Type'), dataIndex: 'note_type_id', width: 15, 
                renderer: Tine.widgets.activities.getTypeIcon },
            { resizable: true, id: 'note', header: this.translation._('Note'), dataIndex: 'note'},
            { resizable: true, id: 'created_by', header: this.translation._('Created By'), dataIndex: 'created_by', width: 70},
            { resizable: true, id: 'creation_time', header: this.translation._('Timestamp'), dataIndex: 'creation_time', width: 70}
        ]);

        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 20,
            store: this.store,
            displayInfo: true,
            displayMsg: this.translation._('Displaying notes {0} - {1} of {2}'),
            emptyMsg: this.translation._("No notes to display")
        }); 

        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Activities_Grid',
            store: this.store,
            cm: columnModel,
            tbar: pagingToolbar,     
            autoSizeColumns: false,
            selModel: rowSelectionModel,
            //enableColLock:false,
            //loadMask: true,
            autoExpandColumn: 'note',
            autoHeight:true,
            border: false,            
            view: new Ext.grid.GridView({
                autoFill: true,
                forceFit:true,
                ignoreAdd: true,
                emptyText: 'No activities to display'
            })            
            
        });
        
        return gridPanel;
    	
    	/*
        gridPanel.on('rowcontextmenu', function(_grid, _rowIndex, _eventObject) {
            _eventObject.stopEvent();
            if(!_grid.getSelectionModel().isSelected(_rowIndex)) {
                _grid.getSelectionModel().selectRow(_rowIndex);
            }
            var contextMenu = new Ext.menu.Menu({
                id:'ctxMenuContacts', 
                items: [
                    this.actions.editContact,
                    this.actions.deleteContact,
                    this.actions.exportContact,
                    '-',
                    this.actions.addContact 
                ]
            });
            contextMenu.showAt(_eventObject.getXY());
        }, this);
        
        gridPanel.on('rowdblclick', function(_gridPar, _rowIndexPar, ePar) {
            var record = _gridPar.getStore().getAt(_rowIndexPar);
            try {
                var popupWindow = new Tine.Addressbook.EditPopup({
                    contactId: record.data.id
                    //containerId:
                });                        
            } catch(e) {
                // alert(e);
            }
        }, this);

        gridPanel.on('keydown', function(e){
             if(e.getKey() == e.DELETE && Ext.getCmp('Addressbook_Contacts_Grid').getSelectionModel().getCount() > 0){
                 this.handlers.deleteContact();
             }
        }, this);

        // temporary resizeing
        filterToolbar.on('bodyresize', function(ftb, w, h) {
            var availableGridHeight = Ext.getCmp('center-panel').getSize().height;
            gridPanel.setHeight(availableGridHeight - h);
        }, this);
        
        */
    },
    
    /**
     * init the contacts json grid store
     */
    initStore: function(){

        this.store = new Ext.data.JsonStore({
            idProperty: 'id',
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
            options.params.sort  = store.getSortState() ? store.getSortState().field : this.paging.sort,
            options.params.dir   = store.getSortState() ? store.getSortState().direction : this.paging.dir,
            options.params.start = options.params.start ? options.params.start : this.paging.start,
            options.params.limit = options.params.limit ? options.params.limit : this.paging.limit
            
            options.params.paging = Ext.util.JSON.encode(options.params);
            
            var filterToolbar = Ext.getCmp('activitiesFilterToolbar');
            var filter = filterToolbar ? filterToolbar.getFilter() : [];
            filter.push(
                {field: 'record_model', operator: 'equals', value: this.record_model },
                {field: 'record_id', operator: 'equals', value: this.record_id },
                {field: 'record_backend', operator: 'equals', value: 'Sql' }
            );
                        
            options.params.filter = Ext.util.JSON.encode(filter);
        }, this);
                        
        this.store.load({});
    },

    /**
     * @private
     */
    initComponent: function(){
    	
    	// get translations
    	this.translation = new Locale.Gettext();
        this.translation.textdomain('Tinebase');
        
        // translate / update title
        this.title = this.translation._('Activities');
        
    	// get store
        this.initStore();

        // get grid
        this.activitiesGrid = this.getActivitiesGrid();
        
        // the filter toolbar
        var filterToolbar = new Tine.widgets.FilterToolbar({
            id : 'activitiesFilterToolbar',
            filterModel: [
                {label: this.translation._('Note'), field: 'query', opdefault: 'contains'},
                // user search is note working yet -> see NoteFilter.php
                //{label: this.translation._('User'), field: 'created_by', opdefault: 'contains'},
                {label: this.translation._('Time'), field: 'creation_time', opdefault: 'contains'},
                {label: this.translation._('Type'), field: 'note_type_id', opdefault: 'contains'}
             ],
             defaultFilter: 'query',
             filters: []
        });
        
        filterToolbar.on('filtertrigger', function() {
            this.store.load({});
        }, this);
                                                
        this.items = [        
            new Ext.Panel({
                layout: 'fit',
                autoHeight:true,
                tbar: filterToolbar,
                items: this.activitiesGrid
            })
        ];
        
        this.store.load({});
        
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
     * returns JSON encoded notes data of the current record
     */
    getValue: function() {
        var value = [];
        this.recordNotesStore.each(function(note){
        	value.push(note.data);
        });
        return Ext.util.JSON.encode(value);
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
                method: 'Tinebase.getNoteTypes',
            },
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            remoteSort: false
        });
        
        if (Tine.Tinebase.NoteTypes) {
            store.loadData(Tine.Tinebase.NoteTypes);
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
 */
Tine.widgets.activities.getTypeIcon = function(id) {	
    var typesStore = Tine.widgets.activities.getTypesStore();
    typeRecord = typesStore.getById(id);
    if (typeRecord) {
        return '<img src="' + typeRecord.data.icon + '" ext:qtip="' + typeRecord.data.description + '"/>';
    } else {
    	return '';
    }
};
