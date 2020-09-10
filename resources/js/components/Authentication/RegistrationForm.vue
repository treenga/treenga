<template>
  <div class="form-wrapper">
    <h3>Register with email and password</h3>
    <el-form
      ref="registrationForm"
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
        <tr-password-input
          v-model="form.password"
        />
      </div>

      <el-form-item class="buttons">
        <el-row
          type="flex"
          justify="between"
        >
          <el-button
            type="primary"
            @click="onFormSubmit"
          >
            Register
          </el-button>

          <router-link
            to="/login"
            class="big-route-link"
          >
            Login?
          </router-link>
        </el-row>
      </el-form-item>

      <span class="recovery-link">
        By registering you agree to
        <a
          href="https://treenga.com/terms.html"
          target="_blank"
        >
          Terms of Use
        </a>
      </span>
    </el-form>
  </div>
</template>

<script>
import TrPasswordInput from '../Inputs/TrPasswordInput'
import Validation from '@/js/mixins/Validation'

export default {
  name: 'RegistrationForm',

  components: {
    TrPasswordInput,
  },

  mixins: [Validation],

  data() {
    return {
      isPassword: true,
      form: {
        email: '',
        password: '',
      },
    }
  },

  computed: {
    isRegistrationValid() {
      return !!this.form.email && !!this.form.password
    },
  },

  methods: {
    onFormSubmit() {
      this.$refs.registrationForm.validate((valid) => {
        if (valid) {
          this.$emit('submit', this.form)
        }
      })
    },

    togglePassword() {
      this.isPassword = !this.isPassword
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

    .recovery-link {
      font-size: $small-body;
    }
  }

</style>
