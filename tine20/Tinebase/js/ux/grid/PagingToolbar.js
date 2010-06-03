/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Ext.ux.grid');


/**
 * Paging toolbar with build in selection support
 * 
 *
 * @namespace   Ext.ux.grid
 * @class       Ext.ux.grid.PagingToolbar
 * @extends     Ext.PagingToolbar
 * @constructor
 * @param       {Object} config
 */
Ext.ux.grid.PagingToolbar = Ext.extend(Ext.PagingToolbar, {
    /**
     * @cfg {Bool} displayPageInfo 
     * True to display the displayMsg (defaults to false)
     */
    displayPageInfo: false,
    /**
     * @cfg {Bool} displaySelectionHelper
     * True to display the selectionMsg (defaults to false)
     */
    displaySelectionHelper: false,
    /**
     * @cfg {Ext.grid.AbstractSelectionModel}
     */
    sm: null,
    
    /**
     * @private
     */
    initComponent: function() {
        
        // initialise i18n
        this.selHelperText = {
            'main'         : _('{0} selected'),
            'deselect'     : _('Unselect all'),
            'selectvisible': _('Select all visible ({0} records)'),
            'selectall'    : _('Select all pages ({0} records)'),
            'toggle'       : _('Toggle selection')
        };

        Ext.ux.grid.PagingToolbar.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    onRender : function(ct, position) {
        Ext.ux.grid.PagingToolbar.superclass.onRender.call(this, ct, position);
        
        if (this.displaySelectionHelper) {
            this.renderSelHelper();
        }
    },
    
    /**
     * @private
     */
    renderSelHelper: function() {
        this.deselectBtn = new Ext.Action({
            iconCls: 'x-ux-pagingtb-deselect',
            text: this.getSelHelperText('deselect'),
            scope: this,
            handler: function() {this.sm.clearSelections();}
        });
        this.selectAllPages = new Ext.Action({
            iconCls: 'x-ux-pagingtb-selectall',
            text: this.getSelHelperText('selectall'),
            scope: this,
            handler: function() {this.sm.selectAll();}
        });
        this.selectVisibleBtn = new Ext.Action({
            iconCls: 'x-ux-pagingtb-selectvisible',
            text: this.getSelHelperText('selectvisible'),
            scope: this,
            handler: function() {this.sm.selectAll(true);}
        });
        this.toggleSelectionBtn = new Ext.Action({
            iconCls: 'x-ux-pagingtb-toggle',
            text: this.getSelHelperText('toggle'),
            scope: this,
            handler: function() {this.sm.toggleSelection();}
        });
        
        this.addSeparator();
        this.selHelperBtn = new Ext.Action({
            xtype: 'tbsplit',
            text: this.getSelHelperText('main'),
            iconCls: 'x-ux-pagingtb-main',
            //handler: Ext.emptyFn,
            menu: new Ext.menu.Menu({
                items: [
                    this.deselectBtn,
                    this.selectAllPages,
                    this.selectVisibleBtn, // usefull?
                    this.toggleSelectionBtn
                ]
            })
        });
        
        this.add(this.selHelperBtn);
        
        // update buttons when data or selection changes
        this.sm.on('selectionchange', this.updateSelHelper, this);
        this.store.on('load', this.updateSelHelper, this);
    },
    
    /**
     * update all button descr.
     */
    updateSelHelper: function() {
        this.selHelperBtn.setText(this.getSelHelperText('main'));
        this.selectVisibleBtn.setText(this.getSelHelperText('selectvisible'));
        this.selectAllPages.setText(this.getSelHelperText('selectall'));
    },

    /**
     * get test for button
     * @param {String} domain 
     * @return {String}
     */
    getSelHelperText: function(domain) {
        var num;
        switch(domain) {
            case 'main':
                num = this.sm.getCount();
                break;
            case 'selectvisible':
                num = this.store.getCount();
                break;
            case 'selectall':
                num = this.store.getTotalCount();
                break;
            default:
                return this.selHelperText[domain];
                break;
        }
        
        return String.format(this.selHelperText[domain], num);
    }    
});