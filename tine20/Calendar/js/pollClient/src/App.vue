<template>
  <div id="root" class="container">
    <template v-if="!transferingPoll">
      <div class="row">
        <h1>{{ poll.name }}</h1>
      </div>
      <div class="row">
        <!-- grid goes here -->
      </div>
      <div class="row">
        <b-button-group class="mx-1">
          <b-btn @click="onCancelChanges" variant="secondary">Abbrechen</b-btn>
          <b-btn @click="onApplyChanges" variant="primary">Speichern</b-btn>
        </b-button-group>
      </div>
    </template>
    <div>
      <b-modal ref="loadMask" :visible="transferingPoll" hide-header hide-footer no-fade no-close-on-esc no-close-on-backdrop centered>
        <spinner size="medium" :message="$gettext('Bitte warten...')"></spinner>
      </b-modal>
    </div>
  </div>
</template>

<script>
  import axios from 'axios'
  import bModal from 'bootstrap-vue/es/components/modal/modal'
  import bModalDirective from 'bootstrap-vue/es/directives/modal/modal'
  import bBtn from 'bootstrap-vue/es/components/button/button'
  import bBtnGrp from 'bootstrap-vue/es/components/button-group/button-group'
  import Spinner from 'vue-simple-spinner'
  import _ from 'lodash'

  export default {
    data () {
      return {
        transferingPoll: true,
        poll: {}
      }
    },
    computed: {
      event () {
        return _.first(this.poll.alternative_dates)
      },
      attendees () {
        return _.reduce(this.poll.alternative_dates, (attendees, date) => {
          _.each(date.attendee, (attendee) => {
            const id = this.getAttendeeId(attendee)
            _.set(attendees, id, _.set(_.get(attendees, id, _.assign({}, attendee)), date.id, attendee))
          })
        }, {})
      }
    },
    components: {
      'b-modal': bModal,
      'b-btn': bBtn,
      'b-button-group': bBtnGrp,
      Spinner
    },
    directives: {
      'b-modal': bModalDirective
    },
    mounted () {
      this.transferingPoll = true
      let url = window.location.href.replace(/\/view\//, '/')
      axios.get(url, {
        auth: {
          password: 's00pers3cret'
        }
      }).then(response => {
        this.poll = response.data
        this.transferingPoll = false
      }).catch(error => {
        console.log(error)
        console.log(arguments)
      })
    },
    methods: {
      onApplyChanges () {
        this.transferingPoll = true
      },
      getAttendeeId (attendee) {
        return attendee.user_type + '-' + _.get(attendee.user_id, 'id', attendee.user_id)
      }
    }
  }
</script>

<style scoped>
  #root {
    padding: 10px;
  }
  h1 {
    color: red;
  }
</style>
