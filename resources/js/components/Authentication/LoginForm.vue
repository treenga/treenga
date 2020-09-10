<template>
  <div class="form-wrapper">
    <el-form
      ref="loginForm"
      :model="form"
      novalidate
      @submit.native.prevent="onFormSubmit"
      @keyup.native.enter="onFormSubmit"
    >
      <el-form-item
        :rules="tr_rules.auth_email"
        class="input"
        prop="email"
      >
        <el-input
          ref="inputEmail"
          v-model="form.email"
          placeholder="Email"
          type="email"
        />
      </el-form-item>

      <div>
        <tr-password-input v-model="form.password" />
      </div>

      <el-form-item class="buttons">
        <el-row
          type="flex"
          justify="center"
        >
          <el-button
            type="primary"
            @click="onFormSubmit"
          >
            Login
          </el-button>
        </el-row>
      </el-form-item>

      <router-link
        to="/recovery"
        class="recovery-link"
      >
        Forgot password?
      </router-link>
    </el-form>
  </div>
</template>

<script>
import TrPasswordInput from '../Inputs/TrPasswordInput'
import Validation from '@/js/mixins/Validation'

export default {
  name: 'LoginForm',

  components: {
    TrPasswordInput,
  },

  mixins: [Validation],

  data() {
    return {
      form: {
        email: '',
        password: '',
      },
    }
  },

  methods: {
    onFormSubmit() {
      this.$refs.loginForm.validate((valid) => {
        if (valid) {
          this.$emit('submit', this.form)
        }
      })
    },
  },
}
</script>

<style lang="scss" scoped>
  @import 'resources/sass/_variables.scss';

  .form-wrapper {
    h3 {
      margin-top: 0;
    }

    .input {
      margin-bottom: 22px;
    }

    .el-button {
      width: 100%;
    }

    .recovery-link {
      font-size: $small-body;
    }
  }
</style>
