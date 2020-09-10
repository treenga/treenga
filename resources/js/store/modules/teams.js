import api from '@/js/api'
import router from '@/js/router'
import globalStore from '..'
import Vue from 'vue'

const initialTreeState = {
  main: {
    public_tree: [0],
    private_tree: [0],
    authors_tree: [0],
    assignees_tree: [0],
    system_tree: [0],
  },
  edit: {
    public_tree: [0],
    private_tree: [0],
    assignees_tree: [0],
  },
  create: {
    public_tree: [0],
    private_tree: [0],
    assignees_tree: [0],
  },
  filter: {
    public_tree: [0],
    private_tree: [0],
    authors_tree: [0],
    assignees_tree: [0],
    due_date_tree: ['main title'],
  },
  sort_by: '',
}

const teams = {
  state: {
    teams: {},
    selectedTeam: {},
    teamUsers: [],
    autocompleteUsers: [],
    lastState: {},
    treeState: JSON.parse(JSON.stringify(initialTreeState)),
    isAddCategoryDialogOpened: false,
    isCreateDialogOpened: false,
    isEditDialogOpened: false,
    isProfileDialogOpened: false,
    isTeamLoader: false,
    isAccess: null,
    lastTask: null,
  },

  getters: {
    isPrivateTeam(state) {
      return state.selectedTeam.private
    },

    lastTaskId(state) {
      return state.treeState.last_task || null
    },
  },

  mutations: {
    accessForbidden(state) {
      state.isAccess = false
    },

    accessGranted(state) {
      state.isAccess = true
    },

    teamSelected(state, payload) {
      state.selectedTeam = payload
      state.isAccess = true
    },

    updatedTreeState(state, payload) {
      state.treeState = payload
      if (!state.lastTask) {
        state.lastTask = payload.last_task
      }
    },

    setLastTask(state, payload) {
      state.lastTask = payload
    },

    updatedLastState(state, payload) {
      state.lastState = payload
    },

    notificationAddedToTeam(state, payload) {
      const stateTeams = Object.keys(state.teams)

      stateTeams.forEach((team) => {
        for (let index = state.teams[team].length - 1; index >= 0; index -= 1) {
          if (state.teams[team][index].id === payload) {
            state.teams[team][index].user_notifications_count += 1
            break
          }
        }
      })
    },

    teamLoaderEnabled(state) {
      state.isTeamLoader = true
    },

    teamLoaderDisabled(state) {
      state.isTeamLoader = false
    },

    userDeletedFromTeam(state, payload) {
      Object.keys(state.teams).forEach((key) => {
        state.teams[key] = state.teams[key].filter(team => team.id !== payload.teamId)
      })
    },

    createTeamDialogOpened(state) {
      state.isCreateDialogOpened = true
    },

    createTeamDialogClosed(state) {
      state.isCreateDialogOpened = false
    },

    profileDialogOpened(state) {
      state.isProfileDialogOpened = true
    },

    profileDialogClosed(state) {
      state.isProfileDialogOpened = false
    },

    addCategoryDialogOpened(state) {
      state.isAddCategoryDialogOpened = true
    },

    addCategoryDialogClosed(state) {
      state.isAddCategoryDialogOpened = false
    },

    editTeamDialogOpened(state) {
      state.isEditDialogOpened = true
    },

    editTeamDialogClosed(state) {
      state.isEditDialogOpened = false
    },

    teamRemoved(state, payload) {
      Object.keys(state.teams).forEach((key) => {
        state.teams[key] = state.teams[key].filter(team => team.id !== payload)
      })
    },

    teamAdded(state, payload) {
      const filtered = state.teams.shared.filter((team => team.id === payload.id))
      if (!filtered.length) {
        state.teams.shared.push(payload)
      }
    },

    updatedTeams(state, payload) {
      payload.userteams.sort((a, b) => {
        if (a.name < b.name) { return -1 }
        if (a.name > b.name) { return 1 }
        return 0
      })
      state.teams = payload
    },

    updatedTeam(state, payload) {
      const index = state.teams.userteams.map(e => e.id).indexOf(payload.id)
      state.teams.userteams[index].name = payload.name
      state.teams.userteams[index].slug = payload.slug
      state.teams.userteams[index].username = payload.auth_username
    },

    updatedTeamUsers(state, payload) {
      const newArr = payload.map((user) => {
        user.value = user.username
        user.key = user.email
        return user
      })

      newArr.sort((a, b) => a.id - b.id)

      state.teamUsers = newArr
    },

    updatedAutocompleteUsers(state, payload) {
      state.autocompleteUsers = payload
    },

    changedUsername(state, payload) {
      if (state.selectedTeam.id !== payload.id) return

      state.teamUsers = state.teamUsers.map((user) => {
        if (user.id === payload.user_id) {
          user.username = payload.username
        }

        return user
      })
    },
  },

  actions: {
    updateUsername(store, payload) {
      return api.updateUsername(payload)
    },

    saveTreeState({ commit }, payload) {
      commit('updatedTreeState', payload)
      return api.saveTreeState(payload)
    },

    saveLastTask({ commit }, payload) {
      commit('setLastTask', payload.task_id);
      return api.saveLastTask(payload)
    },

    getTeamsList({ commit }) {
      return api.getTeamsList().then((response) => {
        if (response.data) {
          commit('updatedTeams', response.data)
        }

        return JSON.parse(JSON.stringify(response))
      })
    },

    deleteUserTeam({ state, commit }, payload) {
      commit('userDeletedFromTeam', payload)

      if (payload.teamId === state.selectedTeam.id) {
        if (!state.teams.userteams.length) {
          commit('teamSelected', {})
          router.push('/')
          return
        }

        commit('teamSelected', state.teams.userteams[0])
        router.push(`/${state.teams.userteams[0].slug}`)
      }
    },

    deleteTeam({ commit }, payload) {
      commit('teamLoaderEnabled')
      return api.deleteTeam(payload).then((response) => {
        commit('teamLoaderDisabled')
        return response
      })
    },

    createTeam(store, payload) {
      return api.createTeam(payload)
    },

    updateTeam({ commit }, payload) {
      return api.updateTeam(payload).then((responce) => {
        if (responce.data) {
          commit('updatedTeam', responce.data)
        }

        return JSON.parse(JSON.stringify(responce))
      })
    },

    getTeamAutocompleteUsers({ commit }, payload) {
      return api.getTeamAutocompleteUsers(payload).then((responce) => {
        if (responce.data) {
          commit('updatedAutocompleteUsers', responce.data)
        }

        return responce
      })
    },

    addUserToTeam({ commit }, payload) {
      commit('teamLoaderEnabled')
      return api.addUserToTeam(payload).then((response) => {
        commit('teamLoaderDisabled')
        if (response.data) {
          commit('updatedTeamUsers', response.data.users)
        }

        return response
      })
    },

    deleteUserFromTeam({ commit }, payload) {
      commit('teamLoaderEnabled')
      return api.deleteUserFromTeam(payload).then((response) => {
        commit('teamLoaderDisabled')
        if (response.data) {
          commit('updatedTeamUsers', response.data.users)
        }

        return response
      })
    },

    getTeamInfo({ commit }) {
      return api.getTeamInfo().then((response) => {
        if (response.data) {
          commit('updatedTeamUsers', response.data.users)

          if (response.data.auth_treestate) {
            if (!response.data.auth_treestate.create) {
              response.data.auth_treestate.create = { ...initialTreeState.create }
            }

            if (!response.data.auth_treestate.filter) {
              response.data.auth_treestate.filter = { ...initialTreeState.filter }
            }

            commit('updatedTreeState', response.data.auth_treestate)
            globalStore.commit('updatedSortedTasks', response.data.auth_treestate.sort_by || 'Newest')
            globalStore.commit('updatedSortedNotificationTasks', response.data.auth_treestate.sort_notifications_by || 'Early')
          } else {
            commit('updatedTreeState', initialTreeState)
          }

          if (response.data.auth_current_state) {
            commit('updatedLastState', response.data.auth_current_state)
          }

          if (response.data.auth_filter) {
            globalStore.commit('clearCheckedPublicFilters')
            globalStore.commit('updatedCheckedPublicFilters', response.data.auth_filter.public)
            globalStore.commit('updatedCheckedPrivateFilters', response.data.auth_filter.private)
            globalStore.commit('updatedFilterText', response.data.auth_filter.filterText)
          }
        }

        return JSON.parse(JSON.stringify(response))
      })
    },
  },
}

export default teams
