import api from '@/js/api'
import globalStore from '..'

const categories = {
  state: {
    categories: {},
    selectedCategory: {},
    checkedCategories: [],
    checkedAssignee: [],
    categoriesLoader: true,
  },

  mutations: {
    categorySelected(state, payload) {
      state.selectedCategory = payload
    },

    updatedCategories(state, payload) {
      state.categories = { ...state.categories, ...payload }
    },

    clearCategories(state) {
      state.categories = {}
    },

    categoryChecked(state, payload) {
      state.checkedCategories.push(payload)
    },

    categoryUnchecked(state, payload) {
      const index = state.checkedCategories.indexOf(payload)
      state.checkedCategories.splice(index, 1)
    },

    assigneeChecked(state, payload) {
      state.checkedAssignee.push(payload)
    },

    assigneeUnchecked(state, payload) {
      const index = state.checkedAssignee.indexOf(payload)
      state.checkedAssignee.splice(index, 1)
    },

    allCategoriesUnchecked(state) {
      state.checkedCategories = []
      state.checkedAssignee = []
    },

    changedCatsUsername(state, payload) {
      if (state.categories.teamUsers) {
        state.categories.teamUsers = state.categories.teamUsers.map((user) => {
          if (user.id === payload.user_id) {
            user.teamusername = payload.username
          }

          return user
        })
      }

      if (state.categories.teamAuthors) {
        state.categories.teamAuthors = state.categories.teamAuthors.map((user) => {
          if (user.id === payload.user_id) {
            user.teamusername = payload.username
          }

          return user
        })
      }
    },

    setCategoriesLoader(state, payload) {
      state.categoriesLoader = payload
    },
  },

  actions: {
    getTeamCategories({ commit, state, rootState }, payload) {
      if (!payload && !rootState.notifications.isNewUserNotification) {
        commit('clearCategories')
        commit('setCategoriesLoader', true)
      }
      return api.getTeamCategories()
    },

    getTeamCategoriesShortInfo(store, payload) {
      return api.getTeamCategoriesShortInfo(payload)
    },

    updateCategories({ commit }, payload) {
      return new Promise((resolve) => {
        commit('updatedCategories', payload)
        resolve()
      })
    },

    getCategoryInfo() {
      return api.getCategoryInfo()
    },

    subscribeUserToCategory(store, payload) {
      return api.subscribeUserToCategory(payload)
    },

    unsubscribeUserFromCategory(store, payload) {
      return api.unsubscribeUserFromCategory(payload)
    },

    createPrivateCategory(store, payload) {
      return api.createPrivateCategory(payload)
    },

    createPublicCategory(store, payload) {
      return api.createPublicCategory(payload)
    },

    moveCategory(store, payload) {
      return api.moveCategory(payload)
    },

    updateCategoryName(store, payload) {
      return api.updateCategoryName(payload)
    },

    setCategoryDescription(store, payload) {
      return api.setCategoryDescription(payload)
    },

    updateCategoryDescription(store, payload) {
      return api.updateCategoryDescription(payload)
    },

    createCategoryComment(store, payload) {
      return api.createCategoryComment(payload)
    },

    getCategoryHistory(store, payload) {
      return api.getCategoryHistory(payload)
    },

    revertCategoryHistory(store, payload) {
      return api.revertCategoryHistory(payload)
    },

    deleteCategory(store, payload) {
      return api.deleteCategory(payload)
    },
  },
}

export default categories
