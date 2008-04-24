/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.widgets', 'Tine.widgets.tags');

/**
 * Class for a single tag panel
 */
Tine.widgets.tags.TagPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {String} app Application which uses this panel
     */
    app: '',
    /**
     * @cfg {String} recordId Id of record this panel is displayed for
     */
    recordId: '',
    /**
     * @cfg {Array} tags Initial tags
     */
    tags: [],
    /**
     * @cfg {Bool} findGlobalTags true to find global tags during search (default: false)
     */
    findGlobalTags: false,
    /**
     * @var {Ext.data.JsonStore}
     * Holds tags of the record this panel is displayed for
     */
    recordTagsStore: null,
    /**
     * @var {Ext.data.JsonStore} Store for available tags
     */
    availableTagsStore: false,
    /**
     * @var {Ext.form.ComboBox} live search field to search tags to add
     */
    searchField: null,
    
    title: 'Tags',
    layout: 'hfit',
    bodyStyle: 'padding: 2px 2px 2px 2px',
    
    /**
     * @private
     */
    initComponent: function(){
        // init recordTagsStore
        this.tags = [];
        this.recordTagsStore = new Ext.data.JsonStore({
            id: 'id',
            fields: Tine.widgets.tags.Tag,
            data: this.tags
        });
        
        // init availableTagsStore
        this.availableTagsStore = new Ext.data.JsonStore({
            id: 'id',
            root: 'results',
            totalProperty: 'totalCount',
            fields: Tine.widgets.tags.Tag,
            baseParams: {
                method: 'Tinebase.getTags',
                context: this.app,
                owner: Tine.Tinebase.Registry.get('currentAccount').accountId,
                findGlobalTags: this.findGlobalTags,
            }
        });
        
        this.initSearchField();
        
        this.bbar = [
            this.searchField, '->',
            new Ext.Button({
                text: 'List'
            })
        ];
        
        var tagTpl = new Ext.XTemplate(
            '<tpl for=".">',
               '<div class="x-widget-tag-tagitem" id="{id}">',
                    '<div class="x-widget-tag-tagitem-color" style="background-color: {color};">&#160;</div>', 
                    '<div class="x-widget-tag-tagitem-text" ext:qtitle="{type}-tag: {name}" ext:qtip="{description}" >', 
                        '{name}',
                    '</div>',
                '</div>',
            '</tpl>'
        );
        
        this.dataView = new Ext.DataView({
            store: this.recordTagsStore,
            tpl: tagTpl,
            autoHeight:true,
            multiSelect: true,
            overClass:'x-widget-tag-tagitem-over',
            selectedClass:'x-widget-tag-tagitem-selected',
            itemSelector:'div.x-widget-tag-tagitem',
            emptyText: 'No Tags to display'
        });
        this.dataView.on('contextmenu', function(dataView, selectedIdx, node, event){
            if (!this.dataView.isSelected(selectedIdx)) {
                this.dataView.clearSelections();
                this.dataView.select(selectedIdx);
            }
            event.preventDefault();
            
            var selectedTags = this.dataView.getSelectedRecords();
            var tagString = 'Tag' + (selectedTags.length>1 ? 's' : '');
            
            var menu = new Ext.menu.Menu({
                items: [
                    new Ext.Action({
                        scope: this,
                        text: 'Detach ' + tagString,
                        iconCls: 'x-widget-tag-action-detach',
                        handler: function() {
                            for (var i=0,j=selectedTags.length; i<j; i++){
                                this.recordTagsStore.remove(selectedTags[i]);
                            }
                        }
                    }),
                    '-',
                    {
                        text: 'Edit ' + tagString,
                        disabled: true,
                        menu: {
                            items: [
                                new Ext.Action({
                                    scope: this,
                                    disabled: selectedTags.length>1,
                                    text: 'Rename',
                                    //iconCls: 'action_edit',
                                    /*
                                    handler: function() {
                                        var dlg = new Tine.widgets.tags.TagEditDialog();
                                        dlg.show();
                                    }
                                    */
                                }),
                                new Ext.Action({
                                    text: 'Edit Description',
                                    disabled: selectedTags.length>1,
                                
                                }),
                                {
                                    text: 'Change Color',
                                    disabled: selectedTags.length>1,
                                    menu: new Ext.menu.ColorMenu({
                                        
                                    })
                                }
                                    
                            ]
                        }
                    },
                    new Ext.Action({
                        hidden: true,
                        scope: this,
                        text: 'Delete ' + tagString,
                        iconCls: 'action_delete',
                        handler: function() {
                            var tagsToDelete = [];
                            for (var i=0,j=selectedTags.length; i<j; i++){
                                // don't request to delete non existing tags
                                if (selectedTags[i].id.length > 20) {
                                    tagsToDelete.push(selectedTags[i].id);
                                }
                            }
                            Ext.MessageBox.wait('Please wait a moment...', 'Deleting '+ tagString);
                            Ext.Ajax.request({
                                params: {
                                    method: 'Tinebase.deleteTags', 
                                    ids: Ext.util.JSON.encode(tagsToDelete)
                                },
                                success: function(_result, _request) {
                                    for (var i=0,j=selectedTags.length; i<j; i++){
                                        this.recordTagsStore.remove(selectedTags[i]);
                                    }
                                    Ext.MessageBox.hide();
                                },
                                failure: function ( result, request) { 
                                    Ext.MessageBox.alert('Failed', 'Could not delete Tag(s).'); 
                                },
                                scope: this 
                            });
                            
                        }
                    })
                ]
            });
            menu.showAt(event.getXY());
        },this);
        
        this.formField = {
            layout: 'form',
            items: new Tine.widgets.tags.TagFormField({
                recordTagsStore: this.recordTagsStore
            })
        };
        
        this.items = [
            this.dataView,
            this.formField
        ]
        Tine.widgets.tags.TagPanel.superclass.initComponent.call(this);
    },
    /**
     * @private
     */
    onResize : function(w,h){
        Tine.widgets.tags.TagPanel.superclass.onResize.call(this, w, h);
        // maximize search field and let space for list button
        this.searchField.setWidth(w-37);
    },
    /**
     * @private
     */
    initSearchField: function() {
        this.searchField = new Ext.form.ComboBox({
            store: this.availableTagsStore,
            mode: 'local',
            displayField:'name',
            typeAhead: true,
            emptyText: 'Enter tag name',
            loadingText: 'Searching...',
            typeAheadDelay: 10,
            minChars: 1,
            hideTrigger:true,
            expand: function(){}
        });
        
        this.searchField.on('focus', function(searchField){
            searchField.hasFocus = false;
            // hack to supress selecting the first item from the freshly
            // retrieved store
            this.availableTagsStore.load({
                scope: this,
                callback: function() {
                    searchField.hasFocus = true;
                }
            });
        }, this);
        
        this.searchField.on('select', function(searchField, selectedTag){
            if(this.recordTagsStore.getById(selectedTag.id) == undefined) {
                this.recordTagsStore.add(selectedTag);
            }
            searchField.emptyText = '';
            searchField.clearValue();
        },this);
        
        this.searchField.on('specialkey', function(searchField, e){
            //only accept ENTER when search had no result!
             if(e.getKey() == e.ENTER){
                var value = searchField.getValue();
                if (value.length < 3) {
                    Ext.Msg.show({
                       title:'Notice',
                       msg: 'The minimum tag length is three.',
                       buttons: Ext.Msg.OK,
                       animEl: 'elId',
                       icon: Ext.MessageBox.INFO
                    });
                } else {
                    var nameExists = false;
                    this.recordTagsStore.each(function(tag){
                        if(tag.data.name == value) {
                            nameExists = true;
                        }
                    },this);
                    
                    if (!nameExists) {
                        var newTag = new Tine.widgets.tags.Tag({
                            name: value
                        });
                        this.recordTagsStore.add(newTag);
                    }
                }
                searchField.emptyText = '';
                searchField.clearValue();
             }
        }, this);
        
        // workaround extjs bug:
        this.searchField.on('blur', function(searchField){
            searchField.emptyText = 'Enter tag name';
            searchField.clearValue();
        },this);
    }
});

/**
 * @private Helper class to have tags processing in the standard form/record cycle
 */
Tine.widgets.tags.TagFormField = Ext.extend(Ext.form.Field, {
    /**
     * @cfg {Ext.data.JsonStore} recordTagsStore a store where the record tags are in.
     */
    recordTagsStore: null,
    
    name: 'tags',
    hidden: true,
    labelSeparator: '',
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.tags.TagFormField.superclass.initComponent.call(this);
        //this.hide();
    },
    /**
     * returns JSON encoded tags data of the current record
     */
    getValue: function() {
        var value = [];
        this.recordTagsStore.each(function(tag){
            if(tag.id.length > 5) {
                //if we have a valid id we just return the id
                value.push(tag.id);
            } else {
                //it's a new tag and will be saved on the fly
                value.push(tag.data);
            }
        });
        return Ext.util.JSON.encode(value);
    },
    /**
     * sets tags from an array of tag data objects (not records)
     */
    setValue: function(value){
        this.recordTagsStore.loadData(value);
    },

});

/**
 * Tine.widgets.tags.Tag
 * 
 * @constructor {Ext.data.Record}
 * Record definition of a tag
 */
Tine.widgets.tags.Tag = Ext.data.Record.create([
    {name: 'id'         },
    {name: 'app'        },
    {name: 'owner'      },
    {name: 'name'       },
    {name: 'type'       },
    {name: 'description'},
    {name: 'color'      },
    {name: 'occurrence' },
]);

/**
 * Dialog for editing a tag itself
 */
Tine.widgets.tags.TagEditDialog = Ext.extend(Ext.Window, {
    width: 200,
    height: 300,
    layout: 'fit',
    margins: '0px 5px 0px 5px',
    
    initComponent: function() {
        this.items = new Ext.form.FormPanel({
            defaults: {
                xtype: 'textfield',
                anchor: '100%'
            },
            labelAlign: 'top',
            items: [
                {
                    name: 'name',
                    fieldLabel: 'Name'
                },
                {
                    name: 'description',
                    fieldLabel: 'Description'
                },
                {
                    name: 'color',
                    fieldLabel: 'Color'
                }
            ]
            
        });
        Tine.widgets.tags.TagEditDialog.superclass.initComponent.call(this);
    }
});

Tine.widgets.tags.EditDialog = Ext.extend(Ext.Window, {
    layout:'border',
    width: 640,
    heigh: 480,
    
    initComponent: function() {
        this.items = [
        {
            region: 'west',
            split: true
        },
        {
            region: 'center',
            split: true
        }
        ];
        Tine.widgets.tags.EditDialog.superclass.call(this);
    }
});