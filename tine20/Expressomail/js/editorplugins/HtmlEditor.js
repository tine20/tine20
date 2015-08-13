/*!
 * @author Fernando Lages
 * Some changes to Ext.form.HtmlEditor
 */

Tine.Expressomail.HtmlEditor = Ext.extend(Ext.form.HtmlEditor, {
    /**
     * @cfg {Array} fontFamilies An array of available font families
     */
    fontFamilies : {
        'Arial': true,
        'Courier New': true,
        'Tahoma': false,
        'Times New Roman': true,
        'Verdana': true,
        'Calibri': false,
        'Cambria': false,
        'Franklin Gothic book': false,
        'Myriad Pro': false,
        'Spranq Eco Sans': false,
        'Tekton Pro Ext': false
    },
    defaultFont: 'Tahoma',
    /**
     * @cfg {Array} fontSizes An array of available font sizes
     */
    fontSizes : [
        '8', '9', '10', '11', '12', '13', '14', '16', '18', '20', '24', '28', '32', '40', '48'
    ],
    // kept for IE8 compatibility
    fontSizesIE : [
        '10', '13', '16', '18', '24', '32', '48'
    ],
    defaultSize: '16',
    // character for unidentified styles
    unidentified: '\u200B',

    // private
    /**
     * Shortcut for translation on Expressomail's scope
     * @param {String} msgid - string to be translated
     * @returns {String} translated string
     */
    _ : function(msgid) {
        return this.messageEdit.app.i18n._(msgid);
    },

    // private
    /**
     * check if the font is installed
     * @param {String} fontName A string with the font to be checked
     * @return {Boolean} true if the font is installed
     */
    doesFontExist : function(fontName) {
        try {
            var canvas = document.createElement("canvas");
            var context = canvas.getContext("2d");
            var text = "abcdefghijklmnopqrstuvwxyz0123456789";
            context.font = "180px monospace";
            var baselineSize = context.measureText(text).width;
            context.font = "180px '" + fontName + "', monospace";
            var newSize = context.measureText(text).width;
            delete canvas;
            return (newSize !== baselineSize);
        }
        catch (e) {
            return (true);
        }
    },

    /**
     * get the font used at the selection
     * @param {Object} target The selection
     * @return {String} the font name
     */
    getFontFromSelection : function(target) {
        return this.getNodeStyle('fontFamily', target);
    },

    /**
     * get the first existent font from a list of fonts
     * @param {String} fontlist a list of fonts (comma sepparated)
     * @return {String} the name from the applied font
     */
    getAppliedFont : function(fontlist) {
        var font;
        var fonts = fontlist.replace(/,\s/g,",").split(",");
        if (fonts.length===1) {
            return fonts[0];
        }
        else {
            for(var i = 0, len = fonts.length; i< len; i++){
                font = fonts[i];
                if (this.fontFamilies[font]||this.doesFontExist(font)||font===this.unidentified) {
                    return font;
                }
            }
            return this.defaultFont;
        }
    },

    /**
     * get the style of the node
     * @param {String} stylename A string with the css style name
     * @param {Object} target The target node
     * @return {String} the definition for that style
     */
    getNodeStyle : function(stylename, target) {
        var selection = this.win.getSelection();
        var node = selection.anchorNode;
        var styles = [];
        var nodeStyle, focusStyle;
        if (target) {
            nodeStyle = this.getStyleFromNode(stylename, target);
            if (!this.isEmptySelection(selection)) {
                styles = this.getStylesFromSelection(stylename, selection, nodeStyle);
                if ((styles.length>1)) {
                    return this.unidentified;
                }
            }
            node = target;
            if (styles.length>0) {
                nodeStyle = nodeStyle||styles[0];
            }
        }
        else {
            nodeStyle = this.getStyleFromNode(stylename, node);
        }
        focusStyle = this.getStyleFromNode(stylename, selection.focusNode);
        if (!styles || (styles[0] && nodeStyle!=='' && styles[0]!==nodeStyle) ||
            (selection.focusOffset>0 && nodeStyle!=='' && focusStyle!=='' && focusStyle!==nodeStyle)) {
            return this.unidentified;
        }
        else {
            return nodeStyle||focusStyle;
        }
    },

    /**
     * get all styles applied to a selection
     * @param {String} stylename A string with the css style name
     * @param {Object} selection The selection
     * @param {String} docstyle The main document style
     * @return {String} the styles from the selection
     */
    getStylesFromSelection : function(stylename, selection, docstyle) {
        var style;
        var childNode;
        var node = selection.getRangeAt(0).cloneContents();
        var styles = [];
        for (var i=0; i<node.childNodes.length;i++) {
            childNode = node.childNodes[i];
            if (childNode.textContent.replace(/\u200B/g,"").length>0) {
                style = this.getStyleFromNode(stylename, childNode);
                if (style!=="" && styles.indexOf(style)<0) {
                    styles.push(style);
                }
                else {
                    styles.push(docstyle);
                }
            }
        }
        return styles;
    },

    /**
     * get all styles applied to a selection
     * @param {String} stylename A string with the css style name
     * @param {Object} node the node to check
     * @return {String} the style from the node
     */
    getStyleFromNode : function(stylename, node) {
        var value;
        var fontstyle;
        if (stylename==='fontFamily') {
            fontstyle = 'face';
        }
        else if (stylename==='fontSize') {
            fontstyle = 'size';
        }
        if (node) {
            if (!value && node.style && (!node.tagName ||
                 (node.tagName && node.tagName.toLowerCase()!=='html' && node.tagName.toLowerCase()!=='body'))) {
                value = node.style[stylename].replace(/'/g,""); // clean single quotes in style
            }
            while (!value && node.parentNode && (!node.tagName ||
                    (node.tagName && node.tagName.toLowerCase()!=='html' && node.tagName.toLowerCase()!=='body'))) {
                node = node.parentNode;
                if (node.style) {
                    value = node.style[stylename].replace(/'/g,""); // clean single quotes in style
                }
                if (!value && node.attributes && node.attributes[fontstyle]) {
                    var size = node.attributes[fontstyle].value;
                    if (size) {
                        value = this.fontSizesIE[size-1]; // applies even though it is not IE
                    }
                }
            }
        }
        else {
            value = this.win.document.body.style[stylename];
        }
        if (value) {
            return this.getAppliedFont(value);
        }
        else {
            return "";
        }
    },

    /**
     * get if the selection point is empty
     * @param {Object} selection The selection
     * @return {Boolean} true if it is empty
     */
    isEmptySelection : function(selection) {
        return (selection.anchorOffset===selection.focusOffset && selection.anchorNode===selection.focusNode);
    },

    // private
    createFontFamilyOptions : function(){
        if (!Ext.isIE && !Ext.isNewIE) {
            var menutpl = new Ext.XTemplate(
                '<a id="{itemId}" class="x-menu-item x-menu-fontfamily-noicon">',
                    '<tpl if="true">',
                        '<span class="x-edit-fontfamily-text" style="font-family:{text}">{text}</span>',
                    '</tpl>',
                '</a>'
            );
            menutpl.compile();
            var ffsHandler = function(e) {
                var font = e.text;
                this.focus();
                this.relayCmd('stylewithcss', true);
                this.relayCmd('fontname',font);
                this.deferFocus();
            };
            var ffsToggle = function(state) {
                state = state === undefined ? !this.pressed : !!state;
                if(state !== this.pressed) {
                    if(this.rendered){
                        this.el[state ? 'addClass' : 'removeClass']('x-btn-pressed');
                        this.el[state ? 'addClass' : 'removeClass']('x-menu-item-selected');
                        this.pressed = state;
                    }
                    this.fireEvent('toggle', this, state);
                    if(this.toggleHandler){
                        this.toggleHandler.call(this.scope || this, this, state);
                    }
                }
                return this;
            };
            var buf = [], fs = this.fontFamilies, ff, lc;
            for (var ff in fs) {
                lc = ff.toLowerCase();
                buf.push({
                    itemId : 'fontfamily'+ff,
                    enableToggle: true,
                    handler: ffsHandler,
                    disabled: !(fs[ff]||this.doesFontExist(ff)),
                    tooltip: ff,
                    text: ff,
                    hideLabel: true,
                    textCls: 'x-edit-fontfamily-text',
                    group: 'fontfamily',
                    toggle: ffsToggle,
                    itemTpl: menutpl,
                    scope: this
                });
            }
            return buf;
        }
        else {
            var buf = [], fs = this.fontFamilies, ff, lc;
            for (var ff in fs) {
                lc = ff.toLowerCase();
                buf.push(
                    '<option value="',lc,'" style="font-family:',ff,';"',
                        (this.defaultFont === lc ? ' selected="true">' : '>'),
                        ff,
                    '</option>'
                );
            }
            return buf.join('');
        }
    },

    createFontSizeOptions : function(){
        if (!Ext.isIE && !Ext.isNewIE) {
            var menutpl = new Ext.XTemplate(
                '<a id="{itemId}" class="x-menu-item x-menu-fontsize-noicon">',
                    '<tpl if="true">',
                        '<span class="{iconCls}">a</span>',
                        '<span class="x-edit-fontsize-text">{text}</span>',
                    '</tpl>',
                    '<hr class="separator">',
                '</a>'
            );
            menutpl.compile();
            var fssHandler = function(e) {
                var size = e.text;
                var selection = this.win.getSelection();
                var node = selection.focusNode;
                var sentence;
                if (this.isEmptySelection(selection)) {
                    sentence = '&nbsp;';
                }
                else {
                    if (selection.rangeCount) {
                        var container = document.createElement("div");
                        for (var i = 0, len = selection.rangeCount; i < len; ++i) {
                            container.appendChild(selection.getRangeAt(i).cloneContents());
                        }
                        sentence = container.innerHTML;
                        sentence = sentence.replace(/font-size:.[^;"']*/g,""); // clean inner font-size settings
                        sentence = sentence.replace(/<(.[^>])*><\/(.[^>])*>/g,""); // clean empty tags
                    } else {
                        sentence = selection.toString();
                    }
                }
                this.insertAtCursor("<span style='font-size:"+size+"px'>" + sentence + "</span>");
                selection.extend (selection.focusNode, 0);
                this.deferFocus();
            };
            var fssToggle = function(state) {
                state = state === undefined ? !this.pressed : !!state;
                if(state !== this.pressed) {
                    if(this.rendered){
                        this.el[state ? 'addClass' : 'removeClass']('x-btn-pressed');
                        this.el[state ? 'addClass' : 'removeClass']('x-menu-item-selected');
                        this.pressed = state;
                    }
                    this.fireEvent('toggle', this, state);
                    if(this.toggleHandler){
                        this.toggleHandler.call(this.scope || this, this, state);
                    }
                }
                return this;
            };
            var buf = [], fs = this.fontSizes, ff, lc;
            for(var i = 0, len = fs.length; i< len; i++){
                ff = fs[i];
                lc = ff.toLowerCase();
                buf.push({
                    itemId : 'fontsize'+ff,
                    enableToggle: true,
                    handler: fssHandler,
                    clickEvent:'mousedown',
                    tooltip: ff,
                    text: ff,
                    hideLabel: true,
                    iconCls: 'x-edit-fontsize'+ff,
                    textCls: 'x-edit-fontsize-text',
                    itemCls: 'x-menu-item',
                    group: 'fontsize',
                    toggle: fssToggle,
                    itemTpl: menutpl,
                    scope: this
                });
            }
            return buf;
        }
        else {
            var buf = [], fs = this.fontSizesIE, ff, lc;
            for(var i = 0, len = fs.length; i< len; i++){
                ff = fs[i];
                lc = ff.toLowerCase();
                buf.push(
                    '<option value="',lc,'" style="font-size:',ff,';"',
                        (this.defaultSize === lc ? ' selected="true">' : '>'),
                        ff,
                    '</option>'
                );
            }
            return buf.join('');
        }
    },

    /*
     * Protected method that will not generally be called directly. It
     * is called when the editor creates its toolbar. Override this method if you need to
     * add custom toolbar buttons.
     * @param {HtmlEditor} editor
     */
    createToolbar : function(editor){
        var items = [];
        var tipsEnabled = Ext.QuickTips && Ext.QuickTips.isEnabled();


        function btn(id, toggle, handler){
            return {
                itemId : id,
                cls : 'x-btn-icon',
                iconCls: 'x-edit-'+id,
                enableToggle:toggle !== false,
                scope: editor,
                handler:handler||editor.relayBtnCmd,
                clickEvent:'mousedown',
                tooltip: tipsEnabled ? editor.supr().buttonTips[id] || undefined : undefined,
                overflowText: editor.supr().buttonTips[id].title || undefined,
                tabIndex:-1
            };
        }


        if(this.enableFont && !Ext.isSafari2){
            if (!Ext.isIE && !Ext.isNewIE) {
                var ffsToggleHandler = function (e) {
                    var target;
                    if (e) {
                        var target = e.target;
                    }
                    else {
                        var target = editor.win.getSelection();
                    }
                    var font = editor.getFontFromSelection(target);
                    if (font) {
                        this.btnEl.dom.className = 'x-btn-text no-icon x-menu-fontfamily-hideicon';
                        this.btnEl.dom.innerHTML = font.replace(/'/g,"");
                    }
                    Ext.each(this.menu.items, function(b, i){
                        b = this.menu.items.get(i);
                        b.toggle(font===b.text);
                    }, this);
                };
                this.fontSelectItem = new Ext.Toolbar.Button({
                    xtype: 'tbsplit',
                    id: 'fontfamily_button',
                    itemId: 'fontfamily',
                    cls: 'x-btn-icon x-menu-layout-fontfamily',
                    iconCls: 'no-icon x-menu-fontsize-hideicon',
                    tooltip: this._('Select font name'),
                    enableToggle: true,
                    menu: {
                        showSeparator: false,
                        items: this.createFontFamilyOptions()
                    },
                    toggle: ffsToggleHandler,
                    scope: this
                });
            }
            else {
                this.fontSelectItem = new Ext.Toolbar.Item({
                   autoEl: {
                        tag:'select',
                        cls:'x-font-select',
                        html: this.createFontFamilyOptions()
                   }
                });
            }

            items.push(
                this.fontSelectItem,
                '-'
            );
        }

        if(this.enableFontSize){
            if (!Ext.isIE && !Ext.isNewIE) {
                var fssToggleHandler = function (e) {
                    var target;
                    if (e) {
                        var target = e.target;
                    }
                    var size = editor.getNodeStyle('fontSize',target)||editor.defaultSize;
                    if (size) {
                        size = size.replace('px','');
                        if (!parseInt(size)) {
                            size = editor.unidentified;
                        }
                        this.btnEl.dom.className = 'x-btn-text no-icon x-menu-fontsize-hideicon';
                        this.btnEl.dom.innerHTML = size;
                    }
                    Ext.each(this.menu.items, function(b, i){
                        b = this.menu.items.get(i);
                        b.toggle(size===b.text.replace('px',''));
                    }, this);
                };

                this.fontSelectSize = new Ext.Toolbar.Button({
                    xtype: 'tbsplit',
                    id: 'fontsize_button',
                    itemId: 'fontsize',
                    cls: 'x-btn-icon x-menu-layout-fontsize',
                    iconCls: 'no-icon x-menu-fontsize-hideicon',
                    tooltip: this._('Select font size'),
                    enableToggle: true,
                    menu: {
                        hideLabel: true,
                        showSeparator: true,
                        items: this.createFontSizeOptions(),
                        layout: 'menu',
                        cls: 'x-menu-layout-fontsize'
                    },
                    showSeparator: true,
                    toggle: fssToggleHandler,
                    scope: this
                });
            }
            else {
                this.fontSelectSize = new Ext.Toolbar.Item({
                   autoEl: {
                        tag:'select',
                        cls:'x-font-select',
                        html: this.createFontSizeOptions()
                   }
                });
            }

            items.push(
                this.fontSelectSize,
                '-'
            );
        }

        if(this.enableFormat){
            items.push(
                btn('bold'),
                btn('italic'),
                btn('underline')
            );
        }

        if(this.enableColors){
            items.push(
                '-', {
                    itemId:'forecolor',
                    cls:'x-btn-icon',
                    iconCls: 'x-edit-forecolor',
                    clickEvent:'mousedown',
                    tooltip: tipsEnabled ? editor.buttonTips.forecolor || undefined : undefined,
                    tabIndex:-1,
                    menu : new Ext.menu.ColorMenu({
                        allowReselect: true,
                        focus: Ext.emptyFn,
                        value:'000000',
                        plain:true,
                        listeners: {
                            scope: this,
                            select: function(cp, color){
                                this.execCmd('forecolor', Ext.isWebKit || Ext.isIE ? '#'+color : color);
                                this.deferFocus();
                            }
                        },
                        clickEvent:'mousedown'
                    })
                }, {
                    itemId:'backcolor',
                    cls:'x-btn-icon',
                    iconCls: 'x-edit-backcolor',
                    clickEvent:'mousedown',
                    tooltip: tipsEnabled ? editor.buttonTips.backcolor || undefined : undefined,
                    tabIndex:-1,
                    menu : new Ext.menu.ColorMenu({
                        focus: Ext.emptyFn,
                        value:'FFFFFF',
                        plain:true,
                        allowReselect: true,
                        listeners: {
                            scope: this,
                            select: function(cp, color){
                                if(Ext.isGecko){
                                    this.execCmd('useCSS', false);
                                    this.execCmd('hilitecolor', color);
                                    this.execCmd('useCSS', true);
                                    this.deferFocus();
                                }else{
                                    this.execCmd(Ext.isOpera ? 'hilitecolor' : 'backcolor', Ext.isWebKit || Ext.isIE ? '#'+color : color);
                                    this.deferFocus();
                                }
                            }
                        },
                        clickEvent:'mousedown'
                    })
                }
            );
        }

        if(!Ext.isSafari2){
            if(this.enableLinks){
                items.push(
                    '-',
                    btn('createlink', false, this.createLink)
                );
            }

            if(this.enableLists){
                items.push(
                    '-',
                    btn('insertorderedlist'),
                    btn('insertunorderedlist')
                );
            }
            if(this.enableSourceEdit){
                items.push(
                    '-',
                    btn('sourceedit', true, function(btn){
                        this.toggleSourceEdit(!this.sourceEditMode);
                    })
                );
            }
        }

        // build the toolbar
        var tb = new Ext.Toolbar({
            renderTo: this.wrap.dom.firstChild,
            items: items
        });

        if (Ext.isIE || Ext.isNewIE) {
            if (this.fontSelectItem) {
                this.fontSelect = this.fontSelectItem.el;

                this.mon(this.fontSelect, 'change', function(){
                    var font = this.fontSelect.dom.value;
                    this.relayCmd('fontname', font);
                    this.deferFocus();
                }, this);
            }
            if (this.fontSelectSize) {
                this.fontSelectS = this.fontSelectSize.el;

                this.mon(this.fontSelectS, 'change', function(){
                    var size = this.fontSelectS.dom.value;
                    this.relayCmd('fontsize', this.fontSizesIE.indexOf(size)+1);
                    this.deferFocus();
                }, this);
            }
        }

        // stop form submits
        this.mon(tb.el, 'click', function(e){
            e.preventDefault();
        });

        this.tb = tb;
    },

    // private
    initEditor : function(){
        //Destroying the component during/before initEditor can cause issues.
        try{
            var dbody = this.getEditorBody(),
                ss = this.el.getStyles('font-size', 'font-family','background-image', 'background-repeat'),
                doc,
                fn;

            ss['font-family'] = 'Tahoma, Arial, Helvetica, Sans-Serif'; // w3c
            ss['background-attachment'] = 'fixed'; // w3c
            dbody.bgProperties = 'fixed'; // ie

            Ext.DomHelper.applyStyles(dbody, ss);

            doc = this.getDoc();

            if(doc){
                try{
                    Ext.EventManager.removeAll(doc);
                }catch(e){}
            }

            /*
             * We need to use createDelegate here, because when using buffer, the delayed task is added
             * as a property to the function. When the listener is removed, the task is deleted from the function.
             * Since onEditorEvent is shared on the prototype, if we have multiple html editors, the first time one of the editors
             * is destroyed, it causes the fn to be deleted from the prototype, which causes errors. Essentially, we're just anonymizing the function.
             */
            fn = this.onEditorEvent.createDelegate(this);
            Ext.EventManager.on(doc, {
                mousedown: fn,
                mouseup: fn,
                dblclick: fn,
                click: fn,
                keyup: fn,
                buffer:100
            });

            if(Ext.isGecko){
                Ext.EventManager.on(doc, 'keypress', this.applyCommand, this);
            }
            if(Ext.isIE || Ext.isWebKit || Ext.isOpera){
                Ext.EventManager.on(doc, 'keydown', this.fixKeys, this);
            }
            doc.editorInitialized = true;
            this.initialized = true;
            this.pushValue();
            this.setReadOnly(this.readOnly);
            this.fireEvent('initialize', this);
            if (!Ext.isIE && !Ext.isNewIE) {
                var font = this.getFontFromSelection();
                if (font) {
                    this.defaultFont = font;
                    doc.body.style.fontFamily = font;
                    this.fontSelectItem.toggle();
                }
                var size = this.getNodeStyle('fontSize');
                if (size) {
                    doc.body.style.fontSize = size;
                    this.fontSelectSize.toggle();
                }
            }
        }catch(e){}
    },

    /**
     * Protected method that will not generally be called directly. It triggers
     * a toolbar update by reading the markup state of the current selection in the editor.
     */
    updateToolbar: function(e){

        if(this.readOnly){
            return;
        }

        if(!this.activated){
            this.onFirstFocus();
            return;
        }

        var btns = this.tb.items.map,
            doc = this.getDoc();

        if(this.enableFont && !Ext.isSafari2){
            if (!Ext.isIE && !Ext.isNewIE) {
                this.fontSelectItem.toggle(e);
            }
            else {
                var name = (doc.queryCommandValue('FontName')||this.defaultFont).toLowerCase();
                if(name != this.fontSelect.dom.value){
                    this.fontSelect.dom.value = name;
                }
            }
        }
        if(this.enableFontSize && !Ext.isSafari2){
            if (!Ext.isIE && !Ext.isNewIE) {
                this.fontSelectSize.toggle(e);
            }
            else {
                var size = (doc.queryCommandValue('FontSize')||this.defaultSize);
                if(size != this.fontSelectS.dom.value){
                    this.fontSelectS.dom.value = this.fontSizesIE[size-1];
                }
            }
        }
        if(this.enableFormat){
            btns.bold.toggle(doc.queryCommandState('bold'));
            btns.italic.toggle(doc.queryCommandState('italic'));
            btns.underline.toggle(doc.queryCommandState('underline'));
        }
        if(!Ext.isSafari2 && this.enableLists){
            btns.insertorderedlist.toggle(doc.queryCommandState('insertorderedlist'));
            btns.insertunorderedlist.toggle(doc.queryCommandState('insertunorderedlist'));
        }

        Ext.menu.MenuMgr.hideAll();

        this.syncValue();
    }

});
Ext.reg('htmleditor', Ext.form.HtmlEditor);