<template>
  <div>
    <div
      v-if="commentData.is_comment"
      v-loading="commentData.is_loading"
      :class="{'is-new': commentData.is_new}"
      class="comment"
    >
      <div class="top-bar">
        <button
          class="toggle"
          @click="onToggleClick"
        >
          [ {{ isOpen ? '-' : '+' }} ]
        </button>

        <div class="name">{{ commentData.username }}</div>
        <div class="date">{{ commentData.diff }}</div>
      </div>

      <div v-show="isOpen">
        <div
          class="comment-content"
          v-html="commentData.body"
        />

        <div v-show="!isReplyOpen">
          <el-button
            type="text"
            @click="onReplyClick"
          >
            reply
          </el-button>
        </div>

        <div
          v-if="isReplyOpen"
          class="reply"
        >
          <tr-trix-input
            :ref="`inner-input-${commentData.id}`"
            v-model="replyBody"
            @ctrlEnterPress="onSendReplyClick"
          />

          <div class="reply-buttons">
            <el-button
              size="medium"
              type="success"
              @click="onSendReplyClick"
            >
              Send reply
            </el-button>

            <el-button
              size="medium"
              plain
              @click="onCancelReplyClick"
            >
              Cancel
            </el-button>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="commentData.children"
      :class="{offset: depth < maxChildrenNumber }"
      class="child"
    >
      <comment
        v-for="child in commentData.children"
        :depth="depth + 1"
        :key="`comment-${child.id}`"
        :comment-data="child"
        :sort-type="sortType"
        :class="{'scrollHere': child.is_new}"
        @reply="onReply($event)"
        @commentRendered="$emit('commentRendered', $event)"
      />
    </div>
  </div>
</template>

<script>
import { mapMutations, mapState, mapActions } from 'vuex'
import TrTrixInput from '../Inputs/TrTrixInput'

export default {
  name: 'Comment',

  isComment: true,

  components: {
    TrTrixInput,
  },

  props: {
    commentData: {
      type: Object,
      required: true,
    },

    sortType: {
      type: String,
      required: true,
    },

    depth: {
      type: Number,
      default: 1,
    },
  },

  data() {
    return {
      isOpen: true,
      isReplyOpen: false,
      replyBody: '',
      maxChildrenNumber: 10,
    }
  },

  computed: {
    ...mapState({
      currentTaskData: state => state.tasks.currentTaskData,
    }),

    isCurrentClosed() {
      return this.currentTaskData.commentsstate.closed_comments
        .includes(this.commentData.id)
    },
  },

  watch: {
    sortType() {
      this.statusHandle()
    },
  },

  mounted() { },

  created() {
    this.$emit('commentRendered', this.commentData)
    this.statusHandle()
  },

  methods: {
    ...mapMutations([
      'alertError',
    ]),

    ...mapActions([
      'updateCommentsState',
    ]),

    onToggleClick() {
      this.isOpen = !this.isOpen

      if (this.sortType === 'all_comments') {
        const state = {
          closed_comments: this.currentTaskData.commentsstate.closed_comments
            || [],
          show_task_all_comments: this.currentTaskData.commentsstate.show_task_all_comments
            || false,
          show_task_details: this.currentTaskData.commentsstate.show_task_details
            || false,
        }

        if (this.isOpen) {
          state.closed_comments.splice(state.closed_comments.indexOf(this.commentData.id), 1)
        } else {
          state.closed_comments.push(this.commentData.id)
        }

        this.updateCommentsState(state)
      }
    },

    onReplyClick() {
      this.isReplyOpen = true
    },

    onSendReplyClick() {
      if (this.validateReply()) {
        const mentionedUsers = this.$refs[`inner-input-${this.commentData.id}`].getMentionedUsers()
        this.onReply({
          body: this.replyBody,
          parent_id: this.commentData.id,
          subscribers: mentionedUsers.length ? mentionedUsers : null,
        })
      }
    },

    onReply(data) {
      this.$emit('reply', Object.assign({}, data))

      this.onCancelReplyClick()
    },

    statusHandle() {
      if (this.sortType === 'new_comments') {
        if (!this.commentData.is_new) {
          this.isOpen = false
        } else {
          this.isOpen = true
          if (this.$parent.$options.isComment) {
            this.$parent.isOpen = true
          }
        }
      } else { // this.sortType === 'all_comments'
        const route = this.$route.name === 'Edit public task'
        if (route && this.isCurrentClosed) {
          this.isOpen = false
          return
        }

        this.isOpen = true
      }
    },

    validateReply() {
      if (this.replyBody.replace(/<[^>]+>/ig, '').length) {
        return true
      }

      this.alertError({
        title: 'Validation failed',
        text: 'Comment text is required',
      })

      return false
    },

    onCancelReplyClick() {
      this.replyBody = ''
      this.isReplyOpen = false
    },

    scrollToFirstNewActivity() {
      let check = false
      if (this.commentData.children) {
        for (const comment of this.commentData.children) {
          if (comment.is_new) {
            check = true
          }
        }
      }

      if (check) {
        let options = {
          container: '.edit-task-scroll .el-scrollbar__wrap',
          easing: 'ease-in',
          offset: -60,
          force: true,
          cancelable: false,
        }

        this.$scrollTo('.scrollHere', 300, options)
      }
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables.scss';

.comment {
  margin-top: 20px;

  &.is-new {
    padding: 8px 16px;
    background-color: #ecf8ff;
    border-radius: 4px;
    border-left: 5px solid #50bfff;
  }

  .top-bar {
    display: flex;
    font-size: $small-body;
    margin-bottom: 10px;

    .toggle {
      background-color: transparent;
      border: none;
      padding: 0;
    }

    .date {
      color: $gray-icon;
    }

    & > div {
      margin-left: 10px;
    }
  }
}

.reply {
  margin-top: 10px;

  .reply-buttons {
    display: flex;
    margin-top: 10px;
  }
}

.offset {
  margin-left: 20px;
}
</style>
