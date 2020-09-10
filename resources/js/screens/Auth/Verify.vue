<template>
  <div/>
</template>

<script>
import { mapActions } from 'vuex'

export default {
  name: 'Verify',

  created() {
    const { hash } = this.$route.params
    this.verifyEmail(hash)
  },

  methods: {
    ...mapActions([
      'verifyEmail',
    ]),
  },
}
</script>

<style>

</style>
