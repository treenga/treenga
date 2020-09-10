<template>
  <div class="form-wrapper">
    <el-form
      ref="creteCategoryForm"
      :hide-required-asterisk="true"
      :model="form"
      status-icon
      novalidate
      label-position="top"
      @keyup.native.enter="onSubmit"
    >
      <el-form-item
        :rules="tr_rules.categoryName"
        label-width="140px"
        class="input"
        prop="name"
      >
        <template slot="label">
          <div class="input-label">
            <span>*</span> Category name

            <div class="counter">
              {{ 64 - form.name.length }}
            </div>
          </div>
        </template>

        <el-input
          ref="inputCategoryName"
          v-model="form.name"
          :autofocus="true"
          placeholder="Enter category name..."
          maxlength="64"
          type="text"
          @input="checkFormValidation"
        />
      </el-form-item>

      <el-form-item
        label-width="140px"
        label="Parent category"
      >
        <el-cascader
          :options="selectArr"
          v-model="selectedOption"
          filterable
          change-on-select
          @change="onCascaderChange"
        />
      </el-form-item>
    </el-form>
  </div>
</template>

<script>
import { mapState } from 'vuex'
import Validation from '@/js/mixins/Validation'

export default {
  name: 'CreateCategoryForm',

  mixins: [Validation],

  props: {
    name: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      form: {
        name: '',
        id: '',
        type: '',
      },
      selectArr: [],
      selectedOption: [],
    }
  },

  computed: {
    ...mapState({
      selectedCategory: state => state.categories.selectedCategory,
      isVisible: state => state.teams.isAddCategoryDialogOpened,
      categories: state => state.categories.categories,
    }),

    isValidSelectedCategory() {
      return this.selectedCategory.id > 0
    },
  },

  watch: {
    name() {
      this.updateForm()
    },

    isVisible(val) {
      if (!val) {
        this.resetForm()
      } else {
        this.form.type = 'public'
        this.form.id = this.isValidSelectedCategory ? this.selectedCategory.id : 'categories'
        // this.generateSelectArray()
        this.$refs.inputCategoryName.focus()
      }
    },

    categories(val) {
      if (val.publicTree) {
        this.generateSelectArray()
        this.checkSelectedType()
      }
    }
  },

  created() {
    this.form.type = 'public'
    this.updateForm()
    this.form.id = this.isValidSelectedCategory ? this.selectedCategory.id : 'categories'
  },

  mounted() {
    /* this.generateSelectArray()
    this.checkSelectedType() */
  },

  methods: {
    checkFormValidation() {
      this.$refs.creteCategoryForm.validate((valid) => {
        if (valid) {
          this.$emit('validationChange', this.form)
          return true
        }

        this.$emit('validationChange', false)
        return false
      })
    },

    generateSelectArray() {
      this.selectArr = [
        {
          label: 'Categories',
          value: 'categories',
          children: this.generateFullCategoriesPaths(this.categories.publicTree, ['categories']),
        },
      ]
    },

    generateFullCategoriesPaths(tree, parent = []) {
      if (!tree) {
        return null
      }

      const fin = tree.map(((el) => {
        const obj = {
          label: el.label,
          value: el.id,
        }

        parent = [...parent, el.id]

        if (this.isValidSelectedCategory && el.id === this.selectedCategory.id) {
          // this.selectedOption = parent
        }

        if (el.children && el.children.length) {
          obj.children = this.generateFullCategoriesPaths(el.children, parent)
        }

        return obj
      }))

      if (!this.selectedOption.length) {
        this.selectedOption = ['categories']
      }

      return fin
    },

    checkSelectedType() {
      this.form.id = this.selectedOption[this.selectedOption.length - 1]
      this.form.type = 'public'
    },

    onCascaderChange() {
      this.checkSelectedType()
    },

    onSubmit() {
      this.checkFormValidation()
      this.$emit('submit')
    },

    updateForm() {
      this.form.name = this.name
    },

    resetForm() {
      this.$refs.creteCategoryForm.resetFields()
      this.selectedOption = ['categories']
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables.scss';

.form-wrapper {
  color: #606266;
}

.checkbox-wrapper {
  margin-top: 15px;
  padding-bottom: 22px;

  .helper-text {
    margin-top: 15px;
    font-style: italic;
    font-size: $sup-text;
  }
}
</style>
