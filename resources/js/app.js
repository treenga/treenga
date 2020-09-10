
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */
import Vue from 'vue'
import ElementUI from 'element-ui'
import VueCookie from 'vue-cookie'
import VueScrollTo from 'vue-scrollto'
import locale from 'element-ui/lib/locale/lang/en'
import * as Sentry from '@sentry/browser';
import * as Integrations from '@sentry/integrations';
import App from './App.vue'
import store from './store'
import router from './router'
import 'trix'

Sentry.init({
  environment: process.env.MIX_APP_ENV,
  dsn: process.env.MIX_SENTRY_DSN_PUBLIC,
  integrations: [new Integrations.Vue({ Vue, attachProps: true })],
});

Vue.use(ElementUI, { locale })
Vue.use(VueCookie)
Vue.use(VueScrollTo)

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  store,
  render: h => h(App),
})
