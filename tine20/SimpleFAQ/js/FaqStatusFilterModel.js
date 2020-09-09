/**
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.SimpleFAQ');

/**
 * @namespace   Tine.SimpleFAQ
 * @class       Tine.SimpleFAQ.FaqStatusFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 *
 */
Tine.SimpleFAQ.FaqStatusFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
   
   field: 'faqstatus_id',
   defaultOperator: 'in',

   /**
    * @private
    */
   initComponent: function() {
       this.label = this.app.i18n._('FAQ Status');
       this.operators = ['in', 'notin'];

       //@todo pr: add this.defaultValue ....
       
       Tine.SimpleFAQ.FaqStatusFilterModel.superclass.initComponent.call(this);
   },

   /**
    * value renderer
    *
    * @param {Ext.data.Record} filter line
    * @param {Ext.Element} element to render to
    */
   valueRenderer: function(filter, el){
       var value = new Tine.SimpleFAQ.FaqStatusFilterModelValueField({
           app: this.app,
           filter: filter,
           //@todo pr: add default value
           //value: filter.data.value ? filter.data.value : this.defaultValue,
           value: filter.data.value,
           renderTo: el
       });
       value.on('specialkey', function(field, e){
             if(e.getKey() == e.ENTER){
                 this.onFiltertrigger();
             }
        }, this);
        value.on('select', this.onFiltertrigger, this);

        return value;
   }
});

Tine.widgets.grid.FilterToolbar.FILTERS['simplefaq.faqstatus'] = Tine.SimpleFAQ.FaqStatusFilterModel;

/**
 * @namespace   Tine.SimpleFAQ
 * @class       Tine.SimpleFAQ.FaqStatusFilterModelValueField
 * @extends     Ext.ux.form.LayerCombo
 * 
 */
Tine.SimpleFAQ.FaqStatusFilterModelValueField = Ext.extend(Ext.ux.form.LayerCombo, {
   hideButtons: false,
   formConfig: {
       labelAlign: 'left',
       labelWidth: 30
   },
   
   getFormValue: function(){
       var ids = [];
       var statusStore = Tine.SimpleFAQ.FaqStatus.getStore();

       var formValues = this.getInnerForm().getForm().getValues();
       for (var id in formValues){
           if(formValues[id] === 'on' && statusStore.getById(id)){
               ids.push(id);
           }
       }

       return ids;
   },

   getItems: function(){
       var items = [];

       Tine.SimpleFAQ.FaqStatus.getStore().each(function(status){
           items.push({
               xtype: 'checkbox',
               boxLabel: status.get('faqstatus'),
               icon: status.get('status_icon'),
               name: status.get('id')
           });
       }, this);

       return items;
   },

   /**
    * @param {String} value
    * @return {Ext.form.Field} this
    */
   setValue: function(value) {
       value = Ext.isArray(value) ? value : [value];
       
       var statusText = [];
       this.currentValue = [];

       Tine.SimpleFAQ.FaqStatus.getStore().each(function(status){
           var id = status.get('id');
           var name = status.get('faqstatus');

           Ext.each(value, function(valueId){
               if(valueId == id) {
                   statusText.push(name);
                   this.currentValue.push(id);
               }
           }, this);
       }, this);

       this.setRawValue(statusText.join(', '));

       return this;
   },

    /**
     * sets values to innerForm
     */
   setFormValue: function(value) {
        this.getInnerForm().getForm().items.each(function(item) {
            item.setValue(value.indexOf(item.name) >= 0 ? 'on' : 'off');
        }, this);
    }
});