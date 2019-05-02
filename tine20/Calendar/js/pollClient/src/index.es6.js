/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import _ from 'lodash'
import Vue from 'vue'
import Tine20 from './plugin/tine20-rpc'
import App from './App.vue'

// FUCK - why isn't the bootstrap css loaded by the components itself???
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'

// import router from 'vue-router'

// import GetTextPlugin from 'vue-gettext'
// import translations from './path/to/translations.json'

// import FormatMessage from 'vue-format-message'

import FormatMessage from 'format-message'
/* global Locale */
/* eslint no-undef: "error" */
require('Locale')
require('Locale/Gettext')

_.each(Tine.__translationData.msgs, function (msgs, category) {
  Locale.Gettext.prototype._msgs[category] = new Locale.Gettext.PO(msgs)
})

let gettext = new Locale.Gettext()
gettext.textdomain('Calendar')

// NOTE: we use gettext tooling for template selection
//       as there is almost no tooling for icu-messages out there
FormatMessage.setup({
  missingTranslation: 'ignore'
})

// auto template selection with gettext
Vue.prototype.formatMessage = function (template) {
  arguments[0] = gettext._hidden(template)
  return FormatMessage.apply(FormatMessage, arguments)
}
_.assign(Vue.prototype.formatMessage, FormatMessage)
// to translate strings which should not go into po files
Vue.prototype.fmHidden = Vue.prototype.formatMessage

Vue.config.productionTip = false

Vue.use(Tine20, {})

/* eslint-disable no-new */
new Vue(App).$mount('#tine-viewport-app')
// router.replace('/')
