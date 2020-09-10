import api from '@/js/api'

const account = {
  state: {
    user: {},
    isLinkDialogOpened: false,
  },

  mutations: {
    userUpdated(state, payload) {
      state.user = {
        ...state.user,
        ...payload,
      }
    },

    linkDialogOpened(state) {
      state.isLinkDialogOpened = true
    },

    linkDialogClosed(state) {
      state.isLinkDialogOpened = false
    },
  },

  actions: {
    getUserData({ commit }) {
      return api.getUserData().then((response) => {
        if (response.data) {
          commit('userUpdated', response.data)
        }

        return response
      })
    },

    changeEmail({ commit }, payload) {
      return api.changeEmail(payload).then((response) => {
        if (response.data) {
          commit('userUpdated', response.data)
        }

        return response
      })
    },

    changePassword(store, payload) {
      return api.changePassword(payload)
    },

    deleteUserAccount({ dispatch }, payload) {
      return api.deleteUserAccount(payload).then((response) => {
        if (response.data) {
          dispatch('logout')
        }
      })
    },

    onAlertClose(state, payload) {
      return api.onAlertClose(payload)
    },
  },
}

export default account
