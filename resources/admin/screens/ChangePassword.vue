<template>
  <div>
    <!-- Header -->
    <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
      <div class="container-fluid">
        <div class="header-body">
          <h2>Change password</h2>
        </div>
      </div>
    </div>
    <!-- Page content -->
    <div class="container-fluid mt--7">
      <div class="row">
        <div class="col">
          <div class="card-wrapper">
            <div class="card shadow">
              <change-password-form />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import changePasswordForm from '../components/Settings/ChangePasswordForm.vue'

export default {
  name: 'ChangePassword',
  components: {
    'change-password-form': changePasswordForm,
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
