import Vue from 'vue'
import api from '@/admin/api'
import VueCookie from 'vue-cookie'
import router from '@/admin/router'
import { resolve } from 'path';

const auth = {
  state: {
    admin_token: VueCookie.get('admin_token') || null,
    account: {}
  },

  getters: {
    admin_token: state => state.admin_token,
    account: state => state.account
  },

  mutations: {
    loggedIn(state, payload) {
      state.admin_token = payload.admin_token
    },

    loggedOut(state) {
      state.admin_token = null
    },

    userUpdated(state, payload) {
      state.account = payload.account
    },
  },

  actions: {    
    login({ commit }, payload) {
      return api.login(payload).then((response) => {
        if (response.data) {
          Vue.cookie.set('admin_token', response.data.access_token, { expires: '1Y' })

          commit('loggedIn', {
            admin_token: response.data.access_token,
          })

          return new Promise((resolve, reject) => {
            return resolve();
          });
        }

        return response
      })
    },

    getUserData({ commit }) {
      return api.getUserData().then((response) => {
        if (response.data) {
          commit('userUpdated', {
            account: response.data
          })
        }
      });
    },

    logout({ commit }) {
      Vue.cookie.delete('admin_token')
      commit('loggedOut')
      router.push('/admin/login')
    },  
  },
}

export default auth
