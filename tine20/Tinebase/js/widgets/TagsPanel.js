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
                searchField.emptyText = '';
                searchField.clearValue();
            }
        },this);
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
        this.items = new Ext.DataView({
            store: this.recordTagsStore,
            tpl: tagTpl,
            autoHeight:true,
            multiSelect: true,
            overClass:'x-widget-tag-tagitem-over',
            selectedClass:'x-widget-tag-tagitem-selected',
            itemSelector:'div.x-widget-tag-tagitem',
            emptyText: 'No Tags to display'
        });
        this.items.on('contextmenu', function(dataView, selectedIdx, node, event){
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


Tine.widgets.TagsPanel = Ext.extend(Ext.Panel, {
    layout: 'anchor',
    
    /**
     * Holds public tags panel
     * @private
     */
    publicTagPanel: null,
    /**
     * Holds private tags panel
     * @private
     */
    privateTagPanel: null,
    /**
     * Holds public tag store
     * @private
     */
    publicTagStore: false,
    /**
     * holds private tag store
     * @private
     */
    privateTagStore: false,
    /**
     * @private
     */
    initComponent: function(){
        this.initTagPanels();
        this.items = [
            this.publicTagPanel,
            this.privateTagPanel
        ];
        Tine.widgets.TagsPanel.superclass.initComponent.call(this);
    },
    /**
     * @private
     */
    initTagPanels: function() {
        this.publicTagPanel = new Ext.Panel({
            title: 'Public Tags',
            layout: 'fit',
            html: '',
            bbar: [
                new Ext.form.TextField({
                    name: 'new'
                }),
                '->',
                new Ext.Button({
                    text: 'list'
                })
            ],
        });
        this.privateTagPanel = new Ext.form.FieldSet({
            title: 'Private Tags',
            checkboxToggle: true,
            layout: 'fit',
            html: ''
        });
        var publicTagEditButton = new Ext.Button({
            text: 'Edit Public Tags',
            iconCls: 'action_edit',
            handler: function() {
                var win = new Tine.widgets.tags.EditDialog({
                });
                win.show();
            }
        });
        //this.bbar.addButton(publicTagEditButton);
        
        this.privateTagPanel.on('collapse', function(){
            this.resizeTagPanels();
        }, this);
        this.privateTagPanel.on('expand', function(){
            this.resizeTagPanels();
        }, this);
        this.displayPublicTags();
    },
    /**
     * @private
     */
    initStores: function(){
        this.getPublicTagStore;
    },
    getPublicTagStore: function(){
        if (!this.publicTagStore) {
            this.publicTagStore = new Ext.data.SimpleStore({
                storeId: 'superStore',
                id: 'id',
                /*fields: [
                    { name: 'id', dataIndex: 'id' },
                    { name: 'label', dataIndex: 'label' },
                    { name: 'occurrence', dataIndex: 'occurrence' }
                ]*/
                fields: ['id', 'label', 'color', 'description', 'occurrence'],
                data: [
                    ['0', 'x-mas card', '#B06296', 'Is this contact going to receive a x-mas card?', '127' ],
                    ['1', 'follow ups', '#4C82FF', 'Need to be contacted by sales people' ,'32' ],
                    ['2', 'week 34+35', '#FFA815', 'Will be visited on calendar weeks 34/35 ', '12' ]
                ]
            });
            
        }
        return this.publicTagStore;
    },
    displayPublicTags: function() {
        /*var tpl = new Ext.XTemplate(
            '<div class="x-widget-tag-tagitem" style="background-color:{color};">',
                '<span class="x-widget-tag-tagitem-text" ext:qtip="{description}">',
                    '{label} [{occurrence}]',
                '</span>',
            '</div>'
        ).compile();*/
        /*
        var tpl = new Ext.XTemplate(
            '<div class="x-widget-tag-tagitem">',
                '<div class="x-widget-tag-bullet"><li style="color:{color};">&#160;</li></div>', 
                '<span class="x-widget-tag-tagitem-text" ext:qtip="{description}">',
                    '{label} <span class="x-widget-tag-tagitem-occurrence">[{occurrence}]</span>',
                '</span>',
            '</div>'
        ).compile();
        */
        var tpl = new Ext.XTemplate(
            '<li class="x-widget-tag-bullet" style="color:{color};">', 
                '<span class="x-widget-tag-tagitem-text" ext:qtip="{description}">{label}</span><span class="x-widget-tag-tagitem-occurrence"> [{occurrence}]</span>',
            '</li>'
        ).compile();
        var store = this.getPublicTagStore();
        var html = '<ul class="x-widget-tag-list">';
        this.publicTagStore.each(function(tag){
            html += tpl.apply(tag.data);
        }, this);
        html += '</ul>'
        this.publicTagPanel.html = html;
    },
    /**
     * @private
     */
    onRender : function(ct, position){
        Tine.widgets.TagsPanel.superclass.onRender.call(this, ct, position);
        this.resizeTagPanels();
    },
    /**
     * @private
     */
    onResize : function(w,h){
        Tine.widgets.TagsPanel.superclass.onResize.call(this, w, h);
        this.resizeTagPanels();
    },
    /**
     * @private
     */
    resizeTagPanels: function(){
        if (this.privateTagPanel.collapsed) {
            var SingleExpandedHeight = this.getSize().height - 20;
            this.publicTagPanel.setHeight(SingleExpandedHeight);
        } else {
            var areaHeights = this.getSize().height/2 - 3
            this.publicTagPanel.setHeight(areaHeights);
            this.privateTagPanel.setHeight(areaHeights);
        }
    },
    
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