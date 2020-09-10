const notifications = {
    state: {
      lastUserNotification: {},
    },
    mutations: {
      addedNotification(state, payload) {
        state.lastUserNotification = payload
      },
    },
  }
  
  export default notifications
  