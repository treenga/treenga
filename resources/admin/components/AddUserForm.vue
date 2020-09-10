<template>
  <div class="form-wrapper">
    <el-form
      ref="addUserForm"
      :model="form"
      novalidate
      class="add-user-form"
      @keyup.native.enter="onFormSubmit"
    >
      <el-form-item
        :rules="tr_rules.email"
        class="input is-no-asterisk"
        prop="email"
      >
        <template slot="label">
          <div class="input-label">
            User email <span>*</span>
          </div>
        </template>

        <el-input
          v-model="form.email"
          :disabled="!!$route.params.id"
          :autofocus="true"
          placeholder="Email..."
          type="text"
        />
      </el-form-item>

      <el-form-item
        class="switch"
        prop="is_team_author"
      >
        <template slot="label">
          <div class="input-label">
            Can create new teams
          </div>
        </template>

        <el-switch
          v-model="form.is_team_author"
          active-color="#3a8ee6"
          @change="onSwitchChange"
        />
      </el-form-item>

      <div class="buttons d-flex">
        <div>
          <el-button
            v-if="!$route.params.id"
            type="primary"
            @click="onFormSubmit"
          >
            Add user
          </el-button>

          <el-button
            v-if="!!$route.params.id"
            icon="el-icon-back"
            @click="onCancelClick"
          >
            Back
          </el-button>
          <el-button
            v-else
            @click="onCancelClick"
          >
            Cancel
          </el-button>
        </div>
        <el-popover
          :ref="'popover'"
          placement="top"
        >
          <p>Remove user? It will be removed from all teams</p>

          <div style="text-align: center; margin: 0">
            <el-button
              type="danger"
              size="medium"
              @click="onRemoveUserClick"
            >
              Remove user
            </el-button>

            <el-button
              size="medium"
              type="text"
              @click="$refs['popover'].doClose()"
            >
              Cancel
            </el-button>
          </div>
          <el-button
            slot="reference"
            class="remove-user hover-button--standart"
            type="danger"
          >
            Remove user
          </el-button>
        </el-popover>
      </div>
    </el-form>
  </div>
</template>

<script>
import { mapActions, mapState } from 'vuex'
import Validation from '@/js/mixins/Validation'

export default {
  name: 'AddUserForm',

  mixins: [Validation],

  data() {
    return {
      form: {
        id: null,
        email: null,
        is_team_author: true,
      },
    }
  },

  created() {
    const { id } = this.$route.params;
    if (!id) return

    this.getOneUser(id).then((response) => {
      if (response.data) {
        this.form = response.data
      }
    });
  },

  methods: {
    ...mapActions([
      'addUser',
      'deleteUser',
      'getOneUser',
    ]),

    ...mapState({
      profile: state => state.users.user,
    }),

    onSwitchChange() {
      if (!this.$route.params.id) { return }
      
      this.addUser(this.form).then(response => response)
    },

    onFormSubmit() {
      this.$refs.addUserForm.validate((valid) => {
        if (valid) {
          this.addUser(this.form).then((response) => {
            if (response.data) {
              this.$router.push('/admin')
            }
          })
        }
      })
    },

    onCancelClick() {
      this.$router.go(-1)
    },

    onRemoveUserClick() {
      this.$refs.popover.doClose()
      this.$refs.addUserForm.validate((valid) => {
        if (valid) {
          this.deleteUser(this.form).then((response) => {
            if (response.data) {
              this.$router.push('/admin')
            }
          })
        }
      })
    },
  },
}
</script>
<style scoped lang="scss">
.form-wrapper {
  margin: 20px;

  .is-no-asterisk {
    ::v-deep label.el-form-item__label {
      margin-bottom: 0;

      span {
        color: red;
      }
    }
  }
  .switch {
    display: flex;
    justify-content: space-between;

    &::before, &::after {
      display: none;
    }

    ::v-deep label {
      line-height: normal;
    }

    ::v-deep .el-form-item__content {
      line-height: normal;
    }
  }
  .buttons {
    justify-content: space-between;

    .hover-button--standart {
      background-color: transparent !important;
      text-decoration: underline;
      border-radius: 3px;
      border: none;
      color: #F56C6C;
      transition: background-color 0.3s ease, color 0.3s ease;

      &:hover {
        background-color: #FFDCDC !important;
      }
    }
  }
}

.el-popover {
  p {
    font-size: inherit;
    font-weight: inherit;
  }
}
</style>
