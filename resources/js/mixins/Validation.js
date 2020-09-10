const validateSlug = (rule, value, callback) => {
  if (value === '') {
    callback(new Error('Please input team URL'))
  } else if (!/^[a-z0-9-]+$/g.test(value)) {
    callback(new Error('Only lowercase letters, dash and numbers'))
  } else {
    // everything ok
    callback()
  }
}

const validateDelete = (rule, value, callback) => {
  if (value !== 'DELETE') {
    callback(new Error('Please input text "DELETE"'))
  } else {
    // everything ok
    callback()
  }
}

const category = (rule, value, callback) => {
  if (value === '') {
    callback(new Error('Please input category name'))
  } else if (value.length > 64) {
    callback(new Error('Category name is too long'))
  } else {
    // everything ok
    callback()
  }
}

export default {
  data() {
    return {
      tr_rules: {
        auth_email: [
          {
            required: true,
            message: 'Please input email address',
            trigger: 'blur',
          },
          {
            type: 'email',
            message: 'Please input correct email address',
            trigger: 'blur',
          },
        ],

        email: [
          {
            required: true,
            message: 'Please input email address',
            trigger: 'blur',
          },
          {
            type: 'email',
            message: 'Please input correct email address',
            trigger: 'blur',
          },
        ],

        required: [
          {
            required: true,
            message: 'Input is required',
            trigger: 'blur',
          },
        ],

        delete: [
          {
            required: true,
            validator: validateDelete,
            trigger: 'change',
          },
        ],

        slug: [
          {
            validator: validateSlug,
            required: true,
            trigger: 'change',
          },
        ],

        categoryName: [
          {
            validator: category,
            required: true,
            trigger: 'change',
          },
        ],

        invite_name: [
          {
            required: true,
            message: 'Please input user name',
            trigger: 'change',
          },
        ],

        invite_email: [
          {
            required: true,
            message: 'Please input email address',
            trigger: 'change',
          },
        ],

        required_change: [
          {
            required: true,
            message: 'Input is required',
            trigger: 'change',
          },
        ],
      },
    }
  },
}
