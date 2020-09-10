<template>
  <el-row
    v-show="loaded"
    :gutter="0"
    type="flex"
    class="create-wrapper"
  >
    <el-col
      v-loading="categoriesLoader"
      :span="6"
      :class="{'categories-list-private': isPrivateTeam, 'hidden-content': categoriesLoader}"
      element-loading-custom-class="white"
      class="categories-list"
    >
      <el-scrollbar
        class="team-users-scroll"
      >
        <div class="small-title small-title--team">{{ selectedTeam.name }}</div>

        <div class="small-title small-title--yellow">Assignments of the task</div>

        <div
          :class="{'is-open': isAddCategoryShown}"
          class="tree"
        >
          <el-tree
            v-if="isInit"
            ref="categoriesTree"
            :data="treeData"
            :props="treeProps"
            :check-strictly="true"
            :check-on-click-node="true"
            :default-expand-all="false"
            :expand-on-click-node="false"
            :default-expanded-keys="
              treeState.create[`public_tree`]
            "
            class="no-label-checkbox-tree"
            empty-text="No categories found"
            show-checkbox
            node-key="id"
            @check="leftMenuSaveDraft(0)"
            @node-collapse="onNodeCollapse($event, `public_tree`)"
            @node-expand="onNodeExpand($event, `public_tree`)"
          >
            <span
              slot-scope="{ node, data }"
              class="custom-tree-node"
            >
              <span class="label">
                <i
                  v-show="!$route.params.userId && data.id === 0"
                  class="icon-folder"
                />

                {{ node.label }}
              </span>
            </span>
          </el-tree>

          <el-popover
            v-if="isAddCategoryShown"
            v-model="createCategoryPopover"
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
              @click="createCategoryPopover = !createCategoryPopover"
            >
              <div class="el-tree-node__content">
                <span class="custom-tree-node">
                  <span class="label padding-left">
                    Add category
                  </span>
                </span>
              </div>
            </div>
          </el-popover>
        </div>

        <div
          v-if="!isPrivateTeam"
          class="tree"
        >
          <el-tree
            ref="usersTree"
            :data="teamUsers"
            :props="usersProps"
            :check-strictly="true"
            :check-on-click-node="true"
            :expand-on-click-node="false"
            :default-expand-all="false"
            :default-expanded-keys="treeState.create.assignees_tree"
            class="no-label-checkbox-tree"
            empty-text="No users found"
            show-checkbox
            node-key="id"
            @check="leftMenuSaveDraft(0)"
            @node-collapse="onNodeCollapse($event, 'assignees_tree')"
            @node-expand="onNodeExpand($event, 'assignees_tree')"
          >
            <span
              slot-scope="{ node, data }"
              class="custom-tree-node"
            >
              <span class="label">
                <i
                  v-show="data.id === 0"
                  class="icon-users"
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
              @deletePeople="getCurrentTeamInfo"
              @close="invitePeoplePopover = false"
            />

            <div
              slot="reference"
              class="custom-node invite-people-node el-tree-node"
              @click="invitePeoplePopover = !invitePeoplePopover"
            >
              <div class="el-tree-node__content">
                <span class="custom-tree-node">
                  <span class="label padding-left">
                    Invite/remove people
                  </span>
                </span>
              </div>
            </div>
          </el-popover>
        </div>

        <div class="due-date">
          <div class="prefix">
            <i class="icon-clock" />
            Due to:
          </div>

          <el-date-picker
            v-model="task.due_date"
            format="MMM d, yyyy"
            value-format="yyyy-MM-dd"
            type="date"
            size="medium"
            placeholder="Set due date"
            class="tr-datetime"
            @change="leftMenuSaveDraft(0)"
          />
        </div>
      </el-scrollbar>
    </el-col>

    <el-col :span="18">
      <el-form
        ref="formMain"
        :model="task"
        :rules="rules"
      >
        <el-row
          type="flex"
          justify="space-between"
          class="top-buttons"
        >
          <el-col :span="11">
            <el-button-group>
              <el-tooltip
                :disabled="isCreateTaskTooltip"
                content="Set task name"
                placement="bottom"
              >
                <el-button
                  :loading="isLoader"
                  type="success"
                  size="medium"
                  @click="createTask"
                >
                  Create task
                </el-button>
              </el-tooltip>

              <el-button
                :loading="isLoader"
                class="edit-button"
                size="medium"
                type="default"
                @click="fakeHandleSaveDraft"
              >
                Save draft
              </el-button>
            </el-button-group>
            <el-button
              :loading="isLoader"
              class="edit-button cancel-button"
              size="medium"
              type="default"
              @click="onDeleteDraftCLick"
            >
              Cancel
            </el-button>
          </el-col>
          <el-col
            :span="6"
            class="text-right"
          >
            <el-button-group>
              <el-button
                :disabled="true"
                class="dropdown-button"
                type="default"
              >
                <div class="drop-item">
                  <i class="el-icon-more" />
                </div>
              </el-button>
              <router-link
                :to="`/team/${$route.params.teamSlug}/create-task`"
                target="_blank"
              >
                <el-button
                  :loading="isLoader"
                  class="new-task-btn"
                  size="medium"
                  type="default"
                >
                  + Task
                </el-button>
              </router-link>
            </el-button-group>
          </el-col>
        </el-row>

        <el-scrollbar class="edit-task-scroll">
          <div class="middle-section">
            <el-form-item
              prop="name"
            >
              <el-input
                v-model="task.name"
                class="task-name"
                placeholder="Task Name..."
                maxlength="256"
                @input="handleSaveDraft(1000)"
                @keydown.once.native="initialSave"
                @keyup.enter.ctrl.native="createTask"
              />
            </el-form-item>

            <tr-trix-input
              ref="trixInput"
              v-model="task.body"
              :full-height="true"
              placeholder="Description"
              @change="handleSaveDraft(1000)"
              @ctrlEnterPress="createTask"
            />
          </div>
        </el-scrollbar>
      </el-form>
    </el-col>
  </el-row>
</template>

<script>
import {
  mapState, mapActions, mapMutations, mapGetters,
} from 'vuex'
import { TrTrixInput, CreateCategoryDialog, InvitePeopleDialog } from '@/js/components'

let timeout = null

export default {
  name: 'TaskCreate',

  components: {
    TrTrixInput,
    CreateCategoryDialog,
    InvitePeopleDialog,
  },

  data() {
    return {
      taskId: null,

      teamTaskId: null,

      isDraftInitial: false,

      isValidStandartTree: true,

      isLoader: false,

      isInit: false,

      isPopover: false,

      treeData: [{
        label: 'Categories',
        id: 0,
        disabled: true,
        children: [],
      }],

      treeProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },

      usersProps: {
        label: 'username',
        children: 'children',
        disabled: 'disabled',
      },

      teamUsers: [
        {
          username: 'Assignee',
          id: 0,
          disabled: true,
          children: [],
        },
      ],

      task: {
        name: '',
        body: '',
        due_date: '',
      },

      rules: {
        name: [
          { required: true, message: 'Set task name', trigger: 'blur' },
        ],
      },

      type: '',

      createCategoryPopover: false,
      invitePeoplePopover: false,

      loaded: false,
    }
  },

  computed: {
    ...mapState({
      currentUser: state => state.account.user,
      categories: state => state.categories.categories,
      teams: state => state.teams.teams,
      selectedTeam: state => state.teams.selectedTeam,
      selectedCategory: state => state.categories.selectedCategory,
      treeState: state => state.teams.treeState,
      tempDraftData: state => state.tasks.tempDraftData,
      categoriesLoader: state => state.categories.categoriesLoader,
      tasksLoader: state => state.tasks.tasksLoader,
    }),

    ...mapGetters([
      'isPrivateTeam',
    ]),

    isCreateTaskTooltip() {
      return !!this.taskId && !!this.task.name
    },

    isAddCategoryShown() {
      for (const item of this.treeState.create.public_tree) {
        if (item == 0) {
          return true
        }
      }

      return false
    },

    isInvitePeopleShown() {
      return this.selectedTeam.is_owner && this.treeState.create.assignees_tree.length
    },
  },

  created() {
    this.getTeamsList().then(() => {
      this.findLastSelectedTeam()
      this.getCategories()
      this.getCurrentTeamInfo()
      this.checkForTempVersion()

      this.loaded = true
    })
  },

  mounted() {
    const pathArr = this.$route.path.split('/')
    this.type = pathArr[pathArr.length - 1]
  },

  methods: {
    ...mapMutations([
      'alertError',
      'updatedCategories',
      'teamSelected',
      'setCategoriesLoader',
      'categorySelected',
    ]),

    ...mapActions([
      'getTeamCategories',
      'getTeamInfo',
      'saveDraft',
      'createPublicTask',
      'createPrivateTask',
      'deleteDraft',
      'saveTreeState',
      'removeTempTask',
      'toggleFakeNotification',
      'updateTempDraftData',
      'getTeamsList',
      'createPublicCategory',
      'addUserToTeam',
      'getTeamAutocompleteUsers',
    ]),

    onResetCategoryForm() {
      this.$refs.createCategoryDialog.$refs.createCategoryForm.$refs.creteCategoryForm.resetFields()
    },

    onResetInviteForm() {
      this.$refs.invitePeopleDialog.resetPeopleForm()
    },

    setPrivateTeam() {
      for (const team of this.teams.userteams) {
        if (team.private) {
          this.categorySelected({})
          this.teamSelected(team)
          if (this.$router.history.current.path != '/private') {
            this.$router.push('/private')
          }
        }
      }
    },

    findLastSelectedTeam() {      
      let currentTeams = []

      if (!this.teams.userteams.length && !this.teams.shared.length) {
        return
      }

      if (this.$route.path === '/') {
        Object.values(this.teams).forEach((team) => {
          if (!currentTeams.length) {
            currentTeams = team.filter(innerTeam => innerTeam.current)
          }
        })

        if (!currentTeams.length) {
          if (this.teams.userteams.length) {
            currentTeams = this.teams.userteams
          } else if (this.teams.shared.length) {
            currentTeams = this.teams.shared
          }
        }
      } else if (this.$route.params.teamSlug) {
        Object.values(this.teams).forEach((team) => {
          if (!currentTeams.length) {
            currentTeams = team
              .filter(innerTeam => innerTeam.slug === this.$route.params.teamSlug)
          }
        })
      }

      if (currentTeams.length) {
        this.teamSelected(currentTeams[0])
      }
    },

    getAssigneesKeys() {
      return this.selectedCategory.type === 'assignee'
      && this.selectedCategory.id > 0
      && this.isValidStandartTree
        ? [this.selectedCategory.id]
        : []
    },

    filterTree(value, data) {
      if (!value) return true
      return data.label.toLowerCase().indexOf(value.toLowerCase()) !== -1
    },

    filterUsersTree(value, data) {
      if (!value) return true
      return data.username.toLowerCase().indexOf(value.toLowerCase()) !== -1
    },

    getCategories(disableLoader = false) {
      this.getTeamCategories(disableLoader).then((responce) => {
        this.setCategoriesLoader(false)
        if (responce.data) {
          this.updatedCategories(responce.data)
          this.treeData[0].children = responce.data.publicTree
          this.isInit = true

          if (this.task.categories_id) {
            setTimeout(() => {
              this.task.categories_id = JSON.parse(JSON.stringify(this.task.categories_id))
            }, 100)
          }
        }
      })
    },

    getCurrentTeamInfo() {
      this.getTeamInfo().then((response) => {
        if (response.data) {
          this.teamUsers[0].children = JSON.parse(JSON.stringify(response.data.users))
        }
      })
    },

    initialSave() {
      this.sendDraftData()
    },

    leftMenuSaveDraft(time) {
      this.isValidStandartTree = false

      if (this.taskId) {
        this.handleSaveDraft(time)
      }
    },

    createTask() {
      this.$refs.formMain.validate(valid => valid);

      if (!this.taskId || !this.task.name) return

      const data = this.validateDraft()

      if (data) {
        this.isLoader = true
        const taskType = 'createPublicTask'
        const mentionedUsers = this.$refs.trixInput.getMentionedUsers()
        data.task_id = this.taskId
        data.subscribers = mentionedUsers.length ? mentionedUsers : null
        clearInterval(timeout)

        data.name = data.name || 'Untitled'
        data.due_date = this.task.due_date || null

        this[taskType](data).then((responce) => {
          this.isLoader = false

          if (responce.data) {
            this.updateTempDraftData({
              name: '',
              body: '',
            })
            this.$router.push(`/${this.$route.params.teamSlug}/${responce.data.team_task_id}`)
          }
        })
      }
    },

    handleSaveDraft(time) {
      clearInterval(timeout)
      this.isValidStandartTree = false

      timeout = setTimeout(() => {
        if (!this.isLoader) {
          this.sendDraftData()
        }
      }, time)
    },

    sendDraftData() {
      const data = this.validateDraft()

      if (data) {
        if (this.taskId) {
          data.task_id = this.taskId
        }

        data.name = data.name || 'Untitled'
        data.type = this.type
        this.isDraftInitial = true
        this.updateTempDraftData(data)

        this.saveDraft({ teamId: this.$route.params.teamSlug, data }).then((responce) => {
          if (responce.data) {
            this.taskId = responce.data.id
            this.teamTaskId = responce.data.team_task_id
          }
        })
      }
    },

    onNodeCollapse(node, treeName) {
      const states = this.treeState
      const tree = states.create[treeName]

      tree.splice(tree.indexOf(node.id), 1)
      states.create[treeName] = tree

      this.saveTreeState(states)
    },

    onNodeExpand(node, treeName) {
      const states = this.treeState
      const tree = states.create[treeName]

      tree.push(node.id)
      states.create[treeName] = tree

      this.saveTreeState(states)
    },

    validateDraft() {
      const users = this.$refs.usersTree ? this.$refs.usersTree.getCheckedKeys() : []
      let categories = this.$refs.categoriesTree ? this.$refs.categoriesTree.getCheckedKeys() : []
      categories = categories.filter(cat => cat > 0)
      categories = categories.length ? categories : null

      return {
        categories, users, ...this.task, due_date: this.task.due_date || null,
      }
    },

    onDeleteDraftCLick() {
      if (!this.taskId) {
        this.$router.push('/')
        return
      }

      this.deleteDraft(this.taskId).then((response) => {
        if (response.data) {
          this.toggleFakeNotification({
            opened: true,
            closed: false,
            isDraft: true,
            isTaskPrivate: false,
          })
          this.$router.push('/')
        }
      })
    },

    checkForTempVersion() {
      if (this.tempDraftData.name || this.tempDraftData.body) {
        this.task.name = this.tempDraftData.name ? this.tempDraftData.name : ''
        this.task.body = this.tempDraftData.body ? this.tempDraftData.body : ''
        const pathArr = this.$route.path.split('/')
        this.type = pathArr[pathArr.length - 1]

        this.sendDraftData()
      }
    },

    onClickCancelCreateTask() {
      this.$router.push(`/${this.$route.params.teamSlug}`)
    },

    fakeHandleSaveDraft() {
      const tempDraftData = {
        name: '',
        body: '',
      }
      this.updateTempDraftData(tempDraftData)
      if (!this.task.name && !this.task.body) {
        this.$refs.formMain.validate(valid => valid)
        return
      }
      this.$router.push(`/${this.$route.params.teamSlug}/${this.teamTaskId}`)
    },

    onCreateCategorySubmit(form) {
      this.$refs.createCategoryDialog.changeLoadingStatus(true)

      if (form.id > 0) {
        form.parent_id = form.id
      }

      delete form.type
      delete form.id

      this.createPublicCategory(form).then((responce) => {
        if (responce.data) {
          this.$refs.createCategoryDialog.changeLoadingStatus(false)
          this.$refs.createCategoryDialog.resetForm()
          this.createCategoryPopover = false
          this.getCategories()
        }
      })
    },

    onEditTeamPeopleSubmit(form) {
      this.addUserToTeam(form).then((responce) => {
        if (responce.data) {
          // this.invitePeoplePopover = false
          this.getTeamAutocompleteUsers(responce.data.id)
          this.$refs.invitePeopleDialog.resetPeopleForm()
          this.getCurrentTeamInfo()
          this.getCategories(true)
        }
      })
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables.scss';

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

.categories-list {
  .back-link {
    color: $gray;

    &.is-disabled {
      pointer-events: none;
    }
  }

  .tree {
    padding: 0 10px;
    margin-bottom: 15px;
  }

  &-private {
    background-color: $light-private;
  }
}

.is-disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.middle-section {
  .el-form-item {
    margin-bottom: 20px;

    ::v-deep .el-form-item__content {
      line-height: normal;
    }

    .task-name {
      line-height: normal;

      ::v-deep input {
        font-weight: 700;
        width: 100%;
        margin: 1px;
        font-size: $main-title;
        background-color: transparent;
        border-width: 0;
        color: $dark;
        padding: 1px 2px;
        height: auto;
        line-height: normal;

        &:focus {
          outline-style: auto;
          outline-offset: 0;
          outline-color: -webkit-focus-ring-color;
          outline-width: 1px;
        }
      }
    }

    &.is-error {
      ::v-deep input {
        border-width: 1px;
        margin: 0;
      }
    }
  }
}

.top-buttons {
  padding-top: 16px;
  padding-bottom: 12px;
  padding-right: 40px;
  margin-bottom: -10px;
  background: transparent linear-gradient(180deg, #FFFFFF 0%, #FFFFFF 85%, #FFFFFF00 100%);
  z-index: 3;

  .cancel-button {
    position: relative;
    top: 1px;
    margin-left: 5px;
  }
  .text-right {
    text-align: right;
  }
  .drop-item {
    padding: 10px 20px;
  }
  .new-task-btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
  }
}

.right-menu {
  position: absolute;
  top: 20px;
  right: 40px;
  min-width: 360px;
  text-align: end;

  .draft-incicator {
    margin-top: 20px;
    display: inline-block;
    background-color: white;
  }

  .edit-buttons {
    display: flex;
    width: 100%;
    justify-content: flex-end;

    button {
      flex: auto;
      max-width: 180px;
    }
  }
}

.due-date {
  margin-top: 15px;
  margin-left: 34px;
  display: flex;

  .prefix {
    margin-right: 10px;
    display: flex;
    align-items: center;
    color: white;

    .icon-clock::before {
      margin-right: .5em;
    }
  }
}

.tooltip-categories {
  margin-top: 35px;
}

.back-wrapper {
  margin-top: 15px;
  margin-bottom: 30px;
}

.cancel-btn {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}

.hidden-content > * {
  opacity: 0;
}
</style>
