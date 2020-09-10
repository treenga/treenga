<template>
  <div>
    <el-row
      type="flex"
      justify="center"
    >
      <el-col :span="12">
        <new-password-form
          @submit="onFormSubmit($event)"
        />
      </el-col>
    </el-row>
  </div>
</template>

<script>
import { NewPasswordForm } from '@/js/components'
import { mapActions } from 'vuex'

export default {
  name: 'NewPassword',

  components: {
    NewPasswordForm,
  },

  methods: {
    ...mapActions([
      'resetPassword',
    ]),

    onFormSubmit(data) {
      data.hash = this.$route.params.hash
      this.resetPassword(data).then((response) => {
        if (response.data) {
          this.$router.push('/login')
        }
      })
    },
  },
}
</script>
