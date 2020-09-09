/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

export default function (condition, timeout, interval) {
  return new Promise(function (resolve, reject) {
    interval = interval || 50
    let until = timeout ? new Date().getTime() + timeout : Infinity

    let fn = function () {
      let result = condition()
      if (!result) {
        if (new Date().getTime() > until) {
          return reject(new Error('did not succeed in given time interval'))
        } else {
          return window.setTimeout(fn, interval)
        }
      }
      resolve(result)
    }

    fn()
  })
};
