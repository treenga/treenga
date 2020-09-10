<template>
  <div
    :class="[
      {'is-search': (filterText != '' && filterText != null)},
      {'is-open': isAddCategoryShown},
      {'has-2-custom': isUnsortedShown},
      {'has-1-custom': !isUnsortedShown},
    ]"
    class="public-tree"
  >
    <div
      v-if="isAllTasksShown"
      :class="{'is-current': activeNodeId === -3}"
      class="custom-node all-tasks-node el-tree-node"
      @click="$emit('all-tasks-click')"
    >
      <div class="el-tree-node__content">
        <span class="el-tree-node__expand-icon el-icon-caret-right is-leaf" />

        <span class="custom-tree-node">
          <span class="label padding-left">
            <el-checkbox
              v-show="isTasksChecked"
              :disabled="true"
              class="custom-tree-checkbox"
            />
            @All tasks
          </span>
        </span>
      </div>
    </div>
    
    <div
      v-if="isUnsortedShown"
      :class="{'is-current': activeNodeId === -2}"
      class="custom-node unsorted-node el-tree-node"
      @click="$emit('unsorted-click')"
    >
      <div class="el-tree-node__content">
        <span class="el-tree-node__expand-icon el-icon-caret-right is-leaf" />

        <span class="custom-tree-node">
          <span class="label padding-left">
            <el-checkbox
              v-show="isTasksChecked"
              :disabled="true"
              class="custom-tree-checkbox"
            />
            Unsorted
            <span class="tasks-count">
              ({{ categories.count_unsorted_public_tasks }})
            </span>
          </span>
        </span>
      </div>
    </div>

    <el-tree
      v-show="!isTasksChecked"
      ref="publicTree"
      key="publicTree"
      :data="categoriesData"
      :props="props"
      :default-expand-all="false"
      :default-expanded-keys="treeState.main.public_tree"
      :draggable="true"
      :highlight-current="true"
      :check-on-click-node="true"
      :expand-on-click-node="false"
      :filter-node-method="filterTree"
      :auto-expand-parent="false"
      :allow-drop="dropValidation"
      :allow-drag="dragValidation"
      :class="{'no-focus': isTasksChecked}"
      node-key="id"
      empty-text="No public categories found"
      @node-click="handleNodeClick"
      @node-drag-end="onDragEnd"
      @node-collapse="onNodeCollapse"
      @node-expand="onNodeExpand"
    >
      <span
        slot-scope="{ node, data }"
        class="custom-tree-node"
      >
        <span
          :class="[
            {'padding-left': data.id}
          ]"
          class="label"
        >
          <i
            v-show="!node.data.id"
            class="icon-folder"
          />
          {{ node.label }}
        </span>
      </span>
    </el-tree>

    <!-- public checkbox tree -->

    <el-tree
      v-if="isTasksChecked"
      ref="publicCheckTree"
      key="publicCheckTree"
      :data="categoriesData"
      :default-expand-all="false"
      :default-expanded-keys="treeState.main.public_tree"
      :highlight-current="false"
      :expand-on-click-node="false"
      :auto-expand-parent="false"
      :props="props"
      :check-on-click-node="true"
      :filter-node-method="filterTree"
      class="no-label-checkbox-tree public-check-tree"
      show-checkbox
      check-strictly
      node-key="id"
      empty-text="No public categories found"
      @check-change="onCheckCategoryChange"
    >
      <span
        slot-scope="{ node, data }"
        class="custom-tree-node"
      >
        <span class="label transparent">
          <i
            v-show="!data.id"
            class="icon-folder"
          />
          {{ node.label }}
        </span>
      </span>
    </el-tree>

    <el-popover
      v-if="isAddCategoryShown"
      v-model="createCategoryPopover"
      :class="{ 'unvisible': isTasksChecked }"
      placement="right"
      trigger="manual"
      width="390"
      @show="onResetCategoryForm"
    >
      <create-category-dialog
        ref="createCategoryDialog"
        @submit="onCreateCategorySubmit($event)"
        @close="createCategoryPopover = false"
      />

      <div
        slot="reference"
        class="custom-node add-category-node el-tree-node"
      >
        <div
          class="el-tree-node__content"
          @click="createCategoryPopover = !createCategoryPopover"
        >
          <span class="custom-tree-node">
            <span class="label padding-left">
              Add category
            </span>
          </span>
        </div>
      </div>
    </el-popover>
  </div>
</template>

<script>
import {
  mapMutations, mapActions, mapState, mapGetters,
} from 'vuex'
import pusher from '@/js/pusher'
import TreeMixin from '@/js/mixins/Trees'
import { CreateCategoryDialog } from '@/js/components'

let timeout = null

export default {
  name: 'TreePublic',

  components: {
    CreateCategoryDialog,
  },

  mixins: [TreeMixin],

  props: {
    categoriesData: {
      type: Array,
      required: true,
    },

    fields: {
      type: Array,
      default: () => ([]),
    },
  },

  data() {
    return {
      activeNodeId: null,
      type: 'public',
      isDrafts: true,
      isUnsorted: true,
      props: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },
      createCategoryPopover: false,
    }
  },

  computed: {
    ...mapState({
      filterText: state => state.tasks.checkedFilters.filterText,
    }),

    ...mapGetters([
      'isPrivateTeam',
    ]),

    isAllTasksShown() {
      return this.categoriesData
        && this.categoriesData.length
        && this.isRootOpen
    },

    isUnsortedShown() {
      return this.categoriesData
        && this.categoriesData.length
        && this.categories.count_unsorted_public_tasks > 0
        && this.isRootOpen
    },

    isAddCategoryShown() {
      return this.isRootOpen
    },
  },

  watch: {
    isTasksChecked(newVal) {
      if (newVal) {
        this.$nextTick(() => {
          const parent = this.$refs.publicCheckTree.$el
          const checkboxes = parent.querySelectorAll('.el-checkbox__input')
          checkboxes.forEach((el) => {
            el.querySelector('input').disabled = false
            el.classList.remove('is-disabled')
          })
        })
      }
    },
  },

  mounted() {
    this.initTeamListeners()
  },

  methods: {
    ...mapActions([
      'moveCategory',
      'getNotifications',
      'getDrafts',
      'getRecently',
      'getUnsortedTasks',
      'getCategoryTasks',
      'createPublicCategory',
      'saveFilterState',
    ]),

    ...mapMutations([
      'updatedCategories',
      'categoryChecked',
      'categoryUnchecked',
      'categorySelected',
      'addCategoryDialogOpened',
      'updatedFilterText',
    ]),

    onResetCategoryForm() {
      this.$refs.createCategoryDialog.$refs.createCategoryForm.$refs.creteCategoryForm.resetFields()
    },

    onCreateCategorySubmit(form) {
      this.$refs.createCategoryDialog.changeLoadingStatus(true)

      if (form.id > 0) {
        form.parent_id = form.id
      }

      /* delete form.type
      delete form.id */

      this.createPublicCategory(form).then((responce) => {
        if (responce.data) {
          this.onCategoryCreated(responce.data)
          this.$refs.createCategoryDialog.changeLoadingStatus(false)
          this.$refs.createCategoryDialog.resetForm()
          this.createCategoryPopover = false
        }
      })
    },

    managePusherListeners() {
      pusher.listenPublicCategoryCreated(this.onCategoryCreated)
      pusher.listenPublicCategoryMoved(this.onCategoryMoved)
      pusher.listenPublicCategoryRenamed(this.onCategoryRenamed)
      pusher.listenPublicCategoryDeleted(this.onCategoryDeleted)
    },

    initTeamListeners() {
      clearTimeout(timeout)
      timeout = setTimeout(() => {
        if (this.selectedTeam.id) {
          this.managePusherListeners()

          this.$watch('selectedTeam', (val) => {
            if (val && val.id) {
              this.managePusherListeners()
            }
          })
        } else {
          this.initTeamListeners()
        }
      }, 300)
    },

    onCategoryChanged(data, type) {
      const node = this.$refs.publicTree.getNode(data.id)

      if (!node) return

      if (type.indexOf('EditedDesc') > -1 || type.indexOf('Commented') > -1) {
        node.data.category_notifications_count += 1
      }

      node.data.user_notifications_count += 1
    },

    updateCategoriesNotifications(categories) {
      if (!categories || !this.$refs.publicTree) {
        return
      }

      if (!categories.length) {
        this.updatedCategories({
          count_unsorted_public_tasks: this.categories.count_unsorted_public_tasks + 1,
        })
        return
      }

      categories.forEach((category) => {
        const node = this.$refs.publicTree.getNode(category)
        node.data.user_notifications_count += 1
      })
    },

    decrementCategoriesNumber(categoryIds) {
      if (!categoryIds.length) {
        this.updatedCategories({
          count_unsorted_public_tasks: this.categories.count_unsorted_public_tasks - 1,
        })
        return
      }

      categoryIds.forEach((catId) => {
        const node = this.$refs.publicTree.getNode(catId)
        node.data.tasks_count -= 1
      })
    },

    incrementCategoriesNumber(categoryIds) {
      if (!categoryIds.length) {
        this.updatedCategories({
          count_unsorted_public_tasks: this.categories.count_unsorted_public_tasks + 1,
        })
        return
      }

      categoryIds.forEach((catId) => {
        const node = this.$refs.publicTree.getNode(catId)
        node.data.tasks_count += 1
      })
    },

    onTaskClosed(task) {
      this.decrementCategoriesNumber(task.categories_id)
    },

    onTaskOpened(task) {
      this.incrementCategoriesNumber(task.categories_id)
    },

    onCategoryMoved(newData) {
      const node = this.$refs.publicTree.getNode(newData.id)
      newData.data = node.data
      this.$refs.publicTree.remove(node)
      const nextId = this.getNextNodeIndex(newData)

      if (nextId) {
        this.$refs.publicTree.insertBefore(newData.data, nextId)
      } else if (newData.parent_id) {
        this.$refs.publicTree.append(newData.data, newData.parent_id)
      } else {
        const root = this.$refs.publicTree.getNode(0)

        if (root.childNodes.length) {
          this.$refs.publicTree.insertBefore(newData.data, root.childNodes[0].data.id)
        } else {
          this.$refs.publicTree.append(newData.data, 0)
        }
      }
    },
  },
}
</script>

<style lang="scss" scoped>
  .public-tree {
    position: relative;
    margin: 0 10px;

    // when custom node added - change top margin of actual categories
    // in app.scss after comment Trees styles

    .custom-node {
      position: absolute;
      z-index: 1;
      width: 100%;
      color: white;
      left: 0;

      .el-tree-node__content {
        padding-left: 18px;
      }

      &.is-current {
        .el-tree-node__content {
          background-color: rgba(255, 255, 255, 0.3)
        }
      }

      &.add-category-node {
        position: relative;
        color: rgba(255, 255, 255, 0.6);
        top: 0;
        left: 42px;
        display: inline-block;
        width: auto;
        text-decoration: underline;
        font-size: 14px;

        &:hover,
        &:focus,
        &:active {
          background: transparent;
          text-decoration: none;
        }

        .el-tree-node__content {
          padding-left: 0;
        }

        .el-tree-node__content:hover,
        .el-tree-node__content:focus,
        .el-tree-node__content:active {
          background: transparent;
        }
      }
    }

    .unvisible {
      visibility: hidden;
    }

    .notifications-node {
      top: 26px
    }

    .drafts-node {
      top: 52px;
    }

    .recently-node {
      top: 78px;
    }

    .unsorted-node {
      top: 104px;
    }
  }

  .has-2-custom {
    .drafts-node {
      top: 26px;
    }

    .unsorted-node {
      top: 52px;
    }
  }

  .public-tree.is-search {
    .custom-node.is-current {
      .el-tree-node__content {
        background: transparent;
      }
    }
  }
</style>

<style lang="scss">
  .public-tree.is-search {
    .el-tree--highlight-current {
      .el-tree-node.is-current {
          .el-tree-node__content {
            background: transparent;
          }
      }
    }
  }
</style>
