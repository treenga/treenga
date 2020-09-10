<template>
  <div class="category-settings">
    <div
      v-show="!isTasksChecked"
      class="category-options"
    >
      <el-checkbox
        v-show="!isInput"
        v-model="isMassCheckbox"
        :disabled="isTasksCheckboxDisabled"
        class="menu-item-margin black-checkbox"
        @change="onMassCheckboxChange"
      />

      <div
        v-show="!isInput"
        class="open-indicator"
      >
        <el-radio-group
          :disabled="isStatusButtonDisabled"
          v-model="isOpenTasks"
          size="mini"
          @change="onStatusButtonChange"
        >
          <el-radio-button label="opened">Open</el-radio-button>
          <el-radio-button label="closed">Closed</el-radio-button>
        </el-radio-group>
      </div>

      <div
        v-show="!isInput"
        :title="selectedCategory.label"
        class="category-name"
      >
        <span
          v-if="filterText == null || filterText == ''"
        >
          {{ selectedCategory.label }}
        </span>

        <span
          v-else
        >
          Search results for "{{ filterText }}"
        </span>

        <span
          v-if="selectedCategory.id != 'recently'
            && tasks.tasks.currentTasks
            && tasks.tasks.childTasks
          "
        >
          {{ getCategoryData }}
        </span>
      </div>
      <el-dropdown
        v-show="isEditButton && ['public', 'private'].indexOf(selectedCategory.type) > -1"
        trigger="click"
        placement="bottom"
        class="category-settings-dropdown"
        @command="handleCogDropdown"
      >
        <el-button
          type="text"
          icon="el-icon-setting"
          class="cog-button"
        />

        <el-dropdown-menu slot="dropdown">
          <el-dropdown-item command="edit-name">Edit name</el-dropdown-item>

          <el-dropdown-item command="delete-category">
            <el-popover
              v-model="isPopover"
              placement="bottom"
              width="330"
              popper-class="confirm-delete"
            >
              <p>Are you sure? Assigned tasks won't be deleted.
                <br>Uncategorized tasks can be found  in "Unsorted"
              </p>

              <div style="text-align: center; margin: 0">
                <el-button
                  type="primary"
                  size="medium"
                  @click="onDeleteCatgoryClick"
                >
                  Delete category
                </el-button>

                <el-button
                  size="mini"
                  type="text"
                  @click="isPopover = false"
                >
                  Close
                </el-button>
              </div>
              <span slot="reference">Delete category</span>
            </el-popover>
          </el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>

      <router-link
        v-if="isDescription
          && !isInput
          && ['public', 'private'].indexOf(selectedCategory.type) > -1
        "
        :to="`team/${selectedTeam.slug}/category/${selectedCategory.id}`"
        class="description-btn hover-button--standart"
      >
        <span :class="{'has-notifications': selectedCategory.category_notifications_count}">
          {{
            selectedCategory.count_word
              ? `Note (${selectedCategory.count_word} words)`
              : '+ Note'
          }}
        </span>
      </router-link>

      <div
        v-show="isInput"
        class="category-name-holder"
      >
        <input
          ref="catName"
          v-model="newCatName"
          class="category-name_input"
          @keyup.enter="onSaveNameClick"
        >
        <div class="prepend-btn">
          <el-button
            :loading="isLoader"
            type="primary"
            @click="onSaveNameClick"
          >
            Save
          </el-button>

          <el-button
            :loading="isLoader"
            @click="onCancelNameClick"
          >
            Cancel
          </el-button>
        </div>
      </div>
    </div>

    <div
      v-show="!isTasksChecked && !isInput"
      class="sort-holder"
    >
      <el-tag
        v-if="isFilterHasConditions && selectedCategory.type != 'system'"
        class="filtered-tasks"
        size="medium"
        type="success"
        effect="dark"
      >
        {{ filteredTasksCount }} FILTERED SHOWN
      </el-tag>

      <el-dropdown
        v-if="selectedCategory.id == 'notification'"
        v-show="!isInput"
        :hide-on-click="false"
        trigger="click"
        placement="bottom"
        @command="handleSortNotificationDropdown"
      >
        <el-button
          class="sort-button"
          type="text"
          icon="icon-sort-name-up"
        />

        <el-dropdown-menu
          slot="dropdown"
        >
          <el-dropdown-item
            :class="{'active': tasks.sortNotificationTaskBy === 'Early'}"
            command="early"
          >
            Early updated first
          </el-dropdown-item>

          <el-dropdown-item
            :class="{'active': tasks.sortNotificationTaskBy === 'Recently'}"
            command="recently"
          >
            Recently updated first
          </el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>

      <el-dropdown
        v-else
        v-show="!isInput && selectedCategory.id != 'recently'"
        :hide-on-click="false"
        trigger="click"
        placement="bottom"
        @command="handleSortDropdown"
      >
        <el-button
          class="sort-button"
          type="text"
          icon="icon-sort-name-up"
        />

        <el-dropdown-menu
          slot="dropdown"
        >
          <el-dropdown-item
            :class="{'active': tasks.sortTaskBy === 'Newest'}"
            command="newest"
          >
            Newest first
          </el-dropdown-item>

          <el-dropdown-item
            :class="{'active': tasks.sortTaskBy === 'Oldest'}"
            command="oldest"
          >
            Oldest first
          </el-dropdown-item>

          <el-dropdown-item
            :class="{'active': tasks.sortTaskBy === 'Duedate'}"
            command="duedate"
          >
            By due date
          </el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>
    </div>

    <div
      v-if="isTasksChecked"
      class="checked-options"
    >
      <el-select
        v-model="selectedType"
        :class="{'small': selectedType === 'set-due-date'}"
        placeholder="Select"
        class="change-type-select menu-item-margin"
      >
        <el-option-group>
          <el-option
            value="assign"
            label="Set categories and assignees"
          />
          <el-option
            value="unassign"
            label="Unset categories and assignees"
          />
        </el-option-group>

        <el-option-group>
          <el-option
            :value="curentTasksStatus === 'closed' ? 'open' : 'close'"
            :label="`${curentTasksStatus === 'closed' ? 'Open' : 'Close'} selected tasks`"
          />
          <el-option
            value="set-due-date"
            label="Set due date"
          />
        </el-option-group>
      </el-select>

      <el-date-picker
        v-show="selectedType === 'set-due-date'"
        v-model="dueDate"
        format="MMM d, yyyy"
        value-format="yyyy-MM-dd"
        type="date"
        class="menu-item-margin left-space"
        placeholder="Select..."
      />

      <el-button
        :loading="isLoader"
        :disabled="isApplyDisabled"
        class="left-space"
        type="primary"
        @click="onApplyClick"
      >
        Apply
      </el-button>

      <el-button
        :loading="isLoader"
        @click="onCancelClick"
      >
        Exit
      </el-button>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters, mapMutations, mapActions } from 'vuex'

export default {
  name: 'CategoriySettings',

  data() {
    return {
      newCatName: '',
      isMassCheckbox: false,
      isInput: false,
      isLoader: false,
      isPopover: false,
      isApplyEnabled: false,
      selectedType: 'assign',
      dueDate: '',
      isApplyClicked: false,
    }
  },

  computed: {
    ...mapState({
      selectedCategory: state => state.categories.selectedCategory,
      selectedTeam: state => state.teams.selectedTeam,
      tasks: state => state.tasks,
      isTasksChecked: state => state.tasks.isTasksChecked,
      checkedTasks: state => state.tasks.checkedTasks,
      curentTasksStatus: state => state.tasks.curentTasksStatus,
      isTasksCheckboxDisabled: state => state.tasks.isTasksCheckboxDisabled,
      categories: state => state.categories,
      checkedCategories: state => state.categories.checkedCategories,
      checkedAssignee: state => state.categories.checkedAssignee,
      treeState: state => state.teams.treeState,
      isFilterEnabled: state => state.tasks.isFilterEnabled,
      isFilterHasConditions: state => state.tasks.isFilterHasConditions,
      filteredTasks: state => state.tasks.filteredTasks,
      filterText: state => state.tasks.checkedFilters.filterText,
    }),

    isOpenTasks: {
      get() {
        return this.curentTasksStatus
      },
      set(val) {
        this.updatedCurentTasksStatus(val)
      },
    },

    isStatusButtonDisabled() {
      return this.selectedCategory.type === 'system'
    },

    isEditButton() {
      return !this.isInput
        && this.selectedCategory.id > 0
    },

    isDescription() {
      const cat = this.selectedCategory
      if (cat.id > 0) {
        return true
      }

      return false
    },

    isApplyDisabled() {
      switch (this.selectedType) {
        case 'assign':
          return !this.checkedAssignee.length && !this.checkedCategories.length
        case 'unassign':
          return !this.checkedAssignee.length && !this.checkedCategories.length
        case 'close':
          return false
        case 'open':
          return false
        case 'set-due-date':
          return !this.dueDate
        default:
          return true
      }
    },

    openTasksCounter() {
      const currentOpened = this.tasks.tasks.currentTasks.tasks_count || 0
      const childOpened = this.tasks.tasks.childTasks.tasks_count || 0
      return currentOpened + childOpened
    },

    closeTasksCounter() {
      const currentClosed = this.tasks.tasks.currentTasks.deleted_tasks_count || 0
      const childClosed = this.tasks.tasks.childTasks.deleted_tasks_count || 0
      return currentClosed + childClosed
    },

    filteredTasksCount() {
      return this.filteredTasks.length
    },

    getCategoryData() {
      /* const descend = this.selectedCategory.descendants_tasks_count === undefined
        ? ''
        : `/${this.selectedCategory.descendants_tasks_count}`

      let current = 0
      if (this.tasks.tasks.currentTasks.tasks) {
        current = this.selectedCategory.tasks_count === undefined
        ? this.tasks.tasks.currentTasks.tasks.length
        : this.selectedCategory.tasks_count
      } */

      const descend = this.tasks.tasks.childTasks[(this.curentTasksStatus == 'closed' ? 'deleted_' : '') + 'tasks_count'] ? `/${this.tasks.tasks.childTasks[(this.curentTasksStatus == 'closed' ? 'deleted_' : '') + 'tasks_count']}` : ''
      const current = this.tasks.tasks.currentTasks[(this.curentTasksStatus == 'closed' ? 'deleted_' : '') + 'tasks_count'] ? this.tasks.tasks.currentTasks[(this.curentTasksStatus == 'closed' ? 'deleted_' : '') + 'tasks_count'] : 0

      return `(${current}${descend})`
    },
  },

  watch: {
    selectedCategory(val) {
      this.isInput = false
      this.newCatName = val.label
    },

    checkedCategories(val) {
      if (['assign', 'unassign'].indexOf(this.selectedType)) {
        this.isApplyEnabled = !!val.length || this.checkedAssignee
      }
    },

    checkedTasks(val) {
      const keys = Object.keys(val)

      if (['assign', 'unassign'].indexOf(this.selectedType) > -1) {
        if (this.checkedCategories.length || this.checkedAssignee.length) {
          this.isApplyEnabled = keys && keys.length
        } else {
          this.isApplyEnabled = false
        }
      } else {
        this.isApplyEnabled = keys && keys.length
      }
    },

    checkedAssignee(val) {
      if (['assign', 'unassign'].indexOf(this.selectedType)) {
        this.isApplyEnabled = !!val.length || this.checkedCategories
      }
    },

    isTasksChecked(val) {
      if (!val) {
        this.isMassCheckbox = false
        this.selectedType = 'assign'
      }
    },
  },

  beforeDestroy() {
    // on main page leave
    this.uncheckedAllTasks()
  },

  methods: {
    ...mapMutations([
      'uncheckedTask',
      'uncheckedAllTasks',
      'checkedAllTasks',
      'allCategoriesUnchecked',
      'categorySelected',
      'sortedTasksByEarly',
      'sortedTasksByRecently',
      'sortedTasksByNewest',
      'sortedTasksByNewest',
      'sortedTasksByOldest',
      'sortedTasksByDuedate',
      'updatedCurentTasksStatus',
      'disabledFilter',
      'alertInfo',
      'alertSuccess',
    ]),

    ...mapActions([
      'updateCategoryName',
      'setTaskAttributes',
      'setMassAttributes',
      'getCategoryTasks',
      'deleteCategory',
      'getUnsortedTasks',
      'getDrafts',
      'getNotifications',
      'getPublicTasks',
      'getAssignedTasks',
      'getRecently',
      'getTasksByAssignee',
      'getTasksByAuthor',
      'getUnassignedTasks',
      'saveTreeState',
      'getAllTasks',
    ]),

    handleCogDropdown(command) {
      switch (command) {
        case 'edit-name':
          this.onEditNameClick()
          break
        default: break
      }
    },

    handleSortNotificationDropdown(command) {
      const state = this.treeState

      switch (command) {
        case 'early':
          this.sortedTasksByEarly()
          break
        case 'recently':
          this.sortedTasksByRecently()
          break
        default: break
      }

      state.sort_notifications_by = command[0].toUpperCase() + command.substring(1)
      this.saveTreeState(state)
    },

    handleSortDropdown(command) {
      const state = this.treeState

      switch (command) {
        case 'newest':
          this.sortedTasksByNewest()
          break
        case 'oldest':
          this.sortedTasksByOldest()
          break
        case 'duedate':
          this.sortedTasksByDuedate()
          break
        default: break
      }

      state.sort_by = command[0].toUpperCase() + command.substring(1)
      this.saveTreeState(state)
    },

    onMassCheckboxChange(val) {
      if (val) {
        this.checkedAllTasks()
      }
    },

    onStatusButtonChange() {
      if (this.filterText) {
        this.getAllTasks({
          params: { search: this.filterText },
          disableLoader: true,
        })
      } else if (this.selectedCategory
          && this.selectedCategory.method
          && this.selectedCategory.method.name
          && this[this.selectedCategory.method.name]) {
        this[this.selectedCategory.method.name]({
          ...this.selectedCategory.method.data,
          disableLoader: true,
        })
      }
    },

    clearFilter() {
      this.mainFilterText = ''
    },

    onApplyClick() {
      this.isLoader = true

      const data = {
        action: this.selectedType,
        tasks: Object.keys(this.tasks.checkedTasks),
        categories: this.categories.checkedCategories,
        users: this.categories.checkedAssignee,
      }

      const tasksCount = data.tasks.length

      if (this.selectedType === 'set-due-date') {
        data.due_date = this.dueDate
      }

      this.setMassAttributes(data).then(() => {
        this.isLoader = false
        this.isApplyEnabled = false
        this.isApplyClicked = true

        this.alertSuccess({
          title: 'Success',
          text: 'Task' + (tasksCount > 1 ? 's' : '') + ' changed',
        })
      })
    },

    onCancelClick() {
      this.uncheckedAllTasks()
      this.allCategoriesUnchecked()

      if (this.isApplyClicked) {
        // this[this.selectedCategory.method.name](this.selectedCategory.method.data)
        this.$emit('exit-click')
      }

      this.isApplyClicked = false
    },

    onEditNameClick() {
      this.isInput = true
    },

    onDeleteCatgoryClick() {
      this.$emit('beforeCategoryDeleteRequest', this.selectedCategory)
      this.deleteCategory({
        teamId: this.selectedTeam.slug,
        categoryId: this.selectedCategory.id,
      }).then(() => {
        this.$emit('afterCategoryDeleteRequest', this.selectedCategory)
        this.isPopover = false
        this.$emit('categoryArchived', this.selectedCategory.id)
      })
    },

    changeTaskStatus() {
      this.$emit('taskStatusChanged', {
        isArchived: this.curentTasksStatus === 'closed',
      })
      this.onCancelClick()
    },

    onSaveNameClick() {
      this.isLoader = true
      const data = {
        teamId: this.selectedTeam.id,
        categoryId: this.selectedCategory.id,
      }
      const newCat = { ...this.selectedCategory }
      data.name = this.newCatName
      newCat.label = this.newCatName

      const methodsUpdate = 'updateCategoryName'

      this[methodsUpdate](data).then((response) => {
        this.isLoader = false
        if (response.data) {
          this.categorySelected(newCat)

          this.isInput = false;
        }
      }).catch(() => {
        this.onCancelNameClick()
      })
    },

    onCancelNameClick() {
      this.$refs.catName.value = this.selectedCategory.label
      this.isInput = false;
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables.scss';

.category-settings {
  box-sizing: border-box;
  display: flex;
  justify-content: space-between;
  height: 100%;

  .category-options {
    font-size: $small-title;
    color: $task-name;
    font-weight: 700;
    display: flex;
    align-items: center;
    height: 45px;
    min-width: 0;
    white-space: nowrap;

    .category-settings-dropdown {
      margin-left: 5px;
    }

    .cog-button {
      background-color: transparent;
      cursor: pointer;
      border: none;
      color: $cog;
      font-size: 16px;
      width: 20px;
      padding: 0;
      transform: translateY(1px);

      .el-icon-setting {
        margin: 0;
      }
    }

    .popover-list {
      display: flex;
    }
  }

  .checked-options {
    display: flex;
    align-items: center;
    padding: 3px 0 2px 31px;

    .cancel-btn {
      margin-left: auto;
    }
  }
}

.popover-btn {
  width: 100%;
  padding: 10px;
  border: none;
  background-color: transparent;
  transition: background-color .2s ease;

  &:hover {
    background-color: $gray-active;
  }
}

.category-name-holder {
  display: flex;
  padding: 5px 30px;

  .category-name_input {
    max-height: 50px;
    padding: 0 15px;
    border: 1px solid #dcdfe6;
    border-radius: 4px;
    width: calc(100% - 220px);
    margin-right: 10px;
  }
}

.description-btn {
  margin-left: 10px;
  color: #282828;
  text-decoration-color: rgba(50, 147, 198, 0.6)!important;
  text-decoration: underline;
  padding: 5px 10px;
  border-radius: 3px;
  font-weight: 400;
  font-size: 14px;
}

.sort-button {
  font-size: $title;
  border: none;
  padding: 8px 0px 8px 10px;
  color: $task-name;

  &-placeholder {
    opacity: 0;
  }
}

.open-indicator {
  height: 100%;
  min-width: 120px;
  display: flex;
  align-items: center;
  font-size: $body;

  .status-button {
    span {
      text-decoration: underline;
    }

    &.active {
      span {
        font-weight: 700;
      }
    }
  }
}

.sort-popover {
  padding-left: 10px;

  .popover-btn {
    position: relative;
    text-align: left;

    i {
      position: absolute;
      left: -12px;
      top: 50%;
      transform: translateY(-50%);
      font-size: $body;
    }
  }
}

 .popover-btn {
   outline: none;
 }

.open-indicator-col {
  width: 180px;
}

.search-holder {
  margin-left: auto;
}

.sort-holder {
  display: flex;
  align-items: center;
  padding: 4px 0;
}

.has-notifications {
  margin-right: 15px;
}

.prepend-btn {
  display: flex;

  button {
    min-width: 71px;
  }
}

.category-name {
  display: inline-block;
  max-width: calc(100% - 174px);
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  font-size: 14px;
  margin-left: 20px;
}

.el-checkbox.menu-item-margin {
  margin-right: 17px;
}

.change-type-select {
  max-width: 300px;
  min-width: 250px;

  &.small {
    max-width: 190px;
    min-width: auto;

    @media (min-width: 1600px) {
      max-width: 300px;
      min-width: 250px;
    }
  }
}

.left-space {
  margin-left: 10px;
}
</style>

<style lang="scss">
  .el-popover.el-popper.confirm-delete {
    margin-top: -80px;
  }
</style>
