/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Vue from 'vue'
import Tine20 from './plugin/tine20-rpc'
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
    'Loading Poll...': 'Umfrage wird geladen...',
    'Please wait...': 'Bitte warten...',
    'ACCEPTED': 'Zugesagt',
    'DECLINED': 'Abgesagt',
    'NEEDS-ACTION': 'Keine Antwort',
    'TENTATIVE': 'Vorläufig',
    'I am not': 'Ich bin nicht',
    'By using this service you agree to our': 'Indem Sie diesen Service verwenden, akzeptieren Sie unsere',
    'terms and conditions': 'AGB',
    'Welcome,': 'Herzlich Willkommen,',
    'Wrong password!': 'Falsches Passwort!',
    'Password': 'Passwort',
    'Submit': 'Absenden',
    'Name': 'Name',
    'Email address': 'Emailadresse',
    'User name': 'Benutzername',
    'Plase log in': 'Bitte melden Sie sich an',
    'Could not log in!': 'Anmeldung fehlgeschlagen!',
    'First name, ĺast name': 'Vorname Name'
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

Vue.use(Tine20, {})

/* eslint-disable no-new */
new Vue(App).$mount('#app')
// router.replace('/')
