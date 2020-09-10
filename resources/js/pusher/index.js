import Echo from 'laravel-echo'
import store from '@/js/store'
import Socketio from 'socket.io-client'

window.Pusher = require('pusher-js')

const pusher = {
  echo: null,
  userChannel: null,
  teamChannel: null,
  taskChannel: null,
  categoryChannel: null,

  init() {
    if (!this.echo) {
      let params = {
        auth: {
          headers: {
            Authorization: `Bearer ${store.state.auth.token}`,
          },
        },
      }

      if (process.env.BROADCAST_DRIVER === 'redis') {
        params = Object.assign(params, {
          broadcaster: 'socket.io',
          host: `${window.location.hostname}:6001`,
          client: Socketio,
        })
      } else {
        params = Object.assign(params, {
          broadcaster: 'pusher',
          key: process.env.PUSHER_APP_KEY,
        })
      }

      this.echo = new Echo(params)
    }
  },

  openUserChannel() {
    if (!this.userChannel) {
      this.userChannel = this.echo.private(`user.${store.state.account.user.id}`)
    }
  },

  openTaskChannel(taskId) {
    if (!this.taskChannel) {
      this.taskChannel = this.echo.private(`task.${taskId}`)
    }
  },

  openTeamChannel() {
    if (!this.teamChannel) {
      this.teamChannel = this.echo.private(`team.${store.state.teams.selectedTeam.id}`)
    }
  },

  listenUserUpdate(callback) {
    this.userChannel.listen('Auth.Verify', (e) => {
      callback(e.user)
    })
  },

  listenLogout(callback) {
    this.userChannel.listen('Auth.Logout', (e) => {
      callback(e.data)
    })
  },

  listenTaskActivities(callback) {
    this.taskChannel.listen('Activity.CreatedInTask', (e) => {
      callback(e.data)
    })
  },

  listenTaskCommented(callback) {
    this.taskChannel.listen('Task.Commented', (e) => {
      e.comment.is_comment = true
      e.comment.is_new = e.comment.author_id !== store.state.account.user.id
      callback(e.comment)
    })
  },

  listenTaskNewSubscriber(callback) {
    this.taskChannel.listen('Task.NewSubscriber', (e) => {
      callback(e.user)
    })
  },

  listenTaskDeleteSubscriber(callback) {
    this.taskChannel.listen('Task.DeleteSubscriber', (e) => {
      callback(e.user)
    })
  },

  listenTeamTaskRestored(callback) {
    this.teamChannel.listen('Task.Restored', (e) => {
      callback(e.task)
    })
  },

  listenTeamTaskDeleted(callback) {
    this.teamChannel.listen('Task.Deleted', (e) => {
      callback(e.task)
    })
  },

  listenPublicCategoryCreated(callback) {
    this.teamChannel.listen('Category.Created', (e) => {
      callback({ ...e.category, descendants_tasks_count: 0 })
    })
  },

  listenPublicCategoryDeleted(callback) {
    this.teamChannel.listen('Category.Deleted', (e) => {
      callback(e)
    })
  },

  listenPublicCategoryRenamed(callback) {
    this.teamChannel.listen('Category.Renamed', (e) => {
      callback(e)
    })
  },

  listenPublicCategoryMoved(callback) {
    this.teamChannel.listen('Category.Moved', (e) => {
      callback(e.category)
    })
  },

  listenTaskClosed() {
    // this.teamChannel.listen('Task.Deleted', (e) => {
    //   store.commit('closedTask', e.task)
    //   if (callback) {
    //     callback(e.task)
    //   }
    // })
  },

  listenTaskOpened() {
    // this.teamChannel.listen('Task.Restored', (e) => {
    //   store.commit('openedTask', e.task)
    //   if (callback) {
    //     callback(e.task)
    //   }
    // })
  },

  listenTeamTaskEdited(callback) {
    this.teamChannel.listen('Task.Edited', (e) => {
      store.dispatch('updateTaskData', e.task)
      if (callback) {
        callback(e.task)
      }
    })
  },

  listenChangeUserName() {
    this.teamChannel.listen('Team.Edit', (e) => {
      store.commit('changedUsername', e.team)
      store.commit('changedCatsUsername', e.team)
    })
  },

  listenUserAddToTeam() {
    this.teamChannel.stopListening('Team.AddUser')
    this.teamChannel.listen('Team.AddUser', (e) => {
      const data = {
        ...e.user,
        teamusername: e.user.username,
        tasks_count: 0,
      }
      let teamUsers = [...store.state.categories.categories.teamUsers];
      teamUsers = teamUsers.findIndex(el => el.id === data.id) === -1 ? [...teamUsers, data] : teamUsers;
      let teamAuthors = [...store.state.categories.categories.teamAuthors]
      teamAuthors = teamAuthors.findIndex(el => el.id === data.id) === -1 ? [...teamAuthors, data] : teamAuthors;
   
      store.commit('updatedCategories', {
        teamUsers: teamUsers,
        teamAuthors: teamAuthors,
      })
    })
  },

  listemUserRemoved() {
    this.teamChannel.listen('Team.DeleteUser', (e) => {
      const teamUsers = store.state.categories.categories.teamUsers
        .filter(user => user.id !== e.user.id)
      const teamAuthors = store.state.categories.categories.teamAuthors
        .filter(user => user.id !== e.user.id)

      store.commit('updatedCategories', {
        teamAuthors,
        teamUsers,
      })
    })
  },

  listenUserRemovedFromTeam() {
    this.userChannel.listen('Team.DeleteUser', (e) => {
      store.dispatch('deleteUserTeam', { teamId: e.team.id })
    })
  },

  listenUserChannel() {
    this.userChannel.notification((e) => {
      store.commit('addedNotification', e)
    })
  },

  closeTeamChannel(teamId = store.state.teams.selectedTeam.id) {
    if (this.echo) {
      this.echo.leave(`team.${teamId}`)
      this.teamChannel = null
    }
  },

  closeUserListener() {
    if (this.echo) {
      this.echo.leave(`user.${store.state.account.user.id}`)
      this.userChannel = null
    }
  },

  closeTaskListener(taskId) {
    if (this.taskChannel) {
      this.echo.leave(`task.${taskId}`)
      this.taskChannel = null
    }
  },
}

export default pusher
