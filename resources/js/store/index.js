import Vue from 'vue'
import Vuex from 'vuex'

// modules
import alerts from './modules/alerts'
import auth from './modules/auth'
import loader from './modules/loader'
import account from './modules/account'
import teams from './modules/teams'
import tasks from './modules/tasks'
import categories from './modules/categories'
import notifications from './modules/notifications'

Vue.use(Vuex)

export default new Vuex.Store({
  modules: {
    account,
    auth,
    alerts,
    loader,
    teams,
    tasks,
    categories,
    notifications,
  },
  strict: false,
})
