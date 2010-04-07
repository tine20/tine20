/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Ext.ns('Ext.ux.form.');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.LayerCombo
 * @extends     Ext.form.TriggerField
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 * 
 * trigger field with support for extra layer on trigger click
 */
Ext.ux.form.LayerCombo = function(config) {
    Ext.ux.form.LayerCombo.superclass.constructor.call(this, config);
    
    this.addEvents(
        /**
         * @event beforecollapse
         * Fires before the layer is collapsed by calling the {@link #collapse} method.
         * Return false from an event handler to stop the collapse.
         * @param {LayerCombo} this
         */
        'beforecollapse'
    );
};

Ext.extend(Ext.ux.form.LayerCombo, Ext.form.TriggerField, {
    /**
     * @cfg {Boolean} hideButton Hide standard form buttons 
     */
    hideButtons: false,
    /**
     * @cfg {Object} config for innerForm
     */
    formConfig: null,
    /**
     * @cfg {Boolean} lazyInit <tt>true</tt> to not initialize the list for this combo until the field is focused
     * (defaults to <tt>true</tt>)
     */
    lazyInit : true,
    /**
     * @cfg {String} triggerClass An additional CSS class used to style the trigger button.  The trigger will always
     * get the class <tt>'x-form-trigger'</tt> and <tt>triggerClass</tt> will be <b>appended</b> if specified
     * (defaults to <tt>'x-form-arrow-trigger'</tt> which displays a downward arrow icon).
     */
    triggerClass : 'x-form-arrow-trigger',
    /**
     * @cfg {Boolean/String} shadow <tt>true</tt> or <tt>"sides"</tt> for the default effect, <tt>"frame"</tt> for
     * 4-way shadow, and <tt>"drop"</tt> for bottom-right
     */
    shadow : 'sides',
    /**
     * @cfg {String/Array} layerAlign A valid anchor position value. See <tt>{@link Ext.Element#alignTo}</tt> for details
     * on supported anchor positions and offsets. To specify x/y offsets as well, this value
     * may be specified as an Array of <tt>{@link Ext.Element#alignTo}</tt> method arguments.</p>
     * <pre><code>[ 'tl-bl?', [6,0] ]</code></pre>(defaults to <tt>'tr-br?'</tt>)
     */
    layerAlign : 'tl-bl?',
    /**
     * @cfg {String} layerClass The CSS class to add to the predefined <tt>'x-combo-layer'</tt> class
     * applied the dropdown layer element (defaults to '').
     */
    layerClass : '',
    /**
     * @cfg {Number} minLayerWidth The minimum width of the dropdown layer in pixels (defaults to <tt>70</tt>, will
     * be ignored if <tt>{@link #layerWidth}</tt> has a higher value)
     */
    minLayerWidth : 70,
    /**
     * @cfg {Number} layerWidth The width (used as a parameter to {@link Ext.Element#setWidth}) of the dropdown
     * layer (defaults to the width of the ComboBox field).  See also <tt>{@link #minLayerWidth}</tt>
     */
    /**
     * @cfg {Number} minLayerHeight The minimum height of the dropdown layer in pixels (defaults to <tt>70</tt>, will
     * be ignored if <tt>{@link #layerHeight}</tt> has a higher value)
     */
    minLayerHeight: 70,
    /**
     * @cfg {Number} layerHeight The width (used as a parameter to {@link Ext.Element#setHeight}) of the dropdown
     * layer (defaults to the width of the ComboBox field).  See also <tt>{@link #minLayerHeight}</tt>
     */
    
    /**
     * @property currentValue
     * @type mixed
     */
    currentValue: null,
    
    editable: false,
    
    /**
     * Hides the dropdown layer if it is currently expanded. Fires the {@link #collapse} event on completion.
     */
    collapse: function() {
        if(!this.isExpanded()){
            return;
        }
        if(this.fireEvent('beforecollapse', this) !== false){
            this.layer.hide();
            Ext.getDoc().un('mousewheel', this.collapseIf, this);
            Ext.getDoc().un('mousedown', this.collapseIf, this);
            this.fireEvent('collapse', this);
        }
    },
    
    
    /**
     * @private
     * @param {Ext.EventObject} e
     */
    collapseIf : function(e){
        if(!e.within(this.wrap) && !e.within(this.layer)){
            this.collapse();
        }
    },
    
    /**
     * this fn is here so this filed is detected like a combo
     * @private
     */
    doQuery: Ext.emptyFn,
    
    /**
     * Expands the dropdown layer if it is currently hidden. Fires the {@link #expand} event on completion.
     */
    expand: function() {
        if(this.isExpanded() || !this.hasFocus){
            return;
        }
        
        //var v = this.getValue();
        //this.startValue = this.getValue();
        this.setFormValue(this.currentValue);
        this.layer.alignTo.apply(this.layer, [this.el].concat(this.layerAlign));
        this.layer.show();
        if(Ext.isGecko2){
            this.innerLayer.setOverflow('auto'); // necessary for FF 2.0/Mac
        }
        this.mon(Ext.getDoc(), {
            scope: this,
            mousewheel: this.collapseIf,
            mousedown: this.collapseIf
        });
        this.fireEvent('expand', this);
    },
    
    /**
     * get values from form
     * 
     * @return {mixed}
     */
    getFormValue: function() {
        var formValues = this.getInnerForm().getForm().getValues();
        return formValues;
    },
    
    /**
     * template fn for subclasses to return inner form items
     */
    getItems: function() {
        return [];
    },
    
    /**
     * <p>Returns the element used to house this ComboBox's pop-up layer. Defaults to the document body.</p>
     * A custom implementation may be provided as a configuration option if the floating layer needs to be rendered
     * to a different Element. An example might be rendering the layer inside a Menu so that clicking
     * the layer does not hide the Menu
     */
    getLayerParent : function() {
        return document.body;
    },
    
    /**
     * returns inner form of the layer
     * 
     */
    getInnerForm: function() {
        if (! this.innerForm) {
            this.innerForm = new Ext.form.FormPanel(Ext.apply({
                //labelWidth: 30,
                height: this.layerHeight || 'auto',
                border: false,
                cls: 'tw-editdialog',
                items: this.getItems(),
                buttonAlign: 'right',
                buttons: this.hideButtons ? false : [{
                    text: _('Cancel'),
                    scope: this,
                    handler: this.onCancel,
                    iconCls: 'action_cancel'
                }, {
                    text: _('Ok'),
                    scope: this,
                    handler: this.onOk,
                    iconCls: 'action_saveAndClose'
                }]
            }, this.formConfig));
        }
        
        return this.innerForm;
    },
    
    /**
     * Returns the currently selected field value or empty string if no value is set.
     * @return {String} value The selected value
     */
    getValue: function() {
        return this.currentValue;
    },
    
    initComponent: function() {
        Ext.ux.form.LayerCombo.superclass.initComponent.apply(this, arguments);
    },

    /**
     * init the dropdown layer
     * 
     * @private
     */
    initLayer: function() {
        if(!this.layer){
            var cls = 'ux-layercombo-layer',
                layerParent = Ext.getDom(this.getLayerParent() || Ext.getBody()),
                zindex = parseInt(Ext.fly(layerParent).getStyle('z-index') ,10);

            if (this.ownerCt && !zindex){
                this.findParentBy(function(ct){
                    zindex = parseInt(ct.getPositionEl().getStyle('z-index'), 10);
                    return !!zindex;
                });
            }

            this.layer = new Ext.Layer({
                parentEl: layerParent,
                shadow: this.shadow,
                cls: [cls, this.layerClass].join(' '),
                constrain:false,
                zindex: (zindex || 8000) + 5
            });

            var lw = this.layerWidth || Math.max(this.wrap.getWidth(), this.minLayerWidth);
            this.layer.setSize(lw, 0);
            this.assetHeight = 0;
            if(this.syncFont !== false){
                this.layer.setStyle('font-size', this.el.getStyle('font-size'));
            }
            if(this.title){
                this.header = this.layer.createChild({cls:cls+'-hd', html: this.title});
                this.assetHeight += this.header.getHeight();
            }

            this.innerLayer = this.layer.createChild({cls:cls+'-inner'});
            //this.mon(this.innerLayer, 'mouseover', this.onViewOver, this);
            //this.mon(this.innerLayer, 'mousemove', this.onViewMove, this);
            this.innerLayer.setWidth(lw - this.layer.getFrameWidth('lr'));
            
            var innerForm = this.getInnerForm();
            innerForm.render(this.innerLayer);
            
            this.setLayerHeight(this.layerHeight ? this.layerHeight : 'auto');
        }
    },
    
    /**
     * Returns true if the dropdown layer is expanded, else false.
     */
    isExpanded : function(){
        return this.layer && this.layer.isVisible();
    },
    
    /**
     * cancel handler
     */
    onCancel: function() {
        this.setValue(this.currentValue);
        this.collapse();
    },
    
    /**
     * do cleanup
     * 
     * @private
     */
    onDestroy : function(){
       if (this.dqTask){
           this.dqTask.cancel();
           this.dqTask = null;
       }
       Ext.destroy(
           this.resizer,
           this.layer
       );
       Ext.destroyMembers(this, 'hiddenField');
       Ext.form.ComboBox.superclass.onDestroy.call(this);
   },
 
    /**
     * ok handler
     */
    onOk: function() {
        this.collapse();
        var v = this.getFormValue();
        this.setValue(v);
        
        this.fireEvent('select', this, v);
        this.startValue = v;
    },
    
    /**
     * executed onRender
     * 
     * @private
     * 
     * @param {Ext.Container} ct
     * @param {Number} position
     */
    onRender : function(ct, position){
        if(this.hiddenName && !Ext.isDefined(this.submitValue)){
            this.submitValue = false;
        }
        Ext.ux.form.LayerCombo.superclass.onRender.apply(this, arguments);
        if(this.hiddenName){
            this.hiddenField = this.el.insertSibling({tag:'input', type:'hidden', name: this.hiddenName,
                    id: (this.hiddenId||this.hiddenName)}, 'before', true);

        }
        if(Ext.isGecko){
            this.el.dom.setAttribute('autocomplete', 'off');
        }

        if(!this.lazyInit){
            this.initLayer();
        }else{
            this.on('focus', this.initLayer, this, {single: true});
        }
    },
    
    /**
     * Implements the default empty TriggerField.onTriggerClick function
     * 
     * @private
     */
    onTriggerClick : function(){
        if (this.readOnly || this.disabled){
            return;
        }
        if(this.isExpanded()){
            this.collapse();
            this.el.focus();
        } else {
            this.onFocus({});
            this.expand()
        }
    },
    
    /**
     * set layer height
     * 
     * @param {Number} height
     */
    setLayerHeight: function(height) {
        if (! Ext.isNumber(height)) {
            height = this.innerForm.getHeight();
        }
        this.innerLayer.dom.style.height = '';
        var pad = this.layer.getFrameWidth('tb') + (this.resizable ? this.handleHeight : 0) + this.assetHeight;

        this.innerLayer.setHeight(height);
        this.layer.beginUpdate();
        this.layer.setHeight(height+pad);
        this.layer.alignTo.apply(this.layer, [this.el].concat(this.layerAlign));
        this.layer.endUpdate();
    },
    
    /**
     * Sets the specified value into the field.  If the value finds a match, the corresponding record text
     * will be displayed in the field.  If the value does not match the data value of an existing item,
     * and the valueNotFoundText config option is defined, it will be displayed as the default field text.
     * Otherwise the field will be blank (although the value will still be set).
     * 
     * @param {String} value The value to match
     * @return {Ext.form.Field} this
     */
    setValue: function(value) {
        this.currentValue = value;
        return this;
    },
    
    /**
     * sets values to innerForm
     */
    setFormValue: function(value) {
        this.getInnerForm().getForm().setValues(value);
    }
});