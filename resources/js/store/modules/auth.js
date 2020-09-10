import Vue from 'vue'
import api from '@/js/api'
import VueCookie from 'vue-cookie'
import router from '@/js/router'

const auth = {
  state: {
    token: VueCookie.get('token') || null,
    showSuccessRegisterMessage: false,
    showSuccessRecoveryMessage: false,
  },

  getters: {
    token: state => state.token,
  },

  mutations: {
    loggedIn(state, payload) {
      state.token = payload.token
    },

    loggedOut(state) {
      state.token = null
      Vue.cookie.delete('token')
      if (router.history.current.path != '/login') {
        router.push('/login')
      }
    },

    registerSuccess(state, payload) {
      state.showSuccessRegisterMessage = payload
    },

    recoverySuccess(state, payload) {
      state.showSuccessRecoveryMessage = payload
    },
  },

  actions: {
    login({ commit }, payload) {
      return api.login(payload).then((response) => {
        if (response.data) {
          Vue.cookie.set('token', response.data.access_token, { expires: '1Y' })

          commit('loggedIn', {
            token: response.data.access_token,
          })

          router.push('/')
        }

        return response
      })
    },

    logout({ commit }) {
      return api.logout().then((response) => {
        commit('loggedOut')
      })
    },

    register({ commit }, payload) {
      return api.register(payload).then((response) => {
        if (response.data) {
          commit('registerSuccess', true)
        }

        return response
      })
    },

    getEmailByHash(state, payload) {
      return api.getEmailByHash(payload)
    },

    registerUserByHash(state, payload) {
      return api.registerUserByHash(payload)
    },

    verifyEmail({ commit }, payload) {
      return api.verifyEmail(payload).then((response) => {
        if (response.data) {
          Vue.cookie.set('token', response.data.access_token, { expires: '1Y' })
          commit('loggedIn', {
            token: response.data.access_token,
          })
          router.push('/')
        }
        return response
      })
    },

    publicTaskUnsubscribe(state, payload) {
      return api.publicTaskUnsubscribe(payload).then((response) => {
        router.push('/login')

        return response
      })
    },

    recoverPassword({ commit }, payload) {
      return api.recoverPassword(payload).then((response) => {
        if (response.data) {
          commit('recoverySuccess', true)
        }

        return response
      })
    },

    resetPassword(state, payload) {
      return api.resetPassword(payload)
    },

    getSocialRedirectLink(state, payload) {
      return api.getSocialRedirectLink({ provider: payload })
    },

    socialLogin({ commit }, payload) {
      return api.socialLogin(payload).then((response) => {
        if (response.data) {
          Vue.cookie.set('token', response.data.access_token, { expires: '1Y' })

          commit('loggedIn', {
            token: response.data.access_token,
          })
        }

        router.push('/')
        return response
      })
    },
  },
}

export default auth
