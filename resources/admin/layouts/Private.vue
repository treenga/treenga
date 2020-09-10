<template>
  <div>
    <nav
      id="sidenav-main"
      class="navbar navbar-vertical fixed-left navbar-expand-md navbar-light bg-white"
    >
      <div class="container-fluid">
        <!-- Toggler -->
        <button
          class="navbar-toggler"
          type="button"
          data-toggle="collapse"
          data-target="#sidenav-collapse-main"
          aria-controls="sidenav-main"
          aria-expanded="false"
          aria-label="Toggle navigation"
          @click="onToggleDropdown"
        >
          <span class="navbar-toggler-icon" />
        </button>
        <!-- Brand -->
        <router-link
          to="/admin"
          class="navbar-brand pt-0 font-weight-bold align-items-center d-flex
          justify-content-center"
        >
          <i class="ni ni-atom" />
          &nbsp;
          Treenga
        </router-link>
        <!-- User -->
        <ul class="nav align-items-center d-md-none">
          <li class="nav-item dropdown">
            <div
              class="dropdown-menu dropdown-menu-arrow dropdown-menu-right"
              aria-labelledby="navbar-default_dropdown_1"
            >
              <a
                class="dropdown-item"
                href="#"
              >
                Action
              </a>
              <a
                class="dropdown-item"
                href="#"
              >
                Another action
              </a>
              <div class="dropdown-divider" />
              <a
                class="dropdown-item"
                href="#"
              >
                Something else here
              </a>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a
              class="nav-link"
              href="#"
              role="button"
              data-toggle="dropdown"
              aria-haspopup="true"
              aria-expanded="false"
            >
              <div class="media align-items-center">
                <span class="avatar avatar-sm rounded-circle">
                  <img
                    alt="Image placeholder"
                    src="//placehold.it/64"
                  >
                </span>
              </div>
            </a>
            <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-right">
              <div class=" dropdown-header noti-title">
                <h6 class="text-overflow m-0">Welcome!</h6>
              </div>
              <a
                href="#"
                class="dropdown-item"
              >
                <i class="ni ni-single-02" />
                <span>My profile</span>
              </a>
              <a
                href="#"
                class="dropdown-item"
              >
                <i class="ni ni-settings-gear-65" />
                <span>Settings</span>
              </a>
              <a
                href="#"
                class="dropdown-item"
              >
                <i class="ni ni-calendar-grid-58" />
                <span>Activity</span>
              </a>
              <a
                href="#"
                class="dropdown-item"
              >
                <i class="ni ni-support-16" />
                <span>Support</span>
              </a>
              <div class="dropdown-divider" />
              <a
                href="#!"
                class="dropdown-item"
              >
                <i class="ni ni-user-run" />
                <span>Logout</span>
              </a>
            </div>
          </li>
        </ul>
        <!-- Collapse -->
        <div
          id="sidenav-collapse-main"
          :class="{'show': dropdown}"
          class="collapse navbar-collapse"
        >
          <!-- Collapse header -->
          <div class="navbar-collapse-header d-md-none">
            <div class="row">
              <div class="col-6 collapse-brand">
                <router-link
                  :to="'/admin'"
                >
                  <i class="ni ni-atom" />
                  &nbsp;
                  Treenga
                </router-link>
              </div>
              <div class="col-6 collapse-close">
                <button
                  type="button"
                  class="navbar-toggler"
                  data-toggle="collapse"
                  data-target="#sidenav-collapse-main"
                  aria-controls="sidenav-main"
                  aria-expanded="false"
                  aria-label="Toggle sidenav"
                  @click="onToggleDropdown"
                >
                  <span />
                  <span />
                </button>
              </div>
            </div>
          </div>
          <!-- Navigation -->
          <ul class="navbar-nav">
            <li class="nav-item">
              <router-link
                to="/admin"
                class="nav-link active"
              >
                <i class="ni ni-single-02 text-blue" /> Users
              </router-link>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- Main content -->
    <div class="main-content">
      <nav
        id="navbar-main"
        class="navbar navbar-top navbar-expand-md navbar-dark"
      >
        <!-- User -->
        <ul class="navbar-nav align-items-center d-none d-md-flex">
          <li class="nav-item dropdown">
            <a
              class="nav-link pr-0"
              href="#"
              role="button"
              data-toggle="dropdown"
              aria-haspopup="true"
              aria-expanded="false"
              @click="onToggleDropdown"
            >
              <div class="media align-items-center">
                <span class="avatar avatar-sm rounded-circle">
                  <img
                    alt="Image placeholder"
                    src="//placehold.it/64"
                  >
                </span>
                <div class="media-body ml-2 d-none d-lg-block">
                  <span class="mb-0 text-sm font-weight-bold">
                    {{ account.name }}
                  </span>
                </div>
              </div>
            </a>
            <div
              :class="{'show': dropdown}"
              class="dropdown-menu dropdown-menu-arrow dropdown-menu-right"
            >
              <div class="dropdown-header noti-title">
                <h6 class="text-overflow m-0">Welcome!</h6>
              </div>
              <router-link
                to="/admin/change-password"
                class="dropdown-item"
                @click.native="changePassword"
              >
                <i class="ni ni-single-02" />
                <span>Change password</span>
              </router-link>
              <div class="dropdown-divider" />
              <a
                href="javascript:;"
                class="dropdown-item"
                @click="logout"
              >
                <i class="ni ni-user-run" />
                <span>Logout</span>
              </a>
            </div>
          </li>
        </ul>
      </nav>
      <transition
        name="component-fade"
        mode="out-in"
      >
        <router-view />
      </transition>
    </div>
  </div>
</template>
<script>
import { mapActions, mapState } from 'vuex'

export default {
  data() {
    return {
      dropdown: false,
    };
  },
  computed: {
    ...mapState({
      account: state => state.auth.account,
    }),
  },
  created() {
    this.getUserData();
  },
  methods: {
    ...mapActions([
      'logout',
      'getUserData',
    ]),
    onToggleDropdown() {
      this.dropdown = !this.dropdown;
    },
    changePassword() {
      this.dropdown = false
    },
  },
}
</script>
<style style="sass" scoped>
.container-fluid {
  justify-content: flex-end;
}
</style>