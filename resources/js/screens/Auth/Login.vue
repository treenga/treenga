<template>
  <login-form
    @submit="onLogin($event)"
  />
</template>

<script>
import { LoginForm } from '@/js/components'
import { mapActions } from 'vuex'

export default {
  name: 'Login',

  components: {
    LoginForm,
  },

  methods: {
    ...mapActions([
      'login',
    ]),

    onLogin(data) {
      this.login(data)
    },
  },
}
</script>
