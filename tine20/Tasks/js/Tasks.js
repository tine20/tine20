/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine', 'Tine.Tasks');



Tine.Tasks.CrmLeadRenderer = new Tine.widgets.relation.GridRenderer({
    appName: 'Tasks', type: 'TASK', foreignApp: 'Crm', foreignModel: 'Lead'
});

Tine.widgets.grid.RendererManager.register(
    'Tasks',
    'Task',
    'lead',
    Tine.Tasks.CrmLeadRenderer.render,
    null,
    Tine.Tasks.CrmLeadRenderer
);

/**
 * @namespace   Tine.Tasks
 * @class       Tine.Tasks.Application
 * @extends     Tine.Tinebase.Application
 * Tasks Application Object <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tasks.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * auto hook text i18n._('New Task')
     */
    addButtonText: 'New Task',
    
    routes: {
        'Task/(.*)': 'openInNewWindow'
    },
    
    async openInNewWindow(id) {
        await Tine.Tasks.getTask(id)
            .then((result) => {
                const record = Tine.Tinebase.data.Record.setFromJson(result, Tine.Tasks.Model.Task);
                const cp = this.getMainScreen().getCenterPanel();
                cp.onEditInNewWindow.call(cp, {actionType: 'edit'}, record);
            })
            .catch((error) => {
                Ext.Msg.show({
                    title: 'Error',
                    msg: error.message,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR
                });
            })
        
        window.location = window.location.href.replace(`/Task/${id}`, '');
    }
});

// default mainscreen
Tine.Tasks.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Task'
});

Tine.Tasks.TaskTreePanel = function(config) {
    Ext.apply(this, config);
    
    this.id = 'TasksTreePanel';
    this.recordClass = Tine.Tasks.Model.Task;
    
    this.filterMode = 'filterToolbar';
    Tine.Tasks.TaskTreePanel.superclass.constructor.call(this);
};
Ext.extend(Tine.Tasks.TaskTreePanel, Tine.widgets.container.TreePanel, {
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);
    }
});

Tine.Tasks.TaskFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Tasks.TaskFilterPanel.superclass.constructor.call(this);
};
Ext.extend(Tine.Tasks.TaskFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Tasks_Model_TaskFilter'}]
});
