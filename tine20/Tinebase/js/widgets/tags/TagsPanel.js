/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO make initial color work again in Ext.menu.ColorMenu
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.tags');

/**
 * Class for a single tag panel
 * 
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagPanel
 * @extends     Ext.Panel
 */
Tine.widgets.tags.TagPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Tine.Tinebase.Application} app Application which uses this panel
     */
    app: null,
    /**
     * @cfg {String} recordId Id of record this panel is displayed for
     */
    recordId: '',
    /**
     * @cfg {Array} tags Initial tags
     */
    tags: null,
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
    
    iconCls: 'action_tag',
    layout: 'fit',
    bodyStyle: 'padding: 2px 2px 2px 2px',
    collapsible: true,
    border: false,
    
    /**
     * @private
     */
    initComponent: function(){
        this.title =  _('Tags');
        this.app = Ext.isString(this.app) ? Tine.Tinebase.appMgr.get(this.app) : this.app;
        
        // init recordTagsStore
        this.tags = [];
        this.recordTagsStore = new Ext.data.JsonStore({
            id: 'id',
            fields: Tine.Tinebase.Model.Tag,
            data: this.tags
        });
        
        // init availableTagsStore
        this.availableTagsStore = new Ext.data.JsonStore({
            id: 'id',
            root: 'results',
            totalProperty: 'totalCount',
            fields: Tine.Tinebase.Model.Tag,
            baseParams: {
                method: 'Tinebase.searchTags',
                filter: {
                    application: this.app.appName,
                    grant: 'use'
                },
                paging : {}
            }
        });
        
        this.searchField = new Tine.widgets.tags.TagCombo({
            app: this.app,
            onlyUsableTags: true,
            disableClearer: true
        });
        this.searchField.on('select', function(searchField, selectedTag){
            if(this.recordTagsStore.getById(selectedTag.id) === undefined) {
                this.recordTagsStore.add(selectedTag);
            }
            searchField.blur();
            searchField.reset();
        },this);
        
        this.bottomBar = new Ext.Container({
            layout: 'column',
            items: [
                Ext.apply(this.searchField, {columnWidth: .99}),
                new Ext.Button({
                    text: '',
                    width: 16,
                    iconCls: 'action_add',
                    tooltip: _('Add a new personal tag'),
                    scope: this,
                    handler: function() {
                        Ext.Msg.prompt(_('Add New Personal Tag'),
                                       _('Please note: You create a personal tag. Only you can see it!') + ' <br />' + _('Enter tag name:'), 
                            function(btn, text) {
                                if (btn == 'ok'){
                                    this.onTagAdd(text);
                                }
                            }, 
                        this, false, this.searchField.lastQuery);
                    }
                })
            ]
        
        });
        
        var tagTpl = new Ext.XTemplate(
            '<tpl for=".">',
               '<div class="x-widget-tag-tagitem" id="{id}">',
                    '<div class="x-widget-tag-tagitem-color" style="background-color: {color};">&#160;</div>', 
                    '<div class="x-widget-tag-tagitem-text" ext:qtip="', 
                        '{[this.encode(values.name)]}', 
                        '<tpl if="type == \'personal\' ">&nbsp;<i>(' + _('personal') + ')</i></tpl>',
                        '</i>&nbsp;[{occurrence}]',
                        '<tpl if="description != null && description.length &gt; 1"><hr>{[this.encode(values.description)]}</tpl>" >',
                        
                        '&nbsp;{[this.encode(values.name)]}',
                    '</div>',
                '</div>',
            '</tpl>' ,{
                encode: function(value) {
                    return Tine.Tinebase.common.doubleEncode(value);
                }
            }
        );
        
        this.dataView = new Ext.DataView({
            store: this.recordTagsStore,
            tpl: tagTpl,
            autoHeight:true,
            multiSelect: true,
            overClass:'x-widget-tag-tagitem-over',
            selectedClass:'x-widget-tag-tagitem-selected',
            itemSelector:'div.x-widget-tag-tagitem',
            emptyText: _('No Tags to display')
        });
        this.dataView.on('contextmenu', function(dataView, selectedIdx, node, event){
            if (!this.dataView.isSelected(selectedIdx)) {
                this.dataView.clearSelections();
                this.dataView.select(selectedIdx);
            }
            event.preventDefault();
            
            var selectedTags = this.dataView.getSelectedRecords();
            var selectedTag = selectedTags.length == 1 ? selectedTags[0] : null;
            
            var allowDelete = true;
            for (var i=0; i<selectedTags.length; i++) {
                if (selectedTags[i].get('type') == 'shared') {
                    allowDelete = false;
                }
            }
            
            var menu = new Ext.menu.Menu({
                items: [
                    new Ext.Action({
                        scope: this,
                        text: Tine.Tinebase.translation.ngettext('Detach tag', 'Detach tags', selectedTags.length),
                        iconCls: 'x-widget-tag-action-detach',
                        handler: function() {
                            for (var i=0,j=selectedTags.length; i<j; i++){
                                this.recordTagsStore.remove(selectedTags[i]);
                            }
                        }
                    }),
                    '-',
                    {
                        text: _('Edit tag'),
                        disabled: !(selectedTag && allowDelete),
                        menu: {
                            items: [
                                new Ext.Action({
                                    text: _('Rename Tag'),
                                    selectedTag: selectedTag,
                                    scope: this,
                                    handler: function(action) {
                                        var tag = action.selectedTag;
                                        Ext.Msg.prompt(_('Rename Tag') + ' "'+ tag.get('name') +'"', _('Please enter a new name:'), function(btn, text){
                                            if (btn == 'ok'){
                                                tag.set('name', text);
                                                this.onTagUpdate(tag);
                                            }
                                        }, this, false, tag.get('name'));
                                    }
                                }),
                                new Ext.Action({
                                    text: _('Edit Description'),
                                    selectedTag: selectedTag,
                                    scope: this,
                                    handler: function(action) {
                                        var tag = action.selectedTag;
                                        Ext.Msg.prompt(_('Description for tag') + ' "'+ tag.get('name') +'"', _('Please enter new description:'), function(btn, text){
                                            if (btn == 'ok'){
                                                tag.set('description', text);
                                                this.onTagUpdate(tag);
                                            }
                                        }, this, 30, tag.get('description'));
                                    }
                                }),
                                new Ext.Action({
                                    text: _('Change Color'),
                                    iconCls: 'action_changecolor',
                                    scope: this,
                                    menu: new Ext.menu.ColorMenu({
                                        // not working any longer ->
                                        //value: selectedTag ? selectedTag.get('color') : '#FFFFFF',
                                        // something like this should work -> 
                                        // (from extjs api doc: (value) The initial color to highlight (should be a valid 6-digit color hex code without the # symbol). Note that the hex codes are case-sensitive.)
                                        //value: selectedTag ? Ext.util.Format.lowercase(selectedTag.get('color').substr(1)) : 'ffffff',
                                        scope: this,
                                        listeners: {
                                            select: function(menu, color) {
                                                color = '#' + color;
                                                
                                                if (selectedTag.get('color') != color) {
                                                    selectedTag.set('color', color);
                                                    this.onTagUpdate(selectedTag);
                                                }
                                            },
                                            scope: this
                                        }
                                    })                                        
                                })                                    
                            ]
                        }
                    },
                    new Ext.Action({
                        disabled: !allowDelete,
                        scope: this,
                        text: Tine.Tinebase.translation.ngettext('Delete Tag', 'Delete Tags', selectedTags.length),
                        iconCls: 'action_delete',
                        handler: function() {
                            var tagsToDelete = [];
                            for (var i=0,j=selectedTags.length; i<j; i++){
                                // don't request to delete non existing tags
                                if (selectedTags[i].id.length > 20) {
                                    tagsToDelete.push(selectedTags[i].id);
                                }
                            }
                            
                            // @todo use correct strings: Realy -> Really / disapear -> disappear
                            Ext.MessageBox.confirm(
                                Tine.Tinebase.translation.ngettext('Realy Delete Selected Tag?', 'Realy Delete Selected Tags?', selectedTags.length), 
                                Tine.Tinebase.translation.ngettext('the selected tag will be deleted and disapear for all entries', 
                                                        'The selected tags will be removed and disapear for all entries', selectedTags.length), 
                                function(btn) {
                                    if (btn == 'yes'){
                                        Ext.MessageBox.wait(_('Please wait a moment...'), Tine.Tinebase.translation.ngettext('Deleting Tag', 'Deleting Tags', selectedTags.length));
                                        Ext.Ajax.request({
                                            params: {
                                                method: 'Tinebase.deleteTags', 
                                                ids: tagsToDelete
                                            },
                                            success: function(_result, _request) {
                                                // reset avail tag store
                                                this.availableTagsStore.lastOptions = null;
                                                
                                                for (var i=0,j=selectedTags.length; i<j; i++){
                                                    this.recordTagsStore.remove(selectedTags[i]);
                                                }
                                                Ext.MessageBox.hide();
                                            },
                                            failure: function ( result, request) {
                                                Ext.MessageBox.alert(_('Failed'), _('Could not delete Tag(s).'));
                                            },
                                            scope: this 
                                        });
                                    }
                            }, this);
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
        
        this.items = [{
            xtype: 'panel',
            layout: 'fit',
            bbar: this.bottomBar,
            items: [
                this.dataView,
                this.formField
            ]
        }];
        
        Tine.widgets.dialog.MultipleEditDialogPlugin.prototype.registerSkipItem(this);
        Tine.widgets.tags.TagPanel.superclass.initComponent.call(this);
    },
    
    getFormField: function() {
        return this.formField.items;
    },
    
    /**
     * @private
     */
    onTagAdd: function(tagName) {
        if (tagName.length < 3) {
            Ext.Msg.show({
               title: _('Notice'),
               msg: _('The minimum tag length is three.'),
               buttons: Ext.Msg.OK,
               animEl: 'elId',
               icon: Ext.MessageBox.INFO
            });
        } else {
            var isAttached = false;
            this.recordTagsStore.each(function(tag){
                if(tag.data.name == tagName) {
                    isAttached = true;
                }
            },this);
            
            if (!isAttached) {
                var tagToAttach = false;
                this.availableTagsStore.each(function(tag){
                    if(tag.data.name == tagName) {
                        tagToAttach = tag;
                    }
                }, this);
                
                if (!tagToAttach) {
                    tagToAttach = new Tine.Tinebase.Model.Tag({
                        name: tagName,
                        type: 'personal',
                        description: '',
                        color: '#FFFFFF'
                    });
                    
                    if (! Ext.isIE) {
                        this.el.mask();
                    }
                    Ext.Ajax.request({
                        params: {
                            method: 'Tinebase.saveTag', 
                            tag: tagToAttach.data
                        },
                        success: function(_result, _request) {
                            var tagData = Ext.util.JSON.decode(_result.responseText);
                            var newTag = new Tine.Tinebase.Model.Tag(tagData, tagData.id);
                            this.recordTagsStore.add(newTag);
                            
                            // reset avail tag store
                            this.availableTagsStore.lastOptions = null;
                            this.el.unmask();
                        },
                        failure: function ( result, request) {
                            Ext.MessageBox.alert(_('Failed'), _('Could not create tag.'));
                            this.el.unmask();
                        },
                        scope: this 
                    });
                } else {
                    this.recordTagsStore.add(tagToAttach);
                }
            }
        }
    },
    onTagUpdate: function(tag) {
        if (tag.get('name').length < 3) {
            Ext.Msg.show({
               title: _('Notice'),
               msg: _('The minimum tag length is three.'),
               buttons: Ext.Msg.OK,
               animEl: 'elId',
               icon: Ext.MessageBox.INFO
            });
        } else {
            this.el.mask();
            Ext.Ajax.request({
                params: {
                    method: 'Tinebase.saveTag', 
                    tag: tag.data
                },
                success: function(_result, _request) {
                    // reset avail tag store
                    this.availableTagsStore.lastOptions = null;
                    this.el.unmask();
                },
                failure: function ( result, request) {
                    Ext.MessageBox.alert(_('Failed'), _('Could not update tag.'));
                    this.el.unmask();
                },
                scope: this 
            });
        }
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
     * returns tags data of the current record
     */
    getValue: function() {
        var value = [];
        this.recordTagsStore.each(function(tag){
            if(tag.id.length > 5 && ! String(tag.id).match(/ext-record/)) {
                //if we have a valid id we just return the id
                value.push(tag.id);
            } else {
                //it's a new tag and will be saved on the fly
                value.push(tag.data);
            }
        });
        return value;
    },
    /**
     * sets tags from an array of tag data objects (not records)
     */
    setValue: function(value){
        // replace template fields
        Tine.Tinebase.Model.Tag.replaceTemplateField(value);
        
        this.recordTagsStore.loadData(value);
    }

});

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
                    fieldLabel: _('Description')
                },
                {
                    name: 'color',
                    fieldLabel: _('Color')
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
