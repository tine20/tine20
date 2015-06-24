/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Tine.Calendar.EventContextTagsItem = Ext.extend(Ext.menu.Item, {
    text: 'Tags',
    event: null,
    datetime: null,

    initComponent: function() {
        this.hidden = ! (
            this.event
            && this.event.get('editGrant')
            && Tine.Tinebase.appMgr.get('Calendar').featureEnabled('featureExtendedEventContextActions')
        );

        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.recentsManager = new Tine.Tinebase.RecentsManager({
            recordClass: Tine.Tinebase.Model.Tag,
            domain: this.app.appName,
            maxRecents: 20
        });
        this.menu = [];
        this.view = this.app.getMainScreen().getCenterPanel().getCalendarPanel(this.app.getMainScreen().getCenterPanel().activeView).getView();
        this.attachedTagIds = [];

        var attachedTags = this.event ? this.event.get('tags') : [],
            recents = this.recentsManager.getRecentRecords(),
            tagRenderer = Tine.Tinebase.common.tagRenderer;

        Ext.each(attachedTags, function(r) {
            r = Ext.isFunction(r.beginEdit) ? r : new Tine.Tinebase.Model.Tag(r);
            this.attachedTagIds.push(r.getId());
            this.menu.push({
                value: r.getId(),
                record: r,
                text: tagRenderer(r),
                checked: true,
                handleClick: this.handleClick
            });
        }, this);

        Ext.each(recents, function(r) {
            if (this.attachedTagIds.indexOf(r.getId()) < 0) {
                this.menu.push({
                    value: r.getId(),
                    record: r,
                    text: tagRenderer(r),
                    checked: false,
                    handleClick: this.handleClick
                });
            }
        }, this);


        var other = new Tine.widgets.tags.TagsMassAttachAction({
            app: this.app,
            text: this.app.i18n._('Additional Tags ...')
        });
        other.initialConfig.handler = other.initialConfig.handler.createSequence(this.onOtherClick, this);
        this.menu.push(other);

        Tine.Calendar.EventContextTagsItem.superclass.initComponent.call(this);

        this.menu.on('hide', this.onMenuHide, this);
    },

    /**
     * prevent menu hide
     */
    handleClick: function(e) {
        if(this.setChecked && !this.disabled && !(this.checked && this.group)){// disable unselect on radio item
            this.setChecked(!this.checked);
        }
        e.stopEvent();
    },

    /**
     * show extra window & prevent autosave
     */
    onOtherClick: function() {
        this.menu.un('hide', this.onMenuHide, this);

        var massAttach = this.menu.items.last().baseAction;

        massAttach.store.add(this.getSelectedRecords());
        massAttach.win.okButton.handler = function() {
            var tags = [];
            massAttach.store.each(function(r) {
                tags.push(r);
            }, this);

            massAttach.win.close();
            this.updateRecord(tags);

        }.createDelegate(this);
        massAttach.manageOkBtn = function() {if (this.win && this.win.okButton) this.win.okButton.setDisabled(false);};
    },

    /**
     * autosave
     */
    onMenuHide: function() {
        if (this.updateRecord(this.getSelectedRecords())) {
            this.menu.un('hide', this.onMenuHide, this);
        }
    },

    updateRecord: function(selectedRecords) {
        var tagDatas = [],
            tagIds = [];

        Ext.each(selectedRecords, function(tag) {
            tagDatas.push(tag.data);
            tagIds.push(tag.getId());
        }, this);

        var migration = this.attachedTagIds.getMigration(tagIds);
        if (migration.toDelete.length || migration.toCreate.length) {
            this.event.set('tags', '');
            this.event.set('tags', tagDatas);
            this.view.editing = this.event;
            this.app.getMainScreen().getCenterPanel().onUpdateEvent(this.event, false, 'update');
            this.ownerCt.destroy.defer(100, this.ownerCt);
            return true;
        }
    },

    getSelectedRecords: function() {
        var records = [];

        this.menu.items.each(function(item) {
            if (item.checked) {
                records.push(item.record);
            }
        }, this);

        return records;
    }
});

Ext.ux.ItemRegistry.registerItem('Calendar-MainScreenPanel-ContextMenu', Tine.Calendar.EventContextTagsItem, 130);
