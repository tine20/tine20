Ext.ns('Tine.widgets.editDialog');

Tine.widgets.dialog.MultipleEditDialogPlugin = function(config) {
	Ext.apply(this, config);
};

Tine.widgets.dialog.MultipleEditDialogPlugin.prototype = {

	app : null,

	editDialog : null,

	form : null,
	changes : null,

	init : function(editDialog) {

		this.editDialog = editDialog;
		this.app = Tine.Tinebase.appMgr.get(this.editDialog.app);
		this.form = this.editDialog.getForm();

		this.editDialog.on('render', function() {this.onAfterRender();}, this);

		this.editDialog.onRecordLoad = this.editDialog.onRecordLoad.createInterceptor(this.onRecordLoad, this);
		this.editDialog.onRecordUpdate = this.editDialog.onRecordUpdate.createInterceptor(this.onRecordUpdate, this);
//		this.editDialog.isValid = this.editDialog.isValid.createInterceptor(this.isValid, this);
		this.editDialog.onApplyChanges = function(button, event, closeWindow) {	this.onRecordUpdate();	}
	},

//	isValid : function() {
//		return true;
//	},

	onRecordLoad : function() {

		if (!this.editDialog.rendered) {
			this.onRecordLoad.defer(250, this);
			return;
		}

		this.editDialog.getForm().loadRecord(this.editDialog.record);

		Tine.log.debug('loading of the following record completed:');
		Tine.log.debug(this.editDialog.record);

		this.editDialog.getForm().clearInvalid();

		this.editDialog.window
				.setTitle(String.format(_('Edit {0} {1}'), this.editDialog.sm
								.getCount(), this.editDialog.i18nRecordsName));

		Ext.each(this.form.record.store.fields.keys, function(fieldKey) {
			var field = this.form.findField(fieldKey);
			// Tine.log.debug('REC',this.editDialog.record);
			if (field) {

				field.validationTask = null;
				field.allowBlankOrig = field.allowBlank;
				field.allowBlank = true;

				Ext.each(this.editDialog.sm.getSelections(), function(
						selection, index) {

					field.originalValue = this.editDialog.record.data[fieldKey];

					if (selection.data[fieldKey] != field.originalValue) {
						this.handleField(field, fieldKey, false);
						return false;
					} else {
						if (index == this.editDialog.sm.selections.length - 1) {
							this.handleField(field, fieldKey, true);
							return false;
						}
					}
				}, this);
			}

		}, this);

		Ext.each(this.editDialog.cfConfigs, function(el) {
			var fieldKey = el.data.name;
			var field = this.form.findField('customfield_' + fieldKey);

			if (field) {
				field.setValue(this.editDialog.record.data.customfields[fieldKey]);
				Ext.each(this.editDialog.sm.getSelections(), function(
						selection, index) {

					if (selection.data.customfields[fieldKey] != this.editDialog.record.data.customfields[fieldKey]) {
						this.handleField(field, fieldKey, false);
						return false;
					} else {
						if (index == this.editDialog.sm.selections.length - 1) {
							this.handleField(field, fieldKey, true);
							return false;
						}
					}
				}, this);
			}
		}, this);

		this.editDialog.updateToolbars(this.editDialog.record, this.editDialog.recordClass.getMeta('containerProperty'));

		Ext.each(this.editDialog.tbarItems, function(el) {
					el.disable();
				});

		this.editDialog.loadMask.hide();

		return false;
	},

	onAfterRender : function() {
		this.form.items.each(function(item) {
			// datefields already have a clearer
			if (item instanceof Ext.form.DateField)	return;
			// disable others
			if ((!(item instanceof Ext.form.TextField))	&& (!(item instanceof Ext.form.Checkbox))) {
				item.disable();
                return;
            }
			if (item instanceof Ext.form.TextField) {
				item.on('focus', function() {
					var subLeft = 0;
					if (item instanceof Ext.form.TriggerField) subLeft += 17;

					var el = this.el.parent().select('.tinebase-editmultipledialog-clearer'), 
                        width = this.getWidth(), 
                        style = {
						    left : (width - 18 - subLeft) + 'px'
					    };

					if (el.elements.length > 0) {
						el.setStyle(style);
                        el.removeClass('hidden');
						return;
					}

					// create Button
					var button = new Ext.Element(document.createElement('span'));

					button.addClass('tinebase-editmultipledialog-clearer');
					button.addClassOnOver('over');
					button.addClassOnClick('click');
					button.setStyle(style);

					button.on('click', function() {
						if (this.multi)	this.cleared = true;
						this.setValue('');
						this.addClass('tinebase-editmultipledialog-apply');
						this.removeClass('tinebase-editmultipledialog-noneedit');
						this.edited = true;

						this.fireEvent('blur');
					}, this);

					this.on('blur', function() {
						if ((this.originalValue != this.getValue())	|| this.cleared) {
							this.removeClass('tinebase-editmultipledialog-noneedit');
							this.addClass('tinebase-editmultipledialog-apply');
							this.edited = true;
						} else {
							this.edited = false;
							this.removeClass('tinebase-editmultipledialog-apply');

							if (this.multi) {
								this.setReadOnly(true);
								this.addClass('tinebase-editmultipledialog-noneedit');
							}
						}
						var el = this.el.parent().select('.tinebase-editmultipledialog-clearer');
						el.addClass('hidden');
					});
					this.el.insertSibling(button);
				});
			}
		});
	},

	onRecordUpdate : function() {
		this.changes = [];
		this.changedHuman = _('<br /><ul style="padding:10px;border:1px">');
		this.form.items.each(function(item) {
					if (item.edited) {
                        Tine.log.debug(item);
						this.changes.push({
									name : item.getName(),
									value : item.getValue()
								});
						this.changedHuman += '<li style="padding: 3px 0 3px 15px">'	+ item.fieldLabel + ': ';
						this.changedHuman += (item.lastSelectionText) ? item.lastSelectionText : item.getValue();  
					    this.changedHuman += '</li>';
					}
				}, this);
		this.changedHuman += '</ul>';

		// Tine.log.debug(this.changes); return false;

		if (this.changes.length == 0) {
			this.editDialog.purgeListeners();
			this.editDialog.window.close();
			return false;
		}

		// if(!this.editDialog.isValid()) {
		//        	
		// Ext.MessageBox.alert(_('Errors'),
		// this.editDialog.getValidationErrorMessage());
		//        	
		// this.form.items.each(function(item){
		// if(item.activeError) {
		// if(!item.edited) item.activeError = null;
		// }
		// Tine.log.debug(item);
		// });
		//        	
		// return false;
		// } else {

		var filter = this.editDialog.sm.getSelectionFilter();

		Ext.MessageBox.confirm(_('Confirm'), 
            String.format(_('Do you really want to change these {0} records?')
			+ this.changedHuman, this.editDialog.sm.getCount()), function(_btn) {
			    if (_btn == 'yes') {
				    Ext.MessageBox.wait(_('Please wait'),_('Applying changes'));
					Ext.Ajax.request({
    					url : 'index.php',
						params : {
	       					method : 'Tinebase.updateMultipleRecords',
							appName : this.editDialog.recordClass.getMeta('appName'),
							modelName : this.editDialog.recordClass.getMeta('modelName'),
							changes : this.changes,
							filter : filter
						},
						success : function(_result, _request) {
						    Ext.MessageBox.hide();
							this.editDialog.fireEvent('update');
							this.editDialog.purgeListeners();
							this.editDialog.window.close();
						},
						scope : this
					});
				}
			}, this);
		return false;
	},

	handleField : function(field, fieldKey, samevalue) {

		if (!samevalue) {

			field.setReadOnly(true);
			field.addClass('tinebase-editmultipledialog-noneedit');
			field.multi = true;
			field.edited = false;
			field.setValue('');
			field.originalValue = '';

			field.on('focus', function() {
				if (this.readOnly) this.originalValue = this.startValue;
				this.setReadOnly(false);
			});
		} else {

			field.on('focus', function() {
				if (!this.edited) this.originalValue = this.startValue;
			});
		}
	}
}