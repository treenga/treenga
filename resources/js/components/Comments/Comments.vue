<template>
  <div class="comments">
    <div class="top-menu">
      <div 
        v-if="isRightMenu && ! isPrivateTeam"
        class="radio-buttons"
      >
        <el-radio
          v-model="sortType"
          label="all_comments"
          @change="onOptionsChange"
        >
          All comments
        </el-radio>

        <el-radio
          v-model="sortType"
          label="new_comments"
          @change="onOptionsChange"
        >
          New comments
        </el-radio>
      </div>
      <el-switch
        v-if="isDetails && isRightMenu"
        v-model="isDetailsHidden"
        class="hide-details__checkbox"
        active-text="Show details"
        @change="onOptionsChange"
      />
    </div>

    <div class="comments-list">
      <div
        v-for="(activity, index) in comments"
        :key="`activity-${activity.is_activity * 1}-${activity.id}-${index}`"
      >
        <comment
          v-if="activity.is_comment"
          :key="`comment-${activity.id}`"
          :comment-data="activity"
          :sort-type="sortType"
          :class="{'scrollHere': activity.is_new}"
          @reply="onReplyClick($event)"
          @commentRendered="$emit('commentRendered', $event)"
        />

        <comment-activities
          v-if="activity.is_activity && (isDetailsHidden || (activity.is_new && isFirstView))"
          :key="`activity-${activity.id}`"
          :body="activity.body"
          :diff="activity.diff"
          :isNew="activity.is_new && isFirstView"
          :class="{'scrollHere': activity.is_new}"
        />
      </div>
    </div>
    
    <div
      class="leave-wrapper"
    >
      <tr-trix-input
        ref="input"
        v-model="newCommentBody"
        @ctrlEnterPress="onLeaveCommentClick"
      />

      <div class="buttons">
        <el-button
          size="medium"
          type="success"
          @click="onLeaveCommentClick"
        >
          Leave comment
        </el-button>
      </div>
    </div>
  </div>
</template>

<script>
import { mapMutations, mapState, mapActions, mapGetters } from 'vuex'
import TrTrixInput from '../Inputs/TrTrixInput'
import Comment from './Comment'
import CommentActivities from './CommentActivities'

export default {
  name: 'Comments',

  components: {
    TrTrixInput,
    Comment,
    CommentActivities,
  },

  props: {
    comments: {
      type: Array,
      default: () => ([]),
    },

    isStatusButton: {
      type: Boolean,
      default: false,
    },

    isRightMenu: {
      type: Boolean,
      default: true,
    },

    isStatusLoader: {
      type: Boolean,
      default: false,
    },

    isDetails: {
      type: Boolean,
      default: false,
    },

    statusText: {
      type: String,
      default: '',
    },
  },

  data() {
    return {
      sortType: '',
      isNewComment: true,
      newCommentBody: '',
      parent_id: null,
      isLoader: false,
      isDetailsHidden: false,
      isFirstView: true,
    }
  },

  computed: {
    ...mapState({
      commentsstate: state => state.tasks.currentTaskData.commentsstate,
      show_task_all_comments: state => state.tasks.currentTaskData.commentsstate ? state.tasks.currentTaskData.commentsstate.show_task_all_comments : true,
      show_task_details: state => state.tasks.currentTaskData.commentsstate ? state.tasks.currentTaskData.commentsstate.show_task_details : false,
      closed_comments: state => state.tasks.currentTaskData
        .commentsstate.closed_comments,
    }),

    ...mapGetters([
      'isPrivateTeam',
    ]),
  },

  mounted() {
    const images = document.getElementsByTagName('img');
    const total = images.length;
    let loaded = 0;
    if (!total) {
      this.scrollToFirstNewActivity()
      return
    }
    for (const img of images) {
      ['load', 'error'].forEach(e => {
        img.addEventListener(e, () => {
          loaded += 1;
          if (loaded === total) {
            this.scrollToFirstNewActivity()
          }
        }, true)
      })
    }
  },

  created() {
    this.sortType = this.show_task_all_comments ? 'all_comments' : 'new_comments'
    this.isDetailsHidden = this.show_task_details || false
  },

  methods: {
    ...mapActions([
      'updateCommentsState',
    ]),

    ...mapMutations([
      'alertError',
    ]),

    onCommentClick() {
      this.isNewComment = !this.isNewComment
      setTimeout(() => {
        this.$refs.input.focus()
      }, 50)
    },

    onCancelClick() {
      this.newCommentBody = ''
      this.$refs.input.clearInput()
      this.isNewComment = false
    },

    onLeaveCommentClick() {
      if (this.validateComment(this.newCommentBody)) {
        const mentionedUsers = this.$refs.input.getMentionedUsers()

        this.leaveComment({
          body: this.newCommentBody,
          parent_id: null,
          subscribers: mentionedUsers.length ? mentionedUsers : null,
        })
        this.newCommentBody = ''
      }
    },

    leaveComment(commentData) {
      this.$emit('createComment', commentData)
      this.isNewComment = false
    },

    onReplyClick(commentData) {
      this.leaveComment(commentData)
    },

    validateComment(body) {
      if (body.replace(/<[^>]+>/ig, '').length) {
        return true
      }

      this.alertError({
        title: 'Validation failed',
        text: 'Comment text is required',
      })

      return false
    },

    onOptionsChange() {
      const data = {
        show_task_all_comments: this.sortType === 'all_comments',
        show_task_details: this.isDetailsHidden,
        closed_comments: this.closed_comments || [],
      }
      this.isFirstView = false
      this.updateCommentsState(data)
    },

    scrollToFirstNewActivity() {
      if (document.querySelectorAll('.scrollHere').length) {
        const options = {
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
.buttons {
  display: flex;
}

.top-menu {
  display: flex;
  align-items: center;
  justify-content: space-between;

  .buttons {
    margin-right: auto;
  }

  .hide-details__checkbox {
    margin-left: 30px;
  }
}

.leave-wrapper {
  text-align: left;
  margin-top: 40px;
  padding-top: 20px;
  padding-bottom: 38vh;
  border-top: 1px solid #EBEEF5;

  button {
    margin-top: 10px;
  }
}

.comments-list {
  margin-top: 30px;
}
</style>
