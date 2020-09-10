<template>
  <div class="auth-wrapper">
    <div
      class="auth-wrapper-inner"
    >
      <img
        :src="require('@/img/icon-text-white.svg')"
        class="auth-logo"
      >

      <el-card
        :class="{thin: isThin}"
        shadow="never"
        class="box-card"
      >
        <div class="card-body">
          <router-view />
        </div>
      </el-card>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Public',

  data() {
    return {
      isThin: false,
    }
  },

  watch: {
    $route() {
      this.isThin = this.$route.path === '/recovery'
    },
  },

  mounted() {
    this.isThin = this.$route.path === '/recovery'
  },
}
</script>

<style lang="scss" scoped>
@import "resources/sass/_variables.scss";

.auth-wrapper {
  background-color: $light-gray;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;

  .auth-wrapper-inner {
    position: relative;

    .auth-logo {
      position: absolute;
      z-index: 0;
      width: 88px;
      top: -115px;
      left: 50%;
      margin-left: -44px;
    }
  }

  .box-card {
    padding: 50px;

    .card-body {
      width: 300px;

      .row {
        padding-top: 0;
      }
    }

    &.thin {
      .card-body {
        width: 440px;
      }
    }
  }
}
</style>
