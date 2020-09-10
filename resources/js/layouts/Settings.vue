<template>
  <el-row :gutter="0">
    <el-col
      :span="6"
      class="left-col settings-col"
    >
      <div class="menu">
        <div class="menu-links">
          <h4>Settings</h4>

          <div class="menu-link">
            <router-link
              :class="{active: $route.path === '/settings/general'}"
              class="link"
              to="/settings/general"
            >
              <i class="icon-user"/> General
            </router-link>
          </div>
        </div>

        <button
          class="hover-button menu-logout__btn"
          @click="onLogoutClick"
        >
          <i
            class="icon-logout with-margin"
          />Log out
        </button>
      </div>
    </el-col>

    <el-col :span="18">
      <el-scrollbar>
        <router-view/>
      </el-scrollbar>
    </el-col>
  </el-row>
</template>

<script>
import { mapActions } from 'vuex'

export default {
  name: 'Settings',

  methods: {
    ...mapActions([
      'logout',
    ]),

    onLogoutClick() {
      this.logout()
    },
  },
}
</script>

<style lang="scss" scoped>
  @import 'resources/sass/_variables.scss';

  .with-margin {
    margin-right: 5px;
  }

  .left-col {
    background-color: $light-blue;
    height: 100vh;

    .menu {
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding-left: 40px;

      .menu-links {
        h4 {
          color: $yellow;
          margin-bottom: 40px;
        }

        .menu-link {
          margin-bottom: 25px;

          .link {
            color: $bright-blue;
            text-decoration: none;
            padding: 5px;
            font-size: $body;
            font-weight: 700;

            &.active {
              border-bottom: 2px solid $yellow;
            }

            &:hover {
              color: $gray-active;
            }
          }
        }
      }

      .menu-logout__btn {
        margin-bottom: 56px;
        width: 78px;
        height: 36px;
      }
    }
  }
</style>
