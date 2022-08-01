Tine.Admin.registerItem({
    text: 'Scheduler', // _('Scheduler')
    iconCls: 'admin-node-scheduler',
    pos: 900,
    dataPanelType: "Tine.Admin.SchedulerTaskGridPanel",
    hidden: !Tine.Admin.showModule('scheduler')
});