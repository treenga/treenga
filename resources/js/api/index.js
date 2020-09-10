import router from '../router'
import store from '../store'

let categoryTasksAbort = false

const AbortController = window.AbortController
const getCurrentTeam = () => store.state.teams.selectedTeam.original_slug || router.currentRoute.params.teamSlug
const getCurrentTask = () => store.state.tasks.currentTaskData.id
const formatQueryParams = (params = {}) => {
  const queryArr = []

  params.onlyTrashed = store.state.tasks.curentTasksStatus === 'closed' ? 1 : 0
  Object.keys(params).forEach((key) => {
    if (params[key]) {
      queryArr.push(`${key}=${params[key]}`)
    }
  })

  return queryArr.join('&')
}

const API_URL = '/api/v1/'

const responseHandler = (response) => {
  const promise = response.json()
  const { ok } = response

  if (store.state.loader.isLoader) {
    store.commit('stopLoader')
  }

  if (response.status === 401) {
    store.commit('loggedOut')
    return false
  }

  promise.then((resp) => {
    if (!ok) {
      if (resp.validate) {
        Object.keys(resp.validate).forEach((field) => {
          resp.validate[field].forEach((message) => {
            store.commit('alertError', {
              title: 'Error',
              text: message,
            })
          })
        })
      } else if (resp.message) {
        store.commit('alertError', {
          title: 'Error',
          text: resp.message,
        })
      } else if (resp.errors) {
        Object.keys(resp.errors).forEach((message) => {
          const type = resp.errors[message]
          const title = type.charAt(0).toUpperCase() + type.slice(1)
          store.commit('alert'.concat(title), {
            title,
            text: message,
          })
        })
      }
    } else if (resp.message && !resp.validate) {
      store.commit('alertSuccess', {
        title: 'Success',
        text: resp.message,
      })
    }
  })

  return promise
}

const errorHandler = (reason) => {
  console.log('Error Handler', reason)
}

const request = (uri, data = {}, method = 'POST', isLoader = false, withAbort = false) => {
  if (isLoader) {
    store.commit('startLoader')
  }

  const controller = new AbortController();
  const { signal } = controller;

  const params = {
    method,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    signal,
  }

  const { token } = store.state.auth

  if (token) {
    params.headers.Authorization = `Bearer ${token}`
  }

  if (method !== 'GET') {
    params.body = JSON.stringify(data)
  }

  const promise = fetch(`${API_URL}${uri}`, params)
    .then(responseHandler, errorHandler)
    .catch(errorHandler)

  return withAbort ? [promise, controller.abort.bind(controller)] : promise
}

const auth = {
  login(data) {
    return request('login', data, 'POST', true)
  },

  logout() {
    return request('logout', {}, 'GET', true)
  },

  publicTaskUnsubscribe(data) {
    return request(`tasks/unsubscribe/${data}`, {}, 'GET', true)
  },

  register(data) {
    return request('register', data, 'POST', true)
  },

  verifyEmail(hash) {
    return request(`verify/${hash}`, {}, 'GET', true)
  },

  getEmailByHash(hash) {
    return request(`invite/${hash}`, {}, 'GET', true)
  },

  registerUserByHash(data) {
    return request(`invite/${data.hash}`, data.body, 'POST', true)
  },

  recoverPassword(data) {
    return request('recovery', data, 'POST', true)
  },

  resetPassword(data) {
    return request('reset', data, 'POST', true)
  },
}

const account = {
  deleteUserAccount(data) {
    return request('account/delete', data, 'POST')
  },

  getUserData() {
    return request('account', {}, 'GET')
  },

  updateTaskOptions(data) {
    return request('account/tasksoptions', data, 'put')
  },

  changeEmail(data) {
    return request('account/changeEmail', data, 'POST')
  },

  changePassword(data) {
    return request('account/changePassword', data, 'POST')
  },

  onAlertClose(data) {
    return request(`account/alert/${data}`, {}, 'GET')
  },
}

const teams = {
  getTeamsList() {
    return request('teams', {}, 'GET')
  },

  createTeam(data) {
    return request('teams', data, 'POST')
  },

  getTeamInfo() {
    return request(`teams/${getCurrentTeam()}`, {}, 'GET')
  },

  saveTreeState(data) {
    return request(`teams/${getCurrentTeam()}/treestate`, data, 'PUT')
  },

  saveLastTask(data) {
    return request(`teams/${getCurrentTeam()}/lasttask`, data, 'PUT')
  },

  updateTeam(data) {
    return request(`teams/${getCurrentTeam()}`, data, 'PUT')
  },

  updateUsername(data) {
    return request(`teams/${getCurrentTeam()}/name`, data, 'PUT')
  },

  getTeamAutocompleteUsers(teamId) {
    return request(`teams/${teamId}/autocomplite`, {}, 'GET')
  },

  addUserToTeam(data) {
    return request(`teams/${getCurrentTeam()}/user`, data, 'POST')
  },

  deleteUserFromTeam(userId) {
    return request(`teams/${getCurrentTeam()}/user/${userId}`, {}, 'DELETE')
  },

  deleteTeam(data) {
    return request(`teams/${data.teamId}`, {}, 'DELETE')
  },
}

const categories = {
  getCategoryInfo() {
    const catId = router.currentRoute.params.categoryId
    return request(`teams/${getCurrentTeam()}/cats/${catId}`, {}, 'GET')
  },

  updateCategoryName(data) {
    return request(`teams/${getCurrentTeam()}/cats/${data.categoryId}/name`, { name: data.name }, 'PUT')
  },

  setCategoryDescription(data) {
    return request(`teams/${getCurrentTeam()}/cats/${data.categoryId}/desc`, data.data, 'POST')
  },

  updateCategoryDescription(data) {
    return request(`teams/${getCurrentTeam()}/cats/${data.categoryId}/desc`, data.data, 'PUT')
  },

  createCategoryComment(data) {
    return request(`teams/${getCurrentTeam()}/cats/${data.categoryId}/comments`, data.data, 'POST')
  },

  getCategoryHistory(data) {
    return request(`teams/${getCurrentTeam()}/cats/${data.categoryId}/history`, {}, 'GET')
  },

  revertCategoryHistory(data) {
    return request(`teams/${getCurrentTeam()}/cats/${data.categoryId}/history/${data.historyId}`, {}, 'PUT')
  },

  deleteCategory(data) {
    return request(`teams/${getCurrentTeam()}/cats/${data.categoryId}`, {}, 'DELETE')
  },

  getTeamCategories() {
    return request(`teams/${getCurrentTeam()}/info`, {}, 'GET')
  },

  getTeamCategoriesShortInfo() {
    return request(`teams/${getCurrentTeam()}/shortinfo`, {}, 'GET')
  },

  createPublicCategory(data) {
    return request(`teams/${getCurrentTeam()}/cats`, data, 'POST')
  },

  moveCategory(data) {
    return request(`teams/${getCurrentTeam()}/cats/${data.categoryId}/move`, data.data, 'PUT')
  },

  subscribeUserToCategory(data) {
    return request(`teams/${getCurrentTeam()}/cats/${data.categoryId}/subscribe`, {}, 'GET')
  },

  unsubscribeUserFromCategory(data) {
    return request(`teams/${getCurrentTeam()}/cats/${data.categoryId}/unsubscribe`, {}, 'GET')
  },
}

const tasks = {
  saveDraft(data) {
    return request(`teams/${getCurrentTeam()}/tasks/draft`, data.data, 'POST')
  },

  saveTempVersion(data) {
    return request(`teams/${getCurrentTeam()}/tasks/${data.taskId}/autosave`, data.data, 'POST')
  },

  removeTempVersion(data) {
    return request(`teams/${getCurrentTeam()}/tasks/${data.taskId}/autosave`, {}, 'DELETE')
  },

  restoreTempVersion(data) {
    return request(`teams/${getCurrentTeam()}/tasks/${data.taskId}/autosave/restore`, {}, 'GET')
  },

  getNotifications(data) {
    const query = formatQueryParams(data.params)
    return request(`teams/${getCurrentTeam()}/tasks/notifications?${query}`, {}, 'GET')
  },

  getDrafts(data) {
    const query = formatQueryParams(data.params)
    return request(`teams/${getCurrentTeam()}/tasks/drafts?${query}`, {}, 'GET')
  },

  getRecently(data) {
    const query = formatQueryParams(data.params)
    return request(`teams/${getCurrentTeam()}/tasks/lastviewed?${query}`, {}, 'GET')
  },

  updateCommentsState(data) {
    return request(`teams/${getCurrentTeam()}/tasks/${getCurrentTask()}/commentsstate`, data, 'PUT')
  },

  saveFilterState(data) {
    return request(`teams/${getCurrentTeam()}/filter`, data, 'POST')
  },

  getMassAttributes(data) {
    return request(`teams/${getCurrentTeam()}/tasks/attributes`, data, 'POST')
  },

  setMassAttributes(data) {
    return request(`teams/${getCurrentTeam()}/tasks/mass`, data, 'POST')
  },

  massRestoreTasks(data) {
    return request(`teams/${getCurrentTeam()}/tasks/restore`, data, 'POST')
  },

  massDeleteTasks(data) {
    return request(`teams/${getCurrentTeam()}/tasks/delete`, data, 'POST')
  },

  getUnsortedTasks(data) {
    const query = formatQueryParams(data.params)
    return request(`teams/${getCurrentTeam()}/tasks/unsorted?${query}`, {}, 'GET')
  },

  deleteDraft(taskId) {
    return request(`teams/${getCurrentTeam()}/tasks/${taskId}/force`, {}, 'DELETE')
  },

  createPublicTask(data) {
    return request(`teams/${getCurrentTeam()}/tasks`, data, 'POST')
  },

  getCategoryTasks(data) {
    if (categoryTasksAbort) {
      categoryTasksAbort()
    }

    const query = formatQueryParams(data.params)
    const [promise, abort] = request(`teams/${getCurrentTeam()}/cats/${data.categoryId}/tasks?${query}`, {}, 'GET', false, true)
    categoryTasksAbort = abort
    return promise
  },

  getAllTasks(data = {}) {
    const query = formatQueryParams(data.params)
    return request(`teams/${getCurrentTeam()}/tasks?${query}`, {}, 'GET')
  },

  getPublicTasks(data = {}) {
    const query = formatQueryParams(data.params)
    return request(`teams/${getCurrentTeam()}/tasks?${query}`, {}, 'GET')
  },

  getAssignedTasks(data = {}) {
    const query = formatQueryParams(data.params)
    return request(`teams/${getCurrentTeam()}/tasks/assigned?${query}`, {}, 'GET')
  },

  getUnassignedTasks(data = {}) {
    const query = formatQueryParams(data.params)
    return request(`teams/${getCurrentTeam()}/tasks/unassigned?${query}`, {}, 'GET')
  },

  getTasksByAssignee(data) {
    const query = formatQueryParams(data.params)
    return request(`teams/${getCurrentTeam()}/tasks/byuser/${data.userId}?${query}`, {}, 'GET')
  },

  getTasksByAuthor(data) {
    const query = formatQueryParams(data.params)
    return request(`teams/${getCurrentTeam()}/tasks/byauthor/${data.userId}?${query}`, {}, 'GET')
  },

  getTaskInfo(taskId) {
    return request(`teams/${getCurrentTeam()}/tasks/${taskId}`, {}, 'GET')
  },

  getTeamTaskInfo() {
    return request(`teams/${getCurrentTeam()}/shortinfo`, {}, 'GET')
  },

  updateTask(data) {
    return request(`teams/${getCurrentTeam()}/tasks/${data.taskId}`, data.data, 'PUT')
  },

  createTaskComment(data) {
    return request(`teams/${getCurrentTeam()}/tasks/${data.taskId}/comments`, data.data, 'POST')
  },

  subscribeUser(taskId) {
    return request(`teams/${getCurrentTeam()}/tasks/${taskId}/subscribe`, {}, 'GET')
  },

  unsubscribeUser(taskId) {
    return request(`teams/${getCurrentTeam()}/tasks/${taskId}/unsubscribe`, {}, 'GET')
  },

  getTaskAttributes(taskId) {
    return request(`teams/${getCurrentTeam()}/tasks/${taskId}/attributes`, {}, 'GET')
  },

  setTaskAttributes(data) {
    return request(`teams/${getCurrentTeam()}/tasks/${data.taskId}/attributes`, data.data, 'PUT')
  },

  closeTask(taskId) {
    return request(`teams/${getCurrentTeam()}/tasks/${taskId}`, {}, 'DELETE')
  },

  openTask(taskId) {
    return request(`teams/${getCurrentTeam()}/tasks/${taskId}/restore`, {}, 'GET')
  },

  getTaskHistory(taskId) {
    return request(`teams/${getCurrentTeam()}/tasks/${taskId}/history`, {}, 'GET')
  },

  revertTaskHistory(data) {
    return request(`teams/${getCurrentTeam()}/tasks/${data.taskId}/history/${data.historyId}`, {}, 'PUT')
  },

  searchTasks(data) {
    return request(`teams/${getCurrentTeam()}/tasks/search`, data, 'POST')
  },
}

export default {
  ...auth,
  ...account,
  ...teams,
  ...tasks,
  ...categories,
}
