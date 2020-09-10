<template>
  <el-form
    ref="addUserForm"
    :model="form"
    label-position="top"
    :hide-required-asterisk="true"
  >
    <el-row
      v-for="(user, index) in teamUsers"
      :gutter="11"
      :key="user.id"
      :class="{'d-none': user.is_owner}"
      class="user-info"
    >
      <el-col :span="10">
        <el-form-item
          class="no-margin"
        >
          <template
            v-if="index == 0"
            slot="label"
          >
            <div class="input-label">
              Name, unique per team <span>*</span>
            </div>
          </template>

          <el-input
            :disabled="true"
            :value="user.username"
            readonly
          />
        </el-form-item>
      </el-col>

      <el-col :span="11">
        <el-form-item
          class="no-margin"
        >
          <template
            v-if="index == 0"
            slot="label"
          >
            <div class="input-label">
              Email of new user <span>*</span>
            </div>
          </template>

          <el-input
            :disabled="true"
            :value="user.email"
            readonly
          />
        </el-form-item>
      </el-col>

      <el-col
        :span="3"
        class="text-right"
        :class="{'button-delete-box': index == 0}"
      >
        <el-popover
          :ref="`popover_${user.id}`"
          placement="bottom"
          width="235"
        >
          <p>Remove user from current team?</p>

          <div style="text-align: center; margin: 0">
            <el-button
              type="primary"
              size="medium"
              @click="onDeleteClick(user.id)"
            >
              Remove user
            </el-button>

            <el-button
              size="mini"
              type="text"
              @click="$refs[`popover_${user.id}`][0].doClose()"
            >
              Close
            </el-button>
          </div>

          <el-button
            slot="reference"
            :loading="loading"
            icon="el-icon-delete"
            class="button-delete"
          />
        </el-popover>
      </el-col>
    </el-row>

    <el-row :gutter="11">
      <el-col :span="10">
        <el-form-item
          :rules="tr_rules.invite_name"
          class="input username"
          prop="username"
        >
          <el-input
            v-model="form.username"
            placeholder="User name, unique per team"
            maxlength="64"
            class="username-input"
            type="text"
            @focus="isUsernameCounter = true"
            @blur="isUsernameCounter = false"
            @keyup.native.enter="onAddClick"
          />

          <div
            v-show="isUsernameCounter"
            class="username-counter"
          >
            {{ 64 - form.username.length }}
          </div>
        </el-form-item>
      </el-col>

      <el-col :span="11">
        <el-form-item
          :rules="tr_rules.invite_email"
          class="input"
          prop="email"
        >
          <el-select
            v-model="form.email"
            clearable
            filterable
            no-data-text="No data here"
            placeholder="User email"
            @change="onAutocompleteSelect"
            @keyup.native.enter="onAddClick"
          >
            <el-option
              v-for="item in autocompleteUsers"
              :key="item.id"
              :label="item.email"
              :value="item.email"
            />
          </el-select>
        </el-form-item>
      </el-col>

      <el-col
        :span="3"
        class="button-add-box"
      >
        <el-button
          :loading="loading"
          type="primary"
          class="button-add"
          @click="onAddClick"
        >
          Add
        </el-button>
      </el-col>
    </el-row>
  </el-form>
</template>

<script>
import { mapState } from 'vuex'
import Validation from '@/js/mixins/Validation'

export default {
  name: 'AddUserToTeamForm',

  mixins: [Validation],
  props: {
    loading: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      isUsernameCounter: false,
      form: {
        email: '',
        username: '',
      },
      isFormValid: false,
    }
  },

  computed: {
    ...mapState({
      autocompleteUsers: state => state.teams.autocompleteUsers,
      selectedTeam: state => state.teams.selectedTeam,
      teamUsers: state => state.teams.teamUsers,
    }),
  },

  watch: {
    $route() {
      this.resetForm()
    },
  },

  methods: {
    onDeleteClick(userId) {
      this.$emit('deleteUser', userId)
      this.$refs[`popover_${userId}`][0].doClose()
    },

    resetForm() {
      this.$refs.addUserForm.resetFields()
      this.isFormValid = false
    },

    onAddClick() {
      this.checkFormValidation()

      if (this.isFormValid) {
        this.$emit('addUser')
      }
    },

    checkFormValidation() {
      this.$refs.addUserForm.validate((valid) => {
        if (valid) {
          this.form.teamId = this.selectedTeam.id
          this.$emit('validationChange', this.form)
          this.isFormValid = true
          return true
        }

        this.$emit('validationChange', false)
        this.isFormValid = false
        return false
      })
    },

    onAutocompleteSelect() {
      this.$refs.addUserForm.validateField('email')
    },

    querySearch(queryString, cb) {
      const links = this.autocompleteUsers.map((user) => {
        user.value = user.email
        return user
      })
      const results = queryString ? links.filter(this.createFilter(queryString)) : links
      cb(results)
    },

    createFilter(queryString) {
      return link => (link.value.toLowerCase().indexOf(queryString.toLowerCase()) === 0)
    },
  },
}
</script>

<style lang="scss" scoped>
@import 'resources/sass/_variables';

.no-margin {
  margin: 0;
}

.delete__button {
  color: $dark;
  width: 100%;
  font-size: $title;

  &:hover {
    color: $red;
  }
}

.button-delete-box {
  padding-top: 29px;
}

.user-info {
  margin-bottom: 10px;
}

.input.username {
  .username-counter {
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 12px;
    line-height: 1;
  }
}

.username-input:focus + .username-counter {
  visibility: visible;
}

.button-add,
.button-delete {
  display: block;
  width: 100%;
}
</style>
