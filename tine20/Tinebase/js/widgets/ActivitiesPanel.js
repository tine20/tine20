/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo add type chooser and icon
 * @todo add layout to the template / tooltip
 * @todo add filter and paging toolbars to tab panel
 * @todo translate
 */
 
Ext.namespace('Tine.widgets', 'Tine.widgets.activities');

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
     * @var {Ext.data.JsonStore}
     * Holds activities of the record this panel is displayed for
     */
    recordNotesStore: null,
    
    title: 'Activities',
    //iconCls: 'action_tag',
    layout: 'hfit',
    bodyStyle: 'padding: 2px 2px 2px 2px',
    
    /**
     * @private
     */
    initComponent: function(){
    	
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
        
        //console.log(this.recordNotesStore);
        
        var ActivitiesTpl = new Ext.XTemplate(
            '<tpl for=".">',
               '<div class="x-widget-activities-activitiesitem" id="{id}">',
                    '<div class="x-widget-activities-activitiesitem-text" ' +
                    '   ext:qtip="{[this.encode(values.note)]} <i>({values.note_type_id})</i>' +
                    '<tpl if="note != null && note.length &gt; 1"><hr>{[this.encode(values.note)]}</tpl>" >', 
                        '{[this.render(values.created_by, "user")]}&nbsp;{[this.render(values.creation_time, "time")]}<br/>' +
                        '{[this.encode(values.note)]}<hr>',
                    '</div>',
                '</div>',
            '</tpl>' ,{
                encode: function(value) {
                    return Ext.util.Format.htmlEncode(value);
                },
                render: function(value, type) {
                	switch (type) {
                        case 'user':
                            return (value) ? value : 'you';
                        case 'time':
                            return (value) ? value : 'now';                		
                	}
                }
            }
        );

        this.activities = new Ext.DataView({
            tpl: ActivitiesTpl,       
            autoHeight:true,                    
            id: 'grid_activities_limited',
            store: this.recordNotesStore,
            overClass: 'x-view-over',
            itemSelector: 'activities-item-small'
        })        
        
        var noteTextarea =  new Ext.form.TextArea({
            //emptyText: this.translation._('Add a Note...')
            emptyText: 'Add a Note...',
            grow: false,
            preventScrollbars:false,
            anchor:'100%',
            height: 55,
            hideLabel: true
        }) 
        
        noteTextarea.on('change', function(noteTextarea, newValue, oldValue){        	
        	var newNote = new Tine.Tinebase.Model.Note({note_type_id: 1, note: newValue});
        	this.recordNotesStore.insert(0, newNote);
        	
        	noteTextarea.setValue('');
            noteTextarea.emptyText = 'Add a Note...';
        },this);

        this.formFields = {
            layout: 'form',
            items: [
                noteTextarea,
                // this form field is only for fetching and saving notes in the record
                new Tine.widgets.activities.NotesFormField({
                    recordNotesStore: this.recordNotesStore
                })
            ]
        };
        
        this.items = [
            this.formFields,
            this.activities
        ];
        
        Tine.widgets.activities.ActivitiesPanel.superclass.initComponent.call(this);
    }      
});

/**
 * Class for a activities tab with notes/activities grid
 * 
 * @todo use new Tinebase.searchNotes function to get grid store
 * @todo add filter & paging
 */
Tine.widgets.activities.ActivitiesTabPanel = Ext.extend(Ext.Panel, {
    /**
     * @var {Ext.data.JsonStore}
     * Holds activities of the record this panel is displayed for
     */
    recordNotesStore: null,
    
    /**
     * the translation object
     */
    translation: null,
 
	title: 'Activities',
    layout: 'hfit',
//    bodyStyle: 'padding: 2px 2px 2px 2px',
    
    getActivitiesGrid: function() 
    {
    	// @todo add filter & paging toolbars
    	// @todo add context menu ?
    	// @todo add buttons ?
    	
    	// @todo add renderers
        // the columnmodel
        var columnModel = new Ext.grid.ColumnModel([
            { resizable: true, id: 'note_type_id', header: this.translation._('Type'), dataIndex: 'tid', width: 30 },
            { resizable: true, id: 'note', header: this.translation._('Note'), dataIndex: 'note'},
            { resizable: true, id: 'created_by', header: this.translation._('Created By'), dataIndex: 'created_by', width: 100},
            { resizable: true, id: 'creation_time', header: this.translation._('Timestamp'), dataIndex: 'creation_time', width: 70}
        ]);

        columnModel.defaultSortable = true; // by default columns are sortable
        
        // the rowselection model
        var rowSelectionModel = new Ext.grid.RowSelectionModel({multiSelect:true});
        
        // the gridpanel
        var gridPanel = new Ext.grid.GridPanel({
            id: 'Activities_Grid',
            store: this.recordNotesStore,
            cm: columnModel,
            //tbar: pagingToolbar,     
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
        // the filter toolbar
        var filterToolbar = new Tine.widgets.FilterToolbar({
            id : 'addressbookFilterToolbar',
            filterModel: [
                {label: this.translation._('Contact'), field: 'query', opdefault: 'contains'},
                {label: this.translation._('First Name'), field: 'n_given', opdefault: 'contains'},
                {label: this.translation._('Last Name'), field: 'n_family', opdefault: 'contains'},
                {label: this.translation._('Company'), field: 'org_name', opdefault: 'contains'},
                {label: this.translation._('Postal Code') + ' (' + this.translation._('Company Address') + ')', field: 'adr_one_postalcode', opdefault: 'equals'},
                {label: this.translation._('City') + '  (' + this.translation._('Company Address') + ')', field: 'adr_one_locality', opdefault: 'contains'}
                //{label: 'Full Name', field: 'n_fn', opdefault: 'equals'},
                //{label: 'Container', field: 'owner'},
             ],
             defaultFilter: 'query',
             filters: []
        });
        
        filterToolbar.on('filtertrigger', function() {
            this.store.load({});
        }, this);
        
        // the paging toolbar
        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: 50,
            store: this.store,
            displayInfo: true,
            displayMsg: this.translation._('Displaying contacts {0} - {1} of {2}'),
            emptyMsg: this.translation._("No contacts to display")
        }); 
                                
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
     * @private
     */
    initComponent: function(){
    	
    	// get translations
    	this.translation = new Locale.Gettext();
        this.translation.textdomain('Tinebase');
        
    	// get store
        this.recordNotesStore = Ext.StoreMgr.lookup('NotesStore');

        // get grid
        this.activitiesGrid = this.getActivitiesGrid();
        
        this.items = [
        
            new Ext.Panel({
                layout: 'fit',
                autoHeight:true,
                //tbar: filterToolbar,
                items: this.activitiesGrid
            })
        ];
        
        Tine.widgets.activities.ActivitiesTabPanel.superclass.initComponent.call(this);
    }        
});

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
    labelSeparator: '',
    
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
