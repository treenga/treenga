const alerts = {
    state: {
      list: [],
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
    },
  }
  
  export default alerts
  