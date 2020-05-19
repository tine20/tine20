<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jan Evers <j.evers@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->
<template>
  <div id="root">
    <div class="container">
      <b-alert class="global-error" variant="danger" :show="globalError.length > 0">{{globalError}}</b-alert>
      <p v-if="publicUrl.length > 0"><a :href="publicUrl">{{formatMessage('Switch to public poll')}}</a></p>
      <template v-if="!transferingPoll && !askPassword && showPoll">
        <div class="row">
          <div class="col-md-8 col-sm-12">
            <h1>{{poll.event_summary}}</h1>
            <h2 v-if="poll.name.length > 0">{{poll.name}}</h2>
          </div>
          <div class="col-md-4 col-sm-12 text-right">
            <a :href="poll.config.brandingWeburl">
              <img style="max-width: 300px; max-height: 80px" :src="poll.config.installLogo" :alt="poll.config.brandingTitle"/>
            </a>
          </div>
        </div>
        <div class="row greetings">
          <div class="col-md-6 col-sm-12 greetings-text">
            <p>
              <span v-if="activeAttendee.id !== null">{{formatMessage('Welcome {name}', {name: activeAttendee.name})}}</span>
              <span v-if="poll.config.is_anonymous && poll.locked === '1'"><br />{{formatMessage('This is a closed poll. No Attendee can be added.')}}</span>
              <span v-if="poll.closed === '1'"><br />{{formatMessage('This poll is closed already')}}</span>
            </p>
          </div>
          <div class="col-md-6 col-sm-12 text-right">
            <b-btn v-if="activeAttendee.id !== null" @click="onOtherUser" variant="primary">{{formatMessage('I am not {name}', {name: activeAttendee.name})}}</b-btn>
            <b-btn v-else @click="askTineLogin = true" variant="primary">{{formatMessage('Login')}}</b-btn>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th></th>
                  <th v-for="date in poll.alternative_dates" :key="date.dtstart"><span class="date">{{formatMessage('{headerDate, date, full} {headerDate, time, short}', {headerDate: new Date(date.dtstart.replace(' ', 'T'))})}}</span></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="attendee in poll.attendee_status" :key="attendee.key" :class="{'row-active': activeAttendee.user_id === attendee.user_id}" v-if="attendee.user_type!= 'group'">
                  <td :class="activeAttendee.user_id === attendee.user_id ? 'table-info' : 'table-light'">{{attendee.name}}</td>
                  <td v-for="datestatus in attendee.status" class="icon-cell" :key="datestatus.id"
                    :class="[{'editable': datestatus.status_authkey !== null}, statusList[datestatus.status].cellclass]"
                    @click="nextStatus(datestatus, attendee.key)"
                    :title="statusName(datestatus.status)"
                    data-toggle="tooltip"
                    data-placement="bottom"
                  >
                    <div class="modified-marker" v-if="datestatus.status !== datestatus.initial"></div>
                    <span @click.stop="showCalendar(datestatus)"
                      class="calendar-symbol"
                      v-if="!poll.config.is_anonymous && activeAttendee.user_id !== null && activeAttendee.user_id === attendee.user_id"
                    >
                      <img :src="getCalendarIcon(datestatus)" alt="formatMessage('Calendar')" />
                    </span>
                    <img :src="getStatusIcon(datestatus.status)" :alt="statusName(datestatus.status)" />
                  </td>
                </tr>
                <tr v-if="activeAttendee.user_id === null && poll.locked == '0' && poll.closed !== '1'" class="row-active">
                  <td class="table-info name-field">
                    <input type="text" v-model="activeAttendee.name" :placeholder="formatMessage('First name, ĺast name')" class="form-control name-field" />
                    <input type="text" v-model="activeAttendee.email" :placeholder="formatMessage('E-Mail address')" class="form-control email-field" />
                  </td>
                  <td v-for="date in poll.alternative_dates" :key="date.id" class="status-cell icon-cell editable"
                    v-if="typeof activeAttendee[date.id] !== 'undefined'"
                    :class="statusList[activeAttendee[date.id].status].cellclass"
                    @click="nextStatus(activeAttendee[date.id], null)"
                    :title="statusName(activeAttendee[date.id].status)"
                    data-toggle="tooltip"
                    data-placement="bottom"
                  >
                    <img :src="getStatusIcon(activeAttendee[date.id].status)" :alt="statusName(activeAttendee[date.id].status)" />
                  </td>
                </tr>
                <tr v-if="activeAttendee.user_id !== null && poll.locked == '0' && newAccountContact && poll.closed !== '1'" class="row-active">
                  <td class="table-info">{{activeAttendee.name}}</td>
                  <td v-for="date in poll.alternative_dates" class="status-cell icon-cell" :key="date.id"
                    v-if="typeof activeAttendee[date.id] !== 'undefined'"
                    :class="statusList[activeAttendee[date.id].status].cellclass"
                    @click="nextStatus(activeAttendee[date.id], null)"
                    :title="statusName(activeAttendee[date.id].status)"
                    data-toggle="tooltip"
                    data-placement="bottom"
                  >
                    <img :src="getStatusIcon(activeAttendee[date.id].status)" :alt="statusName(activeAttendee[date.id].status)" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12" v-if="showChangeButtons()">
            <b-button-group>
              <b-btn @click="onCancelChanges" variant="secondary">{{formatMessage('Cancel')}}</b-btn>
              <b-btn @click="onApplyChanges" variant="primary">{{formatMessage('Save')}}</b-btn>
            </b-button-group>
          </div>
        </div>
        <div class="row footer" v-if="!hidegtcmessage">
          <div class="col-md-12">
            <p>
              <a href="#" @click.prevent="showGtc = true">{{formatMessage('By using this service you agree to our terms and conditions')}}</a>.
            </p>
          </div>
        </div>
      </template>
      <div>
        <b-modal ref="loadMask" :visible="transferingPoll" hide-header hide-footer no-fade no-close-on-esc no-close-on-backdrop centered>
          <spinner size="medium" :message="formatMessage('Please wait...')"></spinner>
        </b-modal>
      </div>
      <div>
        <b-modal ref="gtc" :visible="showGtc" hide-footer centered title="formatMessage('General terms and conditions')" v-model="showGtc">
          {{gtcText}}
        </b-modal>
      </div>
      <div>
        <b-modal ref="linkInfo" :visible="usePersonalLink" hide-footer centered title="formatMessage('Use your personal link please.')" v-model="usePersonalLink" @hide="usePersonalLink">
          <p>{{formatMessage('Use your personal link please.')}}</p>
          <p>{{formatMessage('We have sent it to your E-Mail account again.')}}</p>
          <p>{{formatMessage('If you did not receive the link, please contact the organiser.')}}</p>
        </b-modal>
      </div>
      <div>
        <b-modal ref="password" :visible="askPassword" hide-header hide-footer centered no-close-on-esc no-close-on-backdrop>
          <form>
            <label for="password">{{formatMessage('Password')}}<input id="password" type="password" class="form-control" v-model="password" /></label>
            <b-btn variant="primary" @click.prevent="submitPassword" type="submit">{{formatMessage('Submit')}}</b-btn>
            <b-alert variant="danger" :show="wrongPassword">
              {{formatMessage('Wrong password!')}}
            </b-alert>
          </form>
        </b-modal>
      </div>
      <div>
        <b-modal ref="tine-login" :visible="askTineLogin" hide-header hide-footer centered no-close-on-esc no-close-on-backdrop>
          <form>
            <h2>{{formatMessage('Please log in')}}</h2>
            <label for="tine-user">{{formatMessage('User name')}}<input id="tine-user" type="text" class="form-control" v-model="login.user" /></label>
            <label for="tine-password">{{formatMessage('Password')}}<input id="tine-password" type="password" class="form-control" v-model="login.password" /></label>
            <b-btn variant="primary" @click.prevent="submitTineLogin" type="submit">{{formatMessage('Submit')}}</b-btn>
            <b-alert variant="danger" class="login-error" :show="wrongLogin">
              {{loginErrorMessage}}
            </b-alert>
          </form>
        </b-modal>
      </div>
      <div>
        <!-- formatMessage('Calendar of {name}', {name: activeAttendee.name}) -->
        <b-modal class="calendar-window" ref="calendarWindow" :visible="showCalendarModal" @hide="showCalendarModal = false" hide-footer centered size="lg" :title="formatMessage('Calendar of {name}', {name: activeAttendee.name})">
          <iframe v-if="showCalendarModal" :src="calendarUrl" class="calendar"></iframe>
        </b-modal>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { BModal, BButton, BButtonGroup, BAlert, VBModal } from 'bootstrap-vue'
import Spinner from 'vue-simple-spinner'
import _ from 'lodash'

export default {
  data () {
    return {
      baseUrl: '',
      globalError: '',
      transferingPoll: true,
      showPoll: true,
      poll: {},
      activeAttendee: { name: '', email: '', id: null, user_id: null },
      forceNewAttendee: false,
      showGtc: false,
      gtcText: '',
      hidegtcmessage: true,
      askPassword: false,
      password: '',
      wrongPassword: false,
      statusList: [],
      defaultStatus: 'NEEDS-ACTION',
      askTineLogin: false,
      wrongLogin: false,
      login: { user: '', password: '' },
      loginErrorMessage: '',
      newAccountContact: false,
      showCalendarModal: false,
      calendarUrl: '',
      usePersonalLink: false,
      publicUrl: ''
    }
  },

  watch: {
    poll () {
      if (this.activeAttendee.user_id === null || this.newAccountContact === true) {
        _.each(this.poll.alternative_dates, (date) => {
          this.activeAttendee[date.id] = { status: this.defaultStatus }
        })
      } else {
        _.each(this.poll.attendee_status, (attendee) => {
          if (attendee.user_id === this.activeAttendee.user_id) {
            this.activeAttendee.status = attendee.status
            this.activeAttendee.name = attendee.name
            this.activeAttendee.user_type = attendee.user_type
            this.activeAttendee.user_id = attendee.user_id
          }
        })
      }
    }
  },

  components: {
    'b-modal': BModal,
    'b-btn': BButton,
    'b-button-group': BButtonGroup,
    Spinner,
    'b-alert': BAlert
  },

  directives: {
    'b-modal': VBModal
  },

  mounted () {
    this.loadPoll()

    document.getElementsByClassName('tine-viewport-waitcycle')[0].style.display = 'none'
    document.getElementsByClassName('tine-viewport-poweredby')[0].style.display = 'none'

    let urlParams = window.location.href.substring(window.location.href.indexOf('poll/') + 5).split('/')

    if (urlParams.length > 1) {
      this.activeAttendee.id = urlParams[1]
    }

    this.baseUrl = window.location.href.substr(0, window.location.href.indexOf('/Calendar') + 1)
  },

  methods: {
    onApplyChanges () {
      this.transferingPoll = true
      let action = 'post'
      if (this.activeAttendee.id === null) {
        _.each(this.activeAttendee.status, (date) => {
          date.status = this.activeAttendee[date.id].status
        })
      }

      let needUpdate = false

      let payload = { status: [] }
      if (this.activeAttendee.id === null || this.newAccountContact) {
        action = 'put'
        needUpdate = true

        payload.name = this.activeAttendee.name
        payload.email = this.activeAttendee.email

        _.each(this.poll.alternative_dates, (date) => {
          payload.status.push({
            cal_event_id: date.id,
            status: this.activeAttendee[date.id].status
          })
        })
      } else {
        _.each(this.poll.attendee_status, (attendee) => {
          _.each(attendee.status, (status) => {
            if (status.status_authkey === null || status.status === status.initial) {
              return
            }

            needUpdate = true
            let datePayload = {
              cal_event_id: status.cal_event_id,
              status: status.status,
              user_type: status.user_type,
              user_id: status.user_id,
              status_authkey: status.status_authkey
            }
            payload.status.push(datePayload)
          })
        })
      }

      if (!needUpdate) {
        this.transferingPoll = false
        return
      }

      let url = this.baseUrl + 'Calendar/poll/' + this.poll.id

      let options = { auth: { password: this.password } }

      axios[action](url, payload, options).then((response) => {
        if (action === 'put' && this.activeAttendee.id === null) {
          let first = _.head(response.data)

          if (typeof first === 'undefined') {
            this.transferingPoll = false
            this.loadPoll()
          }

          let target = window.location.href +
            '/' + first.user_type + '-' + first.user_id +
            '/' + first.status_authkey

          window.location.replace(target)
        }

        this.transferingPoll = false
        this.loadPoll()
      }).catch(error => {
        if (error.response.status === 401) {
          if (typeof error.response.data === 'undefined') {
            return
          }

          if (error.response.data.indexOf('poll is locked') > -1) {
            this.poll.locked = 1
            this.askTineLogin = false
          }

          if (error.response.data.indexOf('please log in') > -1) {
            if (this.askTineLogin) {
              this.wrongLogin = true
            }
            this.askTineLogin = true
          }

          if (error.response.data.indexOf('use personal link') > -1) {
            this.askTineLogin = false
            this.usePersonalLink = true
          }
        } else {
          console.log(error)
          console.log(arguments)
        }
      })
    },
    onCancelChanges () {
      this.loadPoll()
    },
    loadPoll () {
      this.transferingPoll = true
      let url = window.location.href.replace(/\/view\//, '/')
      axios.get(url, {
        auth: {
          password: this.password
        }
      }).then(response => {
        if (typeof response.data === 'string') {
          this.globalError = this.formatMessage('An unexpected error occurred.')
          this.transferingPoll = false
          this.showPoll = false
          return
        }

        this.poll = response.data

        this.formatMessage.setup({ locale: this.poll.config.locale || 'en' })

        if (this.poll.config.has_gtc === true) {
          this.hidegtcmessage = false
          this.retrieveGTC()
        }

        this.askPassword = this.poll.password !== null && this.password !== this.poll.password

        let urlParams = window.location.pathname.replace(/\/$/, '').split('/')

        if (!this.forceNewAttendee &&
          typeof this.poll.config.current_contact !== 'undefined' &&
          (urlParams.length <= 5 || !this.poll.config.is_anonymous)
        ) {
          if (this.poll.config.is_anonymous) {
            this.activeAttendee.id = null
            this.forceNewAttendee = true
          } else {
            this.activeAttendee.id = this.poll.config.current_contact.type + '-' + this.poll.config.current_contact.id
            this.activeAttendee.name = this.poll.config.current_contact.n_fn
            this.activeAttendee.email = this.poll.config.current_contact.email
            this.activeAttendee.user_id = this.poll.config.current_contact.id
          }
        }

        this.newAccountContact = true
        if (this.activeAttendee.id === null) {
          this.newAccountContact = false
        } else {
          _.each(this.poll.attendee_status, (attendee) => {
            if (attendee.user_id === this.activeAttendee.user_id) {
              this.newAccountContact = false
            }
          })
        }

        this.defaultStatus = this.poll.config.status_available.default

        if (typeof this.poll.config.jsonKey !== 'undefined') {
          this.$tine20.setJsonKey(this.poll.config.jsonKey)
        }

        let previous = null
        let first = null
        for (var i = 0; i < this.poll.config.status_available.records.length; i++) {
          var status = this.poll.config.status_available.records[i]

          var cellclass = 'table-light'
          switch (status.id) {
            case 'ACCEPTED':
              cellclass = 'table-success'
              break
            case 'DECLINED':
              cellclass = 'table-danger'
              break
            case 'TENTATIVE':
              cellclass = 'table-warning'
              break
          }

          this.statusList[status.id] = {
            icon: status.icon,
            cellclass: cellclass
          }

          if (first === null) {
            first = status.id
          }

          if (previous !== null) {
            this.statusList[previous].next = status.id
          }

          previous = status.id
        }
        this.statusList[previous].next = first

        _.each(this.poll.attendee_status, (attendee) => {
          _.each(attendee.status, (status) => {
            status.initial = status.status
          })
        })

        this.transferingPoll = false
      }).catch(error => {
        if (error.response.status === 401) {
          if (error.response.data.indexOf('authkey mismatch') > -1) {
            this.askPassword = false
            this.globalError = this.formatMessage('Use your personal link please.')
            this.publicUrl = window.location.pathname.replace(/\/$/, '').split('/').slice(0, 5).join('/')
            this.showPoll = false
            this.transferingPoll = false
          } else {
            if (this.askPassword) {
              this.wrongPassword = true
            }
            this.askPassword = true
          }
        } else {
          console.log(error)
          console.log(arguments)
        }
      })
    },
    getAttendeeId (attendee) {
      return attendee.user_type + '-' + _.get(attendee.user_id, 'id', attendee.user_id)
    },
    statusName (status) {
      let statusName = _.get(_.find(_.get(this.poll, 'config.status_available.records', {}), { id: status }), 'value', status)
      return this.fmHidden(statusName)
    },
    nextStatus (attendee, id) {
      if (attendee.status_authkey === null && id !== null) {
        return
      }
      if (!this.showChangeButtons()) {
        return
      }

      attendee.status = this.statusList[attendee.status].next
      this.$forceUpdate()
    },
    onOtherUser () {
      let urlParams = window.location.pathname.replace(/\/$/, '').split('/')

      if (urlParams.length > 5) {
        let target = urlParams.slice(0, -2).join('/')
        window.location.replace(target)
      }

      this.newAccountContact = false
      this.forceNewAttendee = true
      this.activeAttendee = {
        id: null,
        user_id: null,
        name: '',
        email: '',
        status: []
      }

      this.$tine20.request('Tinebase.logout', {}).then(() => {
      }).catch(error => {
        console.log(error)
      }).then(() => {
        this.loadPoll()
      })
    },
    submitPassword () {
      this.wrongPassword = false
      this.loadPoll()
    },
    submitTineLogin () {
      let params = {
        username: this.login.user,
        password: this.login.password
      }

      this.$tine20.request('Tinebase.login', params).then(response => {
        let account = response.result.account
        this.activeAttendee.id = 'user-' + account.contact_id
        this.activeAttendee.name = account.accountDisplayName
        this.activeAttendee.email = account.accountEmailAddress
        this.activeAttendee.user_id = account.contact_id

        this.wrongLogin = false
        this.askTineLogin = false

        // replace with onApplyChanges()
        this.loadPoll()
      }).catch(error => {
        this.wrongLogin = true
        this.loginErrorMessage = error.result.errorMessage
      })
    },
    retrieveGTC () {
      if (this.gtcText.length > 0) {
        return
      }

      let url = window.location.href.substring(0, window.location.href.indexOf('/poll/')) + '/pollagb'
      axios.get(url).then(response => {
        this.gtcText = response.data
        if (this.gtcText.length === 0) {
          this.hidegtcmessage = true
        }
      }).catch(error => {
        console.log(error)
        console.log(arguments)
      })
    },
    showCalendar (date) {
      let calendarUrl = _.get(date, 'info_url', null)

      if (calendarUrl !== null) {
        this.calendarUrl = calendarUrl
        this.showCalendarModal = true
      }
    },
    getStatusIcon (statusId) {
      let iconUrl
      _.each(this.poll.config.status_available.records, (status) => {
        if (status.id === statusId) {
          iconUrl = this.baseUrl + status.icon
        }
      })
      return iconUrl
    },
    getCalendarIcon (date) {
      let start = null
      _.each(this.poll.alternative_dates, (pollDate) => {
        if (date.cal_event_id === pollDate.id) {
          start = pollDate.dtstart
        }
      })

      return this.baseUrl + 'images/icon-set/icon_cal_' + new Date(start.replace(' ', 'T')).getDate() + '.svg'
    },
    showChangeButtons () {
      if (_.isEmpty(this.poll)) {
        return false
      }

      if (this.poll.closed === '1') {
        return false
      }

      if (this.poll.locked === '1' && !this.activeAttendee.id) {
        return false
      }

      return true
    }
  }
}
</script>

<style scoped>
#root {
  padding: 10px;
  color: #555;
}

h1 {
  font-size: 2rem;
  color: #000;
}

h2 {
  font-size: 1.5rem;
  color: #222;
}

button {
  background-color: #DCE8F5;
  color: #222;
  border: 1px solid #008CC9;
}

button:hover {
  background-color: #FFF;
  color: #222;
  border: 1px solid #008CC9;
}

button:active {
  background-color: #DCE8F5;
  color: #222;
  border: 2px solid #008CC9;
}

.greetings {
  margin-top: 10px;
  margin-bottom: 25px;
}

.greetings-text {
  position: relative;
}

.greetings-text p {
  position: absolute;
  bottom: 0;
  display: inline-block;
  margin-bottom: 0;
}

td.table-info {
  background-color: #DCE8F5;
}

td.icon-cell {
  text-align: center;
  vertical-align: middle;
}

tr.row-active td.icon-cell {
  filter: brightness(0.9);
}

tr.row-active td {
  font-weight: bold;
}

td.name-field {
  padding: 5px 5px 5px 5px;
}

td.editable {
  cursor: pointer;
  user-select: none;
}

input.name-field {
  border-radius: 0px;
  margin-bottom: 5px;
  float: left;
  padding: 2px 5px 2px 5px;
}

input.email-field {
  border-radius: 0px;
  padding: 2px 5px 2px 5px;
}

.footer {
  margin-top: 100px;
  padding: 5px;
}

th {
  font-weight: normal;
  text-align: center;
}

div.modified-marker {
  display: block;
  position: relative;
  float: left;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 8px 8px 0 0;
  border-color: #ff0000 transparent transparent transparent;
  margin: -0.75rem 0 0 -0.75rem;
}

.login-error {
  margin-top: 10px;
}

span.calendar-symbol {
  display: absolute;
  float: left;
  margin-right: -21px;
  margin-left: 5px;
}

iframe.calendar {
  width: 100%;
  border: none;
}

@media (min-height: 700px) {
  iframe.calendar {
    min-height: 600px;
  }
}

@media (max-height: 699px) {
  iframe.calendar {
    min-height: 400px;
  }
}
</style>

<style>
  .calendar-window .modal-body {
    padding: 0;
  }

  .icon-cell img {
    width: 24px;
    margin: -2px;
  }

  button {
    cursor: pointer;
  }

  .modal-lg {
    width: 90%;
    height: 90%;
  }

  .modal-content {
    max-height: none;
  }

@media (min-width: 992px) {
  .modal-lg {
    max-width: none;
    max-height: none;
  }
}
</style>
