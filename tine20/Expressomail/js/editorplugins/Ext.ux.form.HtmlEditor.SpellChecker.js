/**
 * @author Fernando Lages
 * @class Ext.ux.form.HtmlEditor.SpellChecker
 * @extends Ext.util.Observable
 * <p>A plugin that creates a button on the HtmlEditor for GoogieSpell spell checker.</p>
 */
Ext.ux.form.HtmlEditor.SpellChecker = Ext.extend(Ext.util.Observable, {
    // Spell Checker language text
    langTitle: 'Spell Checker',
    langText: 'Verifica a ortografia do texto pelos padrões da linguagem.', //Spell checks texts by choosen language standards.
    spellchecker: null,
    // private
    init: function(cmp){
        this.cmp = cmp;
        this.cmp.on('render', this.onRender, this);
        this.cmp.on('beforedestroy', this.onDestroy, this);
    },
    getLanguages: function() {
        var langs = {"pt_BR": "Portugu&#234;s (Brasil)"};
        langs = Tine.Expressomail.registry.get('aspellDicts') || langs;
        return langs;
    },
    getCurrentLanguage: function() {
        return (this.currLang || "pt_BR");
    },
    setCurrentLanguage: function(lang) {
        this.currLang = lang;
    },
    onDestroy: function() {
        if (this.spellchecker)
            this.spellchecker.hideErrorWindow();
    },
    onRender: function() {
        var cmp = this.cmp;
        var menu = [];
        var langs = this.getLanguages();
        var checked = true;
        for (var dict in langs) {
            checked = (dict=='pt_BR');
            menu.push({
                text: langs[dict],
                iconCls: 'email-icon',
                group: 'language',
                lang: dict,
                checked: checked,
                handler: this.setCurrentLanguage.createDelegate(this,[dict]),
                scope: this
            });
        }
        this.btn = this.cmp.getToolbar().addButton({
            xtype: 'tbsplit',
            id: 'spellchecker_button',
            itemId: 'spellchecker',
            cls: 'x-btn-icon',
            iconCls: 'x-edit-spellchecker',
            enableToggle: true,
            menu: menu,
            handler: function(){
                this.onShow();
                if (this.btn.pressed) {
                    this.cmp.disableItems(true);
                    this.cmp.tb.items.get('sourceedit').setDisabled(true);
                    this.cmp.tb.items.get('spellchecker').setDisabled(false);
                    this.spellchecker.spellCheck(this.getCurrentLanguage());
                }
                else {
                    this.cmp.disableItems(false);
                    this.spellchecker.resumeEditing();
                }
            },
            scope: this,
            tooltip: {
                title: i18n._(this.langTitle),
                text: i18n._(this.langText)
            },
            overflowText: i18n._(this.langTitle)
        });
        this.spellchecker = new Ext.GoogieSpell("Expressomail/images/editorplugins/", "Expressomail.CheckSpelling",this);

        this.spellchecker.lang_chck_spell = "";
        this.setCurrentLanguage("pt_BR");
    },
    onShow: function(){
        this.spellchecker.setButton(this.btn.btnEl);
        this.spellchecker.setIsHTML(this.cmp.getEditorBody());
        this.spellchecker.setEditor(this.cmp);
        this.spellchecker.setTextarea(this.cmp.getEditorBody());
    }
});

/****
 GoogieSpell
     LICENSE
        Creative Commons Attribution-NonCommercial-ShareAlike
        http://creativecommons.org/licenses/by-nc-sa/3.0/
        To use GoogieSpell in commercial products you need to buy a license from here:
        http://orangoo.com/labs/GoogieSpell/Buy_License/
     AUTHOR
         Amir Salihefendic (http://amix.dk) - amix@amix.dk

 Modified: 19/09/13 10:30:00 
 By: Fernando Lages
****/
function GoogieSpell(img_dir, server_url, parentObj) {
//    var cookie_value;
    var lang;
//    cookie_value = getCookie('language');
//
//    if(cookie_value != null)
//        GOOGIE_CUR_LANG = cookie_value;
//    else
//        GOOGIE_CUR_LANG = GOOGIE_DEFAULT_LANG;

    this.img_dir = img_dir;
    this.server_url = server_url;
    this.parentObj = parentObj;
    this.ta_scroll_top = 0;
    this.el_scroll_top = 0;
    this.edit_layer_dbl_click = true;

    this.lang_chck_spell = "Check spelling";
    this.lang_revert = i18n._("Reverter para"); //_("Revert to")
    this.lang_close = i18n._("Close");
    this.lang_rsm_edt = i18n._("Continuar editando"); //_("Resume editing")
    this.lang_no_error_found = i18n._("Nenhum erro ortográfico encontrado"); //_("No spelling errors found")
    this.lang_no_suggestions = i18n._("Sem sugestões"); //_("No suggestions")
    
    //Counters
    this.cnt_errors = 0;
    this.cnt_errors_fixed = 0;

    //Set document on click to hide the language and error menu
    var fn = function(e) {
        var elm = getEventElm(e);
        if (!isDefined(elm)) return;
        if(elm.googie_action_btn != "1" && this.isErrorWindowShown())
            this.hideErrorWindow();
    };
    GoogieSpell.addEventListener(document,"click",fn.createDelegate(this));
}

GoogieSpell.addEventListener = function(obj,ev,argument) {
    if (Ext.isIE) {
        obj.attachEvent("on"+ev,argument);
    }
    else {
        obj.addEventListener(ev,argument,false);
    }
}

// Request functions
GoogieSpell.escapeSepcial = function(val) {
    return val.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

// Spell checking functions
GoogieSpell.prototype.parseResult = function(r_text) {
    //Returns an array
    var re_split_text = /\t/g;

    var matched_c = r_text.results;
    var results = new Array();

    if(matched_c == null)
        return results;
    
    for(var i=0; i < matched_c.length; i++) {
        var item = new Array();
        this.errorFound();

        //Get attributes
        item['attrs'] = {
            'o': matched_c[i].o,
            'l': matched_c[i].l,
            's': matched_c[i].s
        };

        //Get suggestions
        item['suggestions'] = new Array();
        var split_t = matched_c[i].suggestions;
        for(var k=0; k < split_t.length; k++) {
        if(split_t[k] != "")
            item['suggestions'].push(split_t[k]);
        }
        results.push(item);
    }
    return results;
}

// Counters
GoogieSpell.prototype.errorFixed = function() { 
    this.cnt_errors_fixed++; 
}
GoogieSpell.prototype.errorFound = function() {this.cnt_errors++;}

// Error menu functions
GoogieSpell.prototype.createErrorWindow = function() {
    this.error_window = document.createElement('DIV');
    this.error_window.className = "googie_window";
    this.error_window.googie_action_btn = "1";
}

GoogieSpell.prototype.isErrorWindowShown = function() {
    return this.error_window != null && this.error_window.style.visibility == "visible";
}

GoogieSpell.prototype.hideErrorWindow = function() {
    try {
        this.error_window.style.visibility = "hidden";
        if(this.error_window_iframe)
            this.error_window_iframe.style.visibility = "hidden";
    }
    catch(e) {}
}

GoogieSpell.prototype.updateOriginalText = function(offset, old_value, new_value, id) {
    var part_1 = this.original_text.substring(0, offset);
    var part_2 = this.original_text.substring(offset+old_value.length);
    this.original_text = part_1 + new_value + part_2;
    this.setValue(this.text_area, this.original_text);
    var add_2_offset = new_value.length - old_value.length;
    for(var j=0; j < this.results.length; j++) {
        //Don't edit the offset of the current item
        if(j != id && j > id){
            this.results[j]['attrs']['o'] += add_2_offset;
        }
    }
}

GoogieSpell.prototype.saveOldValue = function(elm, old_value) {
    elm.is_changed = true;
    elm.old_value = old_value;
}

GoogieSpell.prototype.correctError = function(id, elm, l_elm, /*optional*/ rm_pre_space) {
    var old_value = elm.innerHTML;
    var new_value = l_elm.innerHTML;
    var offset = this.results[id]['attrs']['o'];

    if(rm_pre_space) {
        var pre_length = elm.previousSibling.innerHTML;
        elm.previousSibling.innerHTML = pre_length.slice(0, pre_length.length-1);
        old_value = " " + old_value;
        offset--;
    }

    this.hideErrorWindow();

    this.updateOriginalText(offset, old_value, new_value, id);

    elm.innerHTML = new_value;
    elm.style.color = "green";
    elm.is_corrected = true;

    this.results[id]['attrs']['l'] = new_value.length;

    if(!isDefined(elm.old_value))
        this.saveOldValue(elm, old_value);
    
    this.errorFixed();
}

GoogieSpell.prototype.showErrorWindow = function(elm, id) {
    var me = this;

    this.error_window.innerHTML = "";

    var table = document.createElement('TABLE');
    table.setAttribute('class', 'googie_list');
    table.googie_action_btn = "1";
    var list = document.createElement('TBODY');

    //Build up the result list
    var suggestions = this.results[id]['suggestions'];
    var offset = this.results[id]['attrs']['o'];
    var len = this.results[id]['attrs']['l'];

    if(suggestions.length == 0) {
        var row = document.createElement('TR');
        var item = document.createElement('TD');
        item.style['cursor'] = 'default';
        var dummy = document.createElement('SPAN');
        dummy.innerHTML = this.lang_no_suggestions;
        AJSappendChildNodes(item, document.createTextNode(dummy.innerHTML));
        item.googie_action_btn = "1";
        row.appendChild(item);
        list.appendChild(row);
    }

    for(i=0; i < suggestions.length; i++) {
        var row = document.createElement('TR');
        var item = document.createElement('TD');
        var dummy = document.createElement('SPAN');
        dummy.innerHTML = suggestions[i];
        item.appendChild(document.createTextNode(dummy.innerHTML));

        var fn = function(e) {
            var l_elm = getEventElm(e);
            this.correctError(id, elm, l_elm);
        };

        GoogieSpell.addEventListener(item,"click", fn.createDelegate(this));

        item.onmouseover = GoogieSpell.item_onmouseover;
        item.onmouseout = GoogieSpell.item_onmouseout;
        row.appendChild(item);
        list.appendChild(row);
    }

//    //Append the edit box
//    var edit_row = document.createElement('DIV');
//    edit_row.style['cursor'] = 'default';
//
//    var edit_input = document.createElement('INPUT');
//    edit_input.style['width'] = '120px'; 
//    edit_input.style['margin'] = '0'; 
//    edit_input.style['padding'] = '0';
//    edit_input.style['value'] = elm.innerHTML;
//    edit_input.googie_action_btn = "1";
//
//    var onsub = function () {
//        if(edit_input.value != "") {
//            if(!isDefined(elm.old_value))
//                this.saveOldValue(elm, elm.innerHTML);
//
//            this.updateOriginalText(offset, elm.innerHTML, edit_input.value, id);
//            elm.style.color = "green";
//            elm.is_corrected = true;
//            elm.innerHTML = edit_input.value;
//
//            this.hideErrorWindow();
//        }
//        return false;
//    };
//
//    var ok_pic = document.createElement('IMG');
//    ok_pic.setAttribute('src', this.img_dir + "ok.gif");
//    ok_pic.style['width'] = '32px'; 
//    ok_pic.style['height'] = '16px'; 
//    ok_pic.style['margin-left'] = '2px'; 
//    ok_pic.style['margin-right'] = '2px'; 
//    ok_pic.style['cursor'] = 'pointer';
//    var edit_form = document.createElement('FORM');
//    edit_form.style['margin'] = '0'; 
//    edit_form.style['padding'] = '0';
//    edit_form.style['cursor'] = 'default;'; 
//    edit_form.appendChild(edit_input);
//    edit_form.appendChild(ok_pic);
//
//    edit_form.googie_action_btn = "1";
//
//    GoogieSpell.addEventListener(edit_form,"submit", onsub.createDelegate(this));
//    GoogieSpell.addEventListener(ok_pic,"click", onsub.createDelegate(this));
//
//    edit_row.appendChild(edit_form);

    table.appendChild(list);
    var div_list = document.createElement('DIV');
    div_list.setAttribute('class', 'googie_divlist');
    div_list.appendChild(table);
    this.error_window.appendChild(div_list);
//    this.error_window.appendChild(edit_row);
    
    //The element has changed, append the revert
    if(elm.is_changed && elm.innerHTML != elm.old_value) {
        var old_value = elm.old_value;

        var fn = function(e) { 
            this.updateOriginalText(offset, elm.innerHTML, old_value, id);
            elm.is_corrected = true;
            elm.style.color = "#b91414";
            elm.innerHTML = old_value;
            this.hideErrorWindow();
        };
        AJSappendChildNodes(this.error_window, this.createRevertButton(fn, old_value));

    }

    //Close button
    AJSappendChildNodes(this.error_window, this.createCloseButton(this.hideErrorWindow));
    var max_height = this.edit_layer.style.height || this.edit_layer.clientHeight;
    max_height = (max_height>this.error_window.clientHeight ? this.error_window.clientHeight : max_height);
    div_list.style.maxHeight = "180px";
    if (this.error_window.offsetTop+this.error_window.clientHeight > Ext.getBody().dom.clientHeight) {
        this.error_window.style.top = (this.error_window.offsetTop - this.error_window.clientHeight - 20)+"px";
    }

    //Dummy for IE - dropdown bug fix
    if((Ext.isIE || Ext.isIE9) && !this.error_window_iframe) {
        var iframe = document.createElement('IFRAME');
        iframe.style['position'] = 'absolute'; 
        iframe.style['z-index'] = '0';
        AJSappendChildNodes(Ext.getBody(), iframe);
        this.error_window_iframe = iframe;
    }
    if(Ext.isIE || Ext.isIE9) {
        var iframe = this.error_window_iframe;
        iframe.style['top'] = this.error_window.offsetTop;
        iframe.style['left'] = this.error_window.offsetLeft;

        iframe.style['width'] = this.error_window.offsetWidth;
        iframe.style['height'] = this.error_window.offsetHeight;

        iframe.style.visibility = "visible";
    }

    //Set focus on the last element --
    var link = this.createFocusLink('link');
    var tr1 = document.createElement('TR');
    var td1 = document.createElement('TD');
    td1.style['text-align'] = 'right'; 
    td1.style['font-size'] = '1px'; 
    td1.style['height'] = '1px'; 
    td1.style['margin'] = '0'; 
    td1.style['padding'] = '0';
    td1.appendChild(link);
    tr1.appendChild(td1);
    list.appendChild(tr1);

    var abs_pos = absolutePosition(elm);
    abs_pos.y -= this.edit_layer.scrollTop;
    this.error_window.style.visibility = "visible";

    var top_pos = abs_pos.y+20;
    if (top_pos+150>this.edit_layer.clientOffset) {
        top_pos = this.edit_layer.clientOffset - 150;
    }
    this.error_window.style.top = (top_pos)+'px';
    this.error_window.style.left = (abs_pos.x)+'px';
    link.focus();

    div_list.scrollTop = 0;
}

// Edit layer (the layer where the suggestions are stored)
GoogieSpell.prototype.createEditLayer = function(width, height) {
    this.edit_layer = document.createElement('DIV');
    this.edit_layer.setAttribute('class','googie_edit_layer');

    //Set the style so it looks like edit areas
    this.edit_layer.className = this.editor.getEditorBody().className;
    this.edit_layer.style.fontFamily = this.editor.getEditorBody().style.fontFamily;
    this.edit_layer.style.fontSize = this.editor.getEditorBody().style.fontSize;
    this.edit_layer.style.border = "none";
    this.edit_layer.style.backgroundColor = "#fff";
    this.edit_layer.style.padding = "3px";
    this.edit_layer.style.margin = "0px";
    this.edit_layer.style.padding = this.editor.getEditorBody().style.padding;
    this.edit_layer.style.margin = this.editor.getEditorBody().style.margin;

    this.edit_layer.style.width = (width-8)+'px';

    if(this.text_area.nodeName.toLowerCase() != "input" || this.getValue(this.text_area) == "") {
        this.edit_layer.style.overflow = "auto";
        this.edit_layer.style.height = (height-6)+'px';
    }
    else {
        this.edit_layer.style.overflow = "hidden";
    }

    if(this.edit_layer_dbl_click) {
        var me = this;
        var fn = function(e) {
            if(getEventElm(e).className != "googie_link" && !me.isErrorWindowShown()) {
                me.resumeEditing();
                var fn1 = function() {
                    me.text_area.focus();
                    fn1 = null;
                };
                setTimeout(fn1, 10);
            }
            return false;
        };
        this.edit_layer.ondblclick = fn;
        fn = null;
    }
}

GoogieSpell.prototype.resumeEditing = function() {
    this.switch_lan_pic.style.display = "inline";

    if(this.edit_layer)
        this.el_scroll_top = this.edit_layer.scrollTop;

    this.hideErrorWindow();

    this.spell_span.className = "googie_no_style";

    if(!this.ignore) {
        //Remove the EDIT_LAYER
        try {
            this.edit_layer.parentNode.removeChild(this.edit_layer);
        }
        catch(e) {
        }

        this.editor.getContainer().style.display = '';

        if(this.el_scroll_top != undefined)
            this.text_area.scrollTop = this.el_scroll_top;
    }

    this.checkSpellingState(false);
}

GoogieSpell.prototype.createErrorLink = function(text, id) {
    var elm = document.createElement('SPAN');
    elm.setAttribute('class', 'googie_link');

    var me = this;
    var d = function (e) {
        me.showErrorWindow(elm, id);
        d = null;
        return false;
    };
    GoogieSpell.addEventListener(elm,"click",d);

    elm.googie_action_btn = "1";
    elm.g_id = id;
    elm.is_corrected = false;
    elm.oncontextmenu = d;
    elm.innerHTML = text;
    return elm;
}

GoogieSpell.createPart = function(txt_part) {
    if(txt_part == " ")
        return document.createTextNode();
    var result = document.createElement('SPAN');

    var is_first = true;
    var is_safari = (navigator.userAgent.toLowerCase().indexOf("safari") != -1);

    var part = document.createElement('SPAN');
    txt_part = GoogieSpell.escapeSepcial(txt_part);
    txt_part = txt_part.replace(/\n/g, "<br>");
    txt_part = txt_part.replace(/    /g, " &nbsp;");
    txt_part = txt_part.replace(/^ /g, "&nbsp;");
    txt_part = txt_part.replace(/ $/g, "&nbsp;");
    
    part.innerHTML = txt_part;

    return part;
}

GoogieSpell.prototype.showErrorsInIframe = function() {
    var output = document.createElement('DIV');
    output.style.textAlign = "left";
    var pointer = 0;
    var results = this.results;

    if(results.length > 0) {
        // HTML support
        var outputBuffer = '';
        for(var i=0; i < results.length; i++) {
            var offset = results[i]['attrs']['o'];
            var len = results[i]['attrs']['l'];
            
            var part_1_text = this.original_text.substring(pointer, offset);
            // HTML support
            if (this.getIsHTML()) {
                outputBuffer += part_1_text;
            } else {
                var part_1 = GoogieSpell.createPart(part_1_text);
                output.appendChild(part_1);
            }
            pointer += offset - pointer;
            
            //If the last child was an error, then insert some space
            var err_link = this.createErrorLink(this.original_text.substr(offset, len), i);
            this.error_links.push(err_link);
            // HTML support
            if (this.getIsHTML()) {
                // placeholder for insertErrLinks()
                outputBuffer += '<var class="err_hook">&nbsp;</var> ';
            } else {
                output.appendChild(err_link);
            }
            pointer += len;
        }
        //Insert the rest of the original text
        var part_2_text = this.original_text.substr(pointer, this.original_text.length);

        // HTML support
        if (this.getIsHTML()) {
            outputBuffer += part_2_text;
            output.innerHTML = outputBuffer;
            this.insertErrLinks(output, this.error_links);
        } else {
            var part_2 = GoogieSpell.createPart(part_2_text);
            output.appendChild(part_2);
        }
    }
    else
        output.innerHTML = this.original_text;

    AJSappendChildNodes(this.edit_layer,output);

    //Hide text area
    this.text_area_bottom = this.text_area.offsetTop + this.text_area.offsetHeight;

    this.editor.getContainer().style['display'] = 'none';

    this.editor.getContainer().parentNode.insertBefore(this.edit_layer, this.editor.getContainer());

    this.edit_layer.scrollTop = this.ta_scroll_top;
}

// Choose language menu
GoogieSpell.prototype.createSpellDiv = function() {
    var chk_spell = document.createElement('SPAN');
    chk_spell.setAttribute('class','googie_check_spelling_link');

    chk_spell.innerHTML = this.lang_chck_spell;
    var spell_img = null;
    spell_img = document.createElement('IMG');
    spell_img.setAttribute('src',this.img_dir + "spellc.gif");
    var span = document.createElement('SPAN');
    span.innerHTML = spell_img + chk_spell;
    return span;
}

// State functions
GoogieSpell.prototype.flashNoSpellingErrorState = function() {
    Ext.getCmp('spellchecker_button').toggle();
    this.parentObj.cmp.disableItems(false);
    this.resumeEditing();
    Ext.MessageBox.alert(i18n._('Spell Checker'), i18n._('Nenhum erro ortográfico encontrado')); //_("No spelling errors found")
}

GoogieSpell.prototype.resumeEditingState = function() {
    //Change link text to resume
    this.switch_lan_pic.style['display'] = 'none';
    var dummy = document.createElement('IMG');
    dummy.setAttribute('src', this.img_dir + "blank.gif");
    dummy.style['height'] = '16px'; 
    dummy.style['width'] = '1px';
    var rsm = document.createElement('SPAN');
    rsm.innerHTML = this.lang_rsm_edt;
    var span = document.createElement('SPAN');
    span.appendChild(dummy);
    span.appendChild(rsm);
    replaceChildNodes(this.spell_span, span);

    var fn = function(e) {
        this.resumeEditing();
    }
    this.spell_span.onclick = fn.createDelegate(this);

    this.spell_span.className = "googie_resume_editing";

    try {this.edit_layer.scrollTop = this.ta_scroll_top;}
    catch(e) { }
}

GoogieSpell.prototype.checkSpellingState = function(fire) {
    this.switch_lan_pic = document.createElement('SPAN');

    var span_chck = this.createSpellDiv();
    var fn = function() {
        this.spellCheck();
    };

    span_chck.onclick = fn.createDelegate(this);

    this.spell_span = span_chck;
    replaceChildNodes(this.spell_container, span_chck, document.createElement('SPAN'), this.switch_lan_pic);
}

// Misc. functions
GoogieSpell.item_onmouseover = function(e) {
    var elm = getEventElm(e);
    if(elm.className != "googie_list_revert" && elm.className != "googie_list_close")
        elm.className = "googie_list_onhover";
    else
        elm.parentNode.className = "googie_list_onhover";
}
GoogieSpell.item_onmouseout = function(e) {
    var elm = getEventElm(e);
    if(elm.className != "googie_list_revert" && elm.className != "googie_list_close")
        elm.className = "googie_list_onout";
    else
        elm.parentNode.className = "googie_list_onout";
}

GoogieSpell.prototype.createRevertButton = function(c_fn, old_value) {
    return this.createButton(this.lang_revert + " " + old_value, 'googie_list_revert', c_fn.createDelegate(this));
}

GoogieSpell.prototype.createCloseButton = function(c_fn) {
    return this.createButton(this.lang_close, 'googie_list_close', c_fn.createDelegate(this));
}

GoogieSpell.prototype.createButton = function(name, css_class, c_fn) {
    var btn = document.createElement('DIV');

    btn.onmouseover = GoogieSpell.item_onmouseover;
    btn.onmouseout = GoogieSpell.item_onmouseout;

    var spn_btn;
    if(css_class != "") {
        spn_btn = document.createElement('SPAN');
        spn_btn.setAttribute('class', css_class);
        spn_btn.innerHTML = name;
    }
    else {
        spn_btn = document.createTextNode(name);
    }
    btn.appendChild(spn_btn);
    GoogieSpell.addEventListener(btn,"click", c_fn);

    return btn;
}

GoogieSpell.prototype.createFocusLink = function(name) {
    var a1 = document.createElement('A');
    a1.setAttribute('href', 'javascript:;');
    a1.setAttribute('name', 'name');
    return a1;
}

function setCookie(name, value, expires, path, domain, secure) {
  var curCookie = name + "=" + escape(value) +
      ((expires) ? "; expires=" + expires.toGMTString() : "") +
      ((path) ? "; path=" + path : "") +
      ((domain) ? "; domain=" + domain : "") +
      ((secure) ? "; secure" : "");
  document.cookie = curCookie;
}

function getCookie(name) {
  var dc = document.cookie;
  var prefix = name + "=";
  var begin = dc.indexOf("; " + prefix);
  if (begin == -1) {
    begin = dc.indexOf(prefix);
    if (begin != 0) return null;
  } else
    begin += 2;
  var end = document.cookie.indexOf(";", begin);
  if (end == -1)
    end = dc.length;
  return unescape(dc.substring(begin + prefix.length, end));
}

function getKeys(obj) {
    var keys = [];
    for (var prop in obj) {
        keys.push(prop);
    }
    return keys;
}

function getValues(obj) {
    var values = [];
    for (var prop in obj) {
        values.push(obj[prop]);
    }
    return values;
}

function isDefined(o) {
    return (o != "undefined" && o != null)
}

function getEventElm(e) {
    if(e && !e.type && !e.keyCode)
        return e;
    var targ;
    if (!e) var e = window.event;
    if (!e) return e;
    if (e.target) 
        targ = e.target;
    else if (e.srcElement) 
        targ = e.srcElement;
    if (targ && targ.nodeType == 3) // defeat Safari bug
        targ = targ.parentNode;
    return targ;
}

function map(list, fn,/*optional*/ start_index, end_index) {
    var i = 0, l = list.length;
    if(start_index)
            i = start_index;
    if(end_index)
            l = end_index;
    for(i; i < l; i++) {
        var val = fn(list[i], i);
        if(val != undefined)
            return val;
    }
}

function isString(obj) {
    return (typeof obj == 'string');
}

function isNumber(obj) {
    return (typeof obj == 'number');
}

function AJSappendChildNodes(elm/*, elms...*/) {
    if(arguments.length >= 2) {
        map(arguments, function(n) {
            if(isString(n))
                n = document.createTextNode(n);
            if(isDefined(n))
                elm.appendChild(n);
        }, 1);
    }
    return elm;
}

function replaceChildNodes(elm/*, elms...*/) {
    var child;
    while ((child = elm.firstChild))
        swapDOM(child, null);

    if (arguments.length < 2)
        return elm;
    else
        return AJSappendChildNodes.apply(null, arguments);
    return elm;
}

function swapDOM(dest, src) {
    dest = getElement(dest);
    var parent = dest.parentNode;
    if (src) {
        src = getElement(src);
        parent.replaceChild(src, dest);
    } else {
        parent.removeChild(dest);
    }

    return src;
}

function getElement(id) {
    if(isString(id) || isNumber(id))
        return document.getElementById(id);
    else
        return id;
}

function absolutePosition(elm) {
    if(!elm)
        return {x: 0, y: 0};

    if(elm.scrollLeft)
        return {x: elm.scrollLeft, y: elm.scrollTop};
    else if(elm.clientX)
        return {x: elm.clientX, y: elm.clientY};

    var posObj = {'x': elm.offsetLeft, 'y': elm.offsetTop};

    if(elm.offsetParent) {
        var next = elm.offsetParent;
        while(next) {
            posObj.x += next.offsetLeft;
            posObj.y += next.offsetTop;
            next = next.offsetParent;
        }
    }
    // safari bug
    if (Ext.isSafari && elm.style.position == 'absolute' ) {
        posObj.x -= document.body.offsetLeft;
        posObj.y -= document.body.offsetTop;
    }
    return posObj;
}

// extends GoogieSpell
Ext.GoogieSpell = Ext.extend(GoogieSpell, {
    editor: null,
    setButton: function(btn) {
        this.btnEl = btn;
    },
    setEditor: function(ed){
        this.editor = ed;
    },
    setTextarea: function(id){
        if(typeof(id) == "string") {
            this.text_area = Ext.get(id);
        }
        else {
            this.text_area = id;
        }

        var r_width, r_height;

        if(this.text_area != null) {
            if(!Ext.isDefined(this.spell_container)) {
                var spell_container = new Ext.Element(document.createElement('TD'));
                this.spell_container = spell_container;
            }
            this.checkSpellingState();
        }
        else {
            Ext.MessageBox.alert(i18n._('Errors'), i18n._("Área de edição não encontrada."));
        }
    },
    getEditAreaValue: function(ta){
        return (ta.value ? ta.value : this.editor.getValue());
    },
    getValue: function(ta) {
        return this.getEditAreaValue(ta);
    },
    setValue: function(ta, value) {
        if (this.getIsHTML()) {
            this.editor.setValue(value);
        } 
        else {
            ta.value = value;
        }
    },
    spellCheck: function(lang) {
        var me = this;

        this.cnt_errors_fixed = 0;
        this.cnt_errors = 0;

        this.original_text = this.getValue(this.text_area);
        
        this.btnEl.addClass("loading-indicator");

        this.error_links = [];
        this.ta_scroll_top = this.text_area.scrollTop;

        this.createEditLayer(this.editor.getContainer().clientWidth,this.editor.getContainer().offsetHeight);

        this.createErrorWindow();
        Ext.getBody().appendChild(this.error_window);

        try {netscape.security.PrivilegeManager.enablePrivilege("UniversalBrowserRead");} 
        catch (e) { }

        this.spell_span.onclick = null;

        this.document_fragment = document.createDocumentFragment();
        var div = document.createElement('div');
        div.innerHTML = this.original_text;
        this.document_fragment.appendChild(div);

        //Create request
        var params = {
            application: 'Expressomail',
            method: 'Expressomail.checkSpelling',
            lang: lang,
            data: this.original_text
        };
        Ext.Ajax.request({
            params: params,
            scope: this,
            success: function(res_txt) {
                var r_text = Ext.util.JSON.decode(res_txt.responseText);
                this.btnEl.removeClass("loading-indicator");
                if (r_text.error == 0) {
                    me.results = me.parseResult(r_text);

                    if(me.results != null && me.results.length>0) {
                        //Before parsing be sure that errors were found
                        me.showErrorsInIframe();
                        me.resumeEditingState();
                    }
                    else {
                        me.flashNoSpellingErrorState();
                    }
                }
                else {
                    var err_desc = r_text.description ? r_text.description : i18n._("Erro inesperado na verificação ortográfica. Tente novamente mais tarde.");
                    Ext.MessageBox.alert(i18n._('Spell Checker'), err_desc);
                }
                this.btnEl.dom.style.backgroundImage = '';
            },
            failure: function(res_txt, req) {
                this.btnEl.removeClass("loading-indicator");
                Ext.MessageBox.alert(i18n._('Spell Checker'), i18n._("Um erro ocorreu no servidor. Tente novamente mais tarde."));

                me.checkSpellingState();
                this.btnEl.dom.style['background-image'] = btn_image;
            }
        });
    },
    //GoogieSpell HTML support / switch between modes
    isHTML: false,
    setIsHTML: function(el) {
        var origSupport = new RegExp('input|textarea', 'i');
        this.isHTML = origSupport.test(el.nodeName) ? false : true;
    },
    getIsHTML: function() {
        return this.isHTML;
    },
    insertErrLinks: function(output, err_links) {
        var all_vars = output.getElementsByTagName('var');
        var all_vars_len = all_vars.length;
        var err_count = 0;
        var err_len = err_links.length;
        for (var i = 0; i < all_vars_len; i++) {
            if (all_vars[i].className == 'err_hook') {
                //	Replace &nbsp; with span.    is needed for IE implementation of innerHTML
                all_vars[i].replaceChild(err_links[err_count], all_vars[i].firstChild);
                err_count++;
                if (err_count == err_len) break;
            }
        }
    }
});
