<template>
  <el-col class="categories">
    <el-row
      :gutter="0"
      type="flex"
    >
      <el-col
        v-loading="categoriesLoader"
        :span="6"
        :class="{'categories-list-private': isPrivateTeam, 'hidden-content': categoriesLoader}"
        element-loading-custom-class="white"
        class="categories-list"
      >
        <div class="categories-header">
          <div class="categories-header-buttons">
            <el-button
              class="hidden-md-and-down button-block create-task-button"
              type="success"
              @click="handleCreateNewTask"
            >
              New task
            </el-button>
          </div>
        </div>

        <el-scrollbar class="categories-scroll">
          <div class="categories">

            <tree-system
              ref="systemTree"
              :categories-data="systemCategories"
              @node-click="onSystemNodeClick"
              @node-collapse="onNodeCollapse"
              @node-expand="onNodeExpand"
            />

            <tree-public
              ref="publicTree"
              :categories-data="publicCategories"
              @node-click="onPublicNodeClick"
              @all-tasks-click="handlerSpecialClick('all-tasks')"
              @unsorted-click="handlerSpecialClick('unsorted')"
            />

            <!-- Assignee Tree -->
            <div
              v-if=" ! isPrivateTeam"
              class="assignee-tree"
            >
              <el-tree
                v-show="!isTasksChecked"
                key="assigneeTree"
                ref="assigneeTree"
                :data="assignees"
                :props="assigneeProps"
                :highlight-current="true"
                :default-expand-all="false"
                :check-on-click-node="true"
                :expand-on-click-node="false"
                :filter-node-method="filterTreebyUserName"
                :default-expanded-keys="treeState.main.assignees_tree"
                node-key="id"
                empty-text="No assignees found"
                @node-click="onAssigneeClick"
                @node-collapse="onNodeCollapse($event, 'assignees_tree')"
                @node-expand="onNodeExpand($event, 'assignees_tree')"
              >
                <span
                  slot-scope="{ node, data }"
                  class="custom-tree-node"
                >
                  <span
                    v-if="data.id > -1"
                    :class="{'padding-left': data.id !== 0}"
                    class="label"
                  >
                    <i
                      v-show="!node.data.id"
                      class="icon-users"
                    />
                    {{ node.label }}

                    <span
                      v-show="node.data.id"
                      class="tasks-count"
                    >
                      ({{ node.data.tasks_count }})
                    </span>
                  </span>

                  <span
                    v-if="data.id === -2"
                    class="label padding-left"
                  >
                    {{ node.label }}

                    <span class="tasks-count">
                      ({{ categories.count_unassigned_tasks }})
                    </span>
                  </span>
              </span></el-tree>

              <el-tree
                v-if="isTasksChecked"
                key="assigneeCheckTree"
                ref="assigneeCheckTree"
                :data="assignees"
                :props="assigneeProps"
                :highlight-current="false"
                :default-expand-all="false"
                :check-on-click-node="true"
                :expand-on-click-node="false"
                :default-expanded-keys="treeState.main.assignees_tree"
                :filter-node-method="filterTreebyUserName"
                class="no-label-checkbox-tree"
                show-checkbox
                check-strictly
                node-key="id"
                empty-text="No assignees found"
                @check-change="onCheckAssigneeChange"
              >
                <span
                  slot-scope="{ node, data }"
                  class="custom-tree-node"
                >
                  <span class="label">
                    <i
                      v-show="!data.id"
                      class="icon-users"
                    />
                    {{ node.label }}
                  </span>
                </span>
              </el-tree>
            </div>

            <!-- Author Tree -->
            <div
              v-if=" ! isPrivateTeam"
              :class="{'is-open': isInvitePeopleShown}"
              class="author-tree"
            >
              <el-tree
                v-show="!isTasksChecked"
                ref="authorTree"
                :data="authors"
                :props="assigneeProps"
                :highlight-current="true"
                :default-expand-all="false"
                :check-on-click-node="true"
                :default-expanded-keys="treeState.main.authors_tree"
                :expand-on-click-node="false"
                node-key="id"
                empty-text="No authors found"
                @node-click="onAuthorClick"
                @node-collapse="onNodeCollapse($event, 'authors_tree')"
                @node-expand="onNodeExpand($event, 'authors_tree')"
              >
                <span
                  slot-scope="{ node, data }"
                  class="custom-tree-node"
                >
                  <span
                    :class="{'padding-left': data.id !== 0}"
                    class="label"
                  >
                    <i
                      v-show="!node.data.id"
                      class="icon-copyright"
                    />
                    {{ node.label }}

                    <span
                      v-show="node.data.id"
                      class="tasks-count"
                    >
                      ({{ node.data.tasks_count }})
                    </span>
                  </span>
                </span>
              </el-tree>

              <el-tree
                v-if="isTasksChecked"
                ref="authorCheckTree"
                :data="authors"
                :props="assigneeProps"
                :highlight-current="false"
                :default-expand-all="false"
                :check-on-click-node="true"
                :expand-on-click-node="false"
                :default-expanded-keys="treeState.main.authors_tree"
                :filter-node-method="filterTreebyUserName"
                show-checkbox
                check-strictly
                class="no-label-checkbox-tree"
                node-key="id"
                empty-text="No authors found"
              >
                <span
                  slot-scope="{ node, data }"
                  class="custom-tree-node"
                >
                  <span class="label">
                    <i
                      v-show="!data.id"
                      class="icon-copyright"
                    />
                    {{ node.label }}
                  </span>
                </span>
              </el-tree>

              <el-popover
                v-if="isInvitePeopleShown"
                v-model="invitePeoplePopover"
                placement="right-end"
                trigger="manual"
                width="700"
                @show="onResetInviteForm"
              >
                <invite-people-dialog
                  ref="invitePeopleDialog"
                  @submitPeople="onEditTeamPeopleSubmit($event)"
                  @close="invitePeoplePopover = false"
                />

                <div
                  slot="reference"
                  class="custom-node invite-people-node el-tree-node"
                >
                  <div
                    class="el-tree-node__content"
                    @click="invitePeoplePopover = !invitePeoplePopover"
                  >
                    <span class="custom-tree-node">
                      <span class="label padding-left">
                        Invite/remove people
                      </span>
                    </span>
                  </div>
                </div>
              </el-popover>
            </div>
          </div>
        </el-scrollbar>

        <div class="search-bar">
          <el-popover
            v-if="selectedTeam.is_owner"
            v-model="editTeamPopover"
            placement="top-start"
            trigger="manual"
            width="430"
          >
            <edit-team-dialog
              ref="editTeamDialog"
              @submitDetails="onEditTeamDetailsSubmit($event)"
              @close="editTeamPopover = false"
            />

            <el-button
              v-if=" ! isPrivateTeam"
              slot="reference"
              type="text"
              icon="el-icon-more"
              class="edit-team-dots"
              @click="editTeamPopover = ! editTeamPopover"
            />
          </el-popover>

          <el-popover
            v-else
            v-model="profilePopover"
            placement="top-start"
            trigger="manual"
            width="430"
          >
            <profile-dialog
              ref="profileDialog"
              @submit="onProfileSubmit"
              @close="profilePopover = false"
            />

            <el-button
              v-if=" ! isPrivateTeam"
              slot="reference"
              type="text"
              icon="el-icon-more"
              class="edit-team-dots"
              @click="profilePopover = ! profilePopover"
            />
          </el-popover>

          <div class="filter-input-wrapper">
            <el-input
              v-model="mainFilterText"
              placeholder="Search..."
              class="filter-input"
              clearable
              size="medium"
              @clear="getMainFilteredTasks"
              @keyup.native.enter="getMainFilteredTasks"
            >
              <el-button
                slot="append"
                icon="el-icon-search"
                @click="getMainFilteredTasks"
              />
            </el-input>
          </div>
        </div>
      </el-col>

      <el-col
        v-loading="tasksLoader"
        :class="{'hidden-content': tasksLoader}"
        :span="12"
      >
        <div
          :class="{'main-private': isPrivateTeam}"
          class="main-top-menu"
        >
          <div class="top-menu-left">
            <categories-settings
              ref="caregorySettings"
              @categoryNameUpdated="onCategoryRenamed($event, 'private')"
              @beforeCategoryDeleteRequest="onBeforeCategoryDeleteRequest(selectedCategory)"
              @afterCategoryDeleteRequest="onAfterCategoryDeleteRequest(
                selectedCategory, isPrivateTeam ? 'private' : 'public'
              )"
              @taskStatusChanged="taskStatusChanged($event)"
              @exit-click="getCategories()"
            />
          </div>
        </div>

        <div
          v-loading="onlyTasksLoader"
          class="content"
        >
          <router-view />
        </div>
      </el-col>

      <el-col
        v-loading="categoriesLoader"
        :span="6"
        :class="{'hidden-content': categoriesLoader}"
        class="filters-col"
        element-loading-background="rgba(89, 105, 136, 0.9)"
        element-loading-custom-class="white"
      >
        <categories-filter-menu />
      </el-col>
    </el-row>
  </el-col>
</template>

<script>
import {
  mapMutations, mapActions, mapState, mapGetters,
} from 'vuex'
import {
  EditTeamDialog, CreateCategoryDialog, CategoriesSettings,
  ProfileDialog, InvitePeopleDialog,
} from '@/js/components'
import pusher from '@/js/pusher'
import TreeMixin from '@/js/mixins/Trees'
import CategoriesFilterMenu from './CategoriesFilterMenu.vue'
import TreePublic from './Trees/TreePublic.vue'
import TreeSystem from './Trees/TreeSystem.vue'

export default {
  name: 'Categories',

  components: {
    EditTeamDialog,
    CreateCategoryDialog,
    InvitePeopleDialog,
    ProfileDialog,
    CategoriesSettings,
    CategoriesFilterMenu,
    TreePublic,
    TreeSystem,
  },

  mixins: [TreeMixin],

  data() {
    return {
      defaultProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },

      assigneeProps: {
        children: 'children',
        label: 'teamusername',
        disabled: 'disabled',
      },

      myTasks: [{
        label: '',
        id: 1,
        type: 'mine',
      }],

      publicCategories: [],

      systemCategories: [],

      authors: [],

      assignees: [],

      isArchived: false,

      mainFilterText: '',
      editTeamPopover: false,
      invitePeoplePopover: false,
      profilePopover: false,
    }
  },

  computed: {
    ...mapState({
      teams: state => state.teams.teams,
      selectedTeam: state => state.teams.selectedTeam,
      currentUser: state => state.account.user,
      categories: state => state.categories.categories,
      teamAuthors: state => state.categories.categories.teamAuthors,
      teamAssignees: state => state.categories.categories.teamUsers,
      selectedCategory: state => state.categories.selectedCategory,
      publicCats: state => state.categories.categories.publicTree,
      token: state => state.auth.token,
      teamUsers: state => state.teams.teamUsers,
      isTasksChecked: state => state.tasks.isTasksChecked,
      checkedTasks: state => state.tasks.checkedTasks,
      isFilterEnabled: state => state.tasks.isFilterEnabled,
      curentTasksStatus: state => state.tasks.curentTasksStatus,
      lastUserNotification: state => state.notifications.lastUserNotification,
      isNewUserNotification: state => state.notifications.isNewUserNotification,
      checkedCategories: state => state.tasks.checkedData.categories,
      checkedAssignees: state => state.tasks.checkedData.assignees,
      treeState: state => state.teams.treeState,
      lastState: state => state.teams.lastState,
      categoriesLoader: state => state.categories.categoriesLoader,
      tasksLoader: state => state.tasks.tasksLoader,
      onlyTasksLoader: state => state.tasks.onlyTasksLoader,
      filterText: state => state.tasks.checkedFilters.filterText,
    }),

    ...mapGetters([
      'isPrivateTeam',
    ]),

    selectedTeamId() {
      return this.selectedTeam.id
    },

    isInvitePeopleShown() {
      return this.selectedTeam.is_owner && this.treeState.main.authors_tree.length
    },
  },

  watch: {
    selectedTeamId(val, oldVal) {
      if (oldVal) {
        pusher.closeTeamChannel(oldVal)
      }

      if (val) {
        this.uncheckedAllTasks()
        this.categorySelected({})
        this.getCategories()
        pusher.openTeamChannel()
      }
    },

    teamAuthors(val) {
      if (val) {
        this.authors = [{
          teamusername: 'Authors',
          id: 0,
          disabled: true,
          children: val.map(el => ({
            ...el,
            disabled: true,
          })),
        }]
      }
    },

    teamAssignees(val) {
      if (val) {
        const assignees = val.slice(0)
        assignees
          .sort((a, b) => {
            const nameA = a.teamusername.toUpperCase();
            const nameB = b.teamusername.toUpperCase();
            return (nameA < nameB) ? -1
              : (nameA > nameB) ? 1 : 0;
          })
          .splice(0, 0, {
            id: -2,
            unassigned: true,
            disabled: true,
            teamusername: 'Unassigned',
          });

        this.assignees = [{
          teamusername: 'Assignees',
          id: 0,
          disabled: true,
          children: assignees,
        }]
      }
    },

    publicCats(val) {
      this.publicCategories = [{
        label: 'Categories',
        type: 'public',
        disabled: true,
        id: 0,
        children: val,
      }]
    },

    categories(val) {
      this.systemCategories = this.$refs.systemTree
        ? this.$refs.systemTree.updateData(val) : []
    },

    lastUserNotification(val) {
      this.onUserChannelChange(val)
    },

    checkedCategories(val) {
      const type = this.selectedCategory.type === 'private' ? 'private' : 'public'
      const keys = Object.keys(val)
      const cats = keys.length ? keys : []
      this.$refs[`${type}Tree`].setCheckedKeys(cats)
    },

    checkedAssignees(val) {
      const type = this.selectedCategory.type === 'private' ? 'private' : 'public'
      if (type !== 'public') {
        return
      }
      const keys = Object.keys(val)
      const cats = keys.length ? keys : []
      this.$nextTick(() => {
        if (this.$refs.assigneeCheckTree) {
          this.$refs.assigneeCheckTree.setCheckedKeys(cats)
        }
      })
    },

    isTasksChecked(val) {
      if (val) {
        this.$nextTick(() => {
          const usersCheckboxes = this.$refs.assigneeCheckTree.$el.querySelectorAll('.el-checkbox__input')

          if (this.selectedCategory.type === 'private') {
            usersCheckboxes.forEach((el, index) => {
              if (index !== 1) {
                el.querySelector('input').disabled = true
                el.classList.add('is-disabled')
              }
            })
          } else {
            usersCheckboxes.forEach((el, index) => {
              if (index !== 1) {
                el.querySelector('input').disabled = false
                el.classList.remove('is-disabled')
              }
            })
          }
        })
      }
    },

    filterText() {
      this.mainFilterText = this.filterText
    },

    selectedCategory() {
      this.updatedFilterText(null)
    },
  },

  mounted() {
    if (this.selectedTeam.id) {
      this.getCategories()
      pusher.openTeamChannel()
    }
  },

  beforeDestroy() {
    this.uncheckedAllTasks()
    pusher.closeTeamChannel()
  },

  beforeRouteUpdate(to, from, next) {
    const teams = [...this.teams.userteams, ...this.teams.shared]
    const indexes = teams.map(e => (e.private ? 'private' : e.id + ''))
    const index = indexes.indexOf(to.params.teamSlug)
    this.teamSelected(teams[index])
    next()
  },

  methods: {
    ...mapMutations([
      'editTeamDialogOpened',
      'editTeamDialogClosed',
      'profileDialogOpened',
      'profileDialogClosed',
      'addCategoryDialogOpened',
      'addCategoryDialogClosed',
      'categorySelected',
      'updatedCategories',
      'updatedCurentTasksStatus',
      'updatedTasks',
      'categoryChecked',
      'categoryUnchecked',
      'assigneeChecked',
      'assigneeUnchecked',
      'teamAdded',
      'teamSelected',
      'disabledFilter',
      'notificationAddedToTeam',
      'uncheckedAllTasks',
      'setCategoriesLoader',
      'updatedFilterText',
    ]),

    ...mapActions([
      'addUserToTeam',
      'updateTeam',
      'getTeamAutocompleteUsers',
      'getTeamCategories',
      'createPrivateCategory',
      'createPublicCategory',
      'moveCategory',
      'saveTreeState',
      'getCategoryTasks',
      'getPublicTasks',
      'getPrivateTasks',
      'getAssignedTasks',
      'getUnassignedTasks',
      'closeTask',
      'openTask',
      'getTasksByAssignee',
      'getTasksByAuthor',
      'addTaskWithChanges',
      'getNotifications',
      'getDrafts',
      'getRecently',
      'getUnsortedTasks',
      'updateCategories',
      'massRestoreTasks',
      'massDeleteTasks',
      'updateUsername',
      'updateTempDraftData',
      'toggleFakeNotification',
      'setCurrentPage',
      'setCurrentFilterPage',
      'resetTasks',
      'resetFilteredTasks',
      'saveFilterState',
      'getAllTasks',
      'getTeamInfo',
    ]),

    onResetInviteForm() {
      this.$refs.invitePeopleDialog.resetPeopleForm()
    },

    onBeforeCategoryDeleteRequest(selectedCategory) {
      return this.$refs.publicTree.onCategoryDeleted(selectedCategory)
    },

    onAfterCategoryDeleteRequest(selectedCategory, type) {
      this.onPublicCategoryDeleted(selectedCategory)
    },

    onPrivateNodeClick(id) {
      if (this.isTasksChecked) return

      this.resetCurrentCategory()
      this.$refs.privateTree.selectCategory(id)
      this.resetPagination()
    },

    onPublicNodeClick(id) {
      if (!id || this.isTasksChecked) return

      this.resetCurrentCategory()
      this.$refs.publicTree.selectCategory(id)
      this.resetPagination()
    },

    onSystemNodeClick(node) {
      this.resetCurrentCategory()
      if (!node.id || this.isTasksChecked) return

      switch (node.id) {
        case 'draft':
          this.$refs.systemTree.handleDraftsClick()
          break;
        case 'notification':
          this.$refs.systemTree.handleNotificationsClick()
          break;
        case 'recently':
          this.$refs.systemTree.handleRecentlyClick()
          break;
        default:
      }
      this.resetPagination()
    },

    getMainFilteredTasks() {
      const params = {
        search: this.mainFilterText,
      }

      this.updatedFilterText(this.mainFilterText)
      this.saveFilterState()

      if (this.mainFilterText === '' || this.mainFilterText == null) {
        this.checkSelectedCategory()
        this.getSelectedCategoryTasks()
      } else {
        this.getAllTasks({ params })
      }
    },

    getSelectedCategoryTasks() {
      if (this.selectedCategory && this.selectedCategory.method
          && this.selectedCategory.method.name && this.selectedCategory.method.data) {
        this[this.selectedCategory.method.name](this.selectedCategory.method.data)
        return
      }

      this.getAssignedTasks()
    },

    handlerSpecialClick(type) {
      this.resetCurrentCategory()
      if (this.isTasksChecked) return

      switch (type) {
        case 'unsorted':
          this.$refs.publicTree.handleUnsortedClick()
          break
        case 'all-tasks':
          this.$refs.publicTree.handleAllTasksClick()
          break
        default:
      }
      this.resetPagination()
    },

    updateCurrentCategory() {
      const { type } = this.selectedCategory
      const { id } = this.selectedCategory

      if ((type === 'public' || type === 'private') && id > 0) {
        const newData = this.$refs[`${type}Tree`].getNode(id)
        const countWord = newData && newData.data ? newData.data.count_word : 0
        this.categorySelected({ ...this.selectedCategory, countWord })
      }
    },

    checkSelectedCategory() {
      if (this.filterText != null && this.filterText !== '') {
        this.mainFilterText = this.filterText
        this.getMainFilteredTasks()
      } else {
        const lastStateKeys = Object.keys(this.lastState).length
        this.resetCurrentCategory()

        if (!this.selectedCategory.type && lastStateKeys) {
          this.updateTasksStatus()
          this.selectLastState()
          return
        }

        if (this.selectedCategory.type === 'author') {
          this.$refs.authorTree.setCurrentKey(this.selectedCategory.id)
        } else if (this.selectedCategory.type === 'assignee') {
          this.$refs.assigneeTree.setCurrentKey(this.selectedCategory.id)
        } else if (['private', 'public'].indexOf(this.selectedCategory.type) > -1) {
          this.$refs[`${this.selectedCategory.type}Tree`].selectCategory(this.selectedCategory.id)
        } else if (this.selectedCategory.type === 'system') {
          this.onSystemNodeClick(this.selectedCategory)
        } else {
          this.selectCurrentUser()
        }

        this.updateCurrentCategory()
        this.getSelectedCategoryTasks()
      }
    },

    updateTasksStatus() { // opened or closed
      this.updatedCurentTasksStatus(this.lastState.curentTasksStatus)
    },

    selectLastState() {
      // if absent for the private team item shold be opened in the private team
      // drop this setting to the main Caterories item
      const hiddenItemsForPrivateTeam = ['by_user', 'unassigned', 'by_author', 'notifications']
      if (this.selectedTeam.private
          && hiddenItemsForPrivateTeam.indexOf(this.lastState.point) > -1) {
        this.$refs.publicTree.handleAllTasksClick()
        console.error(this.selectedTeam, this.lastState)
        return
      }
      switch (this.lastState.point) {
        case 'category':
          if (this.lastState.id && this.$refs.publicTree.getNode(this.lastState.id)) {
            this.$refs.publicTree
              .handleNodeClick(this.$refs.publicTree.getNode(this.lastState.id).data)
          } else {
            this.$refs.publicTree.handleAllTasksClick()
          }
          break;
        case 'by_user':
          this.onAssigneeClick(this.$refs.assigneeTree.getNode(this.lastState.id).data)
          break;
        case 'unassigned':
          if (this.$refs.assigneeTree) {
            this.$refs.assigneeTree.setCurrentKey(-2)
            this.onAssigneeClick(this.$refs.assigneeTree.getNode(-2).data)
          } else {
            this.$refs.publicTree.handleNodeClick(
              this.$refs.publicTree.getNode(this.lastState.id).data,
            )
          }
          break;
        case 'by_author':
          this.onAuthorClick(this.$refs.authorTree.getNode(this.lastState.id).data)
          break;
        case 'unsorted':
          this.$refs.publicTree.handleUnsortedClick()
          break;
        case 'all-tasks':
          this.$refs.publicTree.handleAllTasksClick()
          break;
        case 'drafts':
          this.$refs.systemTree.handleDraftsClick()
          break;
        case 'notifications':
          this.$refs.systemTree.handleNotificationsClick()
          break;
        case 'recently':
          this.$refs.systemTree.handleRecentlyClick()
          break;
        default: break;
      }
    },

    selectCurrentUser() {
      const nodeId = this.categories.teamUsers[0].id
      if (this.$refs.assigneeTree) {
        const node = this.$refs.assigneeTree.getNode(nodeId)

        this.categorySelected({
          ...node.data,
          label: `Assignee: ${node.data.teamusername}`,
          type: 'assignee',
          method: {
            name: 'getTasksByAssignee',
            data: { userId: node.data.id },
          },
        })

        this.$refs.assigneeTree.setCurrentKey(node.data.id)
      }
    },

    managePusherListeners() {
      pusher.listenPublicCategoryDeleted(this.onPublicCategoryDeleted)
      pusher.listenTaskClosed(this.onTaskClosed)
      pusher.listenTaskOpened(this.onTaskOpened)
      pusher.listenTeamTaskEdited(this.onTaskEdited)
      pusher.listenChangeUserName()
      pusher.listenUserAddToTeam()
      pusher.listemUserRemoved()
    },

    onTaskEdited() {
      // Edit task realtime handler
      // console.log('edited task', task)
      // this.getCategories()
    },

    onTaskClosed(task) {
      this.$refs.publicTree.onTaskClosed(task)

      if (task.categories_id.indexOf(this.selectedCategory.id) > -1) {
        this.categorySelected({
          ...this.selectedCategory,
          tasks_count: this.selectedCategory.tasks_count - 1,
        })
      }
    },

    onTaskOpened(task) {
      this.$refs.publicTree.onTaskOpened(task)
      if (this.selectedCategory.type === 'public') {
        if (
          this.selectedCategory.id === 0
            || task.categories_id.indexOf(this.selectedCategory.id) > -1
        ) {
          this.categorySelected({
            ...this.selectedCategory,
            tasks_count: this.selectedCategory.tasks_count + 1,
          })
        }
      }
    },

    onNodeExpand(node, type) {
      const states = this.treeState
      const tree = states.main[type]

      tree.push(node.id)
      states.main[type] = tree

      this.saveTreeState(states)
    },

    onNodeCollapse(node, type) {
      const states = this.treeState
      const tree = states.main[type]

      tree.splice(tree.indexOf(node.id), 1)
      states.main[type] = tree

      this.saveTreeState(states)
    },

    onUserChannelChange(e) {
      switch (e.type) {
        case 'App\\Notifications\\Task\\Edited':
          this.onTaskChanged(e.task)
          break;
        case 'App\\Notifications\\Task\\Commented':
          this.onTaskChanged(e.task)
          break;
        // case 'App\\Notifications\\Task\\Deleted': // on task closed notification
        //   break;
        // case 'App\\Notifications\\Task\\Restored': // on task opened notification
        //   break;
        case 'App\\Notifications\\Category\\Changed': // on category rename
          this.onCategoryChanges(e.data, e.type)
          break;
        case 'App\\Notifications\\Team\\AddUser':
          this.listenForTeamAdd(e.team)
          break;
        case 'App\\Notifications\\Task\\Created':
          this.onTaskCreated(e.task)
          break;
        default:
          console.log('user notification', e)
      }
    },

    onTaskCreated(task) {
      console.log('created', task)
    },

    onCategoryChanges(data, type) {
      this.$refs.publicTree.onCategoryChanged(data, type)
    },

    onPublicCategoryDeleted(cat) {
      if (cat.id === this.selectedCategory.id) {
        this.resetCurrentCategory()
        // switch to the Main category after current one was deleted
        this.$refs.publicTree.handleAllTasksClick()
      }
    },

    onTaskRestored(task) {
      console.log('restored', task)
    },

    listenForTeamAdd(team) {
      this.teamAdded({
        ...team,
        user_notifications_count: 0,
      })
    },

    resetPagination() {
      this.resetTasks()
      this.resetFilteredTasks()
      this.setCurrentFilterPage(1)
      this.setCurrentPage(1)
    },

    onTaskChanged(taskData) {
      if (this.selectedTeamId === taskData.team_id && this.$refs.publicTree) {
        this.addTaskWithChanges(taskData)
        this.$refs.publicTree.updateCategoriesNotifications(taskData.categories_id)
      }

      this.notificationAddedToTeam(taskData.team_id)
      this.getCategories(true)
    },

    resetCurrentCategory() {
      if (this.$refs.systemTree) {
        this.$refs.systemTree.selectCategory(null)
      }

      if (this.$refs.publicTree) {
        this.$refs.publicTree.selectCategory(null)
      }

      if (this.$refs.authorTree) {
        this.$refs.authorTree.setCurrentKey(null)
      }

      if (this.$refs.assigneeTree) {
        this.$refs.assigneeTree.setCurrentKey(null)
      }
    },

    filterTreebyLabel(value, data) {
      if (!value) return true
      return data.label.toLowerCase().indexOf(value.toLowerCase()) !== -1
    },

    filterTreebyUserName(value, data) {
      if (!value) return true
      return data.teamusername.toLowerCase().indexOf(value.toLowerCase()) !== -1
    },

    onCreateCategorySubmit(form) {
      const type = form.type.charAt(0).toUpperCase() + form.type.slice(1);
      const method = `create${type}Category`
      this.$refs.createCategoryDialog.changeLoadingStatus(true)

      if (form.id > 0) {
        form.parent_id = form.id
      }

      delete form.type
      delete form.id

      this[method](form).then((responce) => {
        if (responce.data) {
          this.$refs.createCategoryDialog.changeLoadingStatus(false)
          this.addCategoryDialogClosed()
          this.$refs.createCategoryDialog.resetForm()

          if (method === 'createPrivateCategory') {
            this.onPrivateCategoryCreate(responce.data)
          }
        }
      })
    },

    onEditTeamClick() {
      if (this.selectedTeam.is_owner) {
        this.editTeamDialogOpened()
        return
      }

      this.profileDialogOpened()
    },

    onEditTeamDetailsSubmit(form) {
      this.updateTeam(form).then((responce) => {
        this.editTeamPopover = false
        if (responce.data && this.$router.history.current.path !== `/${responce.data.slug}`) {
          this.$router.push(`/${responce.data.slug}`)
        }
      })
    },

    onEditTeamPeopleSubmit(form) {
      const data = {
        id: form.username,
        teamusername: form.username,
        task_count: 0,
      };
      this.updatedCategories({
        teamAssignees: [...this.teamAssignees, data],
        teamAuthors: [...this.teamAuthors, data],
      });
      this.addUserToTeam(form).then((responce) => {
        if (responce.data) {
          this.getTeamAutocompleteUsers(responce.data.id)
          this.getCategories(true)
          this.$refs.invitePeopleDialog.resetPeopleForm()
        }
      })
    },

    onProfileSubmit(username) {
      this.updateUsername({ username }).then(() => {
        this.profilePopover = false
      })
    },

    onAssigneeClick(node) {
      if (!node.id) return

      const method = {
        name: '',
        data: {},
      }

      this.resetCurrentCategory()
      this.resetPagination()
      this.$refs.assigneeTree.setCurrentNode(node)
      if (node.id < 0) {
        this.getUnassignedTasks()
        method.name = 'getUnassignedTasks'
      } else {
        this.getTasksByAssignee({ userId: node.id })
        method.name = 'getTasksByAssignee'
        method.data = { userId: node.id }
      }

      this.categorySelected({
        ...node,
        label: `Assignee: ${node.teamusername}`,
        type: 'assignee',
        method,
      })
    },

    onAuthorClick(node) {
      if (!node.id) return

      this.resetCurrentCategory()
      this.resetPagination()
      this.$refs.authorTree.setCurrentNode(node)
      this.getTasksByAuthor({ userId: node.id })
      this.categorySelected({
        ...node,
        label: `Author: ${node.teamusername}`,
        type: 'author',
        method: {
          name: 'getTasksByAuthor',
          data: { userId: node.id },
        },
      })
    },

    taskStatusChanged(taskData) {
      const method = taskData.isArchived ? 'massRestoreTasks' : 'massDeleteTasks'
      const tasks = Object.keys(this.checkedTasks)
      this[method]({ tasks })
    },

    onPrivateCategoryCreate(data) {
      this.$refs.privateTree.onCategoryCreated({ ...data, descendants_tasks_count: 0 })
    },

    getCategories(disableLoader = false) {
      this.getTeamCategories(disableLoader).then((response) => {
        if (response.data) {
          const { data } = response
          let me = false
          if (data.teamUsers) {
            me = data.teamUsers.filter(u => u.id === this.currentUser.id)
          }

          if (me.length) {
            this.myTasks[0].label = `${me[0].teamusername}`
          } else {
            this.myTasks[0].label = 'currentUser'
          }

          this.publicCategories = [{
            label: 'Categories',
            type: 'public',
            disabled: true,
            id: 0,
            children: data.publicTree,
          }]

          this.systemCategories = this.$refs.systemTree
            ? this.$refs.systemTree.updateData(data) : []

          let children = []
          if (data.teamAuthors) {
            children = data.teamAuthors.map(el => ({
              ...el,
              disabled: true,
            }))
          }

          this.authors = [{
            teamusername: 'Authors',
            id: 0,
            disabled: true,
            children,
          }]

          const assignees = data.teamUsers.slice(0)
          assignees
            .sort((a, b) => {
              const nameA = a.teamusername.toUpperCase()
              const nameB = b.teamusername.toUpperCase()
              return (nameA < nameB) ? -1
                : (nameA > nameB) ? 1 : 0;
            })
            .splice(0, 0, {
              id: -2,
              unassigned: true,
              disabled: true,
              teamusername: 'Unassigned',
            });


          this.assignees = [{
            teamusername: 'Assignees',
            id: 0,
            disabled: true,
            children: assignees,
          }]

          this.updateCategories(data).then(() => {
            this.checkSelectedCategory()
            this.$nextTick(() => {
              this.managePusherListeners()
              this.setCategoriesLoader(false)
            })
          })
        }
      })
    },

    onCheckCategoryChange(node, isSelected) {
      if (isSelected) {
        this.categoryChecked(node.id)
        return
      }

      this.categoryUnchecked(node.id)
      this.checkSelectedCategory()
    },

    onCheckAssigneeChange(node, isSelected) {
      if (isSelected) {
        this.assigneeChecked(node.id)
        return
      }

      this.assigneeUnchecked(node.id)
    },

    handleCreateNewTask() {
      const tempDraftData = {
        name: '',
        body: '',
      }
      this.updateTempDraftData(tempDraftData)
      this.toggleFakeNotification({
        opened: false,
        closed: true,
      })
      this.$router.push(`/team/${this.selectedTeam.slug}/create-task`)
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables.scss';

.public-tree {
  position: relative;
  margin: 10px;
}

.categories {
  .search-bar {
    padding: 6px 37px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;

    .edit-team-dots {
      font-size: 18px;
      color: #fff;
      opacity: 0.7;
      padding: 0 3px;
      transform: rotate(90deg);
    }

    .filter-input-wrapper {
      width: 100%;
      margin-left: 11px;
    }
  }

  .categories-list {
    position: relative;
    padding-bottom: 50px;

    &.disabled::after {
      content: '';
      position: absolute;
      top: 0;
      bottom: 0;
      left: 0;
      right: 0;
      z-index: 2;
      background-color: rgba(255, 255, 255, .5);
      cursor: not-allowed;
    }

    .categories-header-buttons {
      padding: 20px 37px 15px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .assignee-tree,
    .author-tree {
      margin: 10px;

      /* &.is-open {
        padding-bottom: 26px;
      } */
    }

    &-private {
      background-color: $light-private;
    }
  }

  .tasks {
    padding: 0 10px;
  }

  .main-top-menu {
    background: transparent linear-gradient(180deg, #EEEFF3 0%, #FFFFFF 83%, #FFFFFF66 100%);
    margin-bottom: -19px;
    padding-bottom: 10px;
    position: relative;
    z-index: 2;

    &.main-private {
      background: transparent linear-gradient(180deg, #EEF3F2 0%, #FFFFFF 83%, #FFFFFF66 100%);
    }

    .top-menu-left {
      padding: 17px 20px 0px;
    }
  }

  .categories-list-private {
    .filter-input-wrapper {
      padding-right: 0;
    }
  }
}

.filters-col {
  background-color: $light-blue;
}

.popover-btn {
  width: 100%;
  font-size: $small-body;
  color: $gray-icon;
  border: none;
  background-color: transparent;
  transition: background-color .2s ease;

  &:hover {
    background-color: $gray-active;
  }
}

.has-notifications::after {
  right: -13px;
}

.hidden-content > * {
  opacity: 0;
}

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

  &.invite-people-node {
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
</style>
