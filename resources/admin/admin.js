
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */
import Vue from 'vue'
import ElementUI from 'element-ui'
import App from '@/admin/App.vue'
import router from '@/admin/router'
import VeeValidate from 'vee-validate'
import store from '@/admin/store'
import VueCookie from 'vue-cookie'
import locale from 'element-ui/lib/locale/lang/en'
import Toasted from 'vue-toasted'

Vue.use(Toasted)
Vue.use(VeeValidate, { fieldsBagName: 'veeFields' });
Vue.use(VueCookie);
Vue.use(ElementUI, { locale })

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  store,
  render: h => h(App),
})
