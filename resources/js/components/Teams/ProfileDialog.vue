<template>
  <div>
    <el-form
      ref="updateNameForm"
      :model="form"
      :hide-required-asterisk="true"
      novalidate
      label-position="top"
      @submit.native.prevent="onFormSubmit"
    >
      <el-form-item
        :rules="tr_rules.required"
        class="input"
        label=""
        prop="username"
      >
        <template slot="label">
          <div class="input-label">
            <span>*</span> Set your name. It should be unique per team

            <div class="counter">
              {{ 64 - form.username.length }}
            </div>
          </div>
        </template>

        <el-input
          v-model="form.username"
          :rules="tr_rules.required"
          placeholder="Your name..."
          maxlength="64"
          type="text"
          @input="checkFormValidation"
        />
      </el-form-item>

      <div class="text-right">
        <el-button
          :loading="isTeamLoader"
          type="primary"
          class="profile-button"
          @click="onFormSubmit"
        >
          Apply
        </el-button>

        <el-button
          type="default"
          @click="onClose"
        >
          Close
        </el-button>
      </div>
    </el-form>
  </div>
</template>

<script>
import { mapState, mapMutations } from 'vuex'
import Validation from '@/js/mixins/Validation'

export default {
  name: 'ProfileDialog',

  mixins: [Validation],

  data() {
    return {
      form: {
        username: '',
      },
      isValid: false,
    }
  },

  computed: {
    ...mapState({
      isVisible: state => state.teams.isProfileDialogOpened,
      isTeamLoader: state => state.teams.isTeamLoader,
      teamUsers: state => state.categories.categories.teamUsers,
      currentUser: state => state.account.user,
    }),
  },

  watch: {
    teamUsers() {
      if (this.teamUsers) {
        const filtered = this.teamUsers.filter(user => user.id === this.currentUser.id)
        this.form.username = filtered[0].teamusername
      }
    },
  },

  methods: {
    ...mapMutations([
      'profileDialogClosed',
    ]),

    checkFormValidation() {
      this.$refs.updateNameForm.validate((valid) => {
        this.isValid = valid
      })
    },

    onFormSubmit() {
      if (this.isValid) {
        this.$emit('submit', this.form.username)
      }
    },

    onClose() {
      this.$emit('close')
    },
  },
}
</script>

<style lang="scss" scoped>
  @import 'resources/sass/_variables.scss';

  .content {
    padding: 50px 50px 30px 50px;
  }

  .big-modal__title {
    padding: 20px 20px 0 20px;

    h2 {
      margin: 0;
      display: flex;
      height: 100%;
    }

    .navigation {
      margin-left: 50px;
      display: flex;

      .navigation__button {
        font-size: $body;
        padding: 10px;
        color: $dark;
        transition: color .2s ease, background-color .2s ease;
        margin: 0;
        transform: translateY(1px);

        &:hover {
          color: $active;
        }

        &.active {
          color: $active;
          background-color: white;
          border-bottom-right-radius: 0;
          border-bottom-left-radius: 0;
          border: 1px solid #e4e4e4;
          border-bottom-color: white;
        }
      }
    }
  }

  .footer {
    display: flex;
    justify-content: center;
    padding: 20px;
    background-color: $lighter-gray;
    border-top: 1px solid $light-gray;
  }

  .text-right {
    text-align: right;

    .profile-button {
      width: 175px;
    }
  }
</style>
