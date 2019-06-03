/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @class Ext.ux.display.DisplayTextArea
 * @namespace Ext.ux.display
 * @extends Ext.Container
 * @author Cornelius Weiss <c.weiss@metaways.de>
 *
 * Textarea for displaying a text in a displaypanel
 */
Ext.ux.display.DisplayTextArea = Ext.extend(Ext.Container, {
    type: 'text/plain',
    htmlEncode: true,
    nl2br: true,
    linkify: true,

    autoScroll: true,

    layout: 'fit',
    cls: 'x-ux-display-textarea',

    initComponent: function() {
        this.supr().initComponent.call(this);
        var langMatches = String(this.type).match(/^code\/(.*)/);
        this.lang = langMatches ? langMatches[1]: undefined;

        if (this.type == 'text/plain') {
            this.htmlEncode = true;
            this.nl2br = true;
        } else if (this.lang) {
            this.htmlEncode = false;
            this.nl2br = true;
            this.linkify = false;
        } else {
            // rich content needs to be prepared by the server!
            this.linkify = false;
        }
    },

    renderer: Ext.ux.display.DisplayField.prototype.renderer,
    applyRenderer: Ext.ux.display.DisplayField.prototype.applyRenderer,

    afterRender: function() {
        var me = this;

        this.supr().afterRender.call(this);

        if (this.value) {
            this.setValue(this.value);
        }

        if ( this.lang ) {
            import(/* webpackChunkName: "Tinebase/js/ace" */ 'widgets/ace').then(function() {
                me.ed = ace.edit(me.el.id, {
                    mode: 'ace/mode/' + me.lang,
                    fontFamily: 'monospace',
                    fontSize: 12
                });

                me.ed.setOptions({
                    readOnly: true,
                    highlightActiveLine: false,
                    highlightGutterLine: false
                });

                if (me.value) {
                    me.ed.setValue(me.value);
                }
            });
        }
    },

    setValue : function(value) {
        if (value == this.value) {
            return;
        }

        this.value = value;

        if (this.ed) {
            this.ed.setValue(Ext.isString(this.value) ? this.value : JSON.stringify(this.value, undefined, 4), -1);
            return;
        }

        if (! this.rendered) {
            return;
        }

        var v = this.renderer(value);

        if(this.htmlEncode){
            v = Ext.util.Format.htmlEncode(v);
        }

        if (this.nl2br) {
            v = Ext.util.Format.nl2br(v);
        }

        this.getEl().update(v);

        if (this.linkify) {
            Tine.Tinebase.common.linkifyText(v, this.getEl());
        }

    }
});

Ext.reg('ux.displaytextarea', Ext.ux.display.DisplayTextArea);
