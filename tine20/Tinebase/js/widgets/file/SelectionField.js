/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.file');

import './locationRenderer';

Tine.Tinebase.widgets.file.SelectionField = Ext.extend(Ext.form.TriggerField, {
    /**
     * @cfg {String} mode one of source|target
     */
    mode: 'source',

    /**
     * @cfg {String} locationTypesEnabled list of enabled plugins
     */
    locationTypesEnabled: 'fm_node,local',

    /**
     * @cfg {Boolean} allowMultiple
     * allow to select multiple fiels at once (source mode only)
     */
    allowMultiple: true,

    /**
     * @cfg {String|RegExp}
     * A constraint allows to alter the selection behaviour of the picker, for example only allow to select files.
     * By default, file and folder are allowed to be selected, the concrete implementation needs to define it's purpose
     */
    constraint: null,

    /**
     * @cfg {String} initialPath
     * initial filemanager path
     */
    initialPath: null,

    /**
     * @cfg {String} fileName
     * @property {String} fileName
     * (initial) fileName
     */
    fileName: null,

    /**
     * @cfg {Object} pluginConfig
     * config to pass to specific plugin
     * {<pluginName>: {...}}
     */
    pluginConfig: null,

    // private
    editable: false,
    
    onTriggerClick: function () {
        Tine.Tinebase.widgets.file.SelectionDialog.openWindow(
            _.assign(this.getSelectionDialogConfig(), {
                listeners: {
                    apply: _.bind(this.onDialogApply, this)
                }
            })
        )
    },
    
    onDialogApply: async function(locations) {
        await this.setValue(locations);
        this.fireEvent('select', this, locations);
        
        if (this.blurOnSelect) {
            this.fireEvent('blur', this);
        }

        this.validate();
    },
    
    setValue: async function(locations) {
        this.value = _.isArray(locations) ? locations : [locations];
        Tine.Tinebase.common.assertComparable(this.value);
        
        const async = await import(/* webpackChunkName: "Tinebase/js/async" */ 'async');
        const locationNames = await async.map(this.value, Tine.Tinebase.widgets.file.locationRenderer.getLocationName);
        this.setRawValue(_.join(locationNames, ', '));
    },
    
    getValue: function() {
        return this.mode === 'source' && this.allowMultiple ? this.value : _.get(this.value, '[0]');
    },
    
    getSelectionDialogConfig: function() {
        return {
            mode: this.mode,
            locationTypesEnabled: this.locationTypesEnabled,
            allowMultiple: this.allowMultiple,
            constraint: this.constraint,
            initialPath: this.initialPath,
            fileName: this.fileName,
            pluginConfig: this.pluginConfig
        };
    }
});

Ext.reg('fileselectionfield', Tine.Tinebase.widgets.file.SelectionField);
