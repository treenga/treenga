import api from '@/admin/api'

const users = {
  state: {
    users: [],
    user: {},
  },

  getters: {
    users: state => state.users,
    user: state => state.user,
  },

  mutations: {
    setAllUsers(state, payload) {
      state.users = payload.users
    },
    setOneUsers(state, payload) {
      state.user = payload.user
    },
  },

  actions: {
    getAllUsers({ commit }, payload) {
      return api.getAllUser(payload).then((response) => {
        commit('setAllUsers', {
          users: response.data,
        });
      })
    },
    getOneUser({ commit }, payload) {
      return api.getOneUser(payload).then((response) => {
        commit('setOneUsers', {
          user: response.data,
        });

        return response
      })
    },
    addFunds(store, payload) {
      return api.addFunds(payload.id, payload);
    },
    showLink(store, payload) {
      return api.showLink(payload)
    },
    changePassword(store, payload) {
      return api.changePassword(payload)
    },
    addUser(store, payload) {
      return api.addUser(payload)
    },
    deleteUser(store, payload) {
      return api.deleteUser(payload)
    },
  },
}

export default users
