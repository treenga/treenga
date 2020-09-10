<template>
  <div class="password-wrapper">
    <el-form-item
      :prop="prop"
      :rules="tr_rules.required"
      class="input"
    >
      <el-input
        ref="passInput"
        v-model="input"
        :autofocus="autofocus"
        :placeholder="placeholder"
        :disabled="disabled"
        :type="isPassword ? 'password' : 'text'"
        @input="onPasswordChange($event)"
      />
    </el-form-item>

    <button
      :class="{'active': !isPassword}"
      circle
      class="btn-show"
      type="button"
      @click="togglePassword"
    >
      <i class="el-icon-view"/>
    </button>
  </div>
</template>

<script>
import Validation from '@/js/mixins/Validation'

export default {
  name: 'TrPasswordInput',

  mixins: [Validation],

  props: {
    value: {
      type: String,
      required: true,
    },

    disabled: {
      type: Boolean,
      default: false,
    },

    prop: {
      type: String,
      default: 'password',
    },

    placeholder: {
      type: String,
      default: 'Password',
    },

    autofocus: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      isPassword: true,
      input: '',
    }
  },

  watch: {
    value(val) {
      if (!val) {
        this.$refs.passInput.clear()
      }
    },
  },

  methods: {
    togglePassword() {
      this.isPassword = ! this.isPassword
    },

    onPasswordChange(e) {
      this.$emit('input', this.input)
    },
  },
}
</script>

<style lang="scss" scoped>
  @import 'resources/sass/_variables.scss';

  .password-wrapper {
    position: relative;

    .btn-show {
      position: absolute;
      border-radius: 50%;
      right: -50px;
      top: 50%;
      transform: translateY(-50%);
      font-size: $title;
      border: none;
      background-color: transparent;
      height: 45px;
      width: 45px;
      color: $gray;
      cursor: pointer;

      &:focus {
        outline: none;
        box-shadow: none;
        border: 1px solid $gray;
      }

      &.active {
        background-color: #ecf5ff;
        color: #1D76C4;
      }
    }
  }
</style>
