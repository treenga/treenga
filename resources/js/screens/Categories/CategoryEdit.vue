<template>
  <el-row
    :gutter="0"
    type="flex"
  >
    <el-col
      :span="6"
      :class="{'categories-list-private': isPrivateTeam}"
      class="categories-list"
    />
    <el-col :span="18">
      <el-row class="top-buttons">
        <el-col :span="12">
          <el-button
            v-if="!isEdit"
            type="info"
            plain
            size="medium"
            @click="$router.push(`/${$route.params.teamSlug}`)"
          >
            ← Back to task list
          </el-button>

          <el-button
            v-show="isEdit"
            :loading="isLoading"
            type="primary"
            size="medium"
            @click="onSaveClick"
          >
            Save
          </el-button>

          <el-popover
            v-if="isDirty && isEdit"
            v-model="isPopover"
            placement="bottom"
          >
            <p>Discard changes? Unsaved data will be lost</p>
            <div style="text-align: center; margin: 0">
              <el-button
                type="primary"
                size="medium"
                @click="onCancelClick"
              >
                Yes, discard changes
              </el-button>

              <el-button
                size="medium"
                type="text"
                @click="isPopover = false"
              >
                Close
              </el-button>
            </div>

            <el-button
              slot="reference"
              type="default"
              size="medium"
              class="cancel-btn"
              style="margin-left: 10px"
            >
              Cancel
            </el-button>
          </el-popover>

          <el-button
            v-show="!isDirty && isEdit"
            :loading="isLoading"
            type="default"
            size="medium"
            @click="onCancelClick"
          >
            Cancel
          </el-button>
        </el-col>

        <el-col
          :span="12"
          class="text-right"
        >
          <el-button-group>
            <el-button
              v-if="!isEdit"
              type="default"
              size="medium"
              @click="onEditClick"
            >
              Edit
            </el-button>

            <el-button
              :disabled="isEdit"
              class="dropdown-button"
              type="default"
              size="medium"
            >
              <el-dropdown
                placement="bottom"
                trigger="click"
                size="medium"
                style="color: inherit;"
                @command="handleDropdown"
              >
                <div class="drop-item el-button--medium">
                  <i class="el-icon-more" />
                </div>

                <el-dropdown-menu
                  v-if="!isEdit"
                  slot="dropdown"
                >
                  <el-dropdown-item command="history">Show category revisions</el-dropdown-item>
                  <el-dropdown-item command="subscribe">
                    {{ category.is_subscriber ? 'Unsubscribe from' : 'Subscribe to' }} notifications
                  </el-dropdown-item>
                </el-dropdown-menu>
              </el-dropdown>
            </el-button>
          </el-button-group>
        </el-col>
      </el-row>

      <el-scrollbar class="edit-category-scroll">
        <div
          v-if="isEdit"
          class="middle-section"
        >
          <h2 class="category-name">{{ category.name }}</h2>

          <tr-trix-input
            ref="input"
            v-model="category.body"
            class="category-body"
            @change="isDirty = true"
          />
        </div>

        <div
          v-show="!isEdit"
          class="middle-section"
        >
          <h2 class="category-name">{{ category.name }}</h2>

          <div
            class="category-body"
            v-html="category.body"
          />

          <div class="comments-section">
            <comments
              ref="comments"
              :comments="category.comments"
              @createComment="onCreateComment($event)"
            />
          </div>
        </div>
      </el-scrollbar>
    </el-col>
  </el-row>
</template>

<script>
import { mapState, mapActions, mapMutations, mapGetters } from 'vuex'
import { TrTrixInput, Comments } from '@/js/components'

export default {
  name: 'CategoryEdit',

  components: {
    TrTrixInput,
    Comments,
  },

  data() {
    return {
      isEdit: false,

      isInitial: true,

      isPopover: false,

      isDirty: false,

      isLoading: false,

      category: {
        name: '',
        body: '',
        diff: '',
        id: false,
        username: '',
        comments: [],
      },
    }
  },

  computed: {
    ...mapState({
      currentUser: state => state.account.user,
      teamUsers: state => state.teams.teamUsers,
    }),

    ...mapGetters([
      'isPrivateTeam',
    ]),
  },

  created() {
    this.getCategoryData()
    if (!this.teamUsers.length) {
      this.getTeamInfo()
    }
  },

  methods: {
    ...mapActions([
      'createCategoryComment',
      'subscribeUserToCategory',
      'unsubscribeUserFromCategory',
      'getCategoryInfo',
      'setCategoryDescription',
      'updateCategoryDescription',
      'getTeamInfo',
    ]),

    ...mapMutations([
      'alertError',
    ]),

    onCreateComment(data) {
      this.createCategoryComment({
        teamId: this.$route.params.teamSlug,
        categoryId: this.$route.params.categoryId,
        data,
      }).then((response) => {
        if (response.data) {
          this.$refs.comments.onCancelClick()
          this.getCategoryData()
        }
      })
    },

    getCategoryData() {
      this.getCategoryInfo(this.$route.params.categoryId).then((response) => {
        if (response.data) {
          this.category = response.data
          this.isInitial = response.data.body === null
          return
        }

        this.$router.push('/')
      })
    },

    onCancelClick() {
      this.isEdit = false
      this.isDirty = false
      this.isPopover = false
      this.getCategoryData()
    },

    onEditClick() {
      this.isEdit = true
    },

    handleDropdown(command) {
      switch (command) {
        case 'history':
          this.$router.push(`/history/${this.$route.params.teamSlug}/category/${this.$route.params.categoryId}`);
          break
        case 'subscribe':
          this.onSubscribeClick()
          break
        default: break
      }
    },

    onSaveClick() {
      if (this.category.body) {
        this.isLoading = true
        const method = this.isInitial ? 'setCategoryDescription' : 'updateCategoryDescription'
        const mentionedUsers = this.$refs.input.getMentionedUsers()

        this[method]({
          teamId: this.$route.params.teamSlug,
          categoryId: this.$route.params.categoryId,
          data: {
            body: this.category.body,
            subsribers: mentionedUsers.length ? mentionedUsers : null,
          },
        }).then((response) => {
          if (response.data) {
            this.isLoading = false
            this.isEdit = false
            this.isDirty = false
            this.isInitial = false
          }
        })
      }
    },

    onSubscribeClick() {
      const data = {
        teamId: this.$route.params.teamSlug,
        categoryId: this.$route.params.categoryId,
      }

      if (this.category.is_subscriber) {
        this.unsubscribeUserFromCategory(data).then((response) => {
          if (response.data) {
            this.category.is_subscriber = false
          }
        })
        return
      }

      this.subscribeUserToCategory(data).then((response) => {
        if (response.data) {
          this.category.is_subscriber = true
        }
      })
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables.scss';

.categories-list {
  padding: 10px 20px;

  .back-link {
    color: $gray;
  }

  .tree {
    margin-top: 20px;
  }

  &-private {
    background-color: $light-private;
  }
}

.top-buttons {
  .el-dropdown {
    .drop-item {
      padding: 10px 20px;
    }
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
}

.comments-section {
  margin-top: 40px;
  padding-top: 20px;
  border-top: 1px solid #EBEEF5;
}

.additional-info {
  color: $gray-icon;
}
</style>
