<template>
  <div>
    <el-row
      type="flex"
      justify="center"
    >
      <el-col
        v-if=" ! showSuccessRecoveryMessage"
        :span="12"
      >
        <h3>Enter your Email</h3>

        <el-form
          ref="passwordRecoveryForm"
          :model="form"
          novalidate
          @submit.native.prevent="onFormSubmit"
        >
          <el-form-item
            :rules="tr_rules.email"
            class="input"
            prop="email"
          >
            <el-input
              v-model="form.email"
              :autofocus="true"
              placeholder="Email"
              type="email"
            />
          </el-form-item>

          <el-form-item class="buttons">
            <el-row
              type="flex"
              justify="between"
            >
              <el-button
                type="primary"
                @click="onFormSubmit"
              >
                Send instructions
              </el-button>

              <router-link
                class="recovery-link"
                to="/login"
              >
                Back to login
              </router-link>
            </el-row>
          </el-form-item>
        </el-form>
      </el-col>

      <el-col
        v-else
        :span="12"
      >
        <div
          class="message-wrapper"
        >
          <el-alert
            :closable="false"
            title=""
            type="success"
            show-icon>
            <b>Check your email</b><br>
            Or
            <a
              class="back-link"
              @click="onShowForm"
            >
              go back to login
            </a>
          </el-alert>
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import Validation from '@/js/mixins/Validation'

export default {
  name: 'PasswordRecovery',

  mixins: [Validation],

  data() {
    return {
      form: {
        email: '',
        link: `${window.location.origin}/recovery/new/__hash__`,
      },
    }
  },

  computed: {
    ...mapState({
      showSuccessRecoveryMessage: state => state.auth.showSuccessRecoveryMessage,
    }),
  },

  methods: {
    ...mapMutations([
      'recoverySuccess',
    ]),

    ...mapActions([
      'recoverPassword',
    ]),

    onFormSubmit() {
      this.$refs.passwordRecoveryForm.validate((valid) => {
        if (valid) {
          this.sendRequest()
        }
      })
    },

    sendRequest() {
      this.recoverPassword(this.form).then((response) => {
        if (response.data) {
          this.form.email = ''
        }
      })
    },

    onShowForm() {
      this.recoverySuccess(false)
      this.$router.push('/login')
    },
  },
}
</script>

<style lang="scss" scoped>
  .message-wrapper {
    height: 237px;
  }
</style>
