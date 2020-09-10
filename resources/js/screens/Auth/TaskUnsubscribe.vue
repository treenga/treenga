<template>
  <span/>
</template>


<script>
import { mapActions } from 'vuex'

export default {
  name: 'TaskUnsubscribe',

  created() {
    this.publicTaskUnsubscribe(this.$route.params.hash)
  },

  methods: {
    ...mapActions([
      'publicTaskUnsubscribe',
    ]),
  },
}
</script>
