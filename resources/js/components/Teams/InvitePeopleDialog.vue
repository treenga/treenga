<template>
  <div>
    <el-tabs
      v-model="activeTab"
    >
      <el-tab-pane
        label="People"
        name="first"
      >
        <add-user-to-team-form
          ref="addUserForm"
          :loading="isTeamLoader"
          @validationChange="onPeopleFormValidationChange($event)"
          @deleteUser="onUserDeleteClicK($event)"
          @addUser="onPeopleFormSubmit"
        />
      </el-tab-pane>

      <div class="invite-button-box">
        <el-button
          type="default"
          @click="onDialogClose"
        >
          Close
        </el-button>
      </div>
    </el-tabs>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import Validation from '@/js/mixins/Validation'
import AddUserToTeamForm from './AddUserToTeamForm.vue'

export default {
  name: 'InvitePeopleDialog',

  components: {
    AddUserToTeamForm,
  },

  mixins: [Validation],

  data() {
    return {
      activeTab: 'first',
    }
  },

  computed: {
    ...mapState({
      selectedTeam: state => state.teams.selectedTeam,
      autocompleteUsers: state => state.teams.autocompleteUsers,
      isTeamLoader: state => state.teams.isTeamLoader,
      teamAuthors: state => state.categories.categories.teamAuthors,
      teamUsers: state => state.categories.categories.teamUsers,
    }),
  },

  watch: {
    selectedTeam() {
      this.getTeamAutocompleteUsers(this.selectedTeam.id)
    },
  },

  methods: {
    ...mapActions([
      'getTeamAutocompleteUsers',
      'addUserToTeam',
      'deleteUserFromTeam',
      'deleteUserTeam',
    ]),

    ...mapMutations([
      'alertError',
      'alertInfo',
      'updatedCategories',
    ]),

    onPeopleFormValidationChange(status) {
      this.peopleForm = status
    },

    onUserDeleteClicK(userId) {
      this.deleteUser(userId)
    },

    deleteUser(userId) {
      this.updatedCategories({
        teamUsers: this.teamUsers.filter(user => user.id !== userId),
        teamAuthors: this.teamAuthors.filter(user => user.id !== userId)
      });
      this.deleteUserFromTeam(userId).then((response) => {
        if (response.data) {
          this.getTeamAutocompleteUsers(this.selectedTeam.id)
          this.$emit('deletePeople')
        }
      })
    },

    onPeopleFormSubmit() {
      this.$emit('submitPeople', this.peopleForm)
    },

    resetPeopleForm() {
      this.getTeamAutocompleteUsers(this.selectedTeam.id)
      this.$refs.addUserForm.resetForm()
    },

    onDialogClose() {
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

  .text-right {
    text-align: right;
  }

  .apply-button {
    width: 175px;
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

  .paid-text {
    position: absolute;
    right: 10px;
    top: 22px;
    font-size: 14px;
    z-index: 1;
    font-weight: 500;

    a {
      color: #1D76C4;
    }
  }

  .regular-p {
    margin: 0 0 20px 0;
  }

  .delete-team-form {
    display: flex;
    justify-content: flex-end;
    padding-bottom: 18px;

    .el-form-item {
      margin: 0;
    }

    .el-input {
      width: 150px;
    }

    .el-button {
      width: 175px;
      margin-left: 10px;
    }
  }

  .invite-button-box {
    text-align: right;
  }
</style>
