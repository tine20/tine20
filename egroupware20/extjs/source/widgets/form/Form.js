/*
 * Ext JS Library 2.0 Alpha 1
 * Copyright(c) 2006-2007, Ext JS, LLC.
 * licensing@extjs.com
 * 
 * http://extjs.com/license
 */

/**
 * @class Ext.form.FormPanel
 * @extends Ext.Panel
 * Standard form container.
 * <p><b>Although they are not listed, this class also accepts all the config options required to configure its internal {@link Ext.form.BasicForm}</b></p>
 * <br><br>
 * By default, Ext Forms are submitted through Ajax, using {@link Ext.form.Action}.
 * To enable normal browser submission of an Ext Form, override the Form's onSubmit,
 * and submit methods:<br><br><pre><code>
    var myForm = new Ext.form.Form({
        onSubmit: Ext.emptyFn,
        submit: function() {
            this.getEl().dom.submit();
        }
    });</code></pre><br>
 * @constructor
 * @param {Object} config Configuration options
 */

Ext.FormPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Number} labelWidth The width of labels. This property cascades to child containers.
     */
    /**
     * @cfg {String} itemCls A css class to apply to the x-form-item of fields. This property cascades to child containers.
     */
    /**
     * @cfg {String} buttonAlign Valid values are "left," "center" and "right" (defaults to "center")
     */
    buttonAlign:'center',

    /**
     * @cfg {Number} minButtonWidth Minimum width of all buttons in pixels (defaults to 75)
     */
    minButtonWidth:75,

    /**
     * @cfg {String} labelAlign Valid values are "left," "top" and "right" (defaults to "left").
     * This property cascades to child containers if not set.
     */
    labelAlign:'left',

    /**
     * @cfg {Boolean} monitorValid If true the form monitors its valid state <b>client-side</b> and
     * fires a looping event with that state. This is required to bind buttons to the valid
     * state using the config value formBind:true on the button.
     */
    monitorValid : false,

    /**
     * @cfg {Number} monitorPoll The milliseconds to poll valid state, ignored if monitorValid is not true (defaults to 200)
     */
    monitorPoll : 200,

    layout: 'form',

    initComponent :function(){
        this.form = this.createForm();
        
        Ext.FormPanel.superclass.initComponent.call(this);

        this.addEvents({
            /**
             * @event clientvalidation
             * If the monitorValid config option is true, this event fires repetitively to notify of valid state
             * @param {Form} this
             * @param {Boolean} valid true if the form has passed client-side validation
             */
            clientvalidation: true
        });

        this.relayEvents(this.form, ['beforeaction', 'actionfailed', 'actioncomplete']);
    },

    createForm: function(){
        return new Ext.form.BasicForm(null, this.initialConfig);
    },

    initFields : function(){
        var f = this.form;
        var formPanel = this;
        var fn = function(c){
            if(c.doLayout && c != formPanel){
                Ext.applyIf(c, {
                    labelAlign: c.ownerCt.labelAlign,
                    labelWidth: c.ownerCt.labelWidth,
                    itemCls: c.ownerCt.itemCls
                });
                if(c.items){
                    c.items.each(fn);
                }
            }else if(c.isFormField){
                f.add(c);
            }
        }
        this.items.each(fn);
    },

    getLayoutTarget : function(){
        return this.form.el;
    },

    getForm : function(){
        return this.form;
    },

    onRender : function(ct, position){
        this.initFields();

        Ext.FormPanel.superclass.onRender.call(this, ct, position);
        var o = {
            tag: 'form',
            method : this.method || 'POST',
            id : this.formId || Ext.id()
        };
        if(this.fileUpload) {
            o.enctype = 'multipart/form-data';
        }
        this.form.initEl(this.body.createChild(o));
    },

    initEvents : function(){
        Ext.FormPanel.superclass.initEvents.call(this);

        if(this.monitorValid){ // initialize after render
            this.startMonitoring();
        }
    },

    /**
     * Starts monitoring of the valid state of this form. Usually this is done by passing the config
     * option "monitorValid"
     */
    startMonitoring : function(){
        if(!this.bound){
            this.bound = true;
            Ext.TaskMgr.start({
                run : this.bindHandler,
                interval : this.monitorPoll || 200,
                scope: this
            });
        }
    },

    /**
     * Stops monitoring of the valid state of this form
     */
    stopMonitoring : function(){
        this.bound = false;
    },

    load : function(){
        this.form.load.apply(this.form, arguments);  
    },

    // private
    bindHandler : function(){
        if(!this.bound){
            return false; // stops binding
        }
        var valid = true;
        this.form.items.each(function(f){
            if(!f.isValid(true)){
                valid = false;
                return false;
            }
        });
        if(this.buttons){
            for(var i = 0, len = this.buttons.length; i < len; i++){
                var btn = this.buttons[i];
                if(btn.formBind === true && btn.disabled === valid){
                    btn.setDisabled(!valid);
                }
            }
        }
        this.fireEvent('clientvalidation', this, valid);
    }
});
Ext.reg('form', Ext.FormPanel);

Ext.form.FormPanel = Ext.FormPanel;

