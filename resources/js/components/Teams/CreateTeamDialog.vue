<template>
  <div>
    <create-team-form
      ref="createTeamForm"
      @validationChange="onValidationChange($event)"
      @submit="onFormSubmit"
    />

    <div
      class="text-right"
    >
      <el-button
        type="success"
        class="create-team-button"
        @click="onFormSubmit"
      >
        Create
      </el-button>

      <el-button
        type="default"
        @click="onClose"
      >
        Close
      </el-button>
    </div>
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
import CreateTeamForm from './CreateTeamForm'

export default {
  name: 'CteateTeamDialog',

  components: {
    CreateTeamForm,
  },

  data() {
    return {
      form: false,
    }
  },

  computed: {
    ...mapState({
      isVisible: state => state.teams.isCreateDialogOpened,
    }),
  },

  methods: {
    ...mapMutations([
      'createTeamDialogClosed',
    ]),

    onDialogClose() {
      this.$emit('close')
      this.createTeamDialogClosed()
    },

    onValidationChange(status) {
      this.form = status
    },

    resetForm() {
      this.$refs.createTeamForm.resetForm()
    },

    onFormSubmit() {
      this.$refs.createTeamForm.onFormSubmit()
      if (!this.form) return

      this.$emit('submit', { ...this.form })
    },

    onClose() {
      this.$emit('close')
    },
  },
}
</script>

<style lang="scss" scoped>
  @import 'resources/sass/_variables.scss';

  .content {
    padding: 50px 50px 30px 50px;
  }

  .text-right {
    text-align: right;

    .create-team-button {
      width: 175px;
    }
  }
</style>
