/**
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.SimpleFAQ');

/**
 * the grid details panel
 */

Tine.SimpleFAQ.FaqGridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {

    border: false,

    /**
     * inits this component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('SimpleFAQ');

        Tine.SimpleFAQ.FaqGridDetailsPanel.superclass.initComponent.call(this);
        //this.supr().initComponent.call(this);
    },

    /**
     * returns rendered tags with tag-text for grids
     *
     * @param {mixed} tags
     * @return {String} tags as colored text with qtips
     * 
     */
    tagsRenderer: function(tags) {
        var result = '';
        if (tags) {
            for (var i = 0; i < tags.length; i += 1) {
                result += '<div style="float:left; margin:3px; font: 11px arial,tahoma,helvetica,sans-serif;"><div class="tb-grid-tags" style="background-color:' + tags[i].color + ';">&#160;</div> '+ Ext.util.Format.htmlEncode(tags[i].name) +'</div>';
            }
        }
        return result;
    },
    
    htmlRenderer: function(text) {
        var result = '';
        if(text) {
            result +='<div style="float:left; margin:3px; font: 11px arial,tahoma,helvetica,sans-serif;">' + text + '</div>'
        }
        return result;
    },

     /**
     * faq status renderer
     *
     * @param   {Number} id
     * @param   {Store} store
     * @return  {String} label
     */
    dataRenderer: function(id, store, definitionsLabel) {
        record = store.getById(id);
        if (record) {
            return record.data[definitionsLabel];
        } else {
            return 'undefined';
        }
    },


    /**
     * default panel
     *
     * @return {Ext.ux.display.DisplayPanel}
     */
    getDefaultInfosPanel: function() {
        if(!this.defaultInfosPanel){
                this.defaultInfosPanel = new Ext.ux.display.DisplayPanel({
                    layout: 'fit',
                    border: false,
                        items:[{
                            layout: 'hbox',
                            border: false,
                            layoutConfig: {
                                padding: 5,
                                align:'stretch'
                            },
                            defaults:{
                                margins:'0 5 0 0'
                            },
                            items: [{
                                layout: 'ux.display',
                                width: 240,
                                layoutConfig: {
                                    background: 'solid',
                                    declaration: this.app.i18n._('FAQ Infos'),
                                    align:'stretch',
                                    padding: 5
                                }
                            },{
                                layout: 'ux.display',
                                flex: 1,
                                border: false
                            },{
                                layout: 'fit',
                                flex: 1,
                                border: false
                            }]//end of display fields
                    }]//end of main panel
                })
            }
         return this.defaultInfosPanel;
        
    },

    /**
     * single record panel
     *
     * @return {Ext.ux.display.DisplayPanel}
     */
    getSingleRecordPanel: function() {
        if(!this.singleRecordPanel)
            {
                this.singleRecordPanel = new Ext.ux.display.DisplayPanel({
                    layout: 'fit',
                    border: false,
                    items:[{
                        layout: 'vbox',
                        border: false,
                        layoutConfig: {
                            align: 'stretch'
                        },
                        items:[{
                            layout: 'hbox',
                            flex: 0,
                            height: 16,
                            border: false,
                            layoutConfig: {
                                align: 'stretch'
                            },
                            items: [{
                                width: 240,
                                xtype: 'ux.displayfield',
                                cls: 'x-ux-display-header'
                            }, {
                                flex: 1,
                                xtype: 'ux.displayfield',
                                cls: 'x-ux-display-header',
                                style: 'text-align: center;',
                                html: this.app.i18n._('Question')
                            }, {
                                flex: 1,
                                xtype: 'ux.displayfield',
                                cls: 'x-ux-display-header',
                                style: 'text-align: center;',
                                html: this.app.i18n._('Answer')
                            },{
                                width: 150,
                                xtype: 'ux.displayfield',
                                cls: 'x-ux-display-header',
                                style: 'text-align: center;',
                                html: this.app.i18n._('Tags')
                            }]//end of title hbox items
                        },{ //end of title hbox
                            layout: 'hbox',
                            flex: 1,
                            border: false,
                            layoutConfig: {
                                padding: 5,
                                align:'stretch'
                            },
                            defaults:{
                                margins:'0 5 0 0'
                            },
                            items: [{
                                layout: 'ux.display',
                                labelWidth: 85,
                                width: 240,
                                layoutConfig: {
                                    background: 'solid',
                                    declaration: this.app.i18n._('FAQ Infos'),
                                    align:'stretch',
                                    padding: 5
                                },
                                items: [{
                                    xtype: 'ux.displayfield',
                                    name: 'creation_time',
                                    fieldLabel: this.app.i18n._('Creation Time'),
                                    renderer: Tine.Tinebase.common.dateRenderer
                                },{
                                    xtype: 'ux.displayfield',
                                    name: 'created_by',
                                    fieldLabel: this.app.i18n._('Created By'),
                                    renderer: Tine.Tinebase.common.usernameRenderer
                                },{
                                    xtype: 'ux.displayfield',
                                    name: 'faqstatus_id',
                                    fieldLabel: this.app.i18n._('FAQ Status'),
                                    renderer: this.dataRenderer.createDelegate(this, [Tine.SimpleFAQ.FaqStatus.getStore(), 'faqstatus'], true)
                                },{
                                    xtype: 'ux.displayfield',
                                    name: 'faqtype_id',
                                    fieldLabel: this.app.i18n._('FAQ Type'),
                                    renderer: this.dataRenderer.createDelegate(this, [Tine.SimpleFAQ.FaqType.getStore(), 'faqtype'], true)
                                }]
                            },{
                                flex: 1,
                                layout: 'fit',
                                border: false,
                                items: [{
                                    cls: 'x-ux-display-background-border',
                                    xtype: 'ux.displayfield',
                                    name: 'question',
                                    htmlEncode: false,
                                    renderer: this.htmlRenderer
                                }]
                            },{
                                flex: 1,
                                layout: 'fit',
                                border: false,
                                items: [{
                                    cls: 'x-ux-display-background-border',
                                    xtype: 'ux.displayfield',
                                    name: 'answer',
                                    emptyText: this.app.i18n._('No answer yet'),
                                    htmlEncode: false,
                                    renderer: this.htmlRenderer
                                }]
                            },{
                                width: 150,
                                layout: 'fit',
                                border: false,
                                items: [{
                                    cls: 'x-ux-display-background-border',
                                    xtype: 'ux.displayfield',
                                    name: 'tags',
                                    htmlEncode: false,
                                    renderer: this.tagsRenderer 
                                }]
                            }]//end of display fields
                      }
                        //end of content hbox
                    ]//end of hbox
                    }]//end of main panel

                })
            }
         return this.singleRecordPanel;
    },

    /**
     * get panel for multi selection aggregates/information
     *
     * @return {Ext.Panel}
     */
    getMultiRecordsPanel: function() {
        return this.getDefaultInfosPanel();
    },

    /**
     * update lead details panel
     *
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        this.getSingleRecordPanel().loadRecord.defer(100, this.getSingleRecordPanel(), [record]);
    },

    /**
     * show default panel
     *
     * @param {Mixed} body
     */
    showDefault: function(body) {
        //this.getDefaultInfosPanel();
        //this.setPiechartStores.defer(500, this, [true]);
    },

    /**
     * show template for multiple rows
     *
     * @param {Ext.grid.RowSelectionModel} sm
     * @param {Mixed} body
     */
    showMulti: function(sm, body) {
       //this.getDefaultInfosPanel();
    }
});