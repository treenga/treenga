<template>
  <div/>
</template>

<script>
import { mapState, mapMutations } from 'vuex'

export default {
  name: 'Alert',

  computed: {
    ...mapState({
      list: state => state.alerts.list,
    }),
  },

  watch: {
    list(value) {
      if (value.length) {
        value.forEach((message) => {
          this.$toasted.show(message.text, {...message, duration: 1500});
        })

        this.alertClear()
      }
    },
  },

  methods: {
    ...mapMutations([
      'alertClear',
    ]),
  },
}
</script>
<style lang="scss" scoped>

</style>

