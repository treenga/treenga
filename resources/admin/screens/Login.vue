<template>
  <div>
    <div class="main-content">
      <!-- Header -->
      <div class="header bg-gradient-primary py-7 py-lg-8">
        <div class="container">
          <div class="header-body text-center mb-7">
            <div class="row justify-content-center">
              <div class="col-lg-5 col-md-6">
                <h1 class="text-white">Treenga</h1>
                <p class="text-lead text-light">Use these awesome forms to login.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="separator separator-bottom separator-skew zindex-100">
          <svg
            x="0"
            y="0"
            viewBox="0 0 2560 100"
            preserveAspectRatio="none"
            version="1.1"
            xmlns="http://www.w3.org/2000/svg"
          >
            <polygon
              class="fill-default"
              points="2560 0 2560 100 0 100"
            />
          </svg>
        </div>
      </div>
      <!-- Page content -->
      <div class="container mt--8 pb-5">
        <div class="row justify-content-center">
          <div class="col-lg-5 col-md-7">
            <div class="card bg-secondary shadow border-0">
              <div class="card-body px-lg-5 py-lg-5">
                <form
                  role="form"
                  novalidate="novalidate"
                  @submit.prevent="onSubmitForm">
                  <div class="form-group mb-3">
                    <div
                      :class="{'is-invalid': errors.has('name')}"
                      class="input-group input-group-alternative"
                    >
                      <input
                        v-validate="'required'"
                        v-model="auth.name"
                        class="form-control"
                        placeholder="Login"
                        type="text"
                        name="name">
                    </div>
                  </div>
                  <div class="form-group">
                    <div
                      :class="{'is-invalid': errors.has('password')}"
                      class="input-group input-group-alternative"
                    >
                      <input
                        v-validate="'required'"
                        v-model="auth.password"
                        class="form-control"
                        placeholder="Password"
                        type="password"
                        name="password"
                      >
                    </div>
                  </div>
                  <div class="custom-control custom-control-alternative custom-checkbox">
                    <input
                      id="customCheckLogin"
                      v-model="auth.remember_me"
                      class="custom-control-input"
                      type="checkbox"
                    >
                    <label
                      class="custom-control-label"
                      for="customCheckLogin"
                    >
                      <span class="text-muted">Remember me</span>
                    </label>
                  </div>
                  <div class="text-center">
                    <button
                      :class="{'disabled': submited || !isFormValid}"
                      :disabled="submited || !isFormValid"
                      class="btn btn-primary my-4"
                    >
                      Sign in
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { mapActions } from 'vuex'

export default {
  name: 'Login',
  data() {
    return {
      auth: {
        name: '',
        password: '',
        remember_me: false,
      },
      submited: false,
    }
  },
  computed: {
    isFormValid() {
      return !this.errors.items.length
    },
  },
  methods: {
    onSubmitForm() {
      this.$validator.validate().then((res) => {
        if (res) {
          this.submited = true
          this.login(this.auth).then(() => {
            this.submited = false
            this.$router.push('/admin')
          });
        }
      });
    },
    ...mapActions([
      'login',
    ]),
  },
}
</script>
<style lang="scss" scoped>
.form-control {
  text-indent: 10px;
}
</style>
