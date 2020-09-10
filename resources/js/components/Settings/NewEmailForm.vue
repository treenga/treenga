<template>
  <el-form
    ref="form"
    :model="form"
    novalidate
    @keyup.native.enter="onFormSubmit"
  >
    <h3>Change Email</h3>
    <el-form-item
      :rules="tr_rules.email"
      class="input"
      prop="email"
    >
      <el-input
        v-model="form.email"
        :autofocus="true"
        placeholder="New email"
        type="email"
      />
    </el-form-item>

    <tr-password-input
      v-model="form.password"
      placeholder="Your Treenga password"
      prop="password"
    />

    <div class="is-flex">
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
</template>

<script>
import { mapState, mapMutations } from 'vuex'
import Validation from '@/js/mixins/Validation'
import TrPasswordInput from '../Inputs/TrPasswordInput'

export default {
  name: 'NewEmailForm',

  components: {
    TrPasswordInput,
  },

  mixins: [Validation],

  props: {
    value: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      form: {
        email: '',
        password: '',
      },
    }
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
    }),
  },

  methods: {
    ...mapMutations([
      'alertError',
    ]),

    onFormSubmit() {
      this.$refs.form.validate((valid) => {
        if (valid && this.checkIfNotSame()) {
          this.$emit('submit', { ...this.form, link: `${window.location.origin}/verify/__hash__` })
        }
      })
    },

    checkIfNotSame() {
      if (this.form.email !== this.user.email) {
        return true
      }

      this.alertError({
        title: 'System message',
        text: 'Old and new emails are same',
      })
      return false
    },

    onCancelClick() {
      this.$refs.form.resetFields()
      this.$emit('reset')
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
