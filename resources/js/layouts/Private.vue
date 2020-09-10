<template>
  <div>
    <el-row
      :gutter="0"
    >
      <el-col
        :span="3"
        class="menu-col"
      >
        <div class="menu-wrapper">
          <div class="logo flex-center">
            <img
              :src="require('@/img/logo.svg')"
              class="logo-image"
              alt="Trevially"
            >
          </div>

          <div class="teams-wrapper">
            <div class="teams text-center">
              <el-scrollbar class="all-teams-scroll">
                <div class="my-teams">
                  <div class="team-scroll text-left">
                    <div
                      v-for="(team, index) in teams.userteams"
                      :key="index"
                    >
                      <router-link
                        v-if="team.private"
                        :class="{'active active-private': $route.params.teamSlug === team.slug}"
                        :to="`/${team.slug}`"
                        class="teams__btn"
                        @click.native="onTeamClick(team)"
                      >
                        <span>
                          <i class="icon-lock" />
                          {{ team.name }}
                        </span>
                      </router-link>
                    </div>
                  </div>

                  <h4 class="teams-title">my teams</h4>

                  <div class="team-scroll text-left">
                    <div
                      v-for="(team, index) in teams.userteams"
                      :key="index"
                    >
                      <router-link
                        v-if=" ! team.private"
                        :class="{'active': $route.params.teamSlug === team.slug}"
                        :to="`/${team.slug}`"
                        class="teams__btn"
                        @click.native="onTeamClick(team)"
                      >
                        <span
                          :class="{'has-notifications': team.user_notifications_count > 0}"
                        >
                          {{ team.name }}
                        </span>
                      </router-link>
                    </div>
                  </div>

                  <el-popover
                    v-if="user.is_team_author"
                    v-model="createTeamPopover"
                    placement="right"
                    trigger="manual"
                    width="390"
                    @show="onResetTeamForm"
                  >
                    <create-team-dialog
                      ref="createTeamDialog"
                      @submit="onCreateTeamFormSubmit($event)"
                      @close="createTeamPopover = false"
                    />

                    <button
                      slot="reference"
                      :class="{'no-top-margin': teams.userteams && teams.userteams.length == 1}"
                      class="hover-button hover-button--standart teams__btn--add"
                      @click="onAddTeamClick"
                    >
                      + Team
                    </button>
                  </el-popover>
                </div>

                <div
                  v-show="teams.shared && teams.shared.length"
                  class="shared-teams"
                >
                  <h4 class="teams-title">shared with me</h4>

                  <div class="team-scroll text-left">
                    <router-link
                      v-for="(team, index) in teams.shared"
                      :key="index"
                      :class="{active: $route.params.teamSlug === team.slug}"
                      :to="`/${team.slug}`"
                      class="teams__btn"
                      @click.native="onTeamClick(team)"
                    >
                      <span
                        :class="{'has-notifications': team.user_notifications_count > 0}"
                      >
                        {{ team.name }}
                      </span>
                    </router-link>
                  </div>
                </div>
              </el-scrollbar>
            </div>
          </div>

          <div class="settigns">
            <router-link
              :class="{active: $route.path.includes('/settings/') }"
              tag="button"
              class="settings__btn team_btn"
              to="/settings/general"
            >
              <i
                class="icon-account"
              />
              Account
            </router-link>

            <a
              class="settings__btn white"
              target="_blank"
              href="https://help.treenga.com/"
            >
              <i class="el-icon-question"/>Help
            </a>
          </div>
        </div>
      </el-col>

      <el-col
        v-if=" ! $route.params.teamSlug || ($route.params.teamSlug && user.id && isAccess === true)"
        :span="21"
      >
        <div
          v-if="user.is_team_author && isTeamsEmpty && ! ($route.path.indexOf('private') + 1)"
          class="empty-team"
        >
          <el-button
            type="success"
            icon="el-icon-plus"
            class="add-team-button"
            @click="onAddTeamClick"
          >
            <br><br>
            Create new team
          </el-button>
        </div>

        <router-view
          v-else
          :key="$route.fullPath"
        />
      </el-col>

      <el-col
        v-if="$route.params.teamSlug && user.id && isAccess === false && redirect === true"
        :span="21"
        class="access-alert-box"
      >
        <el-alert
          :closable="false"
          title="No access"
          type="warning"
          description="There is no such a team or you don't have permittion to access it"
          show-icon
        />
      </el-col>
    </el-row>
  </div>
</template>

<script>
import {
  mapState, mapGetters, mapActions, mapMutations,
} from 'vuex'
import { CreateTeamDialog } from '@/js/components'
import pusher from '@/js/pusher'

export default {
  name: 'Private',

  components: {
    CreateTeamDialog,
  },

  data() {
    return {
      privateTeam: {
        current: true,
        id: 'private',
        is_owner: true,
        name: 'private',
        slug: 'private',
        user_notifications_count: 0,
        username: 'private',
      },

      createTeamPopover: false,
      redirect: true,
    }
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
      teams: state => state.teams.teams,
      selectedTeam: state => state.teams.selectedTeam,
      isAccess: state => state.teams.isAccess,
    }),

    currentTeamId() {
      return this.selectedTeam.id
    },

    isTeamsEmpty() {
      return this.teams.userteams && !this.teams.userteams.length
        && this.teams.shared && !this.teams.shared.length
    },
  },

  watch: {
    currentTeamId(val) {
      if (val) {
        if (this.currentTeamId === 'private') {
          this.getPrivateTeamInfo();
        } else {
          this.getTeamInfo();
        }
      }
    },

    user(val) {
      if (val.id) {
        pusher.openUserChannel()
      }
    },
  },

  created() {
    this.getTeamsList().then(() => {
      this.findLastSelectedTeam()
    })
  },

  methods: {
    ...mapMutations([
      'createTeamDialogOpened',
      'createTeamDialogClosed',
      'teamSelected',
      'teamAdded',
      'categorySelected',
      'accessForbidden',
      'accessGranted',
    ]),

    ...mapActions([
      'getUserData',
      'getTeamsList',
      'createTeam',
      'getTeamInfo',
      'onAlertClose',
    ]),

    ...mapActions('private_teams', [
      'getPrivateTeamInfo',
    ]),

    onResetTeamForm() {
      this.$refs.createTeamDialog.resetForm()
    },

    onAddTeamClick() {
      this.createTeamPopover = ! this.createTeamPopover
    },

    onCreateTeamFormSubmit(form) {
      this.createTeam(form).then((response) => {
        if (response.data) {
          this.createTeamPopover = false
          this.$refs.createTeamDialog.resetForm()
          this.getTeamsList().then((listResp) => {
            if (listResp.data) {
              this.teamSelected({
                ...response.data,
                is_owner: true,
                username: response.data.auth_username,
              })
              this.$router.push(`/${response.data.slug}`)
            }
          })
        }
      })
    },

    setPrivateTeam() {
      this.redirect = true

      for (const sharedTeam of this.teams.shared) {
        if (sharedTeam.id == this.selectedTeam.id) {
          this.redirect = false
        }
      }

      if (this.redirect) {
        for (const team of this.teams.userteams) {
          if (team.private) {
            this.onTeamClick(team)
            if (this.$router.history.current.path != '/private') {
              this.$router.push('/private')
            }
          }
        }
      }
    },

    onTeamClick(team) {
      this.redirect = true

      for (const sharedTeam of this.teams.shared) {
        if (sharedTeam.id == team.id) {
          this.redirect = false
        }
      }

      this.categorySelected({})
      this.teamSelected(team)
    },

    findLastSelectedTeam() {
      let currentTeams = []

      if (this.isTeamsEmpty) {
        return
      }

      if (this.$route.path === '/') {
        Object.values(this.teams).forEach((team) => {
          if (!currentTeams.length) {
            currentTeams = team.filter(innerTeam => innerTeam.current)
          }
        })

        if (!currentTeams.length) {
          if (this.teams.userteams.length) {
            currentTeams = this.teams.userteams
          } else if (this.teams.shared.length) {
            currentTeams = this.teams.shared
          }
        }
      } else if (this.$route.params.teamSlug) {
        Object.values(this.teams).forEach((team) => {
          if (!currentTeams.length) {
            currentTeams = team
              .filter(innerTeam => innerTeam.slug === this.$route.params.teamSlug)
          }
        })
      }

      if (currentTeams.length) {
        this.teamSelected(currentTeams[0])
        if (this.$route.path != ('/' + currentTeams[0].slug)) {
          this.$router.push(`/${currentTeams[0].slug}`)
        }
      } else {
        this.accessForbidden()
      }
    },

    openInNewTab(url) {
      const routeData = this.$router.resolve(url)
      window.open(routeData.href, '_blank')
    },

    formatTextPrice(price = '') {
      const arr = price.split('')
      arr.splice(arr[0] === '-' ? 1 : 0, 0, '$')

      return arr.join('')
    },
  },
}
</script>

<style lang="scss" scoped>
@import "resources/sass/_variables.scss";

.menu-col {
  background-color: $dark-blue;
  height: 100vh;

  .menu-wrapper {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 6px;
    height: 100%;

    .logo {
        img {
          margin: 26px 0;
          width: calc(80px + 3.2vw);
          height: calc(20px + 0.8vw);
        }
      }

    .teams-wrapper {
      width: 100%;
      height: 100%;
      max-height: calc(100vh - 92px - 36px);
      overflow: hidden;

      .teams {
        display: flex;
        flex-direction: column;

        &__btn {
          display: block;
          text-decoration: none;
          outline: none;
          background-color: $team-not-active;
          color: $team-color-not-active;
          border: none;
          text-align: left;
          padding: 15px;
          margin: 5px 0;
          font-size: $small-body;
          font-weight: 700;
          max-width: calc(100% - 17px);

          &:hover,
          &:focus {
            background-color: $blue;
            color: $team-color-active;
          }

          &.active {
            color: $button-selected;
            font-weight: 500;
            background-color: $light-blue;

            &-private {
              background-color: $light-private;
            }
          }
        }

        .hover-button {
          margin-top: 25px;
          height: auto;

          &.no-top-margin {
            margin-top: 0;
          }
        }
      }
    }

    .settigns {
      width: 100%;
      height: 92px;

      .settings__btn {
        outline: none;
        display: block;
        background-color: transparent;
        border: none;
        width: 100%;
        text-align: left;
        color: $gray-not-active;
        padding: 15px 15px 15px 40px;
        font-size: $small-body;
        font-weight: 500;

        &.active {
          color: white;
          background-color: $light-blue;
        }

        &.white {
          color: white;
          text-align: center;
          width: auto;
          padding: 15px;

          i {
            margin-right: .4rem;
          }
        }

        &.team_btn {
          background-color: $team-not-active;
          color: $team-color-not-active;
          font-size: $small-body;
          font-weight: 700;
          text-align: left;
          padding: 15px;

          &:hover,
          &:focus {
            background-color: $blue;
            color: $team-color-active;
          }

          &.active {
            color: $button-selected;
            background-color: $light-blue;
          }
        }
      }
    }
  }

  .teams-title {
    color: #576F8E;
    font-size: 13px;
    margin-top: 15px;
    margin-bottom: 10px;
  }

  .my-teams,
  .shared-teams {
    padding-bottom: 30px;
  }
}

.empty-team {
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;

  .add-team-button {
    padding:20px;
    font-size:18px
  }
}

.access-alert-box {
  display: flex;
  height: 100vh;
  justify-content: center;
  align-items: center;

  .el-alert {
    width: 430px;
  }
}

.all-teams-scroll {
  height: calc(100vh - 156px);
}
</style>
