import Vue from 'vue'
import Vuex from 'vuex'

// modules
import auth from './modules/auth'
import loader from './modules/loader'
import alerts from './modules/alerts'
import users from './modules/users'
import notifications from './modules/notifications'

Vue.use(Vuex)

export default new Vuex.Store({
  modules: {    
    auth,
    loader,
    alerts,
    users,
    notifications,
  },
  strict: false,
})
