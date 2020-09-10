<template>
  <router-link
    :to="getTaskUrl()"
    :event="''"
    @click.native="nativeClick"
  >
    <el-card
      class="task box-card"
      shadow="hover"
    >
      <div class="checkbox">
        <el-checkbox
          :disabled="isTasksCheckboxDisabled"
          v-model="isChecked"
          @click="onCheckboxChange"
        />
      </div>

      <div class="content">
        <div
          :class="{'last-task': isLastTask}"
          class="name-link task-name"
        >
          <div
            v-if="isTaskUnread"
            class="icon-notification"
          /><i
            v-show="content.is_draft"
            class="icon-eye-off"
          /><span
            v-show="content.is_draft"
            class="draft"
          >Draft:&nbsp;
          </span><i
            v-show="content.is_temp && !content.is_private && !content.is_draft"
            class="icon-pencil"
          /><span class="name">{{ content.name }}</span>
        </div>

        <div class="task-info">
          {{
            `#${content.team_task_id} ` +
              `${content.username ? 'by ' + content.username + ', ' : ''}` +
              `changed ${content.diff}` +
              (content.due_date ? `, due to ${makeShortDate(content.due_date)}` : '')
          }}
        </div>
      </div>
    </el-card>
  </router-link>
</template>

<script>
import { mapState, mapGetters } from 'vuex'
import Dates from '@/js/mixins/Dates'

export default {
  name: 'Task',

  mixins: [Dates],

  props: {
    content: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      isChecked: false,
    }
  },

  computed: {
    ...mapState({
      tasksWithChanges: state => state.tasks.tasksWithChanges,
      userData: state => state.account.user,
      isTasksCheckboxDisabled: state => state.tasks.isTasksCheckboxDisabled,
      checkedTasks: state => state.tasks.checkedTasks,
      lastTask: state => state.teams.lastTask,
    }),

    ...mapGetters([
      'lastTaskId',
    ]),

    isTaskUnread() {
      return this.tasksWithChanges.indexOf(this.content.id) > -1
        || this.content.user_notifications_count > 0
    },

    isLastTask() {
      return this.content.team_task_id.toString() === this.lastTask
    },
  },

  watch: {
    checkedTasks(val) {
      const keys = Object.keys(val)
      if (!keys.length) {
        this.isChecked = false
        return
      }

      if (keys.indexOf(this.content.id.toString()) > -1) {
        this.isChecked = true
      }
    },
  },

  mounted() {
    const keys = Object.keys(this.checkedTasks)
    if (!keys.length) {
      this.isChecked = false
      return
    }

    if (keys.indexOf(this.content.id.toString()) > -1) {
      this.isChecked = true
    }
  },

  methods: {
    onCheckboxChange() {
      if (this.isChecked) {
        this.$emit('taskChecked')
        return
      }

      this.$emit('taskUnchecked', this.content.id)
    },

    getTaskUrl() {
      return `/${this.$route.params.teamSlug}/${this.content.team_task_id}`
    },

    makeShortDate(date) {
      const due = new Date(date)
      const month = this.tr_month_names[due.getMonth()]
      const day = due.getDate()
      const year = due.getFullYear()

      return `${month} ${day}, ${year}`
    },
    nativeClick(e) {
      if (!this.isTasksCheckboxDisabled && e.target.className === 'el-checkbox__inner') {
        this.isChecked = !this.isChecked
        this.onCheckboxChange()
        return
      }
      this.$router.push(this.getTaskUrl())
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables.scss';

.el-card {
  border: none;
  position: relative;
  margin-left: -8px;

  &:hover {
    z-index: 1;
  }
}

.task {
  display: flex;

  .icon-notification {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #2ebbff;
    position: relative;
    display: inline-block;
    vertical-align: top;
    top: 6px;
    margin-right: 3px;
  }

  .checkbox {
    width: 20px;
  }

  .icon-eye-off,
  .icon-pencil {
    color: $orange;
  }

  .content {
    .task-name {
      color: $task-name;
      padding: 5px 10px;
      font-size: 17px;
      font-weight: 500;
      margin-bottom: 2px;
      display: inline-block;

      .name {
        text-align: left;
      }

      .draft,
      .name,
      .private {
        text-decoration: none;
      }

      .draft {
        color: $orange;
      }

      .private {
        color: $green;
      }
    }

    .task-info {
      padding-left: 10px;
      color: $dark-link;
      font-size: $small-body;
    }
  }
}
</style>
