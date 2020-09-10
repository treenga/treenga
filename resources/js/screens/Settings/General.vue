<template>
  <el-scrollbar class="settings-scroll">
    <div class="settings">
      <h2>Email and password</h2>

      <div>
        Email: <span class="email">{{ user.email }}</span>

        <el-button
          type="text"
          class="email-button"
          @click="onChangeEmailClick"
        >
          Change email
        </el-button>

        <el-button
          type="text"
          class="email-button"
          @click="onChangePasswordClick"
        >
          Change password
        </el-button>
      </div>

      <el-row>
        <el-col :span="22">
          <new-email-form
            v-if="blockToShow === 'email'"
            ref="emailForm"
            :value="user.email"
            class="email-form"
            @submit="onEmailFormSubmit($event)"
            @reset="onResetFromClick"
          />

          <change-password-form
            v-if="blockToShow === 'password'"
            ref="passForm"
            class="password-form"
            @submit="onPassFormSubmit($event)"
            @reset="onResetFromClick"
          />
        </el-col>
      </el-row>

      <p>
        To change your username, select the relevant team and click the ellipsis menu
        near the search field at the bottom of the screen
      </p>

      <h2 class="sharing__title">Service termination</h2>

      <el-collapse v-model="activeNames">
        <el-collapse-item
          title="Delete account"
          name="delete"
        >
          <div>
            <p>
              Choosing to delete an account, including all teams, team members,
              categories, tasks and files, will remove EVERYTHING created within all teams,
              including all private and public tasks, all team members, all categories
              and all team settings.
            </p>

            <p>
              If you are sure you want to delete an account,  enter your password in the
              field below and click "Delete account" to confirm.
            </p>

            <p>
              Account deletion is irreversible, be sure to back up all important data beforehand!
            </p>

            <el-form
              ref="deleteForm"
              :model="form"
              class="delete-account-form"
              novalidate
            >
              <el-form-item
                :rules="tr_rules.required"
                class="input"
                prop="deleteInput"
              >
                <el-input
                  ref="deleteInput"
                  v-model="form.deleteInput"
                  size="large"
                  type="password"
                  placeholder="Your password"
                />
              </el-form-item>

              <el-button
                type="danger"
                class="delete-account__btn"
                @click="onDeleteAccountClick"
              >
                Delete account
              </el-button>
            </el-form>
          </div>
        </el-collapse-item>
      </el-collapse>
    </div>
  </el-scrollbar>
</template>

<script>
import { mapState, mapActions } from 'vuex'
import Validation from '@/js/mixins/Validation'
import { NewEmailForm, ChangePasswordForm } from '@/js/components'

export default {
  name: 'General',

  components: {
    NewEmailForm,
    ChangePasswordForm,
  },

  mixins: [Validation],

  data() {
    return {
      activeNames: [],
      form: {
        deleteInput: '',
      },
      blockToShow: '',
      emailForm: {
        email: '',
      },
      passForm: {
        old: '',
        new: '',
        new_confirm: '',
      },
    }
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
    }),
  },

  methods: {
    ...mapActions([
      'changeEmail',
      'changePassword',
      'deleteUserAccount',
    ]),

    onChangeEmailClick() {
      this.blockToShow = 'email'
    },

    onChangePasswordClick() {
      this.blockToShow = 'password'
    },

    onResetFromClick() {
      this.blockToShow = ''
    },

    onEmailFormSubmit(form) {
      this.changeEmail(form).then((response) => {
        if (response.data) {
          this.$refs.emailForm.onCancelClick()
        }
      })
    },

    onPassFormSubmit(form) {
      return this.changePassword(form).then((response) => {
        if (response.data) {
          this.$refs.passForm.onCancelClick()
        }
      })
    },

    onDeleteAccountClick() {
      this.$refs.deleteForm.validate((valid) => {
        if (valid) {
          if (this.form.deleteInput) {
            this.deleteUserAccount({ password: this.form.deleteInput.trim() })
          }
        }
      })
    },
  },
}
</script>

<style lang="scss" scoped>
  @import 'resources/sass/_variables.scss';

  .settings {
    padding: 50px;

    h2 {
      margin-top: 0;
      margin-bottom: 35px;

      &.sharing__title {
        margin-top: 50px;
      }
    }

    .email {
      margin-right: 20px;
    }

    .email-button {
      font-size: 16px;
      font-weight: 400;
    }

    .email-form,
    .password-form {
      margin-bottom: 50px;
      max-width: 300px;
    }

    .delete-account-form {
      max-width: 300px;
    }
  }
</style>
