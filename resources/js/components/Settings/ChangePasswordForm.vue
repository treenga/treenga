<template>
  <div class="form-wrapper">
    <h3>Change password</h3>
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

      <!-- <tr-password-input
        v-model="form.new_password_confirmation"
        placeholder="Password confirmation"
        prop="new_password_confirmation"
      /> -->

      <div class="buttons d-flex">
        <el-button
          type="primary"
          @click="onFormSubmit"
        >
          Change
        </el-button>

        <el-button
          @click.prevent="onCancelClick"
        >
          Cancel
        </el-button>
      </div>
    </el-form>
  </div>
</template>

<script>
import { mapMutations } from 'vuex'
import TrPasswordInput from '../Inputs/TrPasswordInput'

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
      },
    }
  },

  methods: {
    ...mapMutations([
      'alertError',
    ]),

    onFormSubmit() {
      this.$refs.changePasswordForm.validate((valid) => {
        if (valid && this.checkIfPassNotSame()) {
          this.$emit('submit', this.form)
        }
      })
    },

    resetForm() {
      this.form.old_password = ''
      this.form.new_password = ''
    },

    onCancelClick() {
      this.$refs.changePasswordForm.resetFields()
      this.$emit('reset')
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
