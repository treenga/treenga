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
          setTimeout(() => {
            this.$notify[message.type]({
              title: message.title,
              message: message.text,
              position: 'top-right',
            })
          }, 50)
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
