<template>
  <div
    v-loading.fullscreen.lock="isLoader"
    class="root"
  >
    <router-view />

    <alert />

    <div
      class="fake-notification"
      :class="{opened: fakeNotification.opened, closed: fakeNotification.closed}"
    >
      <span>Canceled.</span>
        <button class="fake-notification__link" @click="undoEvent">
          Undo
        </button>
    </div>
  </div>
</template>

<script>
import { Alert } from '@/js/components'
import { mapState, mapMutations, mapActions } from 'vuex'
import pusher from '@/js/pusher'

export default {
  name: 'App',

  components: {
    Alert,
  },

  computed: {
    ...mapState({
      isLoader: state => state.loader.isLoader,
      token: state => state.auth.token,
      fakeNotification: state => state.alerts.fakeNotification,
    }),
  },

  watch: {
    token(val) {
      if (val) {
        this.getCurrentUserInfo()
        pusher.init()
      }
    },
  },

  created() {
    const token = this.$cookie.get('token')
    if (token) {
      this.loggedIn({ token })
      pusher.init()
      this.getCurrentUserInfo()
    }
  },

  methods: {
    ...mapMutations([
      'loggedIn',
      'loggedOut',
      'userUpdated',
    ]),

    ...mapActions([
      'getUserData',
      'restoreTempVersion',
      'toggleIsEdit',
    ]),

    logout() {
      this.loggedOut()
      pusher.closeUserListener()
      pusher.closeTeamChannel()
    },

    getCurrentUserInfo() {
      this.getUserData().then(() => {
        pusher.openUserChannel()
        pusher.listenUserUpdate(this.userUpdated)
        pusher.listenLogout(this.logout)
        pusher.listenUserChannel()
        pusher.listenUserRemovedFromTeam()
      })
    },

    undoEvent() {
      this.fakeNotification.opened = false
      if ( ! this.fakeNotification.isDraft) {
        this.restoreTempVersion({ teamId: this.$route.params.teamSlug, taskId: this.fakeNotification.taskId}).then(() => {
          let { taskId, teamSlug } = this.$route.params
          this.toggleIsEdit({taskId, teamSlug})
          this.fakeNotification.onUndo()
        })
      } else {
        this.$router.push(`/team/${this.$route.params.teamSlug}/create-task`)
      }
    },
  },
}
</script>

<style lang="scss">
  @import 'resources/sass/app.scss';

  .fake-notification {
    height: 20px;
    padding: 0 10px;
    background-color: #FDF6EC;
    font-size: 13px;
    color: #909399;
    position: fixed;
    right: 50%;
    top: -20px;
    opacity: 1;
    z-index: 2001;
  }

  .fake-notification.opened {
    transition: opacity 1s, top .3s, ease .3s;
    top: 0;
  }

  .fake-notification.closed {
    transition: opacity 4s, top 2s, ease 1s;
    top: -20px;
    opacity: 0;
  }

  .fake-notification__link {
    background: none;
    border: none;
    color: $orange;
    padding: 0;
    text-decoration: underline;
  }

  .fake-notification__link:hover {
    text-decoration: none;
    color: $brown;
  }
</style>
