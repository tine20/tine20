/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Vue from 'vue'
import App from './App.vue'

// FUCK - why isn't the bootstrap css loaded by the components itself???
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'

// import router from 'vue-router'

import GetTextPlugin from 'vue-gettext'
// import translations from './path/to/translations.json'

Vue.config.productionTip = false

let translations = {
  'de_DE': {
    'Loading Poll...': 'Umfrage wird geladen...'
  }
}
Vue.use(GetTextPlugin, {
  availableLanguages: {
    de_DE: 'Deutsch'
  },
  defaultLanguage: 'de_DE',
  languageVmMixin: {
    computed: {
      currentKebabCase: function () {
        return this.current.toLowerCase().replace('_', '-')
      }
    }
  },
  translations: translations
})

/* eslint-disable no-new */
new Vue(App).$mount('#app')
// router.replace('/')
