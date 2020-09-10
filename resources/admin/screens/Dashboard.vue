<template>
  <div>
    <!-- Header -->
    <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
      <div class="container-fluid">
        <div class="header-body">
          <form
            class="navbar-search navbar-search-dark
              form-inline mr-3 d-none d-md-flex ml-lg-auto"
          >
            <div class="form-group mb-0">
              <div class="input-group input-group-alternative">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-search" /></span>
                </div>
                <input
                  v-model="searchQuery"
                  class="form-control"
                  placeholder="Search"
                  type="text"
                  autofocus="autofocus"
                >
              </div>
            </div>
            <router-link :to="'/admin/add-user'">
              <el-button type="primary">
                Add new user
              </el-button>
            </router-link>
          </form>
        </div>
      </div>
    </div>
    <!-- Page content -->
    <div class="container-fluid mt--7">
      <!-- Table -->
      <div class="row">
        <div class="col">
          <div class="card shadow">
            <div class="card-header border-0 py-4" />
            <div class="table-responsive">
              <table class="table align-items-center table-flush">
                <thead class="thead-light">
                  <tr>
                    <th scope="col">Email</th>
                    <th scope="col">Date of registration</th>
                    <th scope="col">Can create new teams</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(user, index) in searchable"
                    :key="`${index}-user`"
                  >
                    <th scope="row">
                      <span
                        class="mb-0 pointer"
                        @click="onClickUser(user)"
                      >{{ user.email }}</span>
                    </th>
                    <td>
                      {{ user.date_of_reg }}
                    </td>
                    <td>
                      <i
                        v-if="user.is_team_author"
                        class="el-icon-check"
                      />
                    </td>
                  </tr>
                  <tr v-if="!searchable.length">
                    <td >
                      <span class="mb-0 text-sm">
                        UPS! not found.
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="card-footer py-4" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { mapActions, mapState } from 'vuex'
import 'element-ui/lib/theme-chalk/index.css';

export default {
  data() {
    return {
      searchQuery: '',
    }
  },
  computed: {
    searchable() {
      return this.users.filter((user) => {
        return user.email.toLowerCase().indexOf(this.searchQuery.toLowerCase()) + 1;
      })
    },
    ...mapState({
      users: state => state.users.users,
    }),
  },
  mounted() {
    this.getAllUsers()
  },
  methods: {
    onClickUser(user) {
      this.$router.push(`/admin/user/${user.id}`);
    },
    ...mapActions([
      'getAllUsers',
    ]),
  },
}
</script>

<style lang="scss" scoped>
.header-body {
  .navbar-search {
    a {
      margin-left: 40px;
    }
  }
}
</style>
