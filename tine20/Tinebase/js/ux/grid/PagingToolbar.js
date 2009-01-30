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
 * Plugin capable paging toolbar with build in selection support
 * 
 * @constructor
 * @param {Object} config
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
    
    initComponent: function() {
        this.displayInfo = false;
        this.selHelperText = {
            'main'         : _('{0} selected'),
            'deselect'     : _('Unselect all'),
            'selectvisible': _('Select all visible ({0} records)'),
            'selectall'    : _('Select all pages ({0} records)'),
            'toggle'       : _('Toggle selection')
        };

        Ext.ux.grid.PagingToolbar.superclass.initComponent.call(this);
    },
    
    onRender : function(ct, position) {
        Ext.ux.grid.PagingToolbar.superclass.onRender.call(this, ct, position);
        
        this.addFill();
        this.displayEl = Ext.get(this.addText('').getEl());
        
        if (this.displaySelectionHelper) {
            this.renderSelHelper();
        }
    },
    
    
    renderSelHelper: function() {
        
        this.deselectBtn = new Ext.Action({
            iconCls: 'x-ux-pagingtb-deselect',
            text: this.getSelHelperText('deselect'),
            handler: function() {this.sm.deselectAll();}
        });
        this.selectVisibleBtn = new Ext.Action({
            iconCls: 'x-ux-pagingtb-selectvisible',
            text: this.getSelHelperText('selectvisible'),
            handler: function() {this.sm.selectAll(true);}
        });
        this.selectAllPages = new Ext.Action({
            iconCls: 'x-ux-pagingtb-selectall',
            text: this.getSelHelperText('selectall'),
            handler: function() {this.sm.selectAll();}
        });
        this.toggleSelectionBtn = new Ext.Action({
            iconCls: 'x-ux-pagingtb-toggle',
            text: this.getSelHelperText('toggle'),
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
                    this.selectVisibleBtn,
                    this.selectAllPages,
                    this.toggleSelectionBtn
                ]
            })
        });
        
        this.add(this.selHelperBtn);
        
        this.sm.on('selectionchange', this.updateSelHelper, this);
        this.store.on('load', this.updateSelHelper, this);
    },
    
    updateSelHelper: function() {
        this.selHelperBtn.setText(this.getSelHelperText('main'));
        this.selectVisibleBtn.setText(this.getSelHelperText('selectvisible'));
        this.selectAllPages.setText(this.getSelHelperText('selectall'));
    },
    
    getSelHelperText: function(domain) {
        var num;
        switch(domain) {
            case 'main':
                num = this.sm.getCount();
                break;
            case 'selectvisible':
                num = '?';
                break;
            case 'selectall':
                num = this.store.getCount();
                break;
            default:
                return this.selHelperText[domain];
                break;
        }
        
        return String.format(this.selHelperText[domain], num);
    }    
});