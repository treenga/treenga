<template>
  <el-row
    v-if=" ! showSuccessRegisterMessage"
    class="row auth"
    type="flex"
  >
    <el-col :span="24">
      <router-view />
    </el-col>
  </el-row>

  <div
    v-else
    class="message-wrapper"
  >
    <el-alert
      :closable="false"
      title=""
      type="success"
      show-icon>
      <span style="font-size: 20px; font-weight: 500;">
        Great! Now check your email for the confirmation link
      </span>
      <br>
      Or
      <a
        class="back-link"
        @click="onShowForm"
      >
        try again
      </a>
    </el-alert>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'

export default {
  name: 'Auth',

  computed: {
    ...mapState({
      showSuccessRegisterMessage: state => state.auth.showSuccessRegisterMessage,
    }),
  },

  methods: {
    ...mapMutations([
      'registerSuccess',
    ]),

    ...mapActions([
      'login',
    ]),

    onShowForm() {
      this.registerSuccess(false)
    },
  },
}
</script>

<style lang="scss" scoped>
  @import 'resources/sass/_variables.scss';
  .row {
    padding-top: 20px;

    .mid-section {
      position: relative;
      text-align: center;
      min-height: 200px;

      &::after {
        content: '';
        position: absolute;
        top: -20px;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        border-right: 1px solid $light-gray;
        border-left: none;
      }

      .text {
        position: relative;
        background-color: white;
        font-size: $title;
        opacity: .99;
        height: 20px;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        transform: translateY(-15%);
      }
    }
  }
</style>
