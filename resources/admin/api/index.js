import store from '@/admin/store'

const API_URL = '/api/v1/admin/'

const responseHandler = (response) => {
  const promise = response.json()
  const { ok } = response

  if (store.state.loader.isLoader) {
    store.commit('stopLoader')
  }

  if (response.status === 401) {
    store.dispatch('logout')
    return false
  }

  promise.then((resp) => {
    if (!ok) {
      if (resp.validate) {
        Object.keys(resp.validate).forEach((field) => {
          resp.validate[field].forEach((message) => {
            store.commit('alertError', {
              title: resp.message,
              text: message,
            })
          })
        })
      } else if (resp.message) {
        store.commit('alertError', {
          title: 'System Notification',
          text: resp.message,
        })
      }
    } else if (resp.message && !resp.validate) {
      store.commit('alertSuccess', {
        title: 'System Notification',
        text: resp.message,
      })
    }
  })

  return promise
}

const errorHandler = (reason) => {
  console.error('Error Handler', reason)
}

const request = (uri, data = {}, method = 'POST', isLoader = false) => {
  if (isLoader) {
    store.commit('startLoader')
  }

  const params = {
    method,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
  }

  const { admin_token } = store.state.auth

  if (admin_token) {
    params.headers.Authorization = `Bearer ${admin_token}`
  }

  if (method !== 'GET') {
    params.body = JSON.stringify(data)
  }

  return fetch(`${API_URL}${uri}`, params)
    .then(responseHandler, errorHandler)
    .catch(errorHandler)
}

const auth = {
  login(data) {
    return request('login', data, 'POST', true)
  },
}

const user = {
  getAllUser() {
    return request('users', {}, 'GET', true)
  },
  getOneUser(id) {
    return request(`users/${id}`, {}, 'GET', true)
  },
  getUserData() {
    return request('account', {}, 'GET')
  },
  addFunds(id, data) {
    return request(`users/${id}/founds`, data, 'POST')
  },
  showLink({ id, checked }) {
    return request(`users/${id}/show_link`, { value: checked }, 'POST')
  },
  changePassword(data) {
    return request('change-password', data, 'POST')
  },
  addUser(data) {
    return request('users', data, 'POST')
  },
  deleteUser(data) {
    return request('users', data, 'DELETE')
  },

}

export default {
  ...auth,
  ...user,
}
