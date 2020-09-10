<template>
  <div class="form-wrapper">
    <el-form
      ref="changePasswordForm"
      :model="form"
      novalidate
      @keyup.native.enter="onFormSubmit"
    >
      <tr-password-input
        :autofocus="true"
        v-model="form.old_password"
        placeholder="Old password"
        prop="old_password"
      />

      <tr-password-input
        v-model="form.new_password"
        placeholder="New password"
        prop="new_password"
      />

      <tr-password-input
        v-model="form.new_password_confirmation"
        placeholder="Password confirmation"
        prop="new_password_confirmation"
      />

      <div class="buttons d-flex">
        <el-button
          type="primary"
          @click="onFormSubmit"
        >
          Change password
        </el-button>
      </div>
    </el-form>
  </div>
</template>

<script>
import { mapMutations, mapActions } from 'vuex'
import TrPasswordInput from '../../../js/components/Inputs/TrPasswordInput.vue'

export default {
  name: 'ChangePasswordForm',

  components: {
    TrPasswordInput,
  },

  data() {
    return {
      form: {
        old_password: '',
        new_password: '',
        new_password_confirmation: '',
      },
    }
  },

  methods: {
    ...mapMutations([
      'alertError',
    ]),

    ...mapActions([
      'changePassword',
    ]),

    onFormSubmit() {
      this.$refs.changePasswordForm.validate((valid) => {
        if (valid && this.checkIfPassNotSame()) {
          this.changePassword(this.form)
            .then((response) => {
              if (response.data) {
                this.$router.push('/admin')
              }
            })
        }
      })
    },

    checkIfPassNotSame() {
      if (this.form.new_password !== this.form.old_password) {
        return true
      }

      this.alertError({
        title: 'System message',
        text: 'Old and new passwords are the same',
      })
      return false
    },
  },
}
</script>
<style scoped lang="scss">
.form-wrapper {
  margin: 20px 55px 20px 20px;
}
</style>
