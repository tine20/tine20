/**
 * @author Fernando Lages
 * @class Ext.ux.form.HtmlEditor.TextAlign
 * @extends Ext.util.Observable
 * <p>A plugin that creates a menu on the HtmlEditor to align paragraphs.</p>
 */
Ext.ux.form.HtmlEditor.TextAlign = Ext.extend(Ext.util.Observable, {
    // Table language text
    alignTitle       : 'Text Alignment',
    alignText        : 'Align the text',

    // private
    init: function(cmp){
        this.cmp = cmp;
        this.cmp.on('render', this.onRender, this);
        this.cmp.on('initialize', this.onInitialize, this);
    },

    // private
    onInitialize : function(){
        // Destroying the component during/before initEditor can cause issues.
        try{
            var dbody = this.cmp.getEditorBody(),
                doc,
                fn;

            doc = this.cmp.getDoc();

            /*
             * We need to use createDelegate here, because when using buffer, the delayed task is added
             * as a property to the function. When the listener is removed, the task is deleted from the function.
             * Since onEditorEvent is shared on the prototype, if we have multiple html editors, the first time one of the editors
             * is destroyed, it causes the fn to be deleted from the prototype, which causes errors. Essentially, we're just anonymizing the function.
             */
            fn = this.onEditorClick.createDelegate(this);
            Ext.EventManager.on(doc, {
                mousedown: fn,
                dblclick: fn,
                click: fn,
                keyup: fn,
                buffer:100
            });
        }
        catch(e){}
    },

    // private
    onRender: function(){
        var aligns = {"Left": "Left align", "Center": "Center align", "Right": "Right align", "Full": "Fully justify"};
        var menu = [];
        var checked = true;

        for (var align in aligns) {
            menu.push({
                itemId : 'justify'+align.toLowerCase(),
                enableToggle: true,
                handler: this.cmp.relayBtnCmd,
                text: this._(aligns[align]),
                hideLabel: true,
                iconCls: 'x-edit-justify'+align.toLowerCase(),
                group: 'alignment',
                pressed: true,
                toggle: this.toggleAlignButton,
                scope: this.cmp
            });
        }
        this.btn = this.cmp.getToolbar().addButton({
            xtype: 'tbsplit',
            id: 'textalign_button',
            itemId: 'textalign',
            cls: 'x-btn-icon',
            iconCls: 'x-edit-justifyleft',
            enableToggle: true,
            menu: { items: menu },
            arrowHandler: function(){
                this.onEditorEvent();
            },
            hideBorders: false,
            hideLabel: true,
            showSeparator: true,
            handler: function(){
                this.onEditorEvent();
            },
            scope: this,
            tooltip: {
                title: this._(this.alignTitle),
                text: this._(this.alignText)
            }
        });
    },
    // private
    onEditorClick: function(e) {
        var doc = this.cmp.getDoc();
        Ext.each(this.btn.menu.items, function(b, i){
            b = this.btn.menu.items.get(i);
            if (doc.queryCommandState(b.itemId)) {
                this.btn.btnEl.dom.className = 'x-btn-text x-edit-'+b.itemId;
                this.btn.iconCls = 'x-edit-'+b.itemId;
            }
        }, this);
    },
    // private
    onEditorEvent: function(e) {
        if (e) {
            this.btn.btnEl.dom.className = 'x-btn-text x-edit-'+e.itemId;
            this.btn.iconCls = 'x-edit-'+e.itemId;
        }
        var doc = this.cmp.getDoc();
        Ext.each(this.btn.menu.items, function(b, i){
            b = this.btn.menu.items.get(i);
            b.toggle(doc.queryCommandState(b.itemId));
        }, this);
    },
    /**
     * If a state it passed, it becomes the pressed state otherwise the current state is toggled.
     * @param {Boolean} state (optional) Force a particular state
     * @return {Ext.Button} this
     */
    toggleAlignButton : function(state){
        state = state === undefined ? !this.pressed : !!state;
        if(state !== this.pressed || true) {
            if(this.rendered){
                this.el[state ? 'addClass' : 'removeClass']('x-btn-pressed');
                this.el[state ? 'addClass' : 'removeClass']('x-menu-item-selected');
            }
            this.fireEvent('toggle', this, state);
            if(this.toggleHandler){
                this.toggleHandler.call(this.scope || this, this, state);
            }
        }
        return this;
    },
    // private
    /**
     * Shortcut for translation on Expressomail's scope
     * @param {String} msgid - string to be translated
     * @returns {String} translated string
     */
    _ : function(msgid) {
        return this.cmp.messageEdit.app.i18n._(msgid);
    }
});
