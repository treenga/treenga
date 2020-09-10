<template>
  <el-row
    :gutter="0"
    type="flex"
  >
    <el-col
      :span="6"
      :class="{'categories-list-private': isPrivateTeam}"
      class="histories-list"
    >
      <h4>
        <router-link
          :to="`/${$route.params.teamSlug}`"
          class="back-link"
        >
          ← Back to category list
        </router-link>
      </h4>

      <h4 class="title">Select category version</h4>

      <el-scrollbar class="history-scroll">
        <div class="histories">
          <div
            v-for="history in histories"
            :key="`history-name-${history.id}`"
          >
            <el-button
              :class="{current: currentHistoryId === history.id}"
              type="text"
              class="history-btn"
              @click="selectHistory(history)"
            >
              {{ history.date }}
            </el-button>
          </div>
        </div>
      </el-scrollbar>
    </el-col>

    <el-col :span="12">
      <el-scrollbar class="history-category-scroll">
        <div class="middle-section">
          <div
            class="category-body"
            v-html="category.body"
          />

          <div
            v-if="category.comments && category.comments.length"
            class="comments-section"
          >
            <comments
              ref="comments"
              :comments="category.comments"
            />
          </div>
        </div>
      </el-scrollbar>
    </el-col>

    <el-col :span="6">
      <div
        v-show="currentHistoryId"
        class="right-menu text-right"
      >
        <el-button
          type="primary"
          plain
          @click="onRevertClick"
        >
          <i class="icon-back-in-time" />
          Revert to this version
        </el-button>

        <el-button @click="onCancelClick">
          Cancel
        </el-button>
      </div>
    </el-col>
  </el-row>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'
import { TrTrixInput, Comments } from '@/js/components'

export default {
  name: 'CategoryHistory',

  components: {
    TrTrixInput,
    Comments,
  },

  data() {
    return {
      currentHistoryId: 0,
      currentBody: '',
      category: {
        body: '',
        name: '',
      },
      histories: [],
    }
  },

  computed: {
    ...mapGetters([
      'isPrivateTeam',
    ]),
  },

  created() {
    this.getHistory()
  },

  methods: {
    ...mapActions([
      'getCategoryHistory',
      'revertCategoryHistory',
    ]),

    getHistory() {
      this.getCategoryHistory({
        teamId: this.$route.params.teamSlug,
        categoryId: this.$route.params.categoryId,
      }).then((response) => {
        if (response.data) {
          this.category.name = response.data.name
          this.currentBody = response.data.text
          this.histories = response.data.histories
          this.selectHistory({
            id: 0,
            body: response.data.text,
          })
        }
      })
    },

    selectHistory(history) {
      this.currentHistoryId = history.id
      this.category.body = history.body
    },

    onCancelClick() {
      this.currentHistoryId = 0
      this.category.body = this.currentBody
    },

    onRevertClick() {
      this.revertCategoryHistory({
        teamId: this.$route.params.teamSlug,
        categoryId: this.$route.params.categoryId,
        historyId: this.currentHistoryId,
      }).then((response) => {
        if (response.data) {
          this.getHistory()
        }
      })
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables.scss';

.histories-list {
  background-color: $light-blue;
  height: 100vh;
  padding: 0 0 0 10px;

  .back-link {
    color: $gray;
  }

  .tree {
    margin-top: 20px;
  }
}

.middle-section {
  text-align: left;
  height: 100%;

  .category-name {
    margin-bottom: 20px;
    font-size: $main-title;
    width: 100%;
    background-color: transparent;
    border: none;
    color: $dark;
  }
}

.right-menu {
  padding: 20px 10px;

  .draft-incicator {
    margin-top: 20px;
  }

  .edit-buttons {
    display: flex;
    justify-content: flex-end;

    button {
      flex: auto;
    }
  }
}

.title {
  color: $yellow;
  font-weight: 700;
  margin-bottom: 10px;
  padding-left: 20px;
}

.comments-section {
  margin-top: 50px;
}

.histories {
  padding-left: 20px;

  .history-btn {
    color: white;
    text-decoration: underline;
    font-size: $body;
    position: relative;

    &.current {
      font-weight: 700;

      &::before {
        content: '';
        position: absolute;
        left: -15px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-top: 7px solid transparent;
        border-bottom: 7px solid transparent;
        border-left: 7px solid white;
      }
    }
  }
}
</style>
