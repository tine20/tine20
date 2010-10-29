/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.ComposeEditor
 * @extends     Ext.form.HtmlEditor
 * 
 * <p>Compose HTML Editor</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.ComposeEditor
 */
Tine.Felamimail.ComposeEditor = Ext.extend(Ext.form.HtmlEditor, {
    
    name: 'body',
    allowBlank: true,

    // TODO get styles from head with css selector
    getDocMarkup: function(){
        var markup = '<html>'
            + '<head>'
            + '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
            + '<title></title>'
            + '<style type="text/css">'
                + '.felamimail-body-blockquote {'
                    + 'margin: 5px 10px 0 3px;'
                    + 'padding-left: 10px;'
                    + 'border-left: 2px solid #000088;'
                + '} '
            + '</style>'
            + '</head>'
            + '<body style="padding: 5px 0px 0px 5px; margin: 0px">'
            + '</body></html>';

        return markup;
    },
    
    /**
     * @private
     */
    initComponent: function() {
        
        this.plugins = [
            // TODO which plugins to activate?
            //new Ext.ux.form.HtmlEditor.Word(),  
            //new Ext.ux.form.HtmlEditor.Divider(),  
            //new Ext.ux.form.HtmlEditor.Table(),  
            //new Ext.ux.form.HtmlEditor.HR(),
            new Ext.ux.form.HtmlEditor.IndentOutdent(),  
            //new Ext.ux.form.HtmlEditor.SubSuperScript(),  
            new Ext.ux.form.HtmlEditor.RemoveFormat(),
            new Ext.ux.form.HtmlEditor.EndBlockquote()
        ];
        
        Tine.Felamimail.ComposeEditor.superclass.initComponent.call(this);
    }
});

Ext.namespace('Ext.ux.form.HtmlEditor');

/**
 * @class Ext.ux.form.HtmlEditor.EndBlockquote
 * @extends Ext.util.Observable
 * 
 * plugin for htmleditor that ends blockquotes on ENTER
 * 
 * TODO make it work in all cases
 * TODO make it work for multiple levels of blockquotes
 * TODO move this to ux dir
 */
Ext.ux.form.HtmlEditor.EndBlockquote = Ext.extend(Ext.util.Observable , {

    // private
    init: function(cmp){
        this.cmp = cmp;
        this.cmp.on('initialize', this.onInit, this);
    },
    // private
    onInit: function(){
        Ext.EventManager.on(this.cmp.getDoc(), {
            'keydown': this.onKeydown,
            scope: this
        });
    },

    /**
     * on keydown 
     * 
     * @param {Event} e
     */
    onKeydown: function(e) {
        if (e.getKey() == e.ENTER) {
            var s = this.cmp.win.getSelection();
            if (s) {
//                console.log(s);
//                console.log(s.anchorNode);
//                console.log(s.anchorNode.parentElement);
                // check if in blockquote
                if ((s.anchorNode && s.anchorNode.parentElement.tagName.toLowerCase() == 'blockquote') ||
                    (s.anchorNode.tagName && s.anchorNode.tagName.toLowerCase() == 'blockquote')
                ) {
                    //console.log('blockquote');
                    e.stopEvent();
                    this.cmp.win.focus();
                    this.cmp.execCmd('InsertHTML','<br /><blockquote class="felamimail-body-blockquote"><br />');
                    // TODO find out how oftwen we need to do the outdent cmd
                    this.cmp.execCmd('outdent');
                    this.cmp.execCmd('outdent');
                    this.cmp.execCmd('outdent');
                    this.cmp.execCmd('outdent');
                    this.cmp.execCmd('outdent');
                    this.cmp.deferFocus();
                }
            }
        }
    }
});
