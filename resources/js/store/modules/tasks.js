/* eslint-disable no-unused-vars */
/* eslint-disable no-use-before-define */
import api from '@/js/api'
import globalStore from '..'

const dueDatesEmpty = {
  nextMonth: 0,
  nextWeek: 0,
  no: 0,
  overdue: 0,
  thisMonth: 0,
  thisWeek: 0,
  today: 0,
  tommorow: 0,
}

const checkedPublicFiltersData = {
  categories: [],
  users: [],
  authors: [],
  due_date_type: 'daterange',
  due_date: null,
  invert: false,
  combine: 'and',
}

const checkedPrivateFiltersData = {
  categories: [],
  due_date_type: null,
  due_date: null,
  invert: false,
  combine: 'and',
}

const filterCurrentTasks = (tasksList, filterData) => {
  let filteredTasks = null

  filteredTasks = tasksList.filter((task) => {
    const checkArr = []
    const _filter = {
      isCats: true,
      isAssignees: true,
      isDraft: true,
      isUnsorted: true,
      isUnassigned: true,
      isDate: true,
      isAuthors: true,
    }

    if (filterData.is_draft) {
      _filter.isDraft = task.is_draft
      checkArr.push('isDraft')
    }

    if (filterData.is_unsorted) {
      _filter.isUnsorted = !task.categories_id || task.categories_id.length === 0
      checkArr.push('isUnsorted')
    }

    if (filterData.is_unassigned) {
      _filter.isUnassigned = !task.usersIds || task.usersIds.length === 0
      checkArr.push('isUnassigned')
    }

    if (filterData.categories && filterData.categories.length) {
      _filter.isCats = filterArrayWithArray(
        task.categories_id,
        filterData.categories,
        filterData.combine,
      )
      checkArr.push('isCats')
    }

    if (filterData.authors && filterData.authors.length) {
      _filter.isAuthors =  filterArrayWithArray(
        [task.author_id],
        filterData.authors,
        filterData.combine,
      )
        
      checkArr.push('isAuthors')
    }

    if (filterData.users && filterData.users.length) {
      _filter.isAssignees = filterArrayWithArray(
        task.usersIds,
        filterData.users,
        filterData.combine,
      )
      checkArr.push('isAssignees')
    }

    if (filterData.due_date_type === 'daterange' && filterData.due_date) {
      _filter.isDate = isDueDateInRange(task, filterData.due_date)
      checkArr.push('isDate')
    } else if (filterData.due_date_type !== 'daterange') {
      _filter.isDate = task.due_date_filter === filterData.due_date_type
      checkArr.push('isDate')
    }

    if (checkArr && !checkArr.length) {
      return tasksList
    }

    return checkArr.reduce((acc, val) => acc && _filter[val], true)
  })

  if (filterData.invert) {
    filteredTasks = tasksList.filter(task => filteredTasks.indexOf(task) === -1)
  }

  return filteredTasks
}

const isDueDateInRange = (task, range) => {
  const taskDue = new Date(task.due_date).getTime()
  const start = new Date(range[0]).getTime()
  const end = new Date(range[1]).getTime()
  return taskDue >= start && taskDue <= end
}

const getAllowedCounters = (type, filterData) => {
  const filter = filterData.map(el => `${el}`)
  let counters = Object.keys(tasks.state.counters[type])
  counters = filter
    .filter(el => tasks.state.counters[type][el] && tasks.state.counters[type][el] > 0)
  return counters
}

const checkTasksLength = tasks => {
  if (tasks.currentTasks.tasks && tasks.childTasks.tasks) {
    return tasks.currentTasks.tasks.length === 0 && tasks.childTasks.tasks.length === 0
  }

  return false
}

const filterArrayWithArray = (initial, filterArr, type) => {
  if (type === 'or') {
    return initial.filter(el => filterArr.indexOf(el) > -1).length > 0
  }

  if (initial.length < filterArr.length) {
    return false
  }

  const check = []

  filterArr.forEach((el) => {
    check.push(initial.indexOf(el) > -1)
  })

  return check.reduce((acc, val) => acc && val, true)
}

const getTaskHelpersArrays = (tasks) => {
  const obj = {}
  const keys = Object.keys(tasks)

  keys.forEach((key) => {
    obj[key] = {}

    if (tasks[key].tasks) {
      tasks[key].tasks.forEach((task) => {
        obj[key][task.id] = task
      })
    }
  })

  return obj
}

const countTaskAssignees = (assignees, obj) => {  
  if (!assignees.length) {
    obj.unassigned += 1
  } else {
    assignees.forEach((id) => {
      obj[id] = obj[id] === undefined
        ? 1
        : obj[id] + 1
    })
  }

  return obj
}

const isCheckedTasks = (tasks) => {
  const keys = Object.keys(tasks)
  return keys.length > 0
}

const getArrayDifference = (oldArr, newArr) => ({
  added: newArr.filter(v => !oldArr.includes(v)),
  removed: oldArr.filter(v => !newArr.includes(v)),
})

const updateCouterArray = (oldCounters, diff) => {
  const counters = { ...oldCounters }

  if (diff.removed.length) {
    diff.removed.forEach((cat) => {
      if (counters[cat] > 1) {
        counters[cat] -= 1
      } else {
        delete counters[cat]
      }
    })
  }

  if (diff.added.length) {
    diff.added.forEach((cat) => {
      if (counters[cat]) {
        counters[cat] += 1
      } else {
        counters[cat] = 1
      }
    })
  }

  return counters
}

const getFilterCounters = (tasks) => {
  const arrs = Object.values(tasks)
  const obj = {
    categories: {
      drafts: 0,
      unsorted: 0,
    },
    assignees: {
      unassigned: 0,
    },
    due_dates: { ...dueDatesEmpty },
    authors: {},
  }

  arrs.forEach((arr) => {
    if (arr.tasks) {
      arr.tasks.forEach((task) => {
        const cats = task.categories_id
        obj.assignees = countTaskAssignees(task.usersIds, obj.assignees)
        obj.due_dates[task.due_date_filter || 'no'] += 1
        obj.authors[task.author_id] = obj.authors[task.author_id] === undefined
          ? 1
          : obj.authors[task.author_id] + 1

        // when task is draft, dont count as unsorted
        if (task.is_draft) {
          obj.categories.drafts += 1
        } else if (!cats.length) {
          obj.categories.unsorted += 1
        }

        if (cats.length) {
          cats.forEach((catId) => {
            obj.categories[catId] = obj.categories[catId] === undefined
              ? 1
              : obj.categories[catId] + 1
          })
        }
      })
    }
  })

  return obj
}

const sortType = {
  'sortByLastViewed': (a, b) => new Date(b.updated_lastviewed_at) - new Date(a.updated_lastviewed_at),
  'sortByEarly': (a, b) => new Date(a.updated_notification_at) - new Date(b.updated_notification_at),
  'sortByRecently': (a, b) => new Date(b.updated_notification_at) - new Date(a.updated_notification_at),
  'sortByOldest': (a, b) => new Date(a.updated_at) - new Date(b.updated_at),
  'sortByNewest': (a, b) => new Date(b.updated_at) - new Date(a.updated_at),
  'sortByDuedate': (a, b) => {
    if (!b.due_date) {
      return -1
    }
    return new Date(b.due_date) - new Date(a.due_date)
  },
}

/* --------------------------- State --------------------------------  */

const tasks = {
  state: {
    isEdit: {},
    currentPage: 1,
	  currentFilterPage: 1,
    tempDraftData: {
      name: '',
      body: '',
    },
    tasks: {
      currentTasks: { tasks: [] },
      childTasks: { tasks: [] },
    },
    // _tasks has same data as tasks, but objects, with id of task as key
    // when _tasks is updated tasks updates as well
    _tasks: {
      currentTasks: {},
      childTasks: {},
    },
    tasksWithChanges: [],
    filteredTasks: [],
    checkedFilters: {
      public: checkedPublicFiltersData,
      private: checkedPrivateFiltersData,
      filterText: '',
    },
    isFilterEnabled: false,
    isFilterHasConditions: false,
    isTasksChecked: false,
    isTasksCheckboxDisabled: false,
    sortTaskBy: 'Newest',
    sortNotificationTaskBy: 'Recently',
    checkedTasks: {},
    checkedData: {
      categories: {},
      assignees: {},
    },
    curentTasksStatus: 'opened', // status of all showed tasks
    currentTaskData: {},
    counters: {
      categories: {},
      authors: {},
      assignees: {},
      due_date: {},
    },
    tasksLoader: true,
    onlyTasksLoader: true,
    lastCategoryId: 0,
    lastEditedTaskId: false,
  },

  mutations: {
    setIsEdit(state, payload) {
      state.isEdit = payload
    },

    setLastCategoryId(state, payload) {
      state.lastCategoryId = payload
    },

    setCurrentPage(state, payload) {
      state.currentPage = payload
    },
	
	  setCurrentFilterPage(state, payload) {
      state.currentFilterPage = payload
    },

    setTempDraftData(state, payload) {
      state.tempDraftData = payload
    },

    addedTaskWithChanges(state, payload) {
      if (state.tasksWithChanges.indexOf(payload.id) === -1) {
        state.tasksWithChanges.push(payload.id)
      }
    },

    updatedCurentTasksStatus(state, payload) {
      state.curentTasksStatus = payload
    },

    updatedCurrentTaskData(state, payload) {
      state.currentTaskData = { ...state.currentTaskData, ...payload }
    },

    updateCurrentTaskComments(state, payload) {
      state.currentTaskData.commentsstate = {
        ...state.currentTaskData.commentsstate,
        ...payload,
      }
    },

    closedTask(state, payload) {
      const tasksTypes = Object.keys(state.tasks)
      tasksTypes.forEach((taskType) => {
        state.tasks[taskType].tasks = state.tasks[taskType].tasks
          .filter(task => task.id !== payload.id)
      })
    },

    openedTask(state, payload) {
      const tasksTypes = Object.keys(state.tasks)
      tasksTypes.forEach((taskType) => {
        state.tasks[taskType].tasks = state.tasks[taskType].tasks
          .filter(task => task.id !== payload.id)
      })
    },

    removedTaskWithChanges(state, payload) {
      let idIndex = -1
      state.tasksWithChanges.forEach((id, index) => {
        if (id === payload) {
          idIndex = index
        }
      })

      if (idIndex > -1) {
        state.tasksWithChanges.splice(idIndex, 1)
      }
    },

    resetTasks(state) {
      let payload = {
        childTasks: { tasks: [] },
        currentTasks: { tasks: [] }
      }

      state.tasks = payload
    },

    updatedTasks(state, payload) {
      state.tasks = payload
      state.isTasksCheckboxDisabled = checkTasksLength(payload)
      state._tasks = getTaskHelpersArrays(payload)
      state.counters = getFilterCounters(payload)
    },

    updatedTaskData(state, payload) {
      let type = null

      if (state._tasks.currentTasks[payload.id]) {
        type = 'current'
      } else if (state._tasks.childTasks[payload.id]) {
        type = 'child'
      }

      if (!type) return

      const taskData = state._tasks[`${type}Tasks`][payload.id]
      const oldCats = [...state._tasks[`${type}Tasks`][payload.id].categories_id]
      const oldAssignees = [...state._tasks[`${type}Tasks`][payload.id].usersIds]
      taskData.name = payload.name
      taskData.diff = payload.diff
      taskData.updated_at = payload.updated_at || new Date()
      taskData.due_date_filter = payload.due_date_filter
      taskData.categories_id = payload.categories_id
      taskData.usersIds = payload.usersIds
      state._tasks[`${type}Tasks`][payload.id] = taskData

      const catsDiff = getArrayDifference(oldCats, payload.categories_id)
      const assigneesDiff = getArrayDifference(oldAssignees, payload.usersIds)
      state.counters.categories = updateCouterArray(state.counters.categories, catsDiff)
      state.counters.assignees = updateCouterArray(state.counters.assignees, assigneesDiff)
    },

    resetFilteredTasks(state) {
      state.filteredTasks = []
    },

    updatedFilteredTasks(state, payload) {
      state.filteredTasks = payload
    },

    updatedCheckedPublicFilters(state, payload) {
      state.checkedFilters.public = { ...state.checkedFilters.public, ...payload }
    },

    updatedCheckedPrivateFilters(state, payload) {
      state.checkedFilters.private = { ...state.checkedFilters.private, ...payload }
    },

    updatedFilterText(state, payload) {
      state.checkedFilters.filterText = payload
    },

    clearCheckedPublicFilters(state) {
      state.checkedFilters.public = JSON.parse(JSON.stringify(checkedPublicFiltersData))
    },

    clearCheckedPrivateFilters(state) {
      state.checkedFilters.private = JSON.parse(JSON.stringify(checkedPrivateFiltersData))
    },

    enabledFilter(state) {
      state.isFilterEnabled = true
    },

    disabledFilter(state) {
      state.isFilterEnabled = false
    },

    filterConditions(state, payload) {
      state.isFilterHasConditions = payload
    },

    checkedTask(state, payload) {
      state.checkedTasks = { ...state.checkedTasks, [payload.id]: payload }
      state.isTasksChecked = isCheckedTasks(state.checkedTasks)

      // const obj = { ...state.checkedData }

      // payload.categories_id.forEach((cat) => {
      //   obj.categories[cat] = obj.categories[cat] ? obj.categories[cat] + 1 : 1
      // })

      // payload.usersIds.forEach((user) => {
      //   obj.assignees[user] = obj.assignees[user] ? obj.assignees[user] + 1 : 1
      // })

      // state.checkedData = obj
    },

    uncheckedTask(state, payload) {
      const old = { ...state.checkedTasks }

      delete old[payload]
      state.checkedTasks = old
      state.isTasksChecked = isCheckedTasks(state.checkedTasks)
    },

    uncheckedAllTasks(state) {
      state.checkedTasks = {}
      state.checkedData.categories = {}
      state.checkedData.assignees = {}
      state.isTasksChecked = false
    },

    checkedAllTasks(state) {
      state.checkedTasks = { ...state._tasks.currentTasks, ...state._tasks.childTasks }
      state.isTasksChecked = isCheckedTasks(state.checkedTasks)
    },

    checkedMassTasks(state, payload) {
      const cats = {}
      const users = {}
      payload.categoriesIds.forEach((catId) => {
        cats[catId] = cats[catId] ? cats[catId] + 1 : 1
      })
      payload.usersIds.forEach((userId) => {
        users[userId] = users[userId] ? users[userId] + 1 : 1
      })

      state.checkedData.categories = cats
      state.checkedData.assignees = users
    },

    updatedSortedTasks(state, payload) {
      state.sortTaskBy = payload
    },

    updatedSortedNotificationTasks(state, payload) {
      state.sortNotificationTaskBy = payload
    },

    sortedTasksByLastViewed(state) {
      state.tasks.currentTasks.tasks.sort(sortType.sortByLastViewed)
      state.tasks.childTasks.tasks.sort(sortType.sortByLastViewed)
      state.filteredTasks.sort(sortType.sortByLastViewed)
    },

    sortedTasksByEarly(state) {
      state.tasks.currentTasks.tasks.sort(sortType.sortByEarly)
      state.tasks.childTasks.tasks.sort(sortType.sortByEarly)
      state.filteredTasks.sort(sortType.sortByEarly)
      state.sortNotificationTaskBy = 'Early'
    },

    sortedTasksByRecently(state) {
      state.tasks.currentTasks.tasks.sort(sortType.sortByRecently)
      state.tasks.childTasks.tasks.sort(sortType.sortByRecently)
      state.filteredTasks.sort(sortType.sortByRecently)
      state.sortNotificationTaskBy = 'Recently'
    },

    sortedTasksByOldest(state) {
      state.tasks.currentTasks.tasks.sort(sortType.sortByOldest)
      state.tasks.childTasks.tasks.sort(sortType.sortByOldest)
      state.filteredTasks.sort(sortType.sortByOldest)
      state.sortTaskBy = 'Oldest'
    },

    sortedTasksByNewest(state) {
      if (state.tasks.currentTasks.tasks) {
        state.tasks.currentTasks.tasks.sort(sortType.sortByNewest)
      }

      if (state.tasks.childTasks.tasks) {
        state.tasks.childTasks.tasks.sort(sortType.sortByNewest)
      }

      if (state.filteredTasks) {
        state.filteredTasks.sort(sortType.sortByNewest)
      }
      state.sortTaskBy = 'Newest'
    },

    sortedTasksByDuedate(state) {
      state.tasks.currentTasks.tasks.sort(sortType.sortByDuedate)
      state.tasks.childTasks.tasks.sort(sortType.sortByDuedate)
      state.filteredTasks.sort(sortType.sortByDuedate)
      state.sortTaskBy = 'Duedate'
    },

    setTasksLoader(state, payload) {
      state.tasksLoader = payload
    },

    setOnlyTasksLoader(state, payload) {
      state.onlyTasksLoader = payload
    },

    setLastEditedTaskId(state, payload) {
      state.lastEditedTaskId = payload
    }
  },

  actions: {
    resetFilteredTasks({ commit }) {
      commit('resetFilteredTasks')
    },
    resetTasks({ commit }) {
      commit('resetTasks')
    },
    toggleIsEdit({ commit }, payload) {
      commit('setIsEdit', payload)
    },

    setCurrentPage({ commit }, payload) {
      commit('setCurrentPage', payload)
    },

    setCurrentFilterPage({ commit }, payload) {
      commit('setCurrentFilterPage', payload)
    },

    updateTempDraftData({ commit }, payload) {
      commit('setTempDraftData', payload)
    },

    addTaskWithChanges({ commit, state }, payload) {
      commit('addedTaskWithChanges', payload)
      commit(`sortedTasksBy${state.sortTaskBy}`)
    },

    removeTaskWithChanges({ commit, state }, payload) {
      commit('removedTaskWithChanges', payload)
      commit(`sortedTasksBy${state.sortTaskBy}`)
    },

    getMassAttributes({ state, commit }, payload) {
      state.isTasksCheckboxDisabled = true
      return api.getMassAttributes(payload).then((response) => {
        state.isTasksCheckboxDisabled = false
        if (response.data) {
          commit('checkedMassTasks', response.data)
        }
        return response
      })
    },

    setMassAttributes({ state }, payload) {
      state.isTasksCheckboxDisabled = true
      return api.setMassAttributes(payload).then((response) => {
        state.isTasksCheckboxDisabled = false
        return response
      })
    },

    massRestoreTasks({ state }, payload) {
      state.isTasksCheckboxDisabled = true
      return api.massRestoreTasks(payload).then((response) => {
        state.isTasksCheckboxDisabled = false
        return response
      })
    },

    massDeleteTasks({ state }, payload) {
      state.isTasksCheckboxDisabled = true
      return api.massDeleteTasks(payload).then((response) => {
        state.isTasksCheckboxDisabled = false
        return response
      })
    },

    searchTasks({ commit, state }, payload) {
      const current = state.tasks.currentTasks.tasks
      const child = state.tasks.childTasks.tasks
      const filtered = filterCurrentTasks([...current, ...child], payload)
      commit('updatedFilteredTasks', filtered)
    },

    saveFilterState({ state }) {
      return api.saveFilterState(state.checkedFilters)
    },

    updateTaskData({ commit }, payload) {
      commit('updatedTaskData', payload)
    },

    saveDraft(store, payload) {
      return api.saveDraft(payload)
    },

    saveTempVersion(store, payload) {
      return api.saveTempVersion(payload)
    },

    restoreTempVersion(store, payload) {
      return api.restoreTempVersion(payload)
    },

    deleteDraft(store, payload) {
      return api.deleteDraft(payload)
    },

    removeTempVersion(store, payload) {
      return api.removeTempVersion(payload)
    },

    createPublicTask(store, payload) {
      return api.createPublicTask(payload)
    },

    createPrivateTask(store, payload) {
      return api.createPrivateTask(payload)
    },

    getAllTasks({ commit, state, rootState }, payload) {
      if (!rootState.notifications.isNewUserNotification) {
        if ((payload && !payload.disableLoader) || !payload) {
          commit('setTasksLoader', true)
        } else {
          commit('setOnlyTasksLoader', true)
        }
      }
      return api.getAllTasks(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        if (response && response.data) {
          commit('updatedTasks', {
            currentTasks: response.data,
            childTasks: {
              tasks: [],
            },
          })

          commit(`sortedTasksBy${state.sortTaskBy}`)
        }
        globalStore.commit('markNotificationOld')
      })
    },

    getNotifications({ commit, state, rootState }, payload) {
      if (!rootState.notifications.isNewUserNotification) {
        if ((payload && !payload.disableLoader) || !payload) {
          commit('setTasksLoader', true)
        } else {
          commit('setOnlyTasksLoader', true)
        }
      }
      return api.getNotifications(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        if (response && response.data) {
          if (rootState.categories.selectedCategory.id == 'notification') {
            commit('updatedTasks', {
              currentTasks: response.data,
              childTasks: {
                tasks: [],
              },
            })

            commit(`sortedTasksBy${state.sortNotificationTaskBy}`)
          }
        }
        globalStore.commit('markNotificationOld')
      })
    },

    getDrafts({ commit, state, rootState }, payload) {
      if (!rootState.notifications.isNewUserNotification) {
        if ((payload && !payload.disableLoader) || !payload) {
          commit('setTasksLoader', true)
        } else {
          commit('setOnlyTasksLoader', true)
        }
      }
      return api.getDrafts(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        if (response.data) {
          if (rootState.categories.selectedCategory.id == 'draft') {
            commit('updatedTasks', {
              currentTasks: response.data,
              childTasks: {
                tasks: [],
              },
            })

            commit(`sortedTasksBy${state.sortTaskBy}`)
          }
        }
        globalStore.commit('markNotificationOld')
      })
    },

    getRecently({ commit, state, rootState }, payload) {
      if (!rootState.notifications.isNewUserNotification) {
        if ((payload && !payload.disableLoader) || !payload) {
          commit('setTasksLoader', true)
        } else {
          commit('setOnlyTasksLoader', true)
        }
      }
      return api.getRecently(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        if (response.data) {
          if (rootState.categories.selectedCategory.id == 'recently') {
            commit('updatedTasks', {
              currentTasks: response.data,
              childTasks: {
                tasks: [],
              },
            })

            commit('sortedTasksByLastViewed')
          }
        }
        globalStore.commit('markNotificationOld')
      })
    },

    getUnsortedTasks({ commit, state, rootState }, payload) {
      if (!rootState.notifications.isNewUserNotification) {
        if ((payload && !payload.disableLoader) || !payload) {
          commit('setTasksLoader', true)
        } else {
          commit('setOnlyTasksLoader', true)
        }
      }
      return api.getUnsortedTasks(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        if (response.data) {
          if (rootState.categories.selectedCategory.id == 'unsorted') {
            commit('updatedTasks', {
              currentTasks: response.data,
              childTasks: {
                tasks: [],
              },
            })

            commit(`sortedTasksBy${state.sortTaskBy}`)
          }
        }
        globalStore.commit('markNotificationOld')
      })
    },

    getCategoryTasks({ commit, state, rootState }, payload) {
      function categoryInTeam(tree) {
        let check = false
        for (const item of tree) {
          if (item.id == payload.categoryId) {
            return true
          } else {
            if (item.children && ! check) {
              check = categoryInTeam(item.children)
            }
          }
        }
        return check
      }

      if (!categoryInTeam(rootState.categories.categories.publicTree)) {
        return
      }

      commit('setLastCategoryId', payload.categoryId)
      if (!rootState.notifications.isNewUserNotification) {
        if ((payload && !payload.disableLoader) || !payload) {
          commit('setTasksLoader', true)
        } else {
          commit('setOnlyTasksLoader', true)
        }
      }
      api.getCategoryTasks(payload).then((response) => {
        if (response && response.data) {
          if (typeof rootState.categories.selectedCategory.id == 'number' && rootState.categories.selectedCategory.id == response.data.category_id) {
            commit('setTasksLoader', false)
            commit('setOnlyTasksLoader', false)
            commit('updatedTasks', response.data)
            commit(`sortedTasksBy${state.sortTaskBy}`)
          }
        }
        globalStore.commit('markNotificationOld')
      })
    },

    getAssignedTasks({ commit, state, rootState }, payload) {
      if (!rootState.notifications.isNewUserNotification) {
        if ((payload && !payload.disableLoader) || !payload) {
          commit('setTasksLoader', true)
        } else {
          commit('setOnlyTasksLoader', true)
        }
      }
      return api.getAssignedTasks(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        if (response.data) {
          commit('updatedTasks', {
            currentTasks: response.data,
            childTasks: {
              tasks: [],
            },
          })

          commit(`sortedTasksBy${state.sortTaskBy}`)
        }
        globalStore.commit('markNotificationOld')
      })
    },

    getUnassignedTasks({ commit, state, rootState }, payload) {
      if (!rootState.notifications.isNewUserNotification) {
        if ((payload && !payload.disableLoader) || !payload) {
          commit('setTasksLoader', true)
        } else {
          commit('setOnlyTasksLoader', true)
        }
      }
      return api.getUnassignedTasks(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        if (response.data) {
          commit('updatedTasks', {
            currentTasks: response.data,
            childTasks: {
              tasks: [],
            },
          })

          commit(`sortedTasksBy${state.sortTaskBy}`)
        }
        globalStore.commit('markNotificationOld')
      })
    },

    getTasksByAssignee({ commit, state, rootState }, payload) {
      if (!rootState.notifications.isNewUserNotification) {
        if ((payload && !payload.disableLoader) || !payload) {
          commit('setTasksLoader', true)
        } else {
          commit('setOnlyTasksLoader', true)
        }
      }
      return api.getTasksByAssignee(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        if (response.data) {
          commit('updatedTasks', {
            currentTasks: response.data,
            childTasks: {
              tasks: [],
            },
          })

          commit(`sortedTasksBy${state.sortTaskBy}`)
        }
        globalStore.commit('markNotificationOld')
      })
    },

    getTasksByAuthor({ commit, state, rootState }, payload) {
      if (!rootState.notifications.isNewUserNotification) {
        if ((payload && !payload.disableLoader) || !payload) {
          commit('setTasksLoader', true)
        } else {
          commit('setOnlyTasksLoader', true)
        }
      }
      return api.getTasksByAuthor(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        if (response.data) {
          commit('updatedTasks', {
            currentTasks: response.data,
            childTasks: {
              tasks: [],
            },
          })

          commit(`sortedTasksBy${state.sortTaskBy}`)
        }
        globalStore.commit('markNotificationOld')
      })
    },

    getPublicTasks({ commit, state, rootState }, payload) {
      if (!rootState.notifications.isNewUserNotification) {
        if ((payload && !payload.disableLoader) || !payload) {
          commit('setTasksLoader', true)
        } else {
          commit('setOnlyTasksLoader', true)
        }
      }
      return api.getPublicTasks(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        if (response && response.data) {
          if (rootState.categories.selectedCategory.id == response.data.category_id) {
            commit('updatedTasks', {
              currentTasks: response.data,
              childTasks: {
                tasks: [],
              },
            })
            commit(`sortedTasksBy${state.sortTaskBy}`)
          }
        }
        globalStore.commit('markNotificationOld')
        return response
      })
    },

    getPrivateTasks({ commit, state }, payload) {
      if ((payload && !payload.disableLoader) || !payload) {
        commit('setTasksLoader', true)
      } else {
        commit('setOnlyTasksLoader', true)
      }
      return api.getPrivateTasks(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        commit('updatedTasks', {
          currentTasks: response.data,
          childTasks: {
            tasks: [],
          },
        })

        commit(`sortedTasksBy${state.sortTaskBy}`)
        return response
      })
    },

    getTaskInfo({ commit }, payload) {
      if ((payload && !payload.disableLoader) || !payload) {
        commit('setTasksLoader', true)
      } else {
        commit('setOnlyTasksLoader', true)
      }
      return api.getTaskInfo(payload).then((response) => {
        commit('setTasksLoader', false)
        commit('setOnlyTasksLoader', false)
        if (response.data) {
          if (!response.data.commentsstate) {
            response.data.commentsstate = {
              closed_comments: [],
              show_task_all_comments: true,
              show_task_details: false,
            }
          }
          commit('updatedCurrentTaskData', response.data)
        }

        return response
      })
    },

    updateCommentsState({ commit }, payload) {
      commit('updateCurrentTaskComments', payload)

      const data = {
        closed_comments: payload.closed_comments,
        show_task_all_comments: payload.show_task_all_comments,
        show_task_details: payload.show_task_details,
      }

      return api.updateCommentsState(data)
    },

    updateTask(store, payload) {
      return api.updateTask(payload)
    },

    createTaskComment(store, payload) {
      return api.createTaskComment(payload)
    },

    subscribeUser(store, payload) {
      return api.subscribeUser(payload)
    },

    unsubscribeUser(store, payload) {
      return api.unsubscribeUser(payload)
    },

    getTaskAttributes({ state }, payload) {
      state.isTasksCheckboxDisabled = true
      return api.getTaskAttributes(payload).then((response) => {
        state.isTasksCheckboxDisabled = false
        return response
      })
    },

    setTaskAttributes({ state }, payload) {
      state.isTasksCheckboxDisabled = true
      return api.setTaskAttributes(payload).then((response) => {
        state.isTasksCheckboxDisabled = false
        return response
      })
    },

    closeTask(store, payload) {
      return api.closeTask(payload)
    },

    openTask(store, payload) {
      return api.openTask(payload)
    },

    getTaskHistory(store, payload) {
      return api.getTaskHistory(payload)
    },

    revertTaskHistory(store, payload) {
      return api.revertTaskHistory(payload)
    },
  },
}

export default tasks
