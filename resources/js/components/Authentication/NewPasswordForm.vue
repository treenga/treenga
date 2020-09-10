<template>
  <div class="form-wrapper">
    <h3 class="text-center">{{ title }}</h3>
    <el-form
      ref="newPasswordForm"
      :model="form"
      novalidate
      @submit.native.prevent="onFormSubmit"
    >
      <tr-password-input
        v-model="form.password"
        placeholder="Password"
      />

      <!-- <tr-password-input
        v-model="form.password_confirmation"
        placeholder="Password confirmation"
        prop="password_confirmation"
      /> -->

      <el-form-item class="buttons">
        <el-row
          type="flex"
          justify="between"
        >
          <el-button
            type="primary"
            @click="onFormSubmit"
          >
            {{ submitText }}
          </el-button>

          <router-link
            v-show="isBackLink"
            to="/login"
            class="big-route-link"
          >
            Back to login
          </router-link>
        </el-row>
      </el-form-item>
    </el-form>
  </div>
</template>

<script>
import { mapMutations } from 'vuex'
import TrPasswordInput from '../Inputs/TrPasswordInput'

export default {
  name: 'NewPasswordForm',

  components: {
    TrPasswordInput,
  },

  props: {
    title: {
      type: String,
      default: 'Type new password',
    },

    submitText: {
      type: String,
      default: 'Set password',
    },

    isBackLink: {
      type: Boolean,
      default: true,
    },
  },

  data() {
    return {
      form: {
        password: '',
      },
    }
  },

  methods: {
    ...mapMutations([
      'alertError',
    ]),

    onFormSubmit() {
      if (this.form.password) {
        this.$emit('submit', this.form)
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.form-wrapper {
  h3 {
    margin-top: 0;
  }
}
</style>
