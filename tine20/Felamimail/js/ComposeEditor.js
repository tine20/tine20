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
    
    cls: 'felamimail-message-body-html',
    name: 'body',
    allowBlank: true,

    // TODO get styles from head with css selector
    getDocMarkup: function(){
        var markup = '<html>'
            + '<head>'
            + '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
            + '<title></title>'
            + '<style type="text/css">'
                // standard css reset
                + "html,body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,p,blockquote,th,td{margin:0;padding:0;}img,body,html{border:0;}address,caption,cite,code,dfn,em,strong,th,var{font-style:normal;font-weight:normal;}ol,ul {list-style:none;}caption,th {text-align:left;}h1,h2,h3,h4,h5,h6{font-size:100%;}q:before,q:after{content:'';}"
                // small forms
                + "html,body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,p,blockquote,th,td{font-size: small;}"
                // fmail spechial
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
            var s = this.cmp.win.getSelection(),
                r = s.getRangeAt(0),
                doc = this.cmp.getDoc(),
                level = this.getBlockquoteLevel(s);
            
            if (level > 0) {
                e.stopEvent();
                this.cmp.win.focus();
                if (level == 1) {
                    this.cmp.execCmd('InsertHTML','<br /><blockquote class="felamimail-body-blockquote"><br />');
                    this.cmp.execCmd('outdent');
                    this.cmp.execCmd('outdent');
                } else if (level > 1) {
                    for (var i=0; i < level; i++) {
                        this.cmp.execCmd('InsertHTML','<br /><blockquote class="felamimail-body-blockquote">');
                        this.cmp.execCmd('outdent');
                        this.cmp.execCmd('outdent');
                    }
                    var br = doc.createElement('br');
                    r.insertNode(br);
                }
                this.cmp.deferFocus();
            } else if (e.ctrlKey) {
                // TODO try to move this to cmp or another plugin as we need this only to submit parent dialog with ctrl-enter
                this.cmp.fireEvent('keydown', e);
            }
        }
    },
    
    /**
     * get blockquote level helper
     * 
     * @param {Selection} s
     * @return {Integer}
     */
    getBlockquoteLevel: function(s) {
        var result = 0,
            node = s.anchorNode;
            
        while (node.nodeName == '#text' || node.tagName.toLowerCase() != 'body') {
            if (node.tagName && node.tagName.toLowerCase() == 'blockquote') {
                result++;
            }
            node = node.parentElement;
        }
        
        return result;
    }
});
