/*
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Ext.ux.grid');

/**
 * @namespace   Ext.ux.grid
 * @class       Ext.ux.grid.ActionColumnPlugin
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @plugin for Ext.ux.grid.GridPanel
 */

Ext.ux.grid.ActionColumnPlugin = function(config) {
    Ext.apply(this, config);
};

Ext.extend(Ext.ux.grid.ActionColumnPlugin, Ext.util.Observable, {
    
    // private
    isColumn: true,
    menuDisabled: true,
    
    grid: null,
    gridPanel: null,
    filterable: false,
    /**
     * the template to put the data into
     * 
     * @type {Ext.XTemplate}
     */
    tpl: null,
    
    /**
     * config property actions
     * 
     * @type {Array}
     */
    actions: null,
    
    actionHandlers: null,
    
    /**
     * the main template for one column
     * 
     * @type {String}
     */
    templateHTML: '<div class="ux-action-column">'
        +'<tpl for="actions">'
        +'<div ext:ux-action-column-id="{id}" ext:ux-action-column="{actionName}" class="ux-action-column-item {cls} <tpl if="text">'
        +'ux-action-column-text</tpl>" ext:qtip="{qtip}">'
        +'<tpl if="text"><span ext:qtip="{qtip}">{text}</span></tpl></div>'
        +'</tpl>'
        +'</div>',
    
    /**
     * initializes the plugin
     */
    init : function(grid) {
        this.gridPanel = grid;
        this.grid = grid.getGrid();
        this.initTemplate();
        this.width = 60;

        this.view = this.grid.getView();
        
        this.renderer = this.renderColumn.createDelegate(this);
        this.grid.on('click', this.onClick, this);
    },
    
    /**
     * the onclick handler, finds the action and the index and calls
     */
    onClick: function(e) {
        // handle row action click
        var target = e.getTarget('.ux-action-column-item');
        if (target) {
            var actionName = target.getAttribute(['ext:ux-action-column']);
            if (actionName) {
                var rowIndex = this.view.findRowIndex(target);
                if (rowIndex !== undefined) {
                    this.actionHandlers[actionName].call(this.gridPanel, rowIndex);
                }
            }
        }
    },
    
    /**
     * renders the data into the initialized template
     * 
     * @param {String} value
     * @param {Object} cell
     * @param {Ext.data.Record} record
     * @param {Number} row
     * @param {Number} col
     * @param {Ext.data.Store} store
     * 
     * @return {String}
     */
    renderColumn: function(value, cell, record, row, col, store) {
        return this.tpl.apply({data: record.data});
    },
    
    /**
     * initializes the template and sets actionHandlers
     */
    initTemplate:function() {
        var actions = [];
        this.actionHandlers = [];
        
        // put each action into a template
        Ext.each(this.actions, function(a, i) {
            var action = {
                cls: a.iconCls ? a.iconCls : '',
                text: a.text ? a.text : '  ',
                qtip: a.tooltip ? Ext.util.Format.htmlEncode(Ext.util.Format.htmlEncode(a.tooltip)) : null,
                actionName: a.name
            };
            
            this.actionHandlers[a.name] = a.callback;
            
            actions.push(action);
            
        }, this);
        
        this.tpl = new Ext.XTemplate((new Ext.XTemplate(this.templateHTML)).apply({actions: actions}));
    }
});

Ext.ComponentMgr.registerPlugin('grid-actioncolumn', Ext.ux.grid.ActionColumnPlugin);
