/**
 * @author Shea Frederick - http://www.vinylfox.com
 * @class Ext.ux.form.HtmlEditor.Table
 * @extends Ext.util.Observable
 * <p>A plugin that creates a button on the HtmlEditor for making simple tables.</p>
 */
Ext.ux.form.HtmlEditor.Table = Ext.extend(Ext.util.Observable, {
    // Table language text
    langTitle       : 'Insert Table',
    langInsert      : 'Insert',
    langCancel      : 'Cancel',
    langRows        : 'Rows',
    langColumns     : 'Columns',
    langBorder      : 'Border',
    langCellLabel   : 'Label Cells',
    lang100PcWidth    : '100% Width',
    // private
    cmd: 'table',
    /**
     * @cfg {Boolean} showCellLocationText
     * Set true to display row and column informational text inside of newly created table cells.
     */
    showCellLocationText: false,
    /**
     * @cfg {Boolean} set100PcWidth
     * Set true to insert a 100% width table.
     */
    set100PcWidth: false,
    /**
     * @cfg {String} cellLocationText
     * The string to display inside of newly created table cells.
     */
    cellLocationText: '{0}&nbsp;-&nbsp;{1}',
    /**
     * @cfg {Array} tableBorderOptions
     * A nested array of value/display options to present to the user for table border style. Defaults to a simple list of 5 varrying border types.
     */
    tableBorderOptions: [['none', 'None'], ['1px solid #000', 'Solid Thin'], ['2px solid #000', 'Solid Thick'], ['1px dashed #000', 'Dashed'], ['1px dotted #000', 'Dotted']],
    
    // private
    init: function(cmp){
        this.cmp = cmp;
        this.cmp.on('render', this.onRender, this);
        this.tableBorderOptions = [['none', _('None')], ['1px solid #000', _('Solid Thin')], ['2px solid #000', _('Solid Thick')], ['1px dashed #000', _('Dashed')], ['1px dotted #000', _('Dotted')]];
    },
    // private
    onRender: function(){
        var btn = this.cmp.getToolbar().addButton({
            iconCls: 'x-edit-table',
            handler: function(){
                if (!this.tableWindow){
                    this.tableWindow = new Ext.Window({
                        title: _(this.langTitle),
                        closeAction: 'hide',
                        width: 235,
                        items: [{
                            itemId: 'insert-table',
                            xtype: 'form',
                            border: false,
                            plain: true,
                            bodyStyle: 'padding: 10px;',
                            labelWidth: 85,
                            labelAlign: 'right',
                            items: [{
                                xtype: 'numberfield',
                                allowBlank: false,
                                allowDecimals: false,
                                fieldLabel: _(this.langRows),
                                name: 'row',
                                width: 60
                            }, {
                                xtype: 'numberfield',
                                allowBlank: false,
                                allowDecimals: false,
                                fieldLabel: _(this.langColumns),
                                name: 'col',
                                width: 60
                            }, {
                                xtype: 'combo',
                                fieldLabel: _(this.langBorder),
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
                                anchor: '-15'
                            }, {
                            	xtype: 'checkbox',
                            	fieldLabel: _(this.langCellLabel),
                            	checked: this.showCellLocationText,
                            	listeners: {
                            		check: function(){
                            			this.showCellLocationText = !this.showCellLocationText;
                            		},
                            		scope: this
                            	}
                            }, {
                            	xtype: 'checkbox',
                            	fieldLabel: _(this.lang100PcWidth),
                            	checked: this.set100PcWidth,
                            	listeners: {
                            		check: function(){
                            			this.set100PcWidth = !this.set100PcWidth;
                            		},
                            		scope: this
                            	}
                            }]
                        }],
                        buttons: [{
                            text: _(this.langInsert),
                            handler: function(){
                                var frm = this.tableWindow.getComponent('insert-table').getForm();
                                if (frm.isValid()) {
                                    var border = frm.findField('border').getValue();
                                    var rowcol = [frm.findField('row').getValue(), frm.findField('col').getValue()];
                                    if (rowcol.length == 2 && rowcol[0] > 0 && rowcol[1] > 0) {
                                        var colwidth = Math.floor(100/rowcol[1]);
                                        var html = "<table style='border-collapse: collapse;" + (this.set100PcWidth ? "width:100%;" : "") + "'>";
                                        var cellText = '&nbsp;';
                                        if (this.showCellLocationText){ cellText = this.cellLocationText; }
                                        for (var row = 0; row < rowcol[0]; row++) {
                                            html += "<tr>";
                                            for (var col = 0; col < rowcol[1]; col++) {
                                                html += "<td " + (border=='none' ? "class='editor-wysiwyg-table' " : "") + "width='" + colwidth + "%' style='border: " + border + ";'>" + String.format(cellText, (row+1), String.fromCharCode(col+65)) + "</td>";
                                            }
                                            html += "</tr>";
                                        }
                                        html += "</table><br/>";
                                        this.cmp.insertAtCursor(html);
                                        if (Ext.isIE || Ext.isNewIE) {
                                            var r = this.cmp.currentRange || this.cmp.getDoc().selection.createRange();
                                            if (r) { 
                                                r.select();
                                            }
                                        }
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
                            text: _(this.langCancel),
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
                title: _(this.langTitle)
            },
            overflowText: _(this.langTitle)
        });
    }
});
