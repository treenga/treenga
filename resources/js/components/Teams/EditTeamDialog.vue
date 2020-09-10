<template>
  <div>
    <el-tabs
      v-model="activeTab"
    >
      <el-tab-pane
        label="Details"
        name="first"
      >
        <create-team-form
          ref="editTeamForm"
          :team="selectedTeam"
          @validationChange="onDetailsValidationChange($event)"
          @submit="onDetailsFormSubmit"
        />

        <div
          class="text-right"
        >
          <el-button
            :loading="isTeamLoader"
            type="primary"
            class="apply-button"
            @click="onDetailsFormSubmit"
          >
            Apply
          </el-button>

          <el-button
            type="default"
            @click="onDialogClose"
          >
            Close
          </el-button>
        </div>
      </el-tab-pane>

      <el-tab-pane
        label="Delete team"
        name="second"
      >
        <p class="regular-p">
          This action will delete current team and EVERYTHING created withing this team,
          including all private and public tasks, all team members, all categories and all
          team settings. In most cases it is better to just remove users from a team,
          so nothing is lost and can be quickly transformed to an active team.
        </p>

        <p class="regular-p">
          If you are really sure this is what you want to do, type DELETE in a field below and
          click Delete. This action is unrecoverable!
        </p>

        <el-form
          ref="deleteTeamForm"
          :hide-required-asterisk="true"
          :model="form"
          status-icon
          novalidate
          class="delete-team-form"
          @submit="onDeleteButtonClick"
        >
          <el-form-item
            :rules="tr_rules.delete"
            class="input"
            prop="deleteInput"
          >
            <el-input
              ref="deleteInput"
              v-model="form.deleteInput"
              placeholder="Type DELETE"
              @keyup.native.enter="onDeleteButtonClick"
            />
          </el-form-item>

          <el-button
            :loading="isTeamLoader"
            type="danger"
            class="footer__button"
            @click="onDeleteButtonClick"
          >
            Delete
          </el-button>

          <el-button
            type="default"
            @click="onDialogClose"
          >
            Close
          </el-button>
        </el-form>
      </el-tab-pane>
    </el-tabs>

    <!--add-user-to-team-form
      v-show="currentForm === 'people'"
      ref="addUserForm"
      :loading="isTeamLoader"
      @validationChange="onPeopleFormValidationChange($event)"
      @deleteUser="onUserDeleteClicK($event)"
      @addUser="onPeopleFormSubmit"
    /-->

    <!--div
      v-show="currentForm === 'people'"
      class="paid-text"
    >
      This is a paid feature. <a href="javascript:;">Pricing</a>
    </div-->
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from 'vuex'
import Validation from '@/js/mixins/Validation'
import CreateTeamForm from './CreateTeamForm'
import AddUserToTeamForm from './AddUserToTeamForm'

export default {
  name: 'EditTeamDialog',

  mixins: [Validation],

  components: {
    CreateTeamForm,
    AddUserToTeamForm,
  },

  data() {
    return {
      form: {
        deleteInput: '',
      },
      activeTab: 'first',
    }
  },

  computed: {
    ...mapState({
      isVisible: state => state.teams.isEditDialogOpened,
      selectedTeam: state => state.teams.selectedTeam,
      autocompleteUsers: state => state.teams.autocompleteUsers,
      isTeamLoader: state => state.teams.isTeamLoader,
    }),
  },

  watch: {
    isVisible(val) {
      if (!val) {
        // wait for animation to end
        setTimeout(() => {
          this.currentForm = 'people'
        }, 300)
      }
    },
  },

  methods: {
    ...mapActions([
      'getTeamAutocompleteUsers',
      'addUserToTeam',
      'deleteUserFromTeam',
      'deleteTeam',
      'deleteUserTeam',
    ]),

    ...mapMutations([
      'editTeamDialogClosed',
      'alertError',
      'alertInfo',
    ]),

    onDialogOpened() {
      this.getTeamAutocompleteUsers(this.selectedTeam.id)
    },

    onDialogClose() {
      this.$emit('close')
      this.resetDetailsForm()
      this.resetDeleteForm()
      this.deleteInput = ''
    },

    onDetailsValidationChange(status) {
      this.detailsForm = status
    },

    onPeopleFormValidationChange(status) {
      this.peopleForm = status
    },

    onCancelClick() {
      this.onDialogClose()
    },

    onUserDeleteClicK(userId) {
      this.deleteUser(userId)
    },

    deleteUser(userId) {
      this.deleteUserFromTeam(userId).then((response) => {
        if (response.data) {
          this.getTeamAutocompleteUsers(this.selectedTeam.id)
        }
      })
    },

    onPeopleFormSubmit() {
      this.$emit('submitPeople', this.peopleForm)
    },

    resetDetailsForm() {
      this.$refs.editTeamForm.resetForm()
    },

    resetDeleteForm() {
      this.$refs.deleteTeamForm.resetFields()
    },

    resetPeopleForm() {
      this.$refs.addUserForm.resetForm()
    },

    onDetailsFormSubmit() {
      this.$refs.editTeamForm.onFormSubmit()
      if (!this.detailsForm) return

      this.$emit('submitDetails', this.detailsForm)
      this.detailsForm = false
    },

    onPeopleClick() {
      this.currentForm = 'people'
    },

    onDetailsClick() {
      this.currentForm = 'details'
    },

    onDeleteClick() {
      this.currentForm = 'delete'
    },

    onDeleteButtonClick() {
      this.$refs.deleteTeamForm.validate((valid) => {
        if (valid) {
          this.deleteTeam({ teamId: this.$route.params.teamSlug }).then((response) => {
            if (response.data) {
              this.deleteInput = ''
              this.onDialogClose()
              this.deleteUserTeam({ teamId: this.selectedTeam.id })
            }
          })
        }

        return false
      })
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
    left: 20px;
    font-size: 15px;

    a {
      color: #1D76C4;
    }
  }

  .regular-p {
    margin: 0 0 20px 0;
    word-break: break-word;
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
</style>
