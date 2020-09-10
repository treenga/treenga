// Common methods for category trees
// ment to use is scope with public or private tree, with type set in data e.g. type: 'public'

import { mapState, mapMutations, mapActions } from 'vuex'

export default {
  computed: {
    ...mapState({
      isTasksChecked: state => state.tasks.isTasksChecked,
      categories: state => state.categories.categories,
      selectedTeam: state => state.teams.selectedTeam,
      selectedCategory: state => state.categories.selectedCategory,
      treeState: state => state.teams.treeState,
    }),

    isRootOpen() {
      return this.treeState.main[`${this.type}_tree`]
        && this.treeState.main[`${this.type}_tree`].indexOf(0) > -1
    },
  },

  methods: {
    ...mapMutations([
      'disabledFilter',
      'sortedTasksByRecently',
    ]),

    ...mapActions([
      'saveTreeState',
      'getPublicTasks',
    ]),

    filterTree(value, data) {
      this.isDrafts = false
      this.isUnsorted = false

      if (!value) {
        this.isUnsorted = true
        this.isDrafts = true
        return true
      }

      if (('drafts').indexOf(value) > -1) {
        this.isDrafts = true
        if (data.id === 0) {
          return true
        }
      }

      if (('unsorted').indexOf(value) > -1) {
        this.isUnsorted = true
        if (data.id === 0) {
          return true
        }
      }

      return data.label.toLowerCase().indexOf(value.toLowerCase()) !== -1
    },

    onCategoryCreated(newData) {
      const nextId = this.getNextNodeIndex(newData)
      newData.label = newData.name
      newData.tasks_count = 0
      newData.deleted_tasks_count = 0
      newData.descendants_tasks_count = 0

      if (this.$refs[`${this.type}Tree`].getNode(newData.id)) {
        return
      }

      if (nextId) {
        this.$refs[`${this.type}Tree`].insertBefore(newData, nextId)
      } else if (newData.parent_id) {
        this.$refs[`${this.type}Tree`].append(newData, newData.parent_id)
      } else {
        const root = this.$refs[`${this.type}Tree`].getNode(0)
        this.$refs[`${this.type}Tree`].append(newData, root)
      }

      this.updatedCategories({
        [`${this.type}Tree`]: this.categoriesData[0].children,
      })

      this.selectCategory(this.selectedCategory.id)
    },

    setCheckedKeys(arr = []) {
      if (this.$refs[`${this.type}CheckTree`]) {
        this.$refs[`${this.type}CheckTree`].setCheckedKeys(arr)
      }
    },

    getNode(nodeId) {
      return this.$refs[`${this.type}Tree`].getNode(nodeId)
    },

    getNextNodeIndex(data) {
      let biggerArr = []

      if (data.parent_id) {
        const parentNode = this.$refs[`${this.type}Tree`].getNode(data.parent_id)

        if (!parentNode.childNodes.length) {
          return 0
        }

        biggerArr = parentNode.childNodes.filter(node => data.name < node.label)
        return biggerArr.length ? biggerArr[0].data.id : 0
      }

      const rootNode = this.$refs[`${this.type}Tree`].getNode(0)
      if (!rootNode.childNodes.length) {
        return 0
      }

      biggerArr = rootNode.childNodes.filter(node => data.name < node.label)
      return biggerArr.length ? biggerArr[0].data.id : 0
    },

    onDragEnd(draggingNode, dropNode, dropType) {
      if (dropType === 'none') return

      const data = {
        data: {},
      }
      let parentId = null

      if (dropNode) {
        if (dropType === 'inner') {
          parentId = dropNode.data.id
        } else {
          parentId = dropNode.parent ? dropNode.parent.data.id : null
        }
      }

      data.teamId = this.selectedTeam.id
      data.categoryId = draggingNode.data.id

      if (parentId) {
        data.data.parent_id = parentId
      }

      this.moveCategory(data).then((response) => {
        if (response.data) {
          this.updatedCategories({
            [`${this.type}Tree`]: this.categoriesData[0].children,
          })
        }
      })
    },

    onCheckCategoryChange(node, isSelected) {
      if (isSelected) {
        this.categoryChecked(node.id)
        return
      }

      this.categoryUnchecked(node.id)
    },

    getCheckedCategories() {
      return this.$refs[`${this.type}CheckTree`].getCheckedKeys()
    },

    dropValidation(draggingNode, dropNode) {
      return dropNode.key > 0
    },

    dragValidation(node) {
      if (this.isTasksChecked && this.type !== this.selectedCategory.type) {
        return false
      }

      return node.data.id > 0
    },

    handleUnsortedClick() {
      if (this.isTasksChecked) return

      this.selectCategory('unsorted')
      this.getUnsortedTasks({ type: this.type })

      this.categorySelected({
        label: 'Unsorted',
        fullPath: 'Categories',
        type: this.type,
        id: 'unsorted',
        method: {
          name: 'getUnsortedTasks',
          data: { type: this.type },
        },
      })
    },

    handleAllTasksClick() {
      if (this.isTasksChecked) return

      this.selectCategory('all-tasks')
      this.getPublicTasks({ teamId: this.$route.params.teamSlug })

      this.updatedFilterText('')
      this.saveFilterState()

      this.categorySelected({
        label: '@All tasks',
        fullPath: 'Categories',
        type: this.type,
        id: 'all-tasks',
        method: {
          name: 'getPublicTasks',
          data: { teamId: this.$route.params.teamSlug },
        },
      })
    },

    selectCategory(id) {
      this.$nextTick(() => {
        const intId = parseInt(id, 10)

        if (intId) {
          this.activeNodeId = null
          this.$refs[`${this.type}Tree`].setCurrentKey(id)
          return
        }

        if (intId === 0) {
          this.activeNodeId = null
          this.$refs[`${this.type}Tree`].setCurrentKey(0)
          return
        }

        if (['draft', 'notification', 'recently'].indexOf(id) > -1) {
          this.activeNodeId = null
          this.$refs[`${this.type}Tree`].setCurrentKey(id)
          return
        }

        if (id === 'unsorted') {
          this.activeNodeId = -2
        }

        if (id === 'all-tasks') {
          this.activeNodeId = -3
        }

        if (id === null) {
          this.activeNodeId = null
        }

        this.$refs[`${this.type}Tree`].setCurrentKey(null)
      })
    },

    handleNotificationsClick() {
      if (this.isTasksChecked) return

      this.selectCategory('notification')
      this.getNotifications({ type: this.type })

      this.categorySelected({
        label: 'Notifications',
        fullPath: 'Categories',
        type: this.type,
        id: 'notification',
        method: {
          name: 'getNotifications',
          data: { type: this.type },
        },
      })
    },

    handleDraftsClick() {
      if (this.isTasksChecked) return

      this.selectCategory('draft')
      this.getDrafts({ type: this.type })

      this.categorySelected({
        label: 'Drafts',
        fullPath: 'Categories',
        type: this.type,
        id: 'draft',
        method: {
          name: 'getDrafts',
          data: { type: this.type },
        },
      })
    },

    handleRecentlyClick() {
      if (this.isTasksChecked) return

      this.selectCategory('recently')
      this.getRecently({ type: this.type })

      this.categorySelected({
        label: 'Recently viewed',
        fullPath: 'Categories',
        type: this.type,
        id: 'recently',
        method: {
          name: 'getRecently',
          data: { type: this.type },
        },
      })
    },

    onCategoryDeleted(data) {
      const node = this.$refs[`${this.type}Tree`].getNode(data.id)
      if (!node) {
        return
      }

      if (data.id === this.selectedCategory.id) {
        this.$emit('currentDeleted')
      }
      this.$refs[`${this.type}Tree`].remove(node)

      this.updatedCategories({
        [`${this.type}Tree`]: this.categoriesData[0].children,
      })
    },

    onCategoryRenamed(e) {
      const node = this.$refs[`${this.type}Tree`].getNode(e.id)
      node.data.label = e.name

      this.updatedCategories({
        [`${this.type}Tree`]: this.categoriesData[0].children,
      })
    },

    onNodeCollapse(node) {
      const states = this.treeState
      const tree = states.main[`${this.type}_tree`]

      tree.splice(tree.indexOf(node.id), 1)
      states.main[`${this.type}_tree`] = tree

      this.saveTreeState(states)

      if (node.id === 0) {
        this.isCustomNodesShown = false
      }
    },

    onNodeExpand(node) {
      const states = this.treeState
      const tree = states.main[`${this.type}_tree`]

      tree.push(node.id)
      states.main[`${this.type}_tree`] = tree

      this.saveTreeState(states)

      if (node.id === 0) {
        this.isCustomNodesShown = true
      }
    },

    formatCounter(data) {
      const descCount = data.children && data.children.length ? `/${data.descendants_tasks_count}` : ''
      return `(${data.tasks_count}${descCount})`
    },

    filter(val) {
      this.$refs[`${this.type}Tree`].filter(val)
      if (this.$refs[`${this.type}CheckTree`]) {
        this.$refs[`${this.type}CheckTree`].filter(val)
      }
    },

    handleNodeClick(node) {
      if (!node.id || (this.isTasksChecked && this.type !== this.selectedCategory.type)) {
        this.$refs[`${this.type}Tree`].setCurrentKey(null)
        return
      }

      this.$emit('node-click', node.id)

      this.activeNodeId = null
      const method = {
        name: '',
        data: {},
      }

      if (this.$refs[`${this.type}CheckTree`]) {
        this.$refs[`${this.type}CheckTree`].setCurrentNode(node)
      }

      this.getCategoryTasks({ categoryId: node.id })

      method.name = 'getCategoryTasks'
      method.data = { categoryId: node.id }

      this.updatedFilterText('')
      this.saveFilterState()

      this.categorySelected({
        ...node,
        type: this.type,
        method,
      })
    },
  },
}
