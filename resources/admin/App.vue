<template>
  <div>
      <router-view />
      <Alert />
  </div>  
</template>

<script>
import { Alert } from '@/admin/components'
import { mapState, mapMutations, mapActions } from 'vuex'

export default {
  name: 'App',
  components: {
    Alert
  }
}
</script>

<style lang="scss" scoped>
  @import "./admin.css";
  @import "./custom.css";
</style>