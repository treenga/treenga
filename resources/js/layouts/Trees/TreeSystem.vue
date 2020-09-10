<template>
  <div
    :class="[
      {'is-search': (filterText != '' && filterText != null)},
    ]"
    class="system-tree"
  >
    <el-tree
      ref="systemTree"
      key="systemTree"
      :data="categoriesData"
      :props="props"
      :default-expand-all="false"
      :default-expanded-keys="treeState.main.system_tree"
      :draggable="true"
      :highlight-current="true"
      :expand-on-click-node="false"
      :filter-node-method="filterTree"
      :auto-expand-parent="false"
      :show-checkbox="isTasksChecked"
      class="no-label-checkbox-tree"
      node-key="id"
      @node-click="$emit('node-click', $event)"
      @node-collapse="$emit('node-collapse', $event, 'system_tree')"
      @node-expand="$emit('node-expand', $event, 'system_tree')"
    >
      <span
        slot-scope="{ node, data }"
        class="custom-tree-node"
      >
        <span
          :class="[
            {'padding-left': data.id},
            data.class || ''
          ]"
          class="label"
        >
          <i
            v-show="!node.data.id"
            class="icon-umbrella"
          />
          {{ node.label }}
          <span
            v-if="data.count !== false"
            class="tasks-count"
          >
            ({{ data.count || 0 }})
          </span>
        </span>
      </span>
    </el-tree>

  </div>
</template>

<script>
import { mapMutations, mapActions, mapState, mapGetters } from 'vuex'
import pusher from '@/js/pusher'
import TreeMixin from '@/js/mixins/Trees'

export default {
  name: 'TreeSystem',

  mixins: [TreeMixin],

  props: {
    categoriesData: {
      type: Array,
      required: true,
    },

    fields: {
      type: Array,
      default: () => ([]),
    },
  },

  data() {
    return {
      activeNodeId: null,
      type: 'system',
      isDrafts: true,
      isUnsorted: true,
      props: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },
    }
  },

  computed: {
    ...mapState({
      filterText: state => state.tasks.checkedFilters.filterText,
    }),

    ...mapGetters([
      'isPrivateTeam',
    ]),
  },

  watch: {

  },

  mounted() {
    this.initTeamListeners()
  },

  methods: {
    ...mapActions([
      'getNotifications',
      'getDrafts',
      'getRecently',
      'getUnsortedTasks',
      'getCategoryTasks',
      'saveFilterState',
    ]),

    ...mapMutations([
      'updatedCategories',
      'categoryChecked',
      'categoryUnchecked',
      'categorySelected',
      'updatedFilterText',
    ]),

    managePusherListeners() {

    },

    initTeamListeners() {

    },

    updateData(data) {
      const children = [
        {
          id: 'draft',
          label: 'Drafts',
          disabled: true,
          count: data.count_public_drafts || 0,
        }, {
          id: 'recently',
          label: 'Recently viewed',
          disabled: true,
          count: false,
        },
      ];
      if (!this.isPrivateTeam) {
        children.unshift({
          id: 'notification',
          class: data.count_public_notifications > 0 ? 'has-notifications' : '',
          label: 'Notifications',
          disabled: true,
          count: data.count_public_notifications || 0,
        })
      }
      return [{
        id: 0,
        label: 'My',
        type: 'system',
        disabled: true,
        count: false,
        children,
      }]
    },
  },
}
</script>

<style lang="scss" scoped>
  .system-tree {
    position: relative;
    margin: 0 10px;

    // when custom node added - change top margin of actual categories
    // in app.scss after comment Trees styles

    .custom-node {
      position: relative;
      width: 100%;
      color: white;
      left: 0;

      .el-tree-node__content {
        padding-left: 18px;
      }

      &.is-current {
        .el-tree-node__content {
          background-color: rgba(255, 255, 255, 0.3)
        }
      }
    }
  }
</style>
