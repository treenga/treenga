const notifications = {
  state: {
    lastUserNotification: {},
    isNewUserNotification: false,
  },
  mutations: {
    addedNotification(state, payload) {
      state.lastUserNotification = payload
      this.commit('markNotificationNew')
    },
    markNotificationNew(state) {
      state.isNewUserNotification = true
    },
    markNotificationOld(state) {
      state.isNewUserNotification = false
    },
  },
}

export default notifications
