/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/* global Ext,Tine */
window.lodash = require('lodash')
require('ux/Log')

let log = (level) => {
  Ext.ns('Tine')

  Tine.log = Ext.ux.log
  Tine.log.setPrio(level || Tine.log.EMERG)
}

let registry = (app) => {
  app = app || 'Tinebase'
  Ext.ns('Tine.' + app)

  let registry = {}
  Tine[app].registry = {
    get: (key) => {
      return registry[key]
    },
    getAll: () => {
      return registry
    },
    set: (key, value) => {
      registry[key] = value
    }
  }
}

let i18n = () => {
  window.i18n = {
    _: function (s) {
      return s
    }
  }
}
export {
  registry,
  log,
  i18n
}
