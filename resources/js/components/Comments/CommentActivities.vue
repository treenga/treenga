<template>
  <div
    class="comment-activity"
    :class="{'is_new': isNew}"
  >
    <i class="el-icon-info"/>
    {{ `${body} ${diff}` }}
  </div>
</template>

<script>
export default {
  name: 'CommentActivities',

  props: {
    body: {
      type: String,
      required: true,
    },

    diff: {
      type: String,
      required: true,
    },

    isNew: {
      type: Boolean,
      default: false,
    },
  },
}
</script>

<style lang="scss" scoped>
  .comment-activity {
    background-color: #f4f4f5;
    color: #909399;
    padding: 10px 10px 10px 15px;
    margin-top: 20px;
    border: 1px solid #ebeef5;
    border-radius: 4px;
    display: flex;
    font-size: 14px;
    align-items: center;

    &.is_new {
      background-color: #edf2fc;
    }

    & > i {
      margin-right: 10px;
    }
  }
</style>
