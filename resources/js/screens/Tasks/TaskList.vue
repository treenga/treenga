<template>
  <div>
    <el-scrollbar
      class="tasks-scroll"
      ref="tasksList"
    >
      <div
        v-show="tasks && !isFilterEnabled"
        class="tasks-list"
      >
        <div class="current-tasks">
          <task
            v-for="(task, index) in paginatedTasks"
            :key="task.id"
            :content="task"
            :class="{'last-current': checkIfLastCurrent(index)}"
            class="tasks-list__task"
            @taskChecked="onTaskCheck(task)"
            @taskUnchecked="onTaskUncheck"
          />
        </div>
      </div>

      <div
        v-show="tasks && isFilterEnabled"
        class="tasks-list filter-list"
      >
        <task
          v-for="task in filteredPaginatedTasks"
          :key="`filtered-${task.name}-${task.id}`"
          :content="task"
          class="tasks-list__task"
          @taskChecked="onTaskCheck(task)"
          @taskUnchecked="onTaskUncheck"
        />
      </div>

      <el-pagination
        v-show="tasks && !isFilterEnabled && allTasks.length / perPage > 1"
        :current-page="currentPage"
        :total="allTasks.length / perPage"
        :page-size="1"
        background
        class="pagination"
        layout="prev, pager, next"
        @current-change="onSetCurrentPage($event)"
      />

      <el-pagination
        v-show="tasks && isFilterEnabled && filteredTasks.length / perPage > 1"
        :current-page="currentFilterPage"
        :total="filteredTasks.length / perPage"
        :page-size="1"
        background
        class="pagination"
        layout="prev, pager, next"
        @current-change="onSetCurrentFilterPage($event)"
      />
    </el-scrollbar>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import { Task } from '@/js/components'


export default {
  name: 'TaskList',

  components: {
    Task,
  },

  data() {
    return {      
      perPage: 50,
    }
  },

  computed: {
    ...mapState({
      isFilterEnabled: state => state.tasks.isFilterEnabled,
      filteredTasks: state => state.tasks.filteredTasks,
      tasks: state => state.tasks.tasks,
      selectedTeam: state => state.teams.selectedTeam,
      currentUser: state => state.account.user,
      checkedTasks: state => state.tasks.checkedTasks,
      teamUsers: state => state.teams.teamUsers,
      currentPage: state => state.tasks.currentPage,
      currentFilterPage: state => state.tasks.currentFilterPage,
    }),

    paginatedTasks() {
      const start = (this.currentPage - 1) * this.perPage
      const end = start + this.perPage
      return this.allTasks.slice(start, end)
    },

    filteredPaginatedTasks() {
      const start = (this.currentFilterPage - 1) * this.perPage
      const end = start + this.perPage
      return this.filteredTasks.slice(start, end)
    },

    allTasks() {
      if (this.tasks.currentTasks.tasks && this.tasks.childTasks.tasks) {
        return [...this.tasks.currentTasks.tasks, ...this.tasks.childTasks.tasks]
      }

      return []
    },
  },

  methods: {
    ...mapActions([
      'getMassAttributes',
      'setCurrentPage',
      'setCurrentFilterPage',
    ]),

    ...mapMutations([
      'checkedTask',
      'uncheckedTask',
      'uncheckedAllTasks',
      'allCategoriesUnchecked',
    ]),

    onSetCurrentPage(e) {
      this.$refs.tasksList.$el.querySelector('.el-scrollbar__wrap').scroll({
        top: 0, 
        left: 0, 
        behavior: 'smooth'
      })
      this.setCurrentPage(e)
    },

    onSetCurrentFilterPage(e) {
      this.$refs.tasksList.$el.querySelector('.el-scrollbar__wrap').scroll({
        top: 0, 
        left: 0, 
        behavior: 'smooth'
      })
      this.setCurrentFilterPage(e)
    },

    onTaskCheck(task) {
      this.checkedTask(task)
    },

    checkIfLastCurrent(index) {
      if (!this.tasks.childTasks.tasks.length) {
        return false
      }

      const currentIndex = ((this.currentPage - 1) * this.perPage) + (index + 1)
      return currentIndex === this.tasks.currentTasks.tasks.length
    },

    onTaskUncheck(task) {
      this.uncheckedTask(task)
      const tasks = Object.keys(this.checkedTasks)
      if (!tasks.length) {
        this.uncheckedAllTasks()
        this.allCategoriesUnchecked()
      }
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables.scss';

  .tasks-scroll {
    width: 100%;
  }

  .tasks-list {
    padding: 9px 20px;

    &__task:not(:last-child) {
      margin-bottom: 24px;
    }

    &__task.last-current {
      margin-bottom: 60px;
      position: relative;
    }
  }

  .last-current {
      &::after, &::before {
      content: '';
      position: absolute;
      bottom: -50px;
      width: 100%;
    }

    &::after {
      content: 'Tasks from child categories';
      height: 30px;
      display: flex;
      justify-content: center;
      align-items: center;
      width: 200px;
      left: 50%;
      transform: translateX(-50%);
      color: $cyan;
      background-color: white;
    }

    &::before {
      height: 2px;
      bottom: -38px;
      left: 5px;
      opacity: .5;
      background-image: linear-gradient(to right, transparent 50%, $cyan 50%);
      background-size: 14px 100%;
    }
  }

  .pagination {
    padding-left: 45px;
    padding-top: 25px;
    padding-bottom: 12px;
  }
</style>
