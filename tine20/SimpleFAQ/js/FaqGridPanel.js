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
 * Create a new Tine.SimpleFAQ.SimpleFAQGridPanel
 */
Tine.SimpleFAQ.FaqGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
     /**
      * record class
      * @cfg {Tine.SimpleFAQ.Model.Faq} recordClass
      */
     recordClass: Tine.SimpleFAQ.Model.Faq,

     /**
      * eval grants
      * @cfg {Boolean} evalGrants
      */
     evalGrants: true,
    
     /**
      * grid specific
      * @private
      */
     defaultSortInfo: {field: 'creation_time', direction: 'DESC'},
     gridConfig: {
         loadMask: true,
         enableDragDrop: true,
         ddGroup: 'containerDDGroup',
         autoExpandColumn: 'question'
         
     },

    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.SimpleFAQ.faqBackend;
        this.gridConfig.cm = this.getColumnModel();
        
        this.defaultFilters = [{field: 'container_id', operator: 'equals', value: {path: Tine.Tinebase.container.getMyNodePath()}}];

        this.detailsPanel = new Tine.SimpleFAQ.FaqGridDetailsPanel({
            grid: this
        });

        Tine.SimpleFAQ.FaqGridPanel.superclass.initComponent.call(this);
    },

    /**
     * returns cm
     *
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
         return new Ext.grid.ColumnModel({
        defaults: {
            sortable: true,
            resizable: true
        },
        columns: [{
            id: 'id',
            header: this.app.i18n._('FAQ id'),
            width: 30,
            sortable: false,
            dataIndex: 'id',
            hidden: true
        },{
            id: 'tags',
            header: this.app.i18n._('Tags'),
            width: 50,
            sortable: false,
            dataIndex: 'tags',
            renderer: Tine.Tinebase.common.tagsRenderer
        },{
            id: 'faqstatus',
            header: this.app.i18n._("Status"),
            width: 30,
            dataIndex: 'faqstatus_id',
            renderer: Tine.SimpleFAQ.FaqStatus.Renderer
        },{
            id: 'faqtype',
            header: this.app.i18n._("Type"),
            width: 30,
            dataIndex: 'faqtype_id',
            renderer: Tine.SimpleFAQ.FaqType.Renderer
        },{
            id: 'question',
            header: this.app.i18n._("Question"),
            width: 150,            
            dataIndex: 'question',
            renderer: function(value){return value}
            
        },{
            id: 'answer',
            header: this.app.i18n._("Answer"),
            width: 200,
            dataIndex: 'answer',
            renderer: function(value){return value}
        }].concat(this.getModlogColumns())
       });
    },
    
     /**
     * return additional tb items
     * @private
     */ 
    getToolbarItems: function() {
        return [
        ]
    }

});
