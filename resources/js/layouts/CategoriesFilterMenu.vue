<template>
  <div
    v-loading="isLoading"
    :class="{'disabled': isTasksChecked || isSystemCategory}"
    class="right-filters"
    element-loading-background="rgba(0, 0, 0, 0)"
  >
    <div class="top-search">
      <div
        :class="{active: isGreenLine}"
        class="indicator"
      />

      <div class="filter-title">
        <div class="small-title--yellow">Filter</div>

        <el-switch
          v-model="isMainFilterEnabled"
          class="filter-switch"
          active-color="#13ce66"
          @change="onEnabledChange"
        />

        <div class="controls">
          <el-button
            size="medium"
            class="reset-btn hover-button--standart"
            @click="onResetClick"
          >
            Reset
          </el-button>
        </div>
      </div>
    </div>

    <div>
      <el-scrollbar class="filter-scroll">
        <div class="tree-holder">
          <el-tree
            ref="publicTree"
            key="publicTree"
            :data="publicCategories"
            :default-expand-all="false"
            :highlight-current="false"
            :expand-on-click-node="false"
            :props="defaultProps"
            :check-on-click-node="true"
            :default-checked-keys="checkedPublicFilters.categories"
            :default-expanded-keys="treeState.filter.public_tree"
            class="no-label-all-checkbox-tree categories-tree"
            show-checkbox
            check-strictly
            node-key="id"
            empty-text="No categories"
            @check-change="onFilterChange"
            @node-collapse="onNodeCollapse($event, 'public_tree')"
            @node-expand="onNodeExpand($event, 'public_tree')"
          >
            <span
              slot-scope="{ node, data }"
              class="custom-tree-node"
            >
              <span
                class="label transparent"
              >
                <i
                  v-show="data.id === 0"
                  class="icon-folder"
                />

                <span
                  v-if="data.id === -1"
                  :class="{'label--green': categoriesCounters.drafts}"
                >
                  <el-checkbox
                    v-model="filter.is_draft"
                    @change="onFilterChange"
                  >
                    {{ node.label }} ({{ categoriesCounters.drafts }})

                  </el-checkbox>
                </span>

                <span
                  v-show="data.id === -2"
                  :class="{'label--green': unsortedCounter}"
                >
                  <el-checkbox
                    v-model="filter.is_unsorted"
                    class="checkbox--brown"
                    @change="onFilterChange"
                  >
                    {{ node.label }} ({{ unsortedCounter }})
                  </el-checkbox>
                </span>

                <span
                  v-show="data.id >= 0"
                  :class="{'label--green': categoriesCounters[data.id]}"
                >
                  {{ node.label }}
                  <span>({{ categoriesCounters[data.id] || 0 }})</span>
                </span>
              </span>
            </span>
          </el-tree>

          <div class="tree-holder">
            <el-tree
              v-if=" ! isPrivateTeam"
              key="assigneeTree"
              ref="assigneeTree"
              :data="assignees"
              :props="assigneeProps"
              :highlight-current="false"
              :default-expand-all="false"
              :check-on-click-node="true"
              :expand-on-click-node="false"
              :default-checked-keys="checkedPublicFilters.users"
              :default-expanded-keys="treeState.filter.assignees_tree"
              class="no-label-all-checkbox-tree"
              show-checkbox
              check-strictly
              node-key="id"
              empty-text="No assignees found"
              @check-change="onFilterChange"
              @node-collapse="onNodeCollapse($event, 'assignees_tree')"
              @node-expand="onNodeExpand($event, 'assignees_tree')"
            >
              <span
                slot-scope="{ node, data }"
                class="custom-tree-node"
              >
                <span class="label transparent">
                  <i
                    v-show="data.id === 0"
                    class="icon-users"
                  />

                  <span
                    v-show="data.id < 0"
                    :class="{'label--green': assigneesCounters.unassigned}"
                  >
                    <el-checkbox
                      v-model="filter.is_unassigned"
                      @change="onFilterChange"
                    >
                      {{ node.label }} ({{ assigneesCounters.unassigned }})
                    </el-checkbox>
                  </span>

                  <span
                    v-show="data.id > 0"
                    :class="{'label--green': assigneesCounters[data.id]}"
                  >
                    {{ node.label }} ({{ assigneesCounters[data.id] || 0 }})
                  </span>

                  <span v-show="data.id === 0">{{ node.label }}</span>
                </span>
              </span>
            </el-tree>
          </div>

          <div
            v-if="authorsCounters"
            class="tree-holder"
          >
            <el-tree
              v-if=" ! isPrivateTeam"
              key="authorsTree"
              ref="authorsTree"
              :data="authors"
              :props="assigneeProps"
              :highlight-current="false"
              :default-expand-all="false"
              :check-on-click-node="true"
              :expand-on-click-node="false"
              :default-checked-keys="checkedPublicFilters.authors"
              :default-expanded-keys="treeState.filter.authors_tree"
              class="no-label-all-checkbox-tree"
              show-checkbox
              check-strictly
              node-key="id"
              empty-text="No authors found"
              @check-change="onFilterChange"
              @node-collapse="onNodeCollapse($event, 'authors_tree')"
              @node-expand="onNodeExpand($event, 'authors_tree')"
            >
              <span
                slot-scope="{ node, data }"
                class="custom-tree-node"
              >
                <span
                  :class="{'label--green': authorsCounters[data.id]}"
                  class="label transparent"
                >
                  <i
                    v-show="node.data.id === 0"
                    class="icon-copyright"
                  />

                  {{ node.label }}

                  <span
                    v-if="data.id"
                    class="tasks-count"
                  >
                    ({{ authorsCounters[data.id] || 0 }})
                  </span>
                </span>
              </span>
            </el-tree>
          </div>

          <div
            v-if="dueDatesCounters"
            class="tree-holder"
          >
            <el-tree
              key="dueDatesTree"
              ref="dueDatesTree"
              :data="dueDates"
              :props="dueDatesProps"
              :highlight-current="false"
              :default-expand-all="false"
              :expand-on-click-node="false"
              :default-expanded-keys="treeState.filter.due_date_tree"
              node-key="value"
              empty-text="No authors found"
              @check-change="onFilterChange"
              @node-collapse="onNodeCollapse($event, 'due_date_tree', 'value')"
              @node-expand="onNodeExpand($event, 'due_date_tree', 'value')"
            >
              <span
                slot-scope="{ node, data }"
                class="custom-tree-node"
              >
                <span
                  v-show="data.value !== 'daterange'"
                  class="label transparent"
                >
                  <i
                    v-show="data.id === 0"
                    class="icon-clock"
                  />

                  <el-radio
                    v-if="!data.disabled"
                    v-model="filter.due_date_type"
                    :label="data.value"
                    :class="{'label--green': dueDatesCounters[data.value]}"
                    @change="onFilterChange"
                  >
                    {{ node.label }} ({{ dueDatesCounters[data.value] }})
                  </el-radio>

                  <span v-show="node.data.disabled">
                    {{ node.label }}
                  </span>
                </span>

                <span v-show="node.data.value === 'daterange'">
                  <el-radio
                    v-model="filter.due_date_type"
                    label="daterange"
                    @change="onFilterChange"
                  >
                    <span/>
                  </el-radio>
                  <el-date-picker
                    v-model="due_date"
                    type="daterange"
                    range-separator="-"
                    start-placeholder="From"
                    end-placeholder="To"
                    size="medium"
                    value-format="yyyy-MM-dd"
                    format="MMM d, yyyy"
                    class="tr-datetime"
                    @change="onFilterChange"
                  />
                </span>
              </span>
            </el-tree>
          </div>
        </div>
      </el-scrollbar>
    </div>

    <div class="bottom-control">
      <div class="inner-wrapper">
        <el-radio-group
          v-model="filter.combine"
          class="mini"
          size="mini"
          @change="onFilterChange"
        >
          <el-radio-button label="or">OR</el-radio-button>
          <el-radio-button label="and">AND</el-radio-button>
        </el-radio-group>
        <el-checkbox
          v-model="filter.invert"
          @change="onFilterChange"
        >
          Invert
        </el-checkbox>
      </div>
    </div>
  </div>
</template>

<script>
import {
  mapMutations, mapActions, mapState, mapGetters,
} from 'vuex'

export default {
  name: 'CategoriesFilterMenu',

  data() {
    return {
      filter: {
        combine: 'or',
        invert: false,
        due_date_type: 'daterange',
        is_unassigned: false,
        is_draft: false,
        is_unsorted: false,
      },

      due_date: null,

      isMainFilterEnabled: false,

      isLoading: false,

      defaultProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },

      dueDatesProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },

      assigneeProps: {
        children: 'children',
        label: 'teamusername',
        disabled: 'disabled',
      },

      publicCategories: [],

      authors: [],

      assignees: [],

      dueDates: [],
    }
  },

  computed: {
    ...mapState({
      tasks: state => state.tasks,
      selectedTeam: state => state.teams.selectedTeam,
      categories: state => state.categories.categories,
      selectedCategory: state => state.categories.selectedCategory,
      token: state => state.auth.token,
      checkedFilter: state => state.tasks.checkedFilters,
      checkedPublicFilters: state => state.tasks.checkedFilters.public,
      checkedPrivateFilters: state => state.tasks.checkedFilters.private,
      isTasksChecked: state => state.tasks.isTasksChecked,
      counters: state => state.tasks.counters,
      categoriesCounters: state => state.tasks.counters.categories,
      assigneesCounters: state => state.tasks.counters.assignees,
      dueDatesCounters: state => state.tasks.counters.due_dates,
      authorsCounters: state => state.tasks.counters.authors,
      unsortedCounter: state => state.tasks.counters.categories.unsorted,
      treeState: state => state.teams.treeState,
    }),

    ...mapGetters([
      'isPrivateTeam',
    ]),

    selectedTeamId() {
      return this.selectedTeam.id
    },

    currentType() {
      const type = this.selectedCategory.type === 'private' ? 'private' : 'public'
      return type.charAt(0).toUpperCase() + type.slice(1)
    },

    isSystemCategory() {
      return this.selectedCategory.type === 'system'
    },

    isGreenLine() {
      const currentData = this[`checked${this.currentType}Filters`]
      const isDueDate = currentData.due_date_type !== 'daterange' || currentData.due_date
      const isAuthors = currentData.authors && currentData.authors.length
      const isAssignees = currentData.users && currentData.users.length

      const hasConditions = this.isMainFilterEnabled
        && (
          currentData.categories.length
          || isDueDate
          || isAuthors
          || currentData.is_draft
          || currentData.is_unassigned
          || currentData.is_unsorted
          || currentData.invert
          || isAssignees
        )

      this.filterConditions(hasConditions)

      return hasConditions
    },
  },

  watch: {
    checkedFilter: {
      handler(val) {
        const type = 'public'
        const method = val[type].enabled ? 'enabled' : 'disabled'

        if (val.filterText) {
          this.mainFilterText = val.filterText
        }

        this[`${method}Filter`]()
      },
      deep: true,
    },

    categories(val) {
      this.fillTree(val)
    },

    due_date() {
      if (this.filter.due_date_type === 'daterange') {
        this.getFilteredTasks()
      }
    },

    counters: {
      handler() {
        this.updateFilterData()
        this.getFilteredTasks()
      },
      deep: true,
    },

    checkedPublicFilters() {
      this.isMainFilterEnabled = this.checkedPublicFilters.enabled
    },

    selectedCategory(val) {
      this.selectedCategory.type === 'system' 
        ? this.disabledFilter() 
        : this.isMainFilterEnabled ? this.enabledFilter() : false
    },
  },

  methods: {
    ...mapMutations([
      'enabledFilter',
      'disabledFilter',
      'clearCheckedFilters',
      'updatedCheckedPrivateFilters',
      'updatedCheckedPublicFilters',
      'updatedFilterText',
      'filterConditions',
    ]),

    ...mapActions([
      'searchTasks',
      'saveFilterState',
      'updateCategoryName',
      'setTaskAttributes',
      'saveTreeState',
      'setMassAttributes',
      'getCategoryTasks',
      'deleteCategory',
      'getUnsortedTasks',
      'getDrafts',
      'getPublicTasks',
      'getPrivateTasks',
      'getAssignedTasks',
      'getTasksByAssignee',
      'getTasksByAuthor',
      'getUnassignedTasks',
      'setCurrentFilterPage',
      'setCurrentPage',
      'resetTasks',
      'resetFilteredTasks',
    ]),

    onNodeCollapse(node, treeName, key = 'id') {
      const states = this.treeState
      const tree = states.filter[treeName]

      tree.splice(tree.indexOf(node[key]), 1)
      states.filter[treeName] = tree

      this.saveTreeState(states)
    },

    onNodeExpand(node, treeName, key = 'id') {
      const states = this.treeState
      const tree = states.filter[treeName]

      tree.push(node[key])
      states.filter[treeName] = tree

      this.saveTreeState(states)
    },

    updateFilterData() {
      const sameFields = [
        'combine',
        'invert',
        'is_unassigned',
        'is_draft',
        'is_unsorted',
        'due_date_type',
      ]

      sameFields.forEach((e) => {
        this.filter[e] = this.checkedPublicFilters[e]
      })

      this.due_date = this.checkedPublicFilters.due_date
      this.isMainFilterEnabled = this.checkedPublicFilters.enabled
    },

    getUsersTasks() {
      if (this.selectedCategory.type === 'mine') {
        this.getAssignedTasks()
        return
      }

      if (this.selectedCategory.type === 'author') {
        this.getTasksByAuthor({ userId: this.selectedCategory.id })
        return
      }

      if (this.selectedCategory.type === 'assignee') {
        this.getTasksByAssignee({
          teamId: this.$route.params.teamSlug,
          userId: this.selectedCategory.id,
        })
        return
      }

      this.getMainFilteredTasks()
    },

    onResetClick() {
      this.resetAllFilters()
    },

    getMainFilteredTasks() {
      if (!this.isMainFilterEnabled) return
      const cat = this.selectedCategory
      const params = {
        search: '',
      }

      params.search = this.mainFilterText

      this.updatedFilterText(this.mainFilterText)
      const data = this.prepareDataForApi()
      this.saveFilterToStore(data)
      this.saveFilterState()

      this[cat.method.name]({ ...cat.method.data, params })
    },

    resetAllFilters() {
      this.due_date = null

      if (this.$refs.publicTree) {
        this.$refs.publicTree.setCheckedKeys([])
      }

      if (this.$refs.assigneeTree) {
        this.$refs.assigneeTree.setCheckedKeys([])
      }

      if (this.$refs.authorsTree) {
        this.$refs.authorsTree.setCheckedKeys([])
      }

      if (this.$refs.dueDatesTree) {
        this.$refs.dueDatesTree.setCheckedKeys([])
      }

      this.filter = {
        combine: 'or',
        invert: false,
        due_date_type: 'daterange',
        is_unassigned: false,
        is_draft: false,
        is_unsorted: false,
      }

      this.onFilterChange()
    },

    onEnabledChange(val) {
      this.onFilterChange()
      if (!val) {
        this.disabledFilter()
      }
    },

    prepareDataForApi() {
      let chekedCategories = null
      let checkedUsers = null
      let checkedAuthors = null
      let data = {}

      chekedCategories = this.$refs.publicTree.getCheckedKeys()

      if (this.$refs.assigneeTree) {
        checkedUsers = this.$refs.assigneeTree.getCheckedKeys()
      }

      if (this.$refs.authorsTree) {
        checkedAuthors = this.$refs.authorsTree.getCheckedKeys()
      }

      data = {
        ...this.filter,
        users: checkedUsers,
        categories: chekedCategories,
        authors: checkedAuthors,
        due_date: this.due_date,
        type: 'public',
        enabled: this.isMainFilterEnabled,
      }

      return data
    },

    onFilterChange() {
      const data = this.prepareDataForApi()
      this.saveFilterToStore(data)
      this.saveFilterState()
      this.getFilteredTasks(data)

      this.setCurrentFilterPage(1)
      this.setCurrentPage(1)
    },

    saveFilterToStore(data) {
      if (data.type === 'private') {
        this.updatedCheckedPrivateFilters(data)
      } else {
        this.updatedCheckedPublicFilters(data)
      }
    },

    getFilteredTasks(data) {
      if (this.isMainFilterEnabled) {
        this.searchTasks(data || this.prepareDataForApi())
      }
    },

    fillTree(data) {
      const categories = data.publicTree || [];
      this.publicCategories = [{
        label: 'Categories',
        id: 0,
        disabled: true,
        children: [
          {
            label: 'Drafts',
            disabled: true,
            id: -1,
          },

          {
            label: 'Unsorted',
            disabled: true,
            id: -2,
          },
          ...categories,
        ],
      }]

      const authors = data.teamAuthors || [];
      this.authors = [{
        teamusername: 'Authors',
        id: 0,
        disabled: true,
        children: authors,
      }]

      const users = data.teamUsers || [];
      const assignees = users.slice(0)
      assignees
        .sort(function(a, b) {
          var nameA = a.teamusername.toUpperCase();
          var nameB = b.teamusername.toUpperCase();
          return (nameA < nameB) ? -1
            : (nameA > nameB) ? 1 : 0;
        })
        .splice(0, 0, {
          id: -1,
          disabled: true,
          teamusername: 'Unassigned',
        })

      this.assignees = [{
        teamusername: 'Assignees',
        id: 0,
        disabled: true,
        children: assignees,
      }]

      this.dueDates = [{
        label: 'Due date',
        value: 'main title',
        id: 0,
        disabled: true,
        children: [
          {
            label: 'No due date',
            value: 'no',
          },

          {
            label: 'Overdue',
            value: 'overdue',
          },

          {
            label: 'Today',
            value: 'today',
          },

          {
            label: 'Tomorrow',
            value: 'tommorow',
          },

          {
            label: 'This week',
            value: 'thisWeek',
          },

          {
            label: 'Next week',
            value: 'nextWeek',
          },

          {
            label: 'This month',
            value: 'thisMonth',
          },

          {
            label: 'Next month',
            value: 'nextMonth',
          },

          {
            label: '',
            value: 'daterange',
            disabled: true,
          },
        ],
      }]
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables.scss';

.right-filters {
  color: white;
  overflow: hidden;
  height: 100vh;
  display: flex;
  flex-direction: column;
  padding-left: 10px;
  padding-right: 10px;

  &.disabled::after {
      content: '';
      position: absolute;
      top: 0;
      bottom: 0;
      left: 0;
      right: 0;
      z-index: 2;
      background-color: rgba(#596988, 0.9);
      cursor: not-allowed;
    }

  .top-search {
    padding: 27px 10px 23px 10px;

    .filter-title {
      display: flex;
      justify-content: space-between;
      align-items: center;

      .use {
        margin-right: 10px;
      }
    }

    .filter-input-wrapper {
      margin: 16px 0;
    }
  }

  .bottom-control {
    padding: 6px 10px 0 10px;
    text-align: center;

    label {
      color: white;
    }

    .inner-wrapper {
      display: flex;
      -webkit-box-pack: justify;
      justify-content: space-between;
      -webkit-box-align: center;
      align-items: center;

      .el-radio-button { 
        margin-right: auto; 
      }

      .el-checkbox {
        display: -webkit-box;
        display: flex;
        -webkit-box-pack: justify;
        justify-content: space-between;
        -webkit-box-align: center;
        align-items: center;
      }
    }
  }
}

.controls {
  display: flex;
  justify-content: space-between;
  align-items: center;

  label {
    color: white;
  }

  .reset-btn {
    color: white!important;
    font-weight: 400;

    &:hover {
      color: #282828!important
    }
  }
}

.indicator {
  height: 13px;
  transition: background-color .3s ease;
  background-color: transparent;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;

  &.active {
    background-color: rgba(35, 209, 96, 1);
  }
}

.tree-holder {
  margin: 0 10px;
  position: relative;

  .tree-holder {
    margin-left: 0;
    margin-right: 0;
  }

  &:not(:first-child) {
    margin-top: 10px;
  }

  &:last-child {
    margin-bottom: 20px;
  }
}

.invisible {
  height: 0;
  display: none;
}

.filter-switch {
  margin-right: auto;
  margin-left: 12px;
  margin-top: 2px;
}
</style>
