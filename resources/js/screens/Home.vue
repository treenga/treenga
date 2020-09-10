<template>
  <div>
    Home
  </div>
</template>

<script>
import { TrTrixInput } from '@/js/components'
import { mapState } from 'vuex'
import Echo from 'laravel-echo'
import store from '../store'
// window.io = require('socket.io-client');
window.Pusher = require('pusher-js');

export default {
  name: 'Home',

  components: {
    TrTrixInput,
  },

  data() {
    return {
      trix: '',
    }
  },

  computed: {
    ...mapState({
      selectedTeam: state => state.teams.selectedTeam,
      categories: state => state.categories.categories,
      selectedCategory: state => state.categories.selectedCategory,
    }),
  },

  methods: {
    openTeamConnection(teamId) {
      const { token } = store.state.auth;

      window.Echo = new Echo({
        // broadcaster: 'socket.io',
        // host: window.location.hostname + ':6001',
        broadcaster: 'pusher',
        key: process.env.PUSHER_APP_KEY,
        auth: {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      });

      window.Echo.private(`team.${teamId}`)
        .listen('Category.Created', (e) => {
          console.log('socket', e);
        });
    },
  },
}
</script>
