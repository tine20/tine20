/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as globals from 'globalFakes'
require('PresenceObserver')

describe('PresenceObserver', () => {
  globals.log()

  beforeEach(() => {
    globals.registry()
  })

  it('has a static presence timestamp', () => {
    expect(Tine.Tinebase.PresenceObserver.lastPresence).to.equal(null)
  })

  it('intercepts Ext.EventObjectImpl.prototype.setEvent function to update timestamps', () => {
    Ext.EventObjectImpl.prototype.setEvent({})
    expect(Tine.Tinebase.PresenceObserver.lastPresence).not.to.equal(null)
  })

  describe('instance', () => {
    let clock
    let po
    beforeEach(() => {
      clock = sinon.useFakeTimers(0)
      po = new Tine.Tinebase.PresenceObserver({})
    })
    afterEach(function () {
      clock.restore()
      Tine.Tinebase.PresenceObserver.lastPresence = null
    })

    it('is of type Tine.Tinebase.PresenceObserver', () => {
      expect(po).to.be.instanceof(Tine.Tinebase.PresenceObserver)
    })

    it('resets clock on startChecking', () => {
      expect(Tine.Tinebase.PresenceObserver.lastPresence).to.equal(new Date().getTime())
    })

    it('not to call absence callback before absence time', () => {
      po.absenceCallback = sinon.spy()
      clock.tick(po.maxAbsenceTime * 60000 - 1)
      expect(po.absenceCallback.called).to.be.false
    })

    it('to call absence callback after absence time', () => {
      po.absenceCallback = sinon.spy()
      clock.tick(po.maxAbsenceTime * 60000)
      expect(po.absenceCallback.calledOnce).to.be.true
    })

    it('not to call presence callback when no presence was detected', () => {
      po.presenceCallback = sinon.spy()
      clock.tick(po.maxAbsenceTime * 60000)
      expect(po.presenceCallback.called).to.be.false
    })

    it('to call presence callback after absence time', () => {
      po.presenceCallback = sinon.spy()
      Tine.Tinebase.PresenceObserver.lastPresence = new Date().getTime() + po.maxAbsenceTime / 2 * 60000
      clock.tick(po.maxAbsenceTime * 60000)
      expect(po.presenceCallback.calledOnce).to.be.true
    })

    it('to call presence callback after a period of absence', () => {
      po.absenceCallback = sinon.spy()
      po.presenceCallback = sinon.spy()
      clock.tick(po.maxAbsenceTime * 60000)
      expect(po.absenceCallback.calledOnce).to.be.true
      expect(po.presenceCallback.called).to.be.false

      Tine.Tinebase.PresenceObserver.lastPresence = new Date().getTime()
      clock.tick(po.presenceCheckInterval * 1000)
      expect(po.presenceCallback.calledOnce).to.be.true
    })

    it('to stop checking after stopChecking', () => {
      po.absenceCallback = sinon.spy()
      po.presenceCallback = sinon.spy()
      Tine.Tinebase.PresenceObserver.lastPresence = new Date().getTime() + po.maxAbsenceTime / 2 * 60000
      po.stopChecking()
      clock.tick(po.maxAbsenceTime * 60000)
      expect(po.presenceCallback.called).to.be.false
      expect(po.presenceCallback.called).to.be.false
    })
  })
})
