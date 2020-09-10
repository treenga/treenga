<template>
  <div>
    <el-form
      ref="createTeamForm"
      :model="form"
      :hide-required-asterisk="true"
      novalidate
      label-position="top"
      class="create-team-form"
      @keyup.native.enter="onEnter"
    >
      <el-form-item
        :rules="tr_rules.required_change"
        class="input"
        prop="name"
      >
        <template slot="label">
          <div class="input-label">
            <span>*</span> Team name

            <div class="counter">
              {{ 64 - form.name.length }}
            </div>
          </div>
        </template>

        <el-input
          v-model="form.name"
          placeholder="As seen at leftmost tab(s)"
          maxlength="64"
          type="text"
        />
      </el-form-item>

      <el-form-item
        :rules="tr_rules.required_change"
        class="input"
        prop="username"
      >
        <template slot="label">
          <div class="input-label">
            <span>*</span> Set your name. It should be unique per team

            <div class="counter">
              {{ 64 - form.username.length }}
            </div>
          </div>
        </template>

        <el-input
          v-model="form.username"
          placeholder="Your name..."
          maxlength="64"
          type="text"
        />
      </el-form-item>
    </el-form>
  </div>
</template>

<script>
import Validation from '@/js/mixins/Validation'

export default {
  name: 'CreateTeamForm',

  mixins: [Validation],

  props: {
    team: {
      type: Object,
      default: () => ({
        name: '',
        username: '',
      }),
    },
  },

  data() {
    return {
      form: {
        name: '',
        username: '',
      },
    }
  },

  watch: {
    team: {
      handler() {
        this.updateForm()
      },
      deep: true,
    },
  },

  created() {
    this.updateForm()
  },

  methods: {
    onFormSubmit() {
      this.$refs.createTeamForm.validate((valid) => {
        if (valid) {
          this.$emit('validationChange', this.form)
        }
      })
    },

    updateForm() {
      this.form.name = this.team.name
      this.form.username = this.team.username
    },

    onEnter() {
      this.$refs.createTeamForm.validate((valid) => {
        if (valid) {
          this.$emit('validationChange', this.form)
          this.$emit('submit')
        }
      })
    },

    resetForm() {
      this.$refs.createTeamForm.resetFields()
      this.updateForm()
    },
  },
}
</script>
