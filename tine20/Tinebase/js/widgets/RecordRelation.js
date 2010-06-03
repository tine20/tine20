/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.ns('Tine.Tinebase.Model');

Egw.Tinebase.Model.Relation = Ext.data.Record.create([
    {name: 'identifier'                                       },
    {name: 'created_by'                                       },
    {name: 'creation_time', type: 'date', dateFormat: Date.patterns.ISO8601Long     },
    {name: 'last_modified_by'                                 },
    {name: 'last_modified_time', type: 'date', dateFormat: Date.patterns.ISO8601Long},
    {name: 'is_deleted'                                       },
    {name: 'deleted_time', type: 'date', dateFormat: Date.patterns.ISO8601Long      },
    {name: 'deleted_by'                                       },
    {name: 'own_application'                                  },
    {name: 'own_identifier'                                   },
    {name: 'related_application'                              },
    {name: 'related_identifier'                               },
    {name: 'related_role'                                     }
]);