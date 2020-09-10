<template>
  <div>
    <!-- Header -->
    <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
      <div class="container-fluid">
        <div class="header-body">
          <h2>Add User</h2>
        </div>
      </div>
    </div>
    <!-- Page content -->
    <div class="container-fluid mt--7">
      <div class="row">
        <div class="col">
          <div class="card-wrapper">
            <div class="card shadow">
              <add-user-form />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import AddUserForm from '../components/AddUserForm.vue'

export default {
  name: 'AddUser',
  components: {
    'add-user-form': AddUserForm,
  },
}
</script>
<style lang="scss" scoped>
.card-wrapper {
  display: flex;
  justify-content: center;

  .card {
    width: 600px;
  }
}
</style>
