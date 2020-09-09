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
 * @class       Tine.SimpleFAQ.FaqTypeFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 *
 */
Tine.SimpleFAQ.FaqTypeFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
   
   field: 'faqtype_id',
   defaultOperator: 'in',

   /**
    * @private
    */
   initComponent: function() {
       this.label = this.app.i18n._('FAQ Type');
       this.operators = ['in', 'notin'];

       //@todo pr: add this.defaultValue ....
       
       Tine.SimpleFAQ.FaqTypeFilterModel.superclass.initComponent.call(this);
   },

   /**
    * value renderer
    *
    * @param {Ext.data.Record} filter line
    * @param {Ext.Element} element to render to
    */
   valueRenderer: function(filter, el){
       var value = new Tine.SimpleFAQ.FaqTypeFilterModelValueField({
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

Tine.widgets.grid.FilterToolbar.FILTERS['simplefaq.faqtype'] = Tine.SimpleFAQ.FaqTypeFilterModel;

/**
 * @namespace   Tine.SimpleFAQ
 * @class       Tine.SimpleFAQ.FaqTypeFilterModelValueField
 * @extends     Ext.ux.form.LayerCombo
 * 
 */
Tine.SimpleFAQ.FaqTypeFilterModelValueField = Ext.extend(Ext.ux.form.LayerCombo, {
   hideButtons: false,
   formConfig: {
       labelAlign: 'left',
       labelWidth: 30
   },
   
   getFormValue: function(){
       var ids = [];
       var typeStore = Tine.SimpleFAQ.FaqType.getStore();

       var formValues = this.getInnerForm().getForm().getValues();
       for (var id in formValues){
           if(formValues[id] === 'on' && typeStore.getById(id)){
               ids.push(id);
           }
       }
       
       return ids;
   },

   getItems: function(){
       var items = [];

       Tine.SimpleFAQ.FaqType.getStore().each(function(type){
           items.push({
               xtype: 'checkbox',
               boxLabel: type.get('faqtype'),
               icon: type.get('type_icon'),
               name: type.get('id')
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
       
       var typeText = [];
       this.currentValue = [];

       Tine.SimpleFAQ.FaqType.getStore().each(function(type){
           var id = type.get('id');
           var name = type.get('faqtype');

           Ext.each(value, function(valueId){
               if(valueId == id) {
                   typeText.push(name);
                   this.currentValue.push(id);
               }
           }, this);
       }, this);

       this.setRawValue(typeText.join(', '));

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