const loader = {
  state: {
    isLoader: false,
  },

  mutations: {
    startLoader(state) {
      state.isLoader = true
    },
    stopLoader(state) {
      state.isLoader = false
    },
  },
}

export default loader
