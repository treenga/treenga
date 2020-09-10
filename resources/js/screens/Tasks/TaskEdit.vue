<template>
  <el-row
    v-if="isAccess === true"
    :gutter="0"
    type="flex"
    class="edit-wrapper"
  >
    <el-col
      v-loading="categoriesLoader || tasksLoader"
      :span="6"
      :class="{
        'categories-list-private': isPrivateTeam,
        'hidden-content': categoriesLoader || tasksLoader
      }"
      element-loading-custom-class="white"
      class="categories-list"
    >
      <el-scrollbar
        class="team-users-scroll"
      >
        <div class="small-title small-title--team">{{ selectedTeam.name }}</div>

        <div class="small-title small-title--yellow">Assignments</div>

        <div
          :class="{'is-open': isAddCategoryShown}"
          class="tree"
        >
          <el-tree
            ref="categoriesTree"
            :data="treeData"
            :show-checkbox="true"
            :auto-expand-parent="false"
            :props="treeProps"
            :check-strictly="true"
            :check-on-click-node="true"
            :expand-on-click-node="false"
            :default-expand-all="false"
            :default-expanded-keys="treeState.create[`public_tree`]"
            :default-checked-keys="categoriesIds"
            node-key="id"
            class="no-label-checkbox-tree"
            empty-text="No categories found"
            @check="updateTaskHandler(true)"
            @node-collapse="onNodeCollapse($event, `public_tree`)"
            @node-expand="onNodeExpand($event, `public_tree`)"
          >
            <span
              slot-scope="{ node, data }"
              class="custom-tree-node"
            >
              <span class="label">
                <i
                  v-if="$route.params.userId"
                  class="icon-lock"
                />

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
          class="tree menu-padding"
        >
          <el-tree
            v-if="task.usersIds && teamUsers[0].children.length"
            ref="usersTree"
            :data="teamUsers"
            :props="usersProps"
            :check-on-click-node="true"
            :check-strictly="true"
            :expand-on-click-node="false"
            :default-checked-keys="task.usersIds"
            :default-expand-all="false"
            :default-expanded-keys="treeState.create.assignees_tree"
            class="no-label-checkbox-tree"
            empty-text="No userss found"
            show-checkbox
            node-key="id"
            @check="updateTaskHandler(true)"
            @node-collapse="onNodeCollapse($event, 'assignees_tree')"
            @node-expand="onNodeExpand($event, 'assignees_tree')"
          >
            <span
              slot-scope="{ node, data }"
              class="custom-tree-node"
            >
              <i
                v-show="!data.id"
                class="icon-users"
              />

              {{ node.label }}
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

        <div class="due-date menu-padding">
          <div class="prefix">
            <i class="icon-clock" />
            Due to:
          </div>

          <el-date-picker
            v-model="task.due_date"
            format="MMM d, yyyy"
            value-format="yyyy-MM-dd"
            type="date"
            placeholder="Set due date"
            size="medium"
            class="tr-datetime"
            @change="updateTaskHandler"
          />
        </div>

        <div
          v-if="task.id && !$route.params.userId && !task.is_draft"
          class="participants-tree menu-padding"
        >
          <h2 class="small-title small-title--yellow">Participants</h2>

          <h4
            v-for="participant in participants"
            :key="`participant-${participant.id}`"
            class="participant"
          >
            {{ participant.username }}
          </h4>
        </div>
      </el-scrollbar>
    </el-col>

    <el-col
      v-loading="tasksLoader"
      :class="{'hidden-content': tasksLoader}"
      :span="18"
    >
      <div>
        <!-- Top buttons -->
        <el-row
          type="flex"
          justify="space-between"
          class="top-buttons"
        >
          <el-col
            v-show="!isEditThisTask"
            :span="12"
          >
            <el-button
              type="info"
              size="medium"
              plain
              @click="$router.push(`/${$route.params.teamSlug}`); onToggleIsEdit(false)"
            >
              ← Back to task list
            </el-button>
            <el-button
              v-if="!task.is_draft"
              :loading="isStatusLoader"
              size="medium"
              @click="onTaskStatusToggle"
            >
              <i
                v-if="!task.is_archived"
                class="icon-close-task"
              />
              {{ task.is_archived ? 'Reopen task' : 'Close task' }}
            </el-button>
          </el-col>
          <el-col
            v-show="isEditThisTask && !task.is_draft"
            :span="12"
          >
            <el-button
              :loading="isLoader"
              type="primary"
              class="edit-button"
              size="medium"
              @click="onSaveClick"
            >
              Save changes
            </el-button>

            <el-button
              class="edit-button"
              type="default"
              size="medium"
              @click="onClickCancelEditing(task)"
            >
              Cancel
            </el-button>
          </el-col>
          <el-col
            v-show="isEditThisTask && task.is_draft"
            :span="12"
          >
            <el-button-group>
              <el-tooltip
                :disabled="isCreateTaskTooltip"
                content="Set task name"
                placement="bottom"
              >
                <el-button
                  :loading="isLoader"
                  class="edit-button"
                  type="success"
                  @click="createTask"
                >
                  Create task
                </el-button>
              </el-tooltip>

              <el-button
                v-show="task.id"
                :loading="isLoader"
                class="edit-button"
                @click="updateTaskHandler(false)"
              >
                Save draft
              </el-button>
            </el-button-group>
            <el-button
              v-show="task.id"
              :loading="isLoader"
              class="edit-button cancel-button"
              @click="onClickCancelEditing(task)"
            >
              Cancel
            </el-button>
          </el-col>
          <el-col
            :span="12"
            class="text-right"
          >
            <el-button
              v-show="isEditThisTask && task.is_draft"
              :class="{'is-disabled': !task.id}"
              class="delete-button"
              type="warning"
              size="medium"
              plain
              icon="el-icon-delete"
              @click="onDeleteDraftCLick(task)"
            />
            <el-button-group>
              <el-button
                v-if="!isEditThisTask"
                type="default"
                size="medium"
                @click="onEditClick"
              >
                <i class="icon-pencil" />
                Edit
              </el-button>
              <el-button
                :disabled="isEditThisTask && task.is_draft"
                class="dropdown-button"
                type="default"
                size="medium"
              >
                <el-dropdown
                  placement="bottom"
                  trigger="click"
                  style="color: inherit;"
                  @command="handleDropdown"
                >
                  <div class="drop-item">
                    <i class="el-icon-more" />
                  </div>

                  <el-dropdown-menu
                    slot="dropdown"
                  >
                    <el-dropdown-item
                      :disabled="isEditThisTask"
                      command="history"
                    >
                      Show task revisions
                    </el-dropdown-item>

                    <el-dropdown-item command="subscribe">
                      {{ task.is_subscriber ? 'Unsubscribe from' : 'Subscribe to' }}  notifications
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </el-dropdown>
              </el-button>

              <router-link
                :to="`/team/${$route.params.teamSlug}/create-task`"
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
      </div>

      <el-scrollbar
        class="edit-task-scroll"
      >
        <div
          v-if="isEditThisTask"
          class="middle-section"
        >
          <el-form
            ref="formMain"
            :model="task"
            :rules="rules"
          >
            <div class="task-name-edit">
              <el-tag
                v-if="task.id && ! task.is_archived"
                class="task-name-tag"
                size="small"
                type="success"
              >
                Open
              </el-tag>
              <el-tag
                v-if="task.id && task.is_archived"
                class="task-name-tag"
                size="small"
                type="info"
              >
                Closed
              </el-tag>
              <el-tag
                v-if="task.id && task.is_draft"
                class="task-name-tag"
                size="small"
                type="warning"
              >
                Draft
              </el-tag>

              <el-form-item
                prop="name"
              >
                <el-input
                  v-model="task.name"
                  class="task-name"
                  placeholder="Task Name..."
                  maxlength="256"
                  @keydown.native="onInputChange"
                  @keyup.enter.ctrl.native="onCtrlEnter"
                />
              </el-form-item>
            </div>
          </el-form>
          <tr-trix-input
            ref="trixInput"
            v-model="task.text"
            :full-height="true"
            placeholder="Description"
            @change="onTrixChange"
            @ctrlEnterPress="onCtrlEnter"
          />
        </div>

        <div
          v-show="!isEditThisTask"
          class="middle-section"
        >
          <h2 class="task-name">
            <el-tag
              v-if="task.id && ! task.is_archived"
              class="task-name-tag"
              size="small"
              type="success"
            >
              Open
            </el-tag>
            <el-tag
              v-if="task.id && task.is_archived"
              class="task-name-tag"
              size="small"
              type="info"
            >
              Closed
            </el-tag>
            <el-tag
              v-if="task.id && task.is_draft"
              class="task-name-tag"
              size="small"
              type="warning"
            >
              Draft
            </el-tag>
            {{ task.name }}
          </h2>

          <p
            v-if="task.team_task_id"
            class="additional-info"
          >
            {{
              `#${task.team_task_id} ` + (`${task.username ? 'by ' + task.username + ', ' : ''}`) +
                `changed ${task.diff}` +
                (task.due_date ? `, due to ${shortDate}` : '')
            }}
          </p>

          <div
            ref="taskContent"
            class="task-body"
            v-html="task.text"
          />

          <div
            v-if="task.id && !task.is_draft"
            class="comments-section"
          >
            <comments
              ref="comments"
              :comments="task.activities"
              :is-details="true"
              :is-right-menu="!isTaskPrivate"
              @createComment="onCreateComment($event)"
              @commentRendered="onCommentRendered($event)"
            />
          </div>
        </div>
      </el-scrollbar>
    </el-col>
  </el-row>
</template>

<script>
import {
  mapState, mapActions, mapMutations, mapGetters,
} from 'vuex'
import {
  TrTrixInput, Comments, CreateCategoryDialog, InvitePeopleDialog,
} from '@/js/components'
import pusher from '@/js/pusher'

let timeout = null

export default {
  name: 'TaskEdit',

  components: {
    TrTrixInput,
    Comments,
    CreateCategoryDialog,
    InvitePeopleDialog,
  },

  data() {
    return {
      submited: false,

      filterText: '',

      isPopover: false,

      isCancelPopover: false,

      isTemp: false,

      isLoader: false,

      isDirty: false,

      isStatusLoader: false,

      ready: false,

      onCtrlEnterResctrict: false,

      commentsList: {},

      temporaryComment: {},

      temporaryCommentId: 'temp',

      timeInterval: null,

      cleanOpenedDocument: null,

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
        diff: '',
        due_date: '',
        id: false,
        username: '',
        comments: [],
      },

      rules: {
        name: [
          { required: true, message: 'Set task name', trigger: 'blur' },
        ],
      },

      participants: [],

      createCategoryPopover: false,
      invitePeoplePopover: false,
    }
  },

  computed: {
    ...mapState({
      currentUser: state => state.account.user,
      categories: state => state.categories.categories,
      users: state => state.teams.teamUsers,
      teams: state => state.teams.teams,
      selectedTeam: state => state.teams.selectedTeam,
      selectedCategory: state => state.categories.selectedCategory,
      lastUserNotification: state => state.notifications.lastUserNotification,
      treeState: state => state.teams.treeState,
      isEdit: state => state.tasks.isEdit,
      isAccess: state => state.teams.isAccess,
      categoriesLoader: state => state.categories.categoriesLoader,
      tasksLoader: state => state.tasks.tasksLoader,
      lastEditedTaskId: state => state.tasks.lastEditedTaskId,
    }),

    ...mapGetters([
      'isPrivateTeam',
    ]),

    isCreateTaskTooltip() {
      return !!this.task.id && !!this.task.name
    },

    shortDate() {
      if (!this.task.due_date) return ''
      const newDate = new Date(this.task.due_date)
      const m = newDate.toLocaleDateString('en-us', { month: 'short' })
      const d = newDate.toLocaleDateString('en-us', { day: 'numeric' })
      const y = newDate.getFullYear()
      return `${m} ${d}, ${y}`
    },

    isTaskPrivate() {
      return this.$route.name === 'Edit private task'
    },

    isEditThisTask() {
      const { taskId, teamSlug } = this.$route.params;
      return this.isEdit.taskId === taskId && this.isEdit.teamSlug === teamSlug;
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

    categoriesIds() {
      return this.task.categories_id
    },
  },

  watch: {
    filterText(val) {
      this.$refs.categoriesTree.filter(val)
      if (this.$refs.usersTree) {
        this.$refs.usersTree.filter(val)
      }
    },

    users(val) {
      this.$set(this.teamUsers[0], 'children', val)
    },

    lastUserNotification(val) {
      this.onUserChannelChange(val)
    },
  },

  created() {
    this.setLastTask(this.$route.params.taskId)

    this.getTaskData().then(() => {
      this.getTeamsList().then(() => {
        this.findLastSelectedTeam()
        if (!this.isAccess) {
          this.$router.push(`/${this.$route.params.teamSlug}`)
          return
        }
        this.ready = true

        this.onToggleIsEdit(this.isDirty || this.isTemp)
      })
    })

    this.getCategories().then(() => {
      this.getCurrentTeamInfo()
      this.saveLastTask({ task_id: this.$route.params.taskId })
    })
  },

  mounted() {
    this.$set(this.teamUsers[0], 'children', this.users)
  },

  beforeDestroy() {
    if (this.task.id) {
      pusher.closeTaskListener(this.task.id)
    }
  },

  methods: {
    ...mapMutations([
      'alertError',
      'updatedTasks',
      'teamSelected',
      'accessForbidden',
      'setCategoriesLoader',
      'updatedCategories',
      'categorySelected',
      'setLastTask',
      'setLastEditedTaskId',
    ]),

    ...mapActions([
      'getTeamCategories',
      'getTeamCategoriesShortInfo',
      'getTaskInfo',
      'saveDraft',
      'saveTempVersion',
      'updateTask',
      'createTaskComment',
      'subscribeUser',
      'unsubscribeUser',
      'createPublicTask',
      'createPrivateTask',
      'getTeamInfo',
      'deleteDraft',
      'removeTaskWithChanges',
      'closeTask',
      'openTask',
      'saveTreeState',
      'saveLastTask',
      'removeTempVersion',
      'restoreTempVersion',
      'toggleFakeNotification',
      'toggleIsEdit',
      'updateTempDraftData',
      'getTeamsList',
      'createPublicCategory',
      'addUserToTeam',
      'getTeamAutocompleteUsers',
    ]),

    managePusherListeners() {
      pusher.openTaskChannel(this.task.id)
      pusher.listenTaskActivities(this.onTaskActivityAdded)
      pusher.listenTaskCommented(this.onTaskCommented)
      pusher.listenTaskNewSubscriber(this.onUserSubscribed)
      pusher.listenTaskDeleteSubscriber(this.onDeleteSubscriber)
    },

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
      if (this.teams.userteams || this.teams.shared) {
        let currentTeams = []
        if (!this.teams.userteams.length
            && !this.teams.shared.length) {
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
        } else {
          this.accessForbidden()
        }
      }
    },

    onUserChannelChange(e) {
      switch (e.type) {
        case 'App\\Notifications\\Task\\Deleted': // on task closed notification
          this.task.is_archived = true
          break;
        case 'App\\Notifications\\Task\\Restored': // on task opened notification
          this.task.is_archived = false
          break;
        default:
          console.log('user notification task', e)
      }
    },

    onNodeExpand(node, type) {
      const states = this.treeState
      const tree = states.create[type]

      tree.push(node.id)
      states.create[type] = tree

      this.saveTreeState(states)
    },

    onNodeCollapse(node, type) {
      const states = this.treeState
      const tree = states.create[type]

      tree.splice(tree.indexOf(node.id), 1)
      states.create[type] = tree

      this.saveTreeState(states)
    },

    onTaskCommented(comment) {
      const isTempComment = comment.id === this.temporaryCommentId;
      if (isTempComment) {
        this.temporaryComment = comment
        this.temporaryComment.id = this.temporaryCommentId + Date.now()
      } else if (this.commentsList[comment.id]) {
        return false
      }
      this.$set(this.commentsList, comment.id, isTempComment ? this.temporaryComment : comment)

      if (!comment.parent_id) {
        if (!this.task.activities) {
          this.task.activities = [];
        }
        this.task.activities.push(isTempComment ? this.temporaryComment : comment)
        return null
      }

      if (this.commentsList[comment.parent_id].children) {
        this.commentsList[comment.parent_id].children
          .push(isTempComment ? this.temporaryComment : comment)
        return null
      }

      this.$set(this.commentsList[comment.parent_id], 'children', [isTempComment ? this.temporaryComment : comment])
      return null
    },

    onCommentRendered(comment) {
      this.commentsList[comment.id] = comment
    },

    onUserSubscribed(user) {
      this.participants.push(user)
    },

    onDeleteSubscriber(user) {
      this.participants = this.participants.filter(p => p.id !== user.id)
    },

    onInputChange() {
      if (!this.onCtrlEnterResctrict) {
        this.handleSaveTempVersion(500)
      }
    },

    onTrixChange() {
      const { editor } = this.$refs.trixInput.editor
      if (!this.cleanOpenedDocument) {
        this.cleanOpenedDocument = editor.getDocument()
      }
      if (!editor.getDocument().isEqualTo(this.cleanOpenedDocument) && !this.onCtrlEnterResctrict) {
        this.handleSaveTempVersion(500)
      }
    },

    handleSaveTempVersion(time) {
      clearInterval(timeout)
      timeout = setTimeout(() => {
        const validData = this.validateTask()

        if (validData) {
          const data = {
            name: validData.name || 'Untitled',
            body: validData.text,
          }

          if (!this.submited) {
            this.saveTempVersion({
              teamId: this.$route.params.teamSlug,
              taskId: this.task.id,
              data,
            }).then(() => {
              this.isDirty = true
            })
          }
        }
      }, time)
    },

    onTaskActivityAdded(activity) {
      if (!this.task.activities) {
        this.task.activities = []
      }

      this.task.activities.push(activity)
    },

    handleDropdown(command) {
      switch (command) {
        case 'history':
          if (this.isEditThisTask) {
            this.confirmLeave().then(() => {
              this.$router.push(`/history/${this.$route.params.teamSlug}/task/${this.task.id}`)
            }).catch(() => {})
          } else {
            this.$router.push(`/history/${this.$route.params.teamSlug}/task/${this.task.id}`)
          }
          break
        case 'subscribe':
          this.onSubscribeClick()
          break
        default: break
      }
    },

    confirmLeave() {
      return this.$confirm('Unsaved data will be lost !', 'Leave page?', {
        dangerouslyUseHTMLString: true,
        confirmButtonText: 'Yes, leave page',
        cancelButtonText: 'Cancel',
        confirmButtonClass: 'el-button--warning el-button--medium',
        cancelButtonClass: ' el-button--medium',
      })
    },

    confirmCancel() {
      return this.$confirm('Unsaved data will be lost', 'Discard changes?', {
        dangerouslyUseHTMLString: true,
        confirmButtonText: 'Yes, discard changes',
        cancelButtonText: 'No',
        confirmButtonClass: 'el-button--warning el-button--medium',
        cancelButtonClass: ' el-button--medium',
      })
    },

    createTask() {
      clearInterval(timeout)
      this.$refs.formMain.validate(valid => valid);

      if (!this.task.name || !this.task.id) return

      const validData = this.validateTask()

      if (validData) {
        const taskType = this.$route.params.userId ? 'createPrivateTask' : 'createPublicTask'
        const data = {
          name: validData.name,
          body: validData.text,
          task_id: validData.id,
          categories: validData.categories,
          users: validData.users,
          due_date: validData.due_date,
        }

        this.removeTempVersion({
          teamId: this.$route.params.teamSlug,
          taskId: this.task.id,
        })
        this[taskType](data).then((responce) => {
          if (responce.data) {
            this.updateTempDraftData({
              name: '',
              body: '',
            })
            this.getTaskData()
            this.onToggleIsEdit(false)
          }
        })
      }
    },

    getShortCategoriesInfo() {
      return this.getTeamCategoriesShortInfo().then((responce) => {
        this.setCategoriesLoader(false)
        if (responce.data) {
          const data = JSON.parse(JSON.stringify(responce.data))
          this.treeData[0].children = data.publicTree
        }

        return responce
      })
    },

    onCreateComment(data) {
      const currentUser = this.users.find(element => element.id === this.currentUser.id)

      data.id = this.temporaryCommentId
      data.is_loading = true
      data.is_comment = true
      data.username = currentUser.username

      this.onTaskCommented(data)

      this.createTaskComment({
        taskId: this.task.id,
        data,
      }).then((response) => {
        if (response.data) {
          this.$refs.comments.onCancelClick()
          this.temporaryComment.id = response.data.id
          this.temporaryComment.parent_id = response.data.parent_id
          this.temporaryComment.diff = 'just now'
          this.temporaryComment.is_loading = false
        }
      })
    },

    onToggleIsEdit(state) {
      if (state) {
        const { taskId, teamSlug } = this.$route.params
        this.toggleIsEdit({ taskId, teamSlug })
        return
      }

      this.toggleIsEdit({})
    },

    getTaskData() {
      this.task = {}
      this.isDirty = false
      if (!this.$route.params.taskId) {
        this.$router.push(`/${this.$route.params.teamSlug}/${this.lastEditedTaskId}`)
        this.onToggleIsEdit(true)
        return null
      }
      return this.getTaskInfo(this.$route.params.taskId).then((response) => {
        if (response.data) {
          if (response.data.is_temp) {
            this.isTemp = true
          } else {
            this.isTemp = false
          }

          this.task = response.data
          this.participants = response.data.participants || []

          this.removeTaskWithChanges(response.data.id)
          this.updatedTasks({
            currentTasks: { tasks: [] },
            childTasks: { tasks: [] },
          })
          this.managePusherListeners()
          this.setDownloadLinks()
        } else {
          this.$router.push('/')
        }

        return response
      })
    },

    setDownloadLinks() {
      this.timeInterval = setInterval(() => {
        if (this.$refs.taskContent === undefined) return
        this.$refs.taskContent.addEventListener('click', (e) => {
          if (e.target.offsetParent
              && e.target.offsetParent.dataset
              && e.target.offsetParent.dataset.trixAttachment) {
            const data = JSON.parse(e.target.offsetParent.dataset.trixAttachment)

            if (!(data.contentType.indexOf('image') + 1)) {
              const link = document.createElement('a')
              link.href = data.url;
              link.setAttribute('target', '_blank')
              document.body.appendChild(link)
              link.click()
            }
          }
        })
        clearInterval(this.timeInterval)
      }, 100);
    },

    onCancelClick() {
      this.onToggleIsEdit(false)
      this.isCancelPopover = false
      this.getTaskData()
    },

    onEditClick() {
      this.cleanOpenedDocument = null
      this.onToggleIsEdit(true)
      this.toggleFakeNotification({ opened: false })
      this.isDirty = false
      this.submited = false
    },

    onCtrlEnter() {
      this.submited = true
      if (!this.task.is_draft) {
        this.onSaveClick()
        return
      }

      this.createTask()
    },

    updateTaskHandler(keepEdit = false) {
      if (!this.task.name && !this.task.body && this.$refs.formMain) {
        this.$refs.formMain.validate(valid => valid)
        return
      }
      if (this.task.is_draft) {
        this.handleSaveDraft(keepEdit && Object.keys(this.isEdit).length)
        return
      }
      this.onSaveClick(keepEdit)
    },

    onSaveClick(keepEdit = false) {
      if (this.$refs.formMain) {
        this.$refs.formMain.validate(valid => valid);
      }
      if (!this.task.name) return

      this.submited = true
      const validData = this.validateTask()

      if (validData) {
        const { usersTree, trixInput } = this.$refs

        const mentionedUsers = trixInput ? trixInput.getMentionedUsers() : []

        const data = {
          name: validData.name,
          body: validData.text,
          categories: validData.categories,
          due_date: validData.due_date,
          subscribers: mentionedUsers.length ? mentionedUsers : null,
        }

        if (usersTree) {
          const users = usersTree.getCheckedKeys()
          if (users.length) {
            data.users = users
          }
        }

        this.task.categories_id = validData.categories

        this.removeTempVersion({ teamId: this.$route.params.teamSlug, taskId: this.task.id })
        this.updateTask({
          taskId: validData.id,
          data,
        }).then((response) => {
          this.isLoader = false
          this.cleanOpenedDocument = null

          if (response.data && keepEdit !== true) {
            this.onToggleIsEdit(false)
          }
        })
      }
    },

    handleSaveDraft(keepEdit = false) {
      const validData = this.validateTask()

      if (validData) {
        const data = {
          name: validData.name || 'Untitled',
          body: validData.text,
          task_id: validData.id,
          categories: validData.categories,
          users: validData.users || [],
          due_date: validData.due_date,
          type: this.$route.params.userId ? 'private' : 'public',
        }

        this.saveDraft({ teamId: this.$route.params.teamSlug, data })
        this.removeTempVersion({
          teamId: this.$route.params.teamSlug,
          taskId: this.task.id,
        }).then(() => {
          this.onToggleIsEdit(keepEdit)
        })
      }
    },

    validateTask() {
      let users = null
      let categories = this.$refs.categoriesTree ? this.$refs.categoriesTree.getCheckedKeys() : []
      categories = categories.filter(cat => cat > 0)
      categories = categories.length ? categories : null

      if (!this.$route.params.userId && this.$refs.usersTree) {
        users = this.$refs.usersTree.getCheckedKeys()
        users = users[0] === 0 ? users.slice(1) : users
        users = users.length ? users : null
      }

      return {
        categories, users, ...this.task, due_date: this.task.due_date || null,
      }
    },

    onSubscribeClick() {
      if (this.task.is_subscriber) {
        this.unsubscribeUser(this.task.id).then((response) => {
          if (response.data) {
            this.task.is_subscriber = false
          }
        })
        return
      }

      this.subscribeUser(this.task.id).then((response) => {
        if (response.data) {
          this.task.is_subscriber = true
        }
      })
    },

    onDeleteDraftCLick() {
      this.deleteDraft(this.task.id).then((response) => {
        if (response.data) {
          this.$router.push('/')
        }
      })
    },

    onTaskStatusToggle() {
      const actionType = this.task.is_archived ? 'open' : 'close'

      this.isStatusLoader = true
      this[`${actionType}CurrentTask`](this.task.id).then(() => {
        this.isStatusLoader = false
      })
    },

    capitalize(string) {
      return string.charAt(0).toUpperCase() + string.slice(1)
    },

    closeCurrentTask(data) {
      return this.closeTask(data).then((response) => {
        if (response.data) {
          this.task.is_archived = true
        }

        return response
      })
    },

    openCurrentTask(data) {
      return this.openTask(data).then((response) => {
        if (response.data) {
          this.task.is_archived = false
        }

        return response
      })
    },

    onClickCancelEditing(task) {
      this.removeTempVersion({ teamId: this.$route.params.teamSlug, taskId: task.id }).then(() => {
        this.onToggleIsEdit(false)
        this.getTaskData()
      })
      this.cleanOpenedDocument = null

      if (this.isDirty || this.isTemp) {
        this.setLastEditedTaskId(this.$route.params.taskId)
        this.toggleFakeNotification({
          opened: true,
          closed: false,
          taskId: task.id,
          taskTeamId: this.task.team_task_id,
          isTaskPrivate: this.isTaskPrivate,
          onUndo: this.getTaskData,
        })
      }
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

    getCurrentTeamInfo() {
      this.getTeamInfo().then((response) => {
        if (response.data) {
          if (this.task.usersIds) {
            this.task.usersIds = JSON.parse(JSON.stringify(this.task.usersIds))
          }
        }
      })
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
          this.getCategories(true)
        }
      })
    },

    getCategories(disableLoader = false) {
      return this.getTeamCategories(disableLoader).then((responce) => {
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

        return responce
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
  }

  .back-menu {
    margin-top: 15px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;

    & > div:first-child {
      flex: auto;
      margin-right: 20px;
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

.middle-section {
  text-align: left;
  height: 100%;

  h2.task-name {
    font-weight: 700;
    width: 100%;
    font-size: $main-title;
    background-color: transparent;
    border-width: 0;
    color: $dark;
    padding: 1px 2px;
    height: auto;
  }

  .el-form-item {
    margin-bottom: 0px;
    width: 100%;

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

  .task-name-edit {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
  }
}

.comments-section {
  margin-top: 40px;
  padding-top: 20px;
  border-top: 1px solid #EBEEF5;
}

.additional-info {
  color: $gray-icon;
}

.due-date {
  margin-top: 15px;
  margin-left: 23px;
  display: flex;

  .prefix {
    margin-right: 10px;
    display: flex;
    align-items: center;
    color: white;

    .icon-clock::before {
      margin-right: .6em;
    }
  }
}

.edit-wrapper {
  position: relative;
}

.participants-tree {
  margin-top: 20px;

  .small-title {
    padding-left: 0;
    padding-top: 0;
  }
}

.menu-padding {
  padding: 10px;
}

.participant {
  font-size: 16px;
  color: white;
  margin: 5px 0 0 45px;
}

.cancel-btn {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}

.delete-button {
  margin-right: 40px;
  position: relative;
  top: 2px;
}

.task-name-tag {
  font-weight: 500;
  vertical-align: middle;
  margin-top: -4px;
}

.task-name-edit {
  .task-name-tag {
    margin-right: 5px;
    margin-top: 0;
  }
}

.edit-task-col {
  padding-bottom: 50px;
}

.hidden-content > * {
  opacity: 0;
}
</style>
