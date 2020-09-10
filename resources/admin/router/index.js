import Vue from 'vue'
import Router from 'vue-router'
import store from '@/admin/store'

// Layouts
import {
  Private, Public,
} from '@/admin/layouts'

// Screens
import {
  Login,
  Dashboard,
  ChangePassword,
  AddUser,
} from '@/admin/screens'

Vue.use(Router)

const ifNotAuthenticated = (to, from, next) => {
  if (!store.state.auth.admin_token) {
    next()
    return
  }
  next('/admin')
}

const ifAuthenticated = (to, from, next) => {
  if (store.state.auth.admin_token) {
    next()
    return
  }
  next('/admin/login')
}

export default new Router({
  mode: 'history',
  routes: [
    {
      path: '/admin/login',
      component: Public,
      beforeEnter: ifNotAuthenticated,
      children: [
        {
          path: '/',
          component: Login,
        },
      ],
    },
    {
      path: '/admin',
      component: Private,
      beforeEnter: ifAuthenticated,
      children: [
        {
          path: '',
          name: 'Dashboard',
          component: Dashboard,
        },
        {
          path: 'user/:id',
          name: 'EditUser',
          component: AddUser,
        },
        {
          path: 'change-password',
          name: 'ChangePassword',
          component: ChangePassword,
        },
        {
          path: 'add-user',
          name: 'AddUser',
          component: AddUser,
        },
      ],
    },
  ],
})
