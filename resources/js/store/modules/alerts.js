let timeout = null;
const alerts = {
  state: {
    list: [],
    fakeNotification: {
      value: false,
      onUndo: () => {},
    },
  },
  mutations: {
    alertAdd(state, payload) {
      state.list.push(payload)
    },
    alertError(state, payload) {
      payload.type = 'error'
      state.list.push(payload)
    },
    alertWarning(state, payload) {
      payload.type = 'warning'
      state.list.push(payload)
    },
    alertSuccess(state, payload) {
      payload.type = 'success'
      state.list.push(payload)
    },
    alertInfo(state, payload) {
      payload.type = 'info'
      state.list.push(payload)
    },
    alertClear(state) {
      state.list = []
    },
    setFakeNotification(state, payload) {
      state.fakeNotification = payload
    },
  },
  actions: {
    toggleFakeNotification({ commit }, payload) {
      commit('setFakeNotification', payload)

      if (payload.opened) {
        if (timeout) {
          clearTimeout(timeout)
        }
        timeout = setTimeout(() => {
          commit('setFakeNotification', {
            opened: true,
            closed: true,
            taskId: payload.taskId,
            taskTeamId: payload.taskTeamId,
            isTaskPrivate: payload.isTaskPrivate,
            isDraft: payload.isDraft,
            tempDraftData: payload.tempDraftData,
            onUndo: () => {},
          })
        }, 10000)
      }
    },
  }
}

export default alerts
