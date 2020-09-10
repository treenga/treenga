<template>
  <registration-form
    @submit="onRegisteration($event)"
  />
</template>

<script>
import { RegistrationForm } from '@/js/components'
import { mapActions, mapMutations } from 'vuex'

export default {
  name: 'Registration',

  components: {
    RegistrationForm,
  },

  methods: {
    ...mapActions([
      'register',
    ]),

    ...mapMutations([
      'alertSuccess',
    ]),

    onRegisteration(data) {
      data.link = `${window.location.origin}/verify/__hash__`

      this.register(data)
    },
  },
}
</script>
