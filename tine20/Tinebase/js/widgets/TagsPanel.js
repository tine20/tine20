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
     * @var {Ext.data.JsonStore} Store for searchd tags
     */
    searchTagsStore: null,
    /**
     * @var {Ext.form.ComboBox} live search field to search tags to add
     */
    searchField: null,
    
    title: 'Tags',
    layout: 'hfit',
    
    /**
     * @private
     */
    initComponent: function(){
        // init recordTagsStore
        this.tags = [
        {id: 'fsgsfdgsf', owner: '1', name: 'x-mas card', description: 'Is this contact going to receive a x-mas card?', color: '#B06296', occurrence: '127' },
        {id: 'fsgsfgsff', owner: '1', name: 'follow ups', description: 'Need to be contacted by sales people', color: '#4C82FF' , occurrence: '32' },
        {id: 'vxbfsgfgf', owner: '1', name: 'week 34+35', description: 'Will be visited on calendar weeks 34/35 ', color: '#FFA815', occurrence: '12' }
        ];
        this.recordTagsStore = new Ext.data.JsonStore({
            id: 'id',
            fields: Tine.widgets.tags.Tag,
            data: this.tags
        });
        
        // init searchTagsStore
        this.searchTagsStore = new Ext.data.JsonStore({
            id: 'id',
            root: 'results',
            totalProperty: 'totalCount',
            fields: Tine.widgets.tags.Tag,
            baseParams: {
                method: 'Tinebase.searchTags',
                context: this.app,
                owner: Tine.Tinebase.Registry.get('currentAccount').accountId,
                findGlobalTags: this.findGlobalTags,
            }
        });
        
        // init searchFild
        var resultTpl = new Ext.XTemplate(
            '<tpl for="."><div class="search-item">',
                '<em class="x-widget-tag-bullet" style="color:{color};">&#8226;</em><b class="x-widget-tag-tagitem-text" style="font-size: 11px;">{name}</b><br/>',
                '<i style="font-size: 10px; color: #B5B8C8;">{description}</i>',
            '</div></tpl>'
        );
        this.searchField = new Ext.form.ComboBox({
            store: this.searchTagsStore,
            displayField:'name',
            typeAhead: false,
            emptyText: 'Enter tag name',
            loadingText: 'Searching...',
            listWidth: 300,
            maxHeight: 300,
            queryDelay: 200,
            minChars: 2,
            pageSize:10,
            hideTrigger:true,
            tpl: resultTpl,
            itemSelector: 'div.search-item',
        });
        this.searchField.on('select', function(searchField, selectedTag){
            if(this.recordTagsStore.getById(selectedTag.id) == undefined) {
                this.recordTagsStore.add(selectedTag);
            }
            searchField.emptyText = '';
            searchField.clearValue();
        },this);
        this.searchField.on('specialkey', function(searchField, e){
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
                    var newTag = new Tine.widgets.tags.Tag({
                        name: value
                    });
                    this.recordTagsStore.add(newTag);
                }
                 
             }
        }, this);
        // workaround extjs bug:
        this.searchField.on('blur', function(searchField){
            searchField.emptyText = 'Enter tag name';
            searchField.clearValue();
        },this);
        
        this.bbar = [
            this.searchField, '->',
            new Ext.Button({
                text: 'List'
            })
        ];
        
        var tagTpl = new Ext.XTemplate(
            '<tpl for=".">',
               '<div class="x-widget-tag-tagitem" id="{id}">',
                    '<div class="x-widget-tag-bullet" style="color:{color};">&#8226;</div>', 
                    '<span class="x-widget-tag-tagitem-text" ext:qtip="{description}">',
                        '{name} <span class="x-widget-tag-tagitem-occurrence">[{occurrence}]</span>',
                    '</span>',
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
            event.preventDefault();
            var menu = new Ext.menu.Menu({
                items: [
                    new Ext.Action({
                        scope: this,
                        text: 'detach tag',
                        iconCls: 'action_delete',
                        handler: function() {
                            //this.recordTagsStore.remove();
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
     * Returns 
     */
    getValue: function() {
        var value = [];
        this.recordTagsStore.each(function(tag){
            if(tag.id.length > 5) {
                //if we have a valid id we just return the id
                value.push(tag.id);
            } else {
                //it's a new tag and will be saved on the fly
                value.push(Ext.util.JSON.encode(tag.data));
            }
        });
        return (value.join(','));
    }
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
    {name: 'description'},
    {name: 'color'      },
    {name: 'occurrence' },
]);

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