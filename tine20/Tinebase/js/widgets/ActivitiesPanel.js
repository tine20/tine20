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
 * @todo resolve creator
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
            /*
            items: new Tine.widgets.tags.TagFormField({
                recordTagsStore: this.recordTagsStore
            })
            */
        };
        
        this.items = [
            this.formFields,
            this.activities
            //this._getNotesGrid(this.recordNotesStore)
        ];
        
        Tine.widgets.activities.ActivitiesPanel.superclass.initComponent.call(this);
    }      
});

/**
 * Class for a activities tab with notes/activities grid
 */
Tine.widgets.activities.ActivitiesTabPanel = Ext.extend(Ext.Panel, {
    /**
     * @var {Ext.data.JsonStore}
     * Holds activities of the record this panel is displayed for
     */
    recordNotesStore: null,
 
	title: 'Activities',
    layout: 'hfit',
//    bodyStyle: 'padding: 2px 2px 2px 2px',
    
    /**
     * @private
     */
    initComponent: function(){
        
        // init recordNotesStore (get store from store manager)
    	/*
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
        */
        
        this.recordNotesStore = Ext.StoreMgr.lookup('NotesStore');
        
        //console.log(this.recordNotesStore);

        //-- init grid
        this.activitiesGrid = new Ext.Panel({});
        
        this.items = [
            this.activitiesGrid
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
