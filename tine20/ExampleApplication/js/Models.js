Ext.namespace('Tine', 'Tine.Example.Model');

Tine.Example.Model.RecordArray = Tine.Tinebase.Model.genericFields.concat([
   { name: 'id' },
   { name: 'container_id' },
   /*** define more fields here ***/
]);

Tine.Example.Model.Record = Tine.Tinebase.data.Record.create(Tine.Example.Model.RecordArray, {
   appName: 'Example',
   modelName: 'Record',
   idProperty: 'id',
   //titleProperty: 'title',
   recordName: 'Record',
   recordsName: 'Records',
   containerProperty: 'container_id',
   containerName: 'Record list',
   containersName: 'Record lists'
});

Tine.Example.Model.Record.getFilterModel = function() {
   var app = Tine.Tinebase.appMgr.get('Example');
   
   return [
       {label : _('Quick search'), field : 'query', operators : [ 'contains' ]},
       {filtertype : 'tine.widget.container.filtermodel', app : app
           , recordClass : Tine.Example.Model.Record},
       {filtertype : 'tinebase.tag', app : app}
   ];
};

/*** add more models here if you need them ***/
