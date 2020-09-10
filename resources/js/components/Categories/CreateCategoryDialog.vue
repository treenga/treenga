<template>
  <div class="content">
    <create-category-form
      ref="createCategoryForm"
      :is-visible="isVisible"
      @validationChange="onValidationChange($event)"
      @submit="onFormSubmit"
    />

    <div class="create-category-button-box">
      <el-button
        :loading="isLoading"
        type="success"
        class="create-category-button"
        @click="onFormSubmit"
      >
        Create
      </el-button>

      <el-button
        type="default"
        @click="onDialogClose"
      >
        Close
      </el-button>
    </div>
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
import CreateCategoryForm from './CreateCategoryForm'

export default {
  name: 'CteateCategoryDialog',

  components: {
    CreateCategoryForm,
  },

  data() {
    return {
      form: false,
      isLoading: false,
    }
  },

  computed: {
    ...mapState({
      isVisible: state => state.teams.isAddCategoryDialogOpened,
    }),
  },

  methods: {
    ...mapMutations([
      'addCategoryDialogClosed',
    ]),

    onDialogClose() {
      this.$emit('close')
      this.addCategoryDialogClosed()
    },

    onValidationChange(status) {
      this.form = status
    },

    resetForm() {
      this.$refs.createCategoryForm.resetForm()
    },

    onFormSubmit() {
      this.$refs.createCategoryForm.$refs.creteCategoryForm.validate((valid) => {
        if (valid) {
          if (!this.form) return

          this.$emit('submit', { ...this.form })
          // this.form = false
        }
      })
    },

    changeLoadingStatus(status = false) {
      this.isLoading = status
    },
  },
}
</script>

<style lang="scss" scoped>
  @import 'resources/sass/_variables.scss';

  .big-modal__title {
    h2 {
      height: 100%;
    }
  }

  .create-category-button-box {
    text-align: right;

    .create-category-button {
      width: 50%;
    }
  }
</style>
