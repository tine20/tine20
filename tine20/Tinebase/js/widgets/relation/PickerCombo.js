/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <alex@stintzing.net>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.relation');

/**
 * @namespace   Tine.widgets.relation
 * @class       Tine.widgets.relation.PickerCombo
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

Tine.widgets.relation.PickerCombo = Ext.extend(Ext.Container, {

    // private
    items: null,
    combo: null,
    layout: 'fit',
    /**
     * @cfg {Tine.widgets.dialog.EditDialog}
     */
    editDialog: null,
    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     */
    recordClass: null,
    /**
     * @cfg {Ext.data.SimpleStore} store 
     */
    store: null,

    app: null,

    /**
     * config spec for additionalFilters
     *
     * @type: {object} e.g.
     * additionalFilterConfig: {config: { 'name': 'configName', 'appName': 'myApp'}}
     * additionalFilterConfig: {preference: {'appName': 'myApp', 'name': 'preferenceName}}
     * additionalFilterConfig: {favorite: {'appName': 'myApp', 'id': 'favoriteId', 'name': 'optionallyuseaname'}}
     */
    additionalFilterSpec: null,

    /**
     * initializes the component
     */
    initComponent: function() {
        if (!this.app && this.recordClass) {
            this.app = Tine.Tinebase.appMgr.get(this.recordClass.getMeta('appName'));
        }

        this.combo = Tine.widgets.form.RecordPickerManager.get(this.app, this.recordClass, Ext.applyIf({},this));
        this.items = [this.combo];

        this.deferredLoading();

        Tine.widgets.relation.PickerCombo.superclass.initComponent.call(this);
    },

    /**
     * wait until the relationPanel is created
     */
    deferredLoading: function() {

        if (!this.editDialog.relationsPanel) {
            this.deferredLoading.defer(100, this);
            return;
        }

        this.store = this.editDialog.relationsPanel.getStore();
        this.fullModelName = this.recordClass.getMeta('appName') + '_Model_' + this.recordClass.getMeta('modelName');

        this.combo.on('select', function() {
            var value = this.combo.getValue();

            if (value.length) {
                // check if another relationPicker has the same record selected
                if (this.modelUnique) {
                    var hasDuplicate = false,
                        fieldName;

                    Ext.each(this.editDialog.relationPickers, function(picker) {
                        if(picker != this) {
                            if(picker.getValue() == this.getValue()) {
                                hasDuplicate = true;
                                fieldName = picker.fieldLabel;
                                return false;
                            }
                        }
                    }, this);
                }

                if (hasDuplicate) {
                    if(this.combo.startRecord) {
                        var split = this.fullModelName.split('_Model_');
                        var startRecord = this.combo.startRecord;
                    } else {
                        startRecord = null;
                    }
                    this.combo.reset();
                    this.combo.setValue(startRecord);
                    this.combo.selectedRecord = startRecord;
                    this.combo.markInvalid(String.format(i18n._('The {1} "{2}" is already used in the Field "{0}" and can be linked only once!'), fieldName, this.recordClass.getRecordName(), recordToAdd.get(this.recordClass.getMeta('titleProperty'))));
                    return false;
                }

                if (value != this.combo.startValue) {
                    this.removeIdFromStore(this.combo.startValue);
                }
                this.addRecord(this.combo.selectedRecord);
            } else {
                this.removeIdFromStore(this.combo.startValue);
            }
        }, this);

        if (!this.editDialog.relationPickers) this.editDialog.relationPickers = [];
        this.editDialog.relationPickers.push(this);
    },

    /**
     * Add a record to store if not existing
     * @param recordToAdd
     */
    addRecord: function (recordToAdd) {
        var record = this.store.findBy(function (record) {
            if (record && record.get('related_id') === this.combo.getValue() && record.get('type') === this.relationType) {
                return true;
            }
        }, this);

        if (record === -1) {
            var relationRecord = new Tine.Tinebase.Model.Relation(Ext.apply(this.editDialog.relationsPanel.getRelationDefaults(), {
                related_record: recordToAdd.data,
                related_id: recordToAdd.id,
                related_model: this.fullModelName,
                type: this.relationType,
                related_degree: this.relationDegree
            }), recordToAdd.id);

            this.combo.startRecord = recordToAdd;
            this.store.add(relationRecord);
        }
    },

    /**
     * removes the relation record by the related record id, if the type is the same handled by this combo
     * @param {String} id
     */
    removeIdFromStore: function(id) {
        if(id) {
            var oldRecPos = this.store.findBy(function(record) {
                if(record) {
                    if(record.get('related_id') == id && record.get('type') == this.relationType) return true;
                } else {
                    return false;
                }
            }, this);
            this.store.removeAt(oldRecPos);
        }
    },

    /**
     * Shortcut for the equivalent method of the combo
     */
    setValue: function(v) {
        // If v might be a record, we try to select it and if not in store we create it in relation store
        if (v.hasOwnProperty('data')) {
            this.addRecord(v);
        }

        return this.combo.setValue(v);
    },
    
    /**
     * Shortcut for the equivalent method of the combo
     */
    clear: function() {
        this.combo.startRecord = null;
        return this.combo.setValue(null);
    },
    
    /**
     * Shortcut for the equivalent method of the combo
     */
    getValue: function() {
        return this.combo.getValue();
    },
    
    /**
     * Shortcut for the equivalent method of the combo
     */
    setReadOnly: function(v) {
        return this.combo.setReadOnly(v);
    }
});

Ext.reg('tinerelationpickercombo', Tine.widgets.relation.PickerCombo);