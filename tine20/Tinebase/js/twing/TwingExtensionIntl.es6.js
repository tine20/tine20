/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { TwingExtension, TwingFilter } from 'twing'

console.warn(TwingExtension);

class TwingExtensionIntl extends TwingExtension {
  constructor () {
    if (!Intl.DateTimeFormat) {
      throw new Error('intl (https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/Intl) is needed to use intl-based filters.')
    }
    super()
  }

  getFilters () {
    return [
      new TwingFilter('localizeddate', this.localizeddate, {needs_environment: true}),
      new TwingFilter('localizednumber', this.localizednumber),
      new TwingFilter('localizedcurrency', this.localizedcurrency)
    ]
  }

  /* eslint no-unused-vars: ["error", { "args": "none" }] */
  static localizeddate (env, date, dateFormat = 'medium', timeFormat = 'medium', locale = null, timezone = null, format = null, calendar = 'gregorian') {

  }
}

export default TwingExtensionIntl
