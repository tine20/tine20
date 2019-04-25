/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

// TODO maybe this should be moved to integration or E2E tests as AppStarter has lots of dependencies and
//      requires correct registry data

import * as global from 'globalFakes'
require('AppManager')
require('widgets/MainScreen')
require('widgets/tree/Loader')
require('widgets/persistentfilter/PickerPanel')
require('widgets/dialog/EditDialog')
require('widgets/grid/GridPanel')
require('data/Record')
require('ApplicationStarter')

describe('ApplicationStarter', () => {
  global.log()

  let uit

  beforeEach(() => {
    global.registry()
    global.registry('Inventory')

    Tine.Tinebase.registry.set('userApplications', [{
      'name': 'Inventory',
      'version': '11.1',
      'status': 'enabled',
      'order': '60',
      'state': null,
      'id': 'c42403b0e133c97b303143eda249a76c02c542b8'
    }])
    Tine.Inventory.registry.set('models', _getModelsRegistryData())

    uit = Tine.Tinebase.ApplicationStarter

    // TODO maybe add a mock for this?
    Tine.Tinebase.appMgr = new Tine.Tinebase.AppManager()

    let director = require('director')
    Tine.Tinebase.router = new director.Router().init()
  })

  it('can execute init', () => {
    uit.init()
    expect(Tine.Inventory.isAuto).to.be.true
  })

  // TODO add some more expectations for InventoryItem
  // TODO add tests for model/grid/renderers/filter/i18n/...

  /**
   * define InventoryItem model ('version' => 12)
   *
   * TODO move to separate file and use json reader to fill registry
   *
   * @returns {}
   * @private
   */
  let _getModelsRegistryData = function () {
    return {
      'models': {
        'InventoryItem': {
          'containerProperty': 'container_id',
          'containersName': 'Inventory item lists',
          'containerName': 'Inventory item list',
          'grantsModel': 'Tinebase_Model_Grants',
          'defaultSortInfo': null,
          'fieldKeys': ['name', 'status', 'inventory_id', 'description', 'location', 'invoice_date', 'total_number', 'active_number', 'invoice', 'price', 'costcenter', 'warranty', 'added_date', 'removed_date', 'deprecated_status', 'image', 'id', 'customfields', 'relations', 'container_id', 'tags', 'attachments', 'notes', 'created_by', 'creation_time', 'last_modified_by', 'last_modified_time', 'seq', 'deleted_by', 'deleted_time', 'is_deleted'],
          'filterModel': {
            'name': {
              'filter': 'Tinebase_Model_Filter_Text'
            },
            'status': {
              'filter': 'Tinebase_Model_Filter_Text'
            },
            'inventory_id': {
              'filter': 'Tinebase_Model_Filter_Text'
            },
            'description': {
              'filter': 'Tinebase_Model_Filter_Text'
            },
            'location': {
              'filter': 'Tinebase_Model_Filter_Text'
            },
            'invoice_date': {
              'filter': 'Tinebase_Model_Filter_DateTime'
            },
            'total_number': {
              'filter': 'Tinebase_Model_Filter_Int'
            },
            'active_number': {
              'filter': 'Tinebase_Model_Filter_Int'
            },
            'invoice': {
              'filter': 'Tinebase_Model_Filter_Text'
            },
            'price': {
              'filter': 'Tinebase_Model_Filter_Float'
            },
            'costcenter': {
              'filter': 'Tinebase_Model_Filter_ForeignId',
              'options': {
                'appName': 'Sales',
                'modelName': 'CostCenter',
                'idProperty': 'id',
                'filtergroup': 'Sales_Model_CostCenterFilter',
                'controller': 'Sales_Controller_CostCenter'
              }
            },
            'warranty': {
              'filter': 'Tinebase_Model_Filter_DateTime'
            },
            'added_date': {
              'filter': 'Tinebase_Model_Filter_DateTime'
            },
            'removed_date': {
              'filter': 'Tinebase_Model_Filter_DateTime'
            },
            'deprecated_status': {
              'filter': 'Tinebase_Model_Filter_Int'
            },
            'id': {
              'filter': 'Tinebase_Model_Filter_Id',
              'options': {
                'idProperty': 'id',
                'modelName': 'Inventory_Model_InventoryItem'
              }
            },
            'customfield': {
              'filter': 'Tinebase_Model_Filter_CustomField',
              'options': {
                'idProperty': 'inventory_item.id'
              }
            },
            'relations': {
              'filter': 'Tinebase_Model_Filter_Relation'
            },
            'container_id': {
              'filter': 'Tinebase_Model_Filter_Container',
              'options': {
                'applicationName': 'Inventory'
              }
            },
            'tag': {
              'key': 'tag',
              'filter': 'Tinebase_Model_Filter_Tag',
              'options': {
                'idProperty': 'inventory_item.id',
                'applicationName': 'Inventory'
              }
            },
            'created_by': {
              'filter': 'Tinebase_Model_Filter_User'
            },
            'creation_time': {
              'filter': 'Tinebase_Model_Filter_DateTime'
            },
            'last_modified_by': {
              'filter': 'Tinebase_Model_Filter_User'
            },
            'last_modified_time': {
              'filter': 'Tinebase_Model_Filter_DateTime'
            },
            'seq': {
              'filter': 'Tinebase_Model_Filter_Int'
            },
            'deleted_by': {
              'filter': 'Tinebase_Model_Filter_User'
            },
            'deleted_time': {
              'filter': 'Tinebase_Model_Filter_DateTime'
            },
            'is_deleted': {
              'filter': 'Tinebase_Model_Filter_Int'
            },
            'query': {
              'label': 'Quick Search',
              'field': 'query',
              'filter': 'Tinebase_Model_Filter_Query',
              'useGlobalTranslation': true,
              'options': {
                'fields': ['name'],
                'modelName': 'Inventory_Model_InventoryItem'
              }
            }
          },
          'defaultFilter': 'query',
          'requiredRight': null,
          'singularContainerMode': null,
          'fields': {
            'name': {
              'type': 'string',
              'length': 255,
              'validators': {
                'allowEmpty': false,
                'presence': 'required'
              },
              'label': 'Name',
              'queryFilter': true,
              'fieldName': 'name',
              'key': 'name'
            },
            'status': {
              'validators': {
                'allowEmpty': true
              },
              'nullable': true,
              'label': 'Status',
              'type': 'keyfield',
              'name': 'inventoryStatus',
              'fieldName': 'status',
              'key': 'status',
              'length': 40
            },
            'inventory_id': {
              'type': 'string',
              'length': 100,
              'nullable': true,
              'validators': {
                'allowEmpty': true
              },
              'label': 'Inventory ID',
              'fieldName': 'inventory_id',
              'key': 'inventory_id'
            },
            'description': {
              'type': 'text',
              'nullable': true,
              'validators': {
                'allowEmpty': true
              },
              'label': 'Description',
              'fieldName': 'description',
              'key': 'description'
            },
            'location': {
              'type': 'string',
              'length': 255,
              'nullable': true,
              'validators': {
                'allowEmpty': true
              },
              'label': 'Location',
              'fieldName': 'location',
              'key': 'location'
            },
            'invoice_date': {
              'validators': {
                'allowEmpty': true
              },
              'label': 'Invoice date',
              'inputFilters': {
                'Zend_Filter_Empty': null
              },
              'hidden': true,
              'default': null,
              'type': 'datetime',
              'nullable': true,
              'fieldName': 'invoice_date',
              'key': 'invoice_date'
            },
            'total_number': {
              'type': 'integer',
              'nullable': true,
              'validators': {
                'allowEmpty': true
              },
              'label': null,
              'inputFilters': {
                'Zend_Filter_Empty': null
              },
              'default': 1,
              'fieldName': 'total_number',
              'key': 'total_number'
            },
            'active_number': {
              'type': 'integer',
              'nullable': true,
              'validators': {
                'allowEmpty': true
              },
              'label': 'Available number',
              'inputFilters': {
                'Zend_Filter_Empty': null
              },
              'default': 1,
              'fieldName': 'active_number',
              'key': 'active_number'
            },
            'invoice': {
              'type': 'string',
              'nullable': true,
              'validators': {
                'allowEmpty': true
              },
              'label': 'Invoice',
              'hidden': true,
              'fieldName': 'invoice',
              'key': 'invoice'
            },
            'price': {
              'type': 'money',
              'nullable': true,
              'validators': {
                'allowEmpty': true
              },
              'label': 'Price',
              'hidden': true,
              'inputFilters': {
                'Zend_Filter_Empty': null
              },
              'fieldName': 'price',
              'key': 'price'
            },
            'costcenter': {
              'label': 'Cost center',
              'hidden': true,
              'type': 'record',
              'nullable': true,
              'validators': {
                'allowEmpty': true,
                'default': null
              },
              'config': {
                'appName': 'Sales',
                'modelName': 'CostCenter',
                'idProperty': 'id'
              },
              'fieldName': 'costcenter',
              'key': 'costcenter'
            },
            'warranty': {
              'validators': {
                'allowEmpty': true
              },
              'label': 'Warranty',
              'hidden': true,
              'inputFilters': {
                'Zend_Filter_Empty': null
              },
              'type': 'datetime',
              'nullable': true,
              'fieldName': 'warranty',
              'key': 'warranty'
            },
            'added_date': {
              'validators': {
                'allowEmpty': true
              },
              'label': 'Item added',
              'hidden': true,
              'inputFilters': {
                'Zend_Filter_Empty': null
              },
              'type': 'datetime',
              'nullable': true,
              'fieldName': 'added_date',
              'key': 'added_date'
            },
            'removed_date': {
              'validators': {
                'allowEmpty': true
              },
              'label': 'Item removed',
              'hidden': true,
              'inputFilters': {
                'Zend_Filter_Empty': null
              },
              'type': 'datetime',
              'nullable': true,
              'fieldName': 'removed_date',
              'key': 'removed_date'
            },
            'deprecated_status': {
              'type': 'integer',
              'validators': {
                'allowEmpty': true,
                'default': 0
              },
              'label': null,
              'default': 0,
              'fieldName': 'deprecated_status',
              'key': 'deprecated_status'
            },
            'image': {
              'validators': {
                'allowEmpty': true
              },
              'inputFilters': {
                'Zend_Filter_Empty': null
              },
              'type': 'image',
              'fieldName': 'image',
              'key': 'image'
            },
            'id': {
              'id': true,
              'label': 'ID',
              'validators': {
                'allowEmpty': true
              },
              'length': 40,
              'shy': true,
              'filterDefinition': {
                'filter': 'Tinebase_Model_Filter_Id',
                'options': {
                  'idProperty': 'id',
                  'modelName': 'Inventory_Model_InventoryItem'
                }
              },
              'fieldName': 'id',
              'type': 'string',
              'key': 'id'
            },
            'customfields': {
              'label': 'Custom Fields',
              'type': 'custom',
              'validators': {
                'allowEmpty': true,
                'default': null
              },
              'fieldName': 'customfields',
              'key': 'customfields'
            },
            'relations': {
              'label': 'Relations',
              'type': 'relation',
              'validators': {
                'allowEmpty': true,
                'default': null
              },
              'fieldName': 'relations',
              'key': 'relations'
            },
            'container_id': {
              'nullable': true,
              'unsigned': true,
              'label': 'Inventory item list',
              'shy': true,
              'type': 'container',
              'validators': {
                'allowEmpty': true
              },
              'filterDefinition': {
                'filter': 'Tinebase_Model_Filter_Container',
                'options': {
                  'applicationName': 'Inventory'
                }
              },
              'fieldName': 'container_id',
              'key': 'container_id'
            },
            'tags': {
              'label': 'Tags',
              'sortable': false,
              'type': 'tag',
              'validators': {
                'allowEmpty': true,
                'default': null
              },
              'useGlobalTranslation': true,
              'filterDefinition': {
                'key': 'tag',
                'filter': 'Tinebase_Model_Filter_Tag',
                'options': {
                  'idProperty': 'inventory_item.id',
                  'applicationName': 'Inventory'
                }
              },
              'fieldName': 'tags',
              'key': 'tags'
            },
            'attachments': {
              'label': null,
              'type': 'attachments',
              'recursiveResolving': true,
              'fieldName': 'attachments',
              'key': 'attachments',
              'validators': {
                'allowEmpty': true
              }
            },
            'notes': {
              'label': null,
              'type': 'note',
              'validators': {
                'allowEmpty': true,
                'default': null
              },
              'useGlobalTranslation': true,
              'fieldName': 'notes',
              'key': 'notes'
            },
            'created_by': {
              'label': 'Created By',
              'type': 'user',
              'validators': {
                'allowEmpty': true
              },
              'shy': true,
              'useGlobalTranslation': true,
              'length': 40,
              'nullable': true,
              'fieldName': 'created_by',
              'key': 'created_by'
            },
            'creation_time': {
              'label': 'Creation Time',
              'type': 'datetime',
              'validators': {
                'allowEmpty': true
              },
              'shy': true,
              'useGlobalTranslation': true,
              'nullable': true,
              'fieldName': 'creation_time',
              'key': 'creation_time'
            },
            'last_modified_by': {
              'label': 'Last Modified By',
              'type': 'user',
              'validators': {
                'allowEmpty': true
              },
              'shy': true,
              'useGlobalTranslation': true,
              'length': 40,
              'nullable': true,
              'fieldName': 'last_modified_by',
              'key': 'last_modified_by'
            },
            'last_modified_time': {
              'label': 'Last Modified Time',
              'type': 'datetime',
              'validators': {
                'allowEmpty': true
              },
              'shy': true,
              'useGlobalTranslation': true,
              'nullable': true,
              'fieldName': 'last_modified_time',
              'key': 'last_modified_time'
            },
            'seq': {
              'label': null,
              'type': 'integer',
              'system': true,
              'validators': {
                'allowEmpty': true
              },
              'shy': true,
              'useGlobalTranslation': true,
              'default': 0,
              'unsigned': true,
              'fieldName': 'seq',
              'key': 'seq'
            },
            'deleted_by': {
              'label': null,
              'system': true,
              'type': 'user',
              'validators': {
                'allowEmpty': true
              },
              'useGlobalTranslation': true,
              'length': 40,
              'nullable': true,
              'fieldName': 'deleted_by',
              'key': 'deleted_by'
            },
            'deleted_time': {
              'label': null,
              'system': true,
              'type': 'datetime',
              'validators': {
                'allowEmpty': true
              },
              'useGlobalTranslation': true,
              'nullable': true,
              'fieldName': 'deleted_time',
              'key': 'deleted_time'
            },
            'is_deleted': {
              'label': null,
              'type': 'integer',
              'system': true,
              'validators': {
                'allowEmpty': true
              },
              'useGlobalTranslation': true,
              'default': 0,
              'fieldName': 'is_deleted',
              'key': 'is_deleted'
            }
          },
          'defaultData': {
            'total_number': 1,
            'active_number': 1,
            'deprecated_status': 0,
            'seq': 0,
            'is_deleted': 0
          },
          'titleProperty': 'name',
          'useGroups': false,
          'fieldGroupFeDefaults': [],
          'fieldGroupRights': [],
          'multipleEdit': null,
          'multipleEditRequiredRight': null,
          'copyEditAction': null,
          'copyOmitFields': null,
          'recordName': 'Inventory item',
          'recordsName': 'Inventory items',
          'appName': 'Inventory',
          'modelName': 'InventoryItem',
          'createModule': true,
          'isDependent': false,
          'hasCustomFields': true,
          'modlogActive': true,
          'hasAttachments': true,
          'hasAlarms': null,
          'idProperty': 'id',
          'splitButton': false,
          'attributeConfig': null,
          'hasPersonalContainer': true,
          'import': {
            'defaultImportContainerRegistryKey': 'defaultInventoryItemContainer'
          },
          'export': {
            'supportedFormats': ['csv', 'ods']
          },
          'virtualFields': [],
          'group': null
        }
      } }
  }
})
