/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.QuickTip
 * @extends Ext.ToolTip
 * @xtype quicktip
 * A specialized tooltip class for tooltips that can be specified in markup and automatically managed by the global
 * {@link Ext.QuickTips} instance.  See the QuickTips class header for additional usage details and examples.
 * @constructor
 * Create a new Tip
 * @param {Object} config The configuration options
 */
Ext.QuickTip = Ext.extend(Ext.ToolTip, {
    /**
     * @cfg {Mixed} target The target HTMLElement, Ext.Element or id to associate with this quicktip (defaults to the document).
     */
    /**
     * @cfg {Boolean} interceptTitles True to automatically use the element's DOM title value if available (defaults to false).
     */
    interceptTitles : false,

    // private
    tagConfig : {
        namespace : "ext",
        attribute : "qtip",
        width : "qwidth",
        target : "target",
        title : "qtitle",
        hide : "hide",
        cls : "qclass",
        align : "qalign",
        anchor : "anchor"
    },

    // private
    initComponent : function(){
        this.target = this.target || Ext.getDoc();
        this.targets = this.targets || {};
        Ext.QuickTip.superclass.initComponent.call(this);
    },

    /**
     * Configures a new quick tip instance and assigns it to a target element.  The following config values are
     * supported (for example usage, see the {@link Ext.QuickTips} class header):
     * <div class="mdetail-params"><ul>
     * <li>autoHide</li>
     * <li>cls</li>
     * <li>dismissDelay (overrides the singleton value)</li>
     * <li>target (required)</li>
     * <li>text (required)</li>
     * <li>title</li>
     * <li>width</li></ul></div>
     * @param {Object} config The config object
     */
    register : function(config){
        var cs = Ext.isArray(config) ? config : arguments;
        for(var i = 0, len = cs.length; i < len; i++){
            var c = cs[i];
            var target = c.target;
            if(target){
                if(Ext.isArray(target)){
                    for(var j = 0, jlen = target.length; j < jlen; j++){
                        this.targets[Ext.id(target[j])] = c;
                    }
                } else{
                    this.targets[Ext.id(target)] = c;
                }
            }
        }
    },

    /**
     * Removes this quick tip from its element and destroys it.
     * @param {String/HTMLElement/Element} el The element from which the quick tip is to be removed.
     */
    unregister : function(el){
        delete this.targets[Ext.id(el)];
    },
    
    /**
     * Hides a visible tip or cancels an impending show for a particular element.
     * @param {String/HTMLElement/Element} el The element that is the target of the tip.
     */
    cancelShow: function(el){
        var at = this.activeTarget;
        el = Ext.get(el).dom;
        if(this.isVisible()){
            if(at && at.el == el){
                this.hide();
            }
        }else if(at && at.el == el){
            this.clearTimer('show');
        }
    },
    
    getTipCfg: function(e) {
        var t = e.getTarget(), 
            ttp, 
            cfg;
        if(this.interceptTitles && t.title && Ext.isString(t.title)){
            ttp = t.title;
            t.qtip = ttp;
            t.removeAttribute("title");
            e.preventDefault();
        }else{
            cfg = this.tagConfig;
            ttp = t.qtip || Ext.fly(t).getAttribute(cfg.attribute, cfg.namespace);
        }
        if (! ttp) {
            t = e.getTarget('.x-grid3-cell-inner')
            if (t && t.offsetWidth - t.scrollWidth > 4) {
                ttp = t.innerHTML;
            }
        }
        return ttp;
    },

    // private
    onTargetOver : function(e){
        if(this.disabled){
            return;
        }
        this.targetXY = e.getXY();
        var t = e.getTarget();
        if(!t || t.nodeType !== 1 || t == document || t == document.body){
            return;
        }
        if(this.activeTarget && ((t == this.activeTarget.el) || Ext.fly(this.activeTarget.el).contains(t))){
            this.clearTimer('hide');
            this.show();
            return;
        }
        if(t && this.targets[t.id]){
            this.activeTarget = this.targets[t.id];
            this.activeTarget.el = t;
            this.anchor = this.activeTarget.anchor;
            if(this.anchor){
                this.anchorTarget = t;
            }
            this.delayShow();
            return;
        }
        var ttp, et = Ext.fly(t), cfg = this.tagConfig, ns = cfg.namespace;
        if(ttp = this.getTipCfg(e)){
            var autoHide = et.getAttribute(cfg.hide, ns);
            this.activeTarget = {
                el: t,
                text: ttp,
                width: et.getAttribute(cfg.width, ns),
                autoHide: autoHide != "user" && autoHide !== 'false',
                title: et.getAttribute(cfg.title, ns),
                cls: et.getAttribute(cfg.cls, ns),
                align: et.getAttribute(cfg.align, ns)
                
            };
            this.anchor = et.getAttribute(cfg.anchor, ns);
            if(this.anchor){
                this.anchorTarget = t;
            }
            this.delayShow();
        }
    },

    // private
    onTargetOut : function(e){

        // If moving within the current target, and it does not have a new tip, ignore the mouseout
        if (this.activeTarget && e.within(this.activeTarget.el) && !this.getTipCfg(e)) {
            return;
        }

        this.clearTimer('show');
        if(this.autoHide !== false){
            this.delayHide();
        }
    },

    // inherit docs
    showAt : function(xy){
        var t = this.activeTarget;
        if(t){
            if(!this.rendered){
                this.render(Ext.getBody());
                this.activeTarget = t;
            }
            if(t.width){
                this.setWidth(t.width);
                this.body.setWidth(this.adjustBodyWidth(t.width - this.getFrameWidth()));
                this.measureWidth = false;
            } else{
                this.measureWidth = true;
            }
            this.setTitle(t.title || '');
            this.body.update(t.text);
            this.autoHide = t.autoHide;
            this.dismissDelay = t.dismissDelay || this.dismissDelay;
            if(this.lastCls){
                this.el.removeClass(this.lastCls);
                delete this.lastCls;
            }
            if(t.cls){
                this.el.addClass(t.cls);
                this.lastCls = t.cls;
            }
            if(this.anchor){
                this.constrainPosition = false;
            }else if(t.align){ // TODO: this doesn't seem to work consistently
                xy = this.el.getAlignToXY(t.el, t.align);
                this.constrainPosition = false;
            }else{
                this.constrainPosition = true;
            }
        }
        Ext.QuickTip.superclass.showAt.call(this, xy);
    },

    // inherit docs
    hide: function(){
        delete this.activeTarget;
        Ext.QuickTip.superclass.hide.call(this);
    }
});
Ext.reg('quicktip', Ext.QuickTip);