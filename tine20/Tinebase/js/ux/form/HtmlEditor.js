/*!
 * MIT license
 */
/**
 * @author Shea Frederick - http://www.vinylfox.com
 * @class Ext.ux.form.HtmlEditor.MidasCommand
 * @extends Ext.util.Observable
 * <p>A base plugin for extending to create standard Midas command buttons.
 * googlecode project page: http://code.google.com/p/ext-ux-htmleditor-plugins/
 * </p>
 */
Ext.ns('Ext.ux.form.HtmlEditor');

/**
 * @namespace   Ext.ux.form.HtmlEditor
 * @class       Ext.ux.form.HtmlEditor.MidasCommand
 * @extends     Ext.util.Observable
 */
Ext.ux.form.HtmlEditor.MidasCommand = Ext.extend(Ext.util.Observable, {
    // private
    init: function(cmp){
        this.cmp = cmp;
        this.btns = [];
        this.cmp.on('render', this.onRender, this);
        this.cmp.on('initialize', this.onInit, this, {
            delay: 100,
            single: true
        });
    },
    // private
    onInit: function(){
        Ext.EventManager.on(this.cmp.getDoc(), {
            'mousedown': this.onEditorEvent,
            'dblclick': this.onEditorEvent,
            'click': this.onEditorEvent,
            'keyup': this.onEditorEvent,
            buffer: 100,
            scope: this
        });
    },
    // private
    onRender: function(){
        var midasCmdButton, tb = this.cmp.getToolbar(), btn;
        Ext.each(this.midasBtns, function(b){
            if (Ext.isObject(b)) {
                midasCmdButton = {
                    iconCls: 'x-edit-' + b.cmd,
                    handler: function(){
                        this.cmp.relayCmd(b.cmd);
                    },
                    scope: this,
                    tooltip: b.tooltip ||
                    {
                        title: b.title
                    },
                    overflowText: b.overflowText || b.title
                };
            } else {
                midasCmdButton = new Ext.Toolbar.Separator();
            }
            btn = tb.addButton(midasCmdButton);
            if (b.enableOnSelection) {
                btn.disable();
            }
            this.btns.push(btn);
        }, this);
    },
    // private
    onEditorEvent: function(){
        var doc = this.cmp.getDoc();
        Ext.each(this.btns, function(b, i){
            if (this.midasBtns[i].enableOnSelection || this.midasBtns[i].disableOnSelection) {
                if (doc.getSelection) {
                    if ((this.midasBtns[i].enableOnSelection && doc.getSelection() !== '') || (this.midasBtns[i].disableOnSelection && doc.getSelection() === '')) {
                        b.enable();
                    } else {
                        b.disable();
                    }
                } else if (doc.selection) {
                    if ((this.midasBtns[i].enableOnSelection && doc.selection.createRange().text !== '') || (this.midasBtns[i].disableOnSelection && doc.selection.createRange().text === '')) {
                        b.enable();
                    } else {
                        b.disable();
                    }
                }
            }
            if (this.midasBtns[i].monitorCmdState) {
                b.toggle(doc.queryCommandState(this.midasBtns[i].cmd));
            }
        }, this);
    }
});
/**
 * @namespace   Ext.ux.form.HtmlEditor
 * @class       Ext.ux.form.HtmlEditor.Divider
 * @extends     Ext.util.Observable
 * @author      Shea Frederick - http://www.vinylfox.com
 * 
 * <p>A plugin that creates a divider on the HtmlEditor. Used for separating additional buttons.</p>
 */
Ext.ux.form.HtmlEditor.Divider = Ext.extend(Ext.util.Observable, {
    // private
    init: function(cmp){
        this.cmp = cmp;
        this.cmp.on('render', this.onRender, this);
    },
    // private
    onRender: function(){
        this.cmp.getToolbar().addButton([new Ext.Toolbar.Separator()]);
    }
});
/**
 * @namespace   Ext.ux.form.HtmlEditor
 * @class       Ext.ux.form.HtmlEditor.IndentOutdent
 * @extends     Ext.ux.form.HtmlEditor.MidasCommand
 * @author      Shea Frederick - http://www.vinylfox.com
 * 
 * <p>A plugin that creates two buttons on the HtmlEditor for indenting and outdenting of selected text.</p>
 */
Ext.ux.form.HtmlEditor.IndentOutdent = Ext.extend(Ext.ux.form.HtmlEditor.MidasCommand, {
    // private
    midasBtns: ['|', {
        cmd: 'outdent',
        tooltip: {
            title: 'Outdent Text'
        },
        overflowText: 'Outdent Text'
    }, {
        cmd: 'indent',
        tooltip: {
            title: 'Indent Text'
        },
        overflowText: 'Indent Text'
    }]
});
/**
 * @namespace   Ext.ux.form.HtmlEditor
 * @class       Ext.ux.form.HtmlEditor.RemoveFormat
 * @extends     Ext.ux.form.HtmlEditor.MidasCommand
 * @author      Shea Frederick - http://www.vinylfox.com
 * 
 * <p>A plugin that creates a button on the HtmlEditor that will remove all formatting on selected text.</p>
 */
Ext.ux.form.HtmlEditor.RemoveFormat = Ext.extend(Ext.ux.form.HtmlEditor.MidasCommand, {
    midasBtns: ['|', {
        enableOnSelection: true,
        cmd: 'removeFormat',
        tooltip: {
            title: 'Remove Formatting'
        },
        overflowText: 'Remove Formatting'
    }]
});
/**
 * @namespace   Ext.ux.form.HtmlEditor
 * @class       Ext.ux.form.HtmlEditor.SubSuperScript
 * @extends     Ext.ux.form.HtmlEditor.MidasCommand
 * @author      Shea Frederick - http://www.vinylfox.com
 * 
 * <p>A plugin that creates two buttons on the HtmlEditor for superscript and subscripting of selected text.</p>
 */
Ext.ux.form.HtmlEditor.SubSuperScript = Ext.extend(Ext.ux.form.HtmlEditor.MidasCommand, {
    // private
    midasBtns: ['|', {
        enableOnSelection: true,
        cmd: 'subscript',
        tooltip: {
            title: 'Subscript'
        },
        overflowText: 'Subscript'
    }, {
        enableOnSelection: true,
        cmd: 'superscript',
        tooltip: {
            title: 'Superscript'
        },
        overflowText: 'Superscript'
    }]
});
/**
 * @namespace   Ext.ux.form.HtmlEditor
 * @class       Ext.ux.form.HtmlEditor.SpecialCharacters
 * @extends     Ext.util.Observable
 * @author      Shea Frederick - http://www.vinylfox.com
 * 
 * <p>A plugin that creates a button on the HtmlEditor for inserting special characters.</p>
 */
Ext.ux.form.HtmlEditor.SpecialCharacters = Ext.extend(Ext.util.Observable, {
    /**
     * @cfg {Array} specialChars
     * An array of additional characters to display for user selection.  Uses numeric portion of the ASCII HTML Character Code only. For example, to use the Copyright symbol, which is &#169; we would just specify <tt>169</tt> (ie: <tt>specialChars:[169]</tt>).
     */
    specialChars: [],
    /**
     * @cfg {Array} charRange
     * Two numbers specifying a range of ASCII HTML Characters to display for user selection. Defaults to <tt>[160, 256]</tt>.
     */
    charRange: [160, 256],
    // private
    init: function(cmp){
        this.cmp = cmp;
        this.cmp.on('render', this.onRender, this);
    },
    // private
    onRender: function(){
        var cmp = this.cmp;
        var btn = this.cmp.getToolbar().addButton({
            iconCls: 'x-edit-char',
            handler: function(){
                if (this.specialChars.length) {
                    Ext.each(this.specialChars, function(c, i){
                        this.specialChars[i] = ['&#' + c + ';'];
                    }, this);
                }
                for (i = this.charRange[0]; i < this.charRange[1]; i++) {
                    this.specialChars.push(['&#' + i + ';']);
                }
                var charStore = new Ext.data.ArrayStore({
                    fields: ['char'],
                    data: this.specialChars
                });
                this.charWindow = new Ext.Window({
                    title: 'Insert Special Character',
                    width: 436,
                    autoHeight: true,
                    layout: 'fit',
                    items: [{
                        xtype: 'dataview',
                        store: charStore,
                        ref: '../charView',
                        autoHeight: true,
                        multiSelect: true,
                        tpl: new Ext.XTemplate('<tpl for="."><div class="char-item">{char}</div></tpl><div class="x-clear"></div>'),
                        overClass: 'char-over',
                        itemSelector: 'div.char-item',
                        listeners: {
                            dblclick: function(t, i, n, e){
                                this.insertChar(t.getStore().getAt(i).get('char'));
                                this.charWindow.close();
                            },
                            scope: this
                        }
                    }],
                    buttons: [{
                        text: 'Insert',
                        handler: function(){
                            Ext.each(this.charWindow.charView.getSelectedRecords(), function(rec){
                                var c = rec.get('char');
                                this.insertChar(c);
                            }, this);
                            this.charWindow.close();
                        },
                        scope: this
                    }, {
                        text: 'Cancel',
                        handler: function(){
                            this.charWindow.close();
                        },
                        scope: this
                    }]
                });
                this.charWindow.show();
            },
            scope: this,
            tooltip: {
                title: 'Insert Special Character'
            },
            overflowText: 'Special Characters'
        });
    },
    /**
     * Insert a single special character into the document.
     * @param c String The special character to insert (not just the numeric code, but the entire ASCII HTML entity).
     */
    insertChar: function(c){
        if (c) {
            this.cmp.insertAtCursor(c);
        }
    }
});
/**
 * @namespace   Ext.ux.form.HtmlEditor
 * @class       Ext.ux.form.HtmlEditor.Table
 * @extends     Ext.util.Observable
 * @author      Shea Frederick - http://www.vinylfox.com
 * 
 * <p>A plugin that creates a button on the HtmlEditor for making simple tables.</p>
 */
Ext.ux.form.HtmlEditor.Table = Ext.extend(Ext.util.Observable, {
    // private
    cmd: 'table',
    /**
     * @cfg {Array} tableBorderOptions
     * A nested array of value/display options to present to the user for table border style. Defaults to a simple list of 5 varrying border types.
     */
    tableBorderOptions: [['none', 'None'], ['1px solid #000', 'Sold Thin'], ['2px solid #000', 'Solid Thick'], ['1px dashed #000', 'Dashed'], ['1px dotted #000', 'Dotted']],
    // private
    init: function(cmp){
        this.cmp = cmp;
        this.cmp.on('render', this.onRender, this);
    },
    // private
    onRender: function(){
        var cmp = this.cmp;
        var btn = this.cmp.getToolbar().addButton({
            iconCls: 'x-edit-table',
            handler: function(){
                if (!this.tableWindow){
                    this.tableWindow = new Ext.Window({
                        title: 'Insert Table',
                        closeAction: 'hide',
                        items: [{
                            itemId: 'insert-table',
                            xtype: 'form',
                            border: false,
                            plain: true,
                            bodyStyle: 'padding: 10px;',
                            labelWidth: 60,
                            labelAlign: 'right',
                            items: [{
                                xtype: 'numberfield',
                                allowBlank: false,
                                allowDecimals: false,
                                fieldLabel: 'Rows',
                                name: 'row',
                                width: 60
                            }, {
                                xtype: 'numberfield',
                                allowBlank: false,
                                allowDecimals: false,
                                fieldLabel: 'Columns',
                                name: 'col',
                                width: 60
                            }, {
                                xtype: 'combo',
                                fieldLabel: 'Border',
                                name: 'border',
                                forceSelection: true,
                                mode: 'local',
                                store: new Ext.data.ArrayStore({
                                    autoDestroy: true,
                                    fields: ['spec', 'val'],
                                    data: this.tableBorderOptions
                                }),
                                triggerAction: 'all',
                                value: 'none',
                                displayField: 'val',
                                valueField: 'spec',
                                width: 90
                            }]
                        }],
                        buttons: [{
                            text: 'Insert',
                            handler: function(){
                                var frm = this.tableWindow.getComponent('insert-table').getForm();
                                if (frm.isValid()) {
                                    var border = frm.findField('border').getValue();
                                    var rowcol = [frm.findField('row').getValue(), frm.findField('col').getValue()];
                                    if (rowcol.length == 2 && rowcol[0] > 0 && rowcol[0] < 10 && rowcol[1] > 0 && rowcol[1] < 10) {
                                        var html = "<table>";
                                        for (var row = 0; row < rowcol[0]; row++) {
                                            html += "<tr>";
                                            for (var col = 0; col < rowcol[1]; col++) {
                                                html += "<td width='20%' style='border: " + border + ";'>" + row + "-" + col + "</td>";
                                            }
                                            html += "</tr>";
                                        }
                                        html += "</table>";
                                        this.cmp.insertAtCursor(html);
                                    }
                                    this.tableWindow.hide();
                                }else{
                                    if (!frm.findField('row').isValid()){
                                        frm.findField('row').getEl().frame();
                                    }else if (!frm.findField('col').isValid()){
                                        frm.findField('col').getEl().frame();
                                    }
                                }
                            },
                            scope: this
                        }, {
                            text: 'Cancel',
                            handler: function(){
                                this.tableWindow.hide();
                            },
                            scope: this
                        }]
                    });
                
                }else{
                    this.tableWindow.getEl().frame();
                }
                this.tableWindow.show();
            },
            scope: this,
            tooltip: {
                title: 'Insert Table'
            },
            overflowText: 'Table'
        });
    }
});
/**
 * @namespace   Ext.ux.form.HtmlEditor
 * @class       Ext.ux.form.HtmlEditor.Word
 * @extends     Ext.util.Observable
 * @author      Shea Frederick - http://www.vinylfox.com
 * 
 * <p>A plugin that creates a button on the HtmlEditor for pasting text from Word without all the jibberish html.</p>
 */
Ext.ux.form.HtmlEditor.Word = Ext.extend(Ext.util.Observable, {
	curLength: 0,
	lastLength: 0,
	lastValue: '',
	wordPasteEnabled: true,
	// private
    init: function(cmp){
        
        this.cmp = cmp;
        this.cmp.on('render', this.onRender, this);
		this.cmp.on('initialize', this.onInit, this, {delay:100, single: true});
        
    },
	// private
	onInit: function(){
		
		Ext.EventManager.on(this.cmp.getDoc(), {
            'keyup': this.checkIfPaste,
            scope: this
        });
		this.lastValue = this.cmp.getValue();
		this.curLength = this.lastValue.length;
		this.lastLength = this.lastValue.length;
		
	},
	// private
	checkIfPaste: function(e){
		
		var diffAt = 0;
		this.curLength = this.cmp.getValue().length;
		
		if (e.V == e.getKey() && e.ctrlKey && this.wordPasteEnabled){
			
			this.cmp.suspendEvents();
			
			diffAt = this.findValueDiffAt(this.cmp.getValue());
			var parts = [
				this.cmp.getValue().substr(0, diffAt),
				this.fixWordPaste(this.cmp.getValue().substr(diffAt, (this.curLength - this.lastLength))),
				this.cmp.getValue().substr((this.curLength - this.lastLength)+diffAt, this.curLength)
			];
			this.cmp.setValue(parts.join(''));
			
			this.cmp.resumeEvents();
		}
		
		this.lastLength = this.cmp.getValue().length;
		this.lastValue = this.cmp.getValue();
		
	},
	// private
	findValueDiffAt: function(val){
		
		for (i=0;i<this.curLength;i++){
			if (this.lastValue[i] != val[i]){
				return i;			
			}
		}
		
	},
    /**
     * Cleans up the jubberish html from Word pasted text.
     * @param wordPaste String The text that needs to be cleansed of Word jibberish html.
     * @return {String} The passed in text with all Word jibberish html removed.
     */
    fixWordPaste: function(wordPaste) {
        
        var removals = [/&nbsp;/ig, /[\r\n]/g, /<(xml|style)[^>]*>.*?<\/\1>/ig, /<\/?(meta|object|span)[^>]*>/ig,
			/<\/?[A-Z0-9]*:[A-Z]*[^>]*>/ig, /(lang|class|type|href|name|title|id|clear)=\"[^\"]*\"/ig, /style=(\'\'|\"\")/ig, /<![\[-].*?-*>/g, 
			/MsoNormal/g, /<\\?\?xml[^>]*>/g, /<\/?o:p[^>]*>/g, /<\/?v:[^>]*>/g, /<\/?o:[^>]*>/g, /<\/?st1:[^>]*>/g, /&nbsp;/g, 
            /<\/?SPAN[^>]*>/g, /<\/?FONT[^>]*>/g, /<\/?STRONG[^>]*>/g, /<\/?H1[^>]*>/g, /<\/?H2[^>]*>/g, /<\/?H3[^>]*>/g, /<\/?H4[^>]*>/g, 
            /<\/?H5[^>]*>/g, /<\/?H6[^>]*>/g, /<\/?P[^>]*><\/P>/g, /<!--(.*)-->/g, /<!--(.*)>/g, /<!(.*)-->/g, /<\\?\?xml[^>]*>/g, 
            /<\/?o:p[^>]*>/g, /<\/?v:[^>]*>/g, /<\/?o:[^>]*>/g, /<\/?st1:[^>]*>/g, /style=\"[^\"]*\"/g, /style=\'[^\"]*\'/g, /lang=\"[^\"]*\"/g, 
            /lang=\'[^\"]*\'/g, /class=\"[^\"]*\"/g, /class=\'[^\"]*\'/g, /type=\"[^\"]*\"/g, /type=\'[^\"]*\'/g, /href=\'#[^\"]*\'/g, 
            /href=\"#[^\"]*\"/g, /name=\"[^\"]*\"/g, /name=\'[^\"]*\'/g, / clear=\"all\"/g, /id=\"[^\"]*\"/g, /title=\"[^\"]*\"/g, 
            /<span[^>]*>/g, /<\/?span[^>]*>/g, /class=/g];
					
        Ext.each(removals, function(s){
            wordPaste = wordPaste.replace(s, "");
        });
        
        // keep the divs in paragraphs
        wordPaste = wordPaste.replace(/<div[^>]*>/g, "<p>");
        wordPaste = wordPaste.replace(/<\/?div[^>]*>/g, "</p>");
        return wordPaste;
        
    },
	// private
    onRender: function() {
        
        this.cmp.getToolbar().add({
            iconCls: 'x-edit-wordpaste',
            pressed: true,
            handler: function(t){
                t.toggle(!t.pressed);
                this.wordPasteEnabled = !this.wordPasteEnabled;
            },
            scope: this,
            tooltip: {
                text: 'Cleanse text pasted from Word or other Rich Text applications'
            }
        });
		
    }
});
/**
 * @namespace   Ext.ux.form.HtmlEditor
 * @class       Ext.ux.form.HtmlEditor.HR
 * @extends     Ext.util.Observable
 * @author      Shea Frederick - http://www.vinylfox.com
 * 
 * <p>A plugin that creates a button on the HtmlEditor for inserting a horizontal rule.</p>
 */
Ext.ux.form.HtmlEditor.HR = Ext.extend(Ext.util.Observable, {
    // private
    cmd: 'hr',
    // private
    init: function(cmp){
        this.cmp = cmp;
        this.cmp.on('render', this.onRender, this);
    },
    // private
    onRender: function(){
        var cmp = this.cmp;
        var btn = this.cmp.getToolbar().addButton({
            iconCls: 'x-edit-hr',
            handler: function(){
                if (!this.hrWindow){
                    this.hrWindow = new Ext.Window({
                        title: 'Insert Rule',
                        closeAction: 'hide',
                        items: [{
                            itemId: 'insert-hr',
                            xtype: 'form',
                            border: false,
                            plain: true,
                            bodyStyle: 'padding: 10px;',
                            labelWidth: 60,
                            labelAlign: 'right',
                            items: [{
                                xtype: 'label',
                                html: 'Enter the width of the Rule in percentage<br/> followed by the % sign at the end, or to<br/> set a fixed width ommit the % symbol.<br/>&nbsp;'
                            }, {
                                xtype: 'textfield',
                                maskRe: /[0-9]|%/,
                                regex: /^[1-9][0-9%]{1,3}/,
                                fieldLabel: 'Width',
                                name: 'hrwidth',
                                width: 60,
                                 listeners: {
                                    specialkey: function(f, e){
                                        if ((e.getKey() == e.ENTER || e.getKey() == e.RETURN) && f.isValid()) {
                                            this.doInsertHR();
                                        }else{
                                            f.getEl().frame();
                                        }
                                    },
                                    scope: this
                                }
                            }]
                        }],
                        buttons: [{
                            text: 'Insert',
                            handler: function(){
                                var frm = this.hrWindow.getComponent('insert-hr').getForm();
                                if (frm.isValid()){
                                    this.doInsertHR();
                                }else{
                                    frm.findField('hrwidth').getEl().frame();
                                }
    						},
                            scope: this
                        }, {
                            text: 'Cancel',
                            handler: function(){
                                this.hrWindow.hide();
                            },
                            scope: this
                        }]
                    });
                }else{
                    this.hrWindow.getEl().frame();
                }
                this.hrWindow.show();
            },
            scope: this,
            tooltip: {
                title: 'Insert Horizontal Rule'
            },
            overflowText: 'Horizontal Rule'
        });
    },
    // private
    doInsertHR: function(){
        var frm = this.hrWindow.getComponent('insert-hr').getForm();
        if (frm.isValid()) {
            var hrwidth = frm.findField('hrwidth').getValue();
            if (hrwidth) {
                this.insertHR(hrwidth);
            } else {
                this.insertHR('100%');
            }
            frm.reset();
            this.hrWindow.hide();
        }
    },
    /**
     * Insert a horizontal rule into the document.
     * @param w String The width of the horizontal rule as the <tt>width</tt> attribute of the HR tag expects. ie: '100%' or '400' (pixels).
     */
    insertHR: function(w){
        this.cmp.insertAtCursor('<hr width="' + w + '">');
    }
});
