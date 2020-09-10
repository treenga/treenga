<template>
  <el-row
    v-if="isAccess === true"
    :gutter="0"
    type="flex"
  >
    <el-col
      v-loading="categoriesLoader"
      element-loading-custom-class="white"
      :span="6"
      class="histories-list"
      :class="{'histories-list-private': isPrivateTeam, 'hidden-content': categoriesLoader}"
    >
      <el-scrollbar class="history-scroll">
        <div class="small-title small-title--team">{{ selectedTeam.name }}</div>

        <div class="small-title small-title--yellow">Select task version</div>

        <div class="histories">
          <div
            v-for="history in histories"
            :key="`history-name-${history.id}`"
          >
            <el-button
              :class="{current: currentHistoryId === history.id}"
              type="text"
              class="history-btn"
              @click="selectHistory(history)"
            >
              {{ history.date }}
            </el-button>
          </div>
        </div>
      </el-scrollbar>
    </el-col>

    <el-col
      v-loading="categoriesLoader"
      :span="18"
      :class="{'hidden-content': categoriesLoader}"
    >
      <el-scrollbar class="edit-category-scroll">
        <div class="top-buttons">
          <el-button
            type="info"
            @click="$router.push(`/${$route.params.teamSlug}/${team_task_id}`)"
          >
            ← Go back
          </el-button>

          <el-button
            v-show="currentHistoryId"
            type="primary"
			plain
            @click="onRevertClick"
          >
            <i class="icon-back-in-time"></i>
			Revert to this version
          </el-button>
        </div>

        <div class="middle-section">
          <h2 class="category-name">{{ task.name }}</h2>

          <div
            class="category-body"
            v-html="task.body"
          />

          <div
            v-if="task.comments && task.comments.length"
            class="comments-section"
          >
            <comments
              ref="comments"
              :comments="task.comments"
            />
          </div>
        </div>
      </el-scrollbar>
    </el-col>
  </el-row>
</template>

<script>
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex'
import { TrTrixInput, Comments } from '@/js/components'

export default {
  name: 'TaskHistory',

  components: {
    TrTrixInput,
    Comments,
  },

  data() {
    return {
      currentHistoryId: 0,
      currentBody: '',
      task: {
        body: '',
        name: '',
      },
      team_task_id: null,
      histories: [],
    }
  },

  computed: {
    ...mapState({
      teams: state => state.teams.teams,
      selectedTeam: state => state.teams.selectedTeam,
      isAccess: state => state.teams.isAccess,
      categoriesLoader: state => state.categories.categoriesLoader,
    }),

    ...mapGetters([
      'isPrivateTeam',
    ]),
  },

  created() {
    this.getTeamsList().then(() => {
      this.findLastSelectedTeam()
      this.getCurrentTeamInfo()
      this.getHistory()
    })
  },

  methods: {
    ...mapMutations([
      'teamSelected',
      'setCategoriesLoader',
    ]),

    ...mapActions([
      'getTaskHistory',
      'revertTaskHistory',
      'getTeamsList',
      'getTeamInfo',
    ]),

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

    getCurrentTeamInfo() {
      this.getTeamInfo()
    },

    getHistory() {
      this.setCategoriesLoader(true)
      this.getTaskHistory(this.$route.params.taskId).then((response) => {
        this.setCategoriesLoader(false)
        if (response.data) {
          this.task.name = response.data.name
          this.team_task_id = response.data.team_task_id
          this.currentBody = response.data.text
          this.histories = response.data.histories
          this.selectHistory({
            id: 0,
            body: response.data.text,
          })
        }
      })
    },

    selectHistory(history) {
      this.currentHistoryId = history.id
      this.task.body = history.body
    },

    onCancelClick() {
      this.currentHistoryId = 0
      this.task.body = this.currentBody
    },

    onRevertClick() {
      this.revertTaskHistory({
        taskId: this.$route.params.taskId,
        historyId: this.currentHistoryId,
      }).then((response) => {
        if (response.data) {
          this.$router.push(`/${this.$route.params.teamSlug}/${this.team_task_id}`)
        }
      })
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables.scss';

.histories-list {
  background-color: $light-blue;
  height: 100vh;
  padding: 0 0 0 10px;

  .back-link {
    color: $gray;
  }

  &-private {
    background-color: $light-private;
  }
}

.middle-section {
  text-align: left;
  height: 100%;

  .category-name {
    margin-bottom: 20px;
    font-size: $main-title;
    width: 100%;
    background-color: transparent;
    border: none;
    color: $dark;
  }
}

.right-menu {
  padding: 20px 10px;

  .draft-incicator {
    margin-top: 20px;
  }

  .edit-buttons {
    display: flex;
    justify-content: flex-end;

    button {
      flex: auto;
    }
  }
}

.comments-section {
  margin-top: 50px;
}

.histories {
  padding-left: 20px;

  .history-btn {
    color: white;
    text-decoration: underline;
    font-size: $body;
    position: relative;

    &.current {
      font-weight: 700;

      &::before {
        content: '';
        position: absolute;
        left: -15px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-top: 7px solid transparent;
        border-bottom: 7px solid transparent;
        border-left: 7px solid white;
      }
    }
  }
}

.hidden-content > * {
  opacity: 0;
}
</style>
