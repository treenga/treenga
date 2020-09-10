<template>
  <div>
    <el-row
      type="flex"
      justify="center"
    >
      <el-col :span="12">
        <h2>Set your password</h2>

        <el-input
          v-model="email"
          :disabled="true"
        />

        <new-password-form
          ref="passwordForm"
          :is-back-link="false"
          submit-text="Create account"
          title=""
          @submit="onFormSubmit($event)"
        />
      </el-col>
    </el-row>
  </div>
</template>

<script>
import { NewPasswordForm } from '@/js/components'
import { mapActions, mapMutations } from 'vuex'

export default {
  name: 'EmailRegistration',

  components: {
    NewPasswordForm,
  },

  data() {
    return {
      email: 'example@gmail.com',
    }
  },

  created() {
    this.getEmailByHash(this.$route.params.hash).then((response) => {
      if (response.data) {
        this.email = response.data.email
        return
      }

      this.$router.push('/')
    })
  },

  methods: {
    ...mapActions([
      'getEmailByHash',
      'registerUserByHash',
    ]),

    ...mapMutations([
      'loggedIn',
    ]),

    onFormSubmit(data) {
      this.registerUserByHash({ body: data, hash: this.$route.params.hash }).then((response) => {
        if (response.data) {
          this.loggedIn({ token: response.data.access_token })
          this.$router.push('/')
        }
      })
    },
  },
}
</script>
