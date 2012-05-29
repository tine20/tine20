/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.editDialog');

/**
 * Token Mode Edit Plugin
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.TokenModeEditDialogPlugin
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config The configuration options.
 */
Tine.widgets.dialog.TokenModeEditDialogPlugin = function(config) {
    Ext.apply(this, config);
};

Tine.widgets.dialog.TokenModeEditDialogPlugin.prototype = {
    
    /**
     * @property app
     * @type Tine.Tinebase.Application
     */
    app : null,
    
    /**
     * @property editDialog
     * @type Tine.widgets.dialog.EditDialog
     */
    editDialog : null,
    
    /**
     * @property form
     * @type Ext.form.BasicForm
     */
    form : null,
    
    /**
     * @property inTokenMode
     * @type Boolean
     */
    inTokenMode: false,
    
    /**
     * @property selection
     * @type Array
     */
    selection: null,
    
    init : function(editDialog) {
        
        this.editDialog = editDialog;
        this.app = Tine.Tinebase.appMgr.get(this.editDialog.app);
        this.form = this.editDialog.getForm();
        
        if (this.editDialog.rendered) {
            this.onAfterRender();
        } else {
            this.editDialog.on('afterrender', this.onAfterRender, this);
        }
        
        this.editDialog.onRecordLoad = this.editDialog.onRecordLoad.createSequence(this.onRecordLoad, this);
    },
    
    onAfterRender: function() {
        this.editDialog.getEl().on('keydown', function (e) {
            if (e.ctrlKey && e.getKey() === e.T) {
                if (this.inTokenMode) {
                    this.endTokenMode();
                } else {
                    this.startTokenMode();
                }
            }
        }, this);
    },
    
    onRecordLoad: function() {
        if (this.inTokenMode) {
            // restart token mode defered to don't consume time before closeing
            this.startTokenMode.defer(100, this);
        }
    },
    
    startTokenMode: function() {
        this.initialize();
        
        if (this.inTokenMode) {
            this.endTokenMode();
        }
        
        this.inTokenMode = true;
        
        this.selection = [];
        
        this.form.items.each(function(item) {
            if (item instanceof Ext.form.TextField && ! item.disabled && ! item.forceSelection) {
                if (item.rendered) {
                    this.tokenizeField(item);
                } else {
                    item.on('render', this.tokenizeField, this);
                }
            }
        }, this);
    },
    
    endTokenMode: function() {
        if (this.inTokenMode) {
            this.inTokenMode = false;
            Ext.select('div[class^=tinebase-tokenedit-tokenbox]', this.editDialog.getEl()).remove();
            
            
            this.form.items.each(function(item) {
                item.un('render', this.tokenizeField, this);
                item.un('resize', this.syncFieldSize, this);
            }, this);
        }
    },
    
    initialize: function() {
        if (! this.isInitialized) {
            this.isInitialized = true;
            
            //this.editDialog.getEl().on('dblclick', this.endTokenMode, this);
            
            this.dragZone = new Ext.dd.DragZone(this.editDialog.getEl(), {
                getDragData: this.getDragData.createDelegate(this),
                getRepairXY: Ext.emptyFn
            });
            
            this.dropZone = new Ext.dd.DropZone(this.editDialog.getEl(), {
                onNodeOver: this.onNodeOver.createDelegate(this),
                onNodeDrop: this.onNodeDrop.createDelegate(this),
                getTargetFromEvent: this.getTargetFromEvent.createDelegate(this)
            });
            
            this.editDialog.getEl().on('mousedown', this.onClick, this);
        }
    },
    
    tokenizeField: function(field) {
        if (! this.inTokenMode) {
            // token mode might be stopped in the meantime
            return;
        }
        
        var el = field.itemCt.insertFirst({
            'tag': 'div',
            'class': 'tinebase-tokenedit-tokenbox x-form-text' + (field instanceof Ext.form.TextArea ? ' tinebase-tokenedit-tokenbox-area' : '')
        });
        
        this.syncFieldSize(field);
        field.on('resize', this.syncFieldSize, this);
        
        var value = Ext.util.Format.htmlEncode(field.getValue());
        if (value) {
            value = value.replace(/\n/g, ' &#x21A9; ');
            
            var tokens = String(value).split(/\s+/);
            this.getTokenTpl().overwrite(el, tokens);
        }
    },
    
    syncFieldSize: function(field) {
        var el = field.itemCt.down('div[class^=tinebase-tokenedit-tokenbox]');
        
        el.setBox(field.el.getBox());
    },
    
    getTokenTpl: function() {
        if (! this.tokenTpl) {
            this.tokenTpl = new Ext.XTemplate(
                '<tpl for=".">',
                    '<span class="tinebase-tokenedit-token">{.}</span>',
                '</tpl>'
            ).compile();
        }
        
        return this.tokenTpl;
    },
    
    onClick: function(e) {
        
        if (this.processedEvent == e.browserEvent) {
            return;
        }
        
        this.processedEvent = e.browserEvent;
        
        var target = e.getTarget('.tinebase-tokenedit-token', 10, true);
        if (target) {
            if (e.ctrlKey) {
                if (this.selection.indexOf(target) < 0) {
                    this.selection.push(target);
                    target.addClass('tinebase-tokenedit-token-selected');
                } else {
                    this.selection.remove(target);
                    target.removeClass('tinebase-tokenedit-token-selected');
                }
            } else {
                Ext.each(this.selection, function(el) {
                    el.removeClass('tinebase-tokenedit-token-selected');
                }, this);
                this.selection = [target];
                target.addClass('tinebase-tokenedit-token-selected');
            }
        }
    },
    
    getDragData: function(e) {
        var target = e.getTarget('.tinebase-tokenedit-token', 10, true);
            
        if (target) {
            // autoselect token
            if (this.selection.indexOf(target) < 0) {
                this.onClick(e);
            }
            this.processedEvent = e.browserEvent;
            
            var ddel = new Ext.Element(document.createElement('div'));
            ddel.id = Ext.id();
            
            var sourceFields = [];
            
            Ext.each(this.selection, function(el) {
                sourceFields.push(Ext.getCmp(el.parent('div[class^=tinebase-tokenedit-tokenbox]').parent('div').child('*[id^=ext-comp]').id));
                
                var clone = el.dom.cloneNode(true);
                clone.id = Ext.id();
                ddel.appendChild(clone);
            }, this);
            
            return {
                ddel: ddel.dom,
                sourceFields: sourceFields
                
            }
        }
    },
    
    getTargetFromEvent: function(e) {
        var target = e.getTarget('.tinebase-tokenedit-tokenbox', 10, true);
        
        this.getPositionEl().setStyle('display', target ? 'inline-block' : 'none');
        return target;
    },
    
    onNodeOver: function(target, dd, e, data) {
        var tokens = target.query('.tinebase-tokenedit-token'),
            nextToken = null,
            nextTokenBox = null,
            eventXY = e.getXY();
        
        // find the 'nearest' other token
        Ext.each(tokens, function(el) {
            var token = Ext.get(el),
                box = token.getBox();
                
            var dx = eventXY[0] > box[0] ?
                Math.max(0, Math.min(eventXY[0] - box[0], eventXY[0] - box['right'])) :
                Math.max(0, Math.min(box[0] - eventXY[0], box['right'] - eventXY[0]));
                
            var dy = eventXY[1] > box[1] ?
                Math.max(0, Math.min(eventXY[1] - box[1], eventXY[1] - box['bottom'])) :
                Math.max(0, Math.min(box[1] - eventXY[1], box['bottom'] - eventXY[1]));
            
            token.distance = dx + dy;
            
            if (! nextToken || token.distance < nextToken.distance) {
                nextToken = token;
                nextTokenBox = box;
            }
        }, this);
        
        if (nextToken) {
            // show positioning symbol left or right to nearest token
            data.insertPosition = eventXY[0] > (nextTokenBox['x'] + nextTokenBox['width']/2) ? 'insertAfter' : 'insertBefore';
            
            this.getPositionEl()[data.insertPosition](nextToken);
        } else {
            target.insertFirst(this.getPositionEl());
        }
        
        // cache nextToken
        data.nextToken = nextToken;
        return Ext.dd.DropZone.prototype.dropAllowed;
    },
    
    onNodeDrop : function(target, dd, e, data) {
        var field = Ext.getCmp(target.up('div').child('*[id^=ext-comp]').id),
            positionEl = this.getPositionEl();
        
        positionEl.setStyle('display', 'none');
        
        Ext.each(this.selection, function(el) {
            el.insertBefore(positionEl);
        }, this);
        
        // update source and target fields
        Ext.each([field].concat(data.sourceFields), function(f) {
            var value = [];
            
            Ext.each(f.itemCt.query('.tinebase-tokenedit-token'), function(el) {
                value.push(el.innerHTML);
            }, this);
            
            value = value.join(' ');
            value = Ext.util.Format.htmlDecode(value.replace(/\s*\u21A9\s*/g, '\n'));
            
            f.setValue(value);
        }, this);
    },
    
    getPositionEl: function() {
        if (! this.positionEl) {
            this.positionEl = this.editDialog.getEl().createChild({
                'tag':  'span',
                'class': 'tinebase-tokenedit-position',
                'html' : '&nbsp;'
            });
        }
        
        return this.positionEl;
    }
};