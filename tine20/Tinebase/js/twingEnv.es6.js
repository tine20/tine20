/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { TwingEnvironment, TwingLoaderArray } from 'twing'
import { TwingExtensionIntl } from 'twing-intl'

let twingEnv

export default function getTwingEnv () {
  if (!twingEnv) {
    let loader = new TwingLoaderArray([])

    twingEnv = new TwingEnvironment(loader)

    twingEnv.addGlobal('app', {
      branding: _.filter(Tine.Tinebase.registry.getAll(), function (v, k) { return k.match(/^branding/) }),
      user: {
        locale: Tine.Tinebase.registry.get('locale').locale || 'en',
        timezone: Tine.Tinebase.registry.get('timeZone') || 'UTC'
      }
    })

    twingEnv.addExtension(new TwingExtensionIntl())
  }

  return twingEnv
}
