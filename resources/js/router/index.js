import Vue from 'vue'
import Router from 'vue-router'
import store from '@/js/store'

// Layouts
import {
  Private, Public, PageNotFound,
  Auth, Settings, Categories,
} from '@/js/layouts'

// Screens
import {
  Login, Registration,
  Verify, PasswordRecovery,
  NewPassword, General, TaskCreate,
  TaskList, TaskEdit, CategoryEdit, TaskHistory,
  CategoryHistory, TaskUnsubscribe, EmailRegistration,
} from '@/js/screens'

Vue.use(Router)

const ifNotAuthenticated = (to, from, next) => {
  if (!store.state.auth.token) {
    next()
    return
  }
  next('/')
}

const ifAuthenticated = (to, from, next) => {
  if (store.state.auth.token) {
    next()
    return
  }
  next('/login')
}

const router = new Router({
  mode: 'history',
  routes: [
    {
      path: '/login',
      component: Public,
      beforeEnter: ifNotAuthenticated,
      children: [
        {
          component: Auth,
          path: '',
          children: [
            {
              path: '',
              name: 'Login',
              component: Login,
            },
          ],
        },
      ],
    },

    {
      path: '/register',
      component: Public,
      beforeEnter: ifNotAuthenticated,
      children: [
        {
          component: Auth,
          path: '',
          children: [
            {
              path: '',
              name: 'Registration',
              component: Registration,
            },
          ],
        },
      ],
    },

    {
      path: '/invite/:hash',
      component: Public,
      beforeEnter: ifNotAuthenticated,
      children: [
        {
          path: '',
          name: 'Email Registration',
          component: EmailRegistration,
        },
      ],
    },

    {
      path: '/verify/:hash',
      component: Public,
      children: [
        {
          path: '',
          component: Verify,
          name: 'Verify',
        },
      ],
    },

    {
      path: '/task/unsubscribe/:hash',
      component: Public,
      children: [
        {
          path: '',
          component: TaskUnsubscribe,
          name: 'Task Unsubscribe',
        },
      ],
    },

    {
      path: '/recovery',
      component: Public,
      beforeEnter: ifNotAuthenticated,
      children: [
        {
          path: '',
          name: 'Recovery',
          component: PasswordRecovery,
        },

        {
          path: 'new/:hash',
          name: 'New Password',
          component: NewPassword,
        },
      ],
    },

    {
      path: '/history/:teamSlug',
      beforeEnter: ifAuthenticated,
      component: {
        render(c) { return c('router-view') },
      },
      children: [
        {
          path: 'task/:taskId',
          name: 'Task History',
          component: TaskHistory,
        },

        {
          path: 'category/:categoryId',
          name: 'Category History',
          component: CategoryHistory,
        },
      ],
    },

    {
      path: '/',
      component: Private,
      beforeEnter: ifAuthenticated,
      children: [
        {
          path: 'settings',
          component: Settings,
          redirect: '/settings/general',
          children: [
            {
              path: 'general',
              name: 'General Settings',
              component: General,
            },
          ],
        },

        {
          path: ':teamSlug',
          component: Categories,
          children: [
            {
              path: '',
              name: 'Tasks',
              component: TaskList,
            },
          ],
        },
      ],
    },

    {
      path: '/team/:teamSlug/create-task',
      name: 'Create public task',
      component: TaskCreate,
    },

    {
      path: '/team/:teamSlug/category/:categoryId',
      name: 'Edit category',
      component: CategoryEdit,
    },

    {
      path: '/:teamSlug/:taskId',
      name: 'Edit public task',
      component: TaskEdit,
    },

    {
      path: '/*',
      component: Public,
      children: [
        {
          path: '',
          name: 'page not found',
          component: PageNotFound,
        },
      ],
    },
  ],
})

router.afterEach((to, from) => {
  store.commit('setCategoriesLoader', true)
  store.commit('setTasksLoader', true)
})

export default router
