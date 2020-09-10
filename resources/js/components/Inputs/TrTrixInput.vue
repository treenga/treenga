<template>
  <div
    ref="wrapper"
    :class="[{noboder: noBorder, 'full-height': fullHeight}]"
    class="trix-wrapper"
    @keyup.enter.ctrl="onCtrlEnterKeyUp"
  >
    <input
      ref="input"
      class="d-none"
      type="file"
    >

    <trix-editor
      v-pre
      class="trix-editor"
      placeholder="Your comment"
    />
  </div>
</template>

<script>
import { mapState } from 'vuex'
import Tribute from 'tributejs'

export default {
  name: 'TrTrixInput',

  props: {
    value: {
      type: String,
      default: '',
    },

    noBorder: {
      type: Boolean,
      default: false,
    },

    fullHeight: {
      type: Boolean,
      default: false,
    },

    placeholder: {
      type: String,
      required: false,
      default: '',
    },
  },

  data() {
    return {
      toolbar: null,
      editor: null,
      host: '/api/v1/upload',
      tribute: null,
      originalEvents: {},
    }
  },

  computed: {
    ...mapState({
      token: state => state.auth.token,
      teamUsers: state => state.teams.teamUsers,
    }),

    isTribute() {
      return this.tribute && this.tribute.isActive
    },
  },

  watch: {
    value(val) {
      if (val !== this.editor.editor.element.innerHTML) {
        this.clearInput()
        this.editor.editor.insertHTML(val)
      }
    },

    isTribute(val) {
      if (val) {
        // when tribure window opened
        this.disableEnters()
        return
      }
      // when tribute window closed
      this.enableEnters()
    },

    teamUsers(val) {
      if (val) {
        this.initTribute()
      }
      this.editor.editor.setSelectedRange(-1)
    },
  },

  mounted() {
    this.editor = this.$refs.wrapper.querySelector('trix-editor')
    this.editor.editor.insertHTML(this.value)
    this.toolbar = this.$refs.wrapper.querySelector('.trix-button-group.trix-button-group--block-tools')
    this.addInputListener()
    this.addFileSizeValidation()
    this.addAttachmentListener()
    this.addTrixChangeListener()
    // this.createToolbarButton()

    if (this.teamUsers.length) {
      this.initTribute()
    }
  },

  methods: {
    createToolbarButton() {
      const button = document.createElement('button')
      button.setAttribute('type', 'button')
      button.setAttribute('class', 'trix-button trix-button--icon trix-button--attachment')
      button.setAttribute('data-trix-action', 'x-attach')
      button.setAttribute('title', 'Attach a file')
      button.setAttribute('tabindex', '-1')
      button.innerText = 'Attach a file'

      this.toolbar.append(button)
      button.addEventListener('click', () => {
        this.$refs.input.click()
      })
    },

    disableEnters() {
      this.editor.editor.composition.delegate.inputController.events.keypress = () => {}
      this.editor.editor.composition.delegate.inputController.events.keydown = () => {}
    },

    enableEnters() {
      this.editor.editor.composition.delegate.inputController
        .events = { ...this.originalEvents };
    },

    onCtrlEnterKeyUp() {
      this.$emit('ctrlEnterPress')
    },

    clearInput() {
      this.editor.editor.element.innerHTML = ''
    },

    initTribute() {
      if (!this.tribute && this.checkIfTribute()) {
        this.tribute = new Tribute({
          values: this.teamUsers,
          selectTemplate: item => `@${item.original.value}`,
          menuItemTemplate: item => item.original.value,
          replaceTextSuffix: '&nbsp;',
        })

        this.tribute.attach(this.editor)

        if (this.editor) {
          this.originalEvents = this.editor.editor.composition.delegate.inputController.events
        }

        if (this.placeholder) {
          this.editor.editor.element.setAttribute('placeholder', this.placeholder)
        }
      }
    },

    checkIfTribute() {
      if (this.$route.params.userId) return false
      return true
    },

    addInputListener() {
      this.$refs.input.addEventListener('change', () => {
        this.editor.editor.insertFile(this.$refs.input.files[0])
      })

      this.editor.addEventListener('paste', (e) => {
        const pastedText = e.clipboardData.getData('Text')
        // we defer so we can get the cursor position after the pasting happens
        // otherwise trix returns the position before
        setTimeout(() => {
          // eslint-disable-next-line no-useless-escape
          const linkRegexp = /^(https?:\/\/(?:www\.|(?!www))[^\s\.]+\.[^\s]{2,}|www\.[^\s]+\.[^\s]{2,})$/ig
          if (pastedText && pastedText.match(linkRegexp)) {
            const { editor } = this.editor
            const currentText = editor.getDocument().toString()
            const currentSelection = editor.getSelectedRange()
            // text up to the cursor position
            const textWeAreInterestedIn = currentText.substring(0, currentSelection[0])
            // search for the start of the url
            const startOfPastedText = textWeAreInterestedIn.lastIndexOf(pastedText)
            // add an undo entry so people can undo the autolinking
            editor.recordUndoEntry('Auto Link Paste')
            // select the url text
            editor.setSelectedRange([startOfPastedText, currentSelection[0]])
            // add a hyperlink to it
            editor.activateAttribute('href', pastedText)
            // go back to the original selection
            editor.setSelectedRange(currentSelection)
          }
        }, 0)
      })
    },

    addAttachmentListener() {
      this.editor.addEventListener('trix-attachment-add', (event) => {
        if (event.attachment.file) {
          this.uploadAttachment(event.attachment)
        }
      })
    },

    addFileSizeValidation() {
      this.editor.addEventListener('trix-file-accept', (event) => {
        // Check for max file size (100mb)
        if (event.file.size > 104857600) {
          const fileSize = (event.file.size * 0.000001).toFixed(2)
          console.error(`Max file size - 100 Mb, current - ${fileSize} Mb`)
          event.preventDefault()
        }
      })
    },

    addTrixChangeListener() {
      this.editor.addEventListener('trix-change', (event) => {
        this.$emit('input', event.target.innerHTML)
        this.$emit('change')
      })
    },

    getMentionedUsers() {
      if (this.$route.params.userId) return []

      return this.teamUsers.reduce((accumulator, current) => {
        if (
          this.value.replace(/&nbsp;|\s/g, ' ').indexOf(`@${current.value} `) > -1
          && accumulator.indexOf(current.id) < 0
        ) {
          return [...accumulator, current.id]
        }

        return accumulator
      }, [])
    },

    focus() {
      this.editor.editor.element.focus()
    },

    uploadAttachment(attachment) {
      const { file } = attachment
      const key = this.createStorageKey(file)
      const formData = this.createFormData(key, file)
      const xhr = new XMLHttpRequest()

      xhr.open('POST', this.host, true)

      xhr.setRequestHeader('Authorization', `Bearer ${this.token}`)

      xhr.upload.addEventListener('progress', (event) => {
        const progress = event.loaded / event.total * 100
        attachment.setUploadProgress(progress)
      })

      xhr.addEventListener('load', () => {
        if (xhr.status === 200) {
          const json = JSON.parse(xhr.responseText)
          attachment.setAttributes({ url: json.data })
        }
      })

      xhr.onerror = (err) => {
        console.log(err)
      }

      xhr.send(formData)
    },

    createStorageKey(file) {
      const date = new Date()
      const day = date.toISOString().slice(0, 10)
      const name = `${date.getTime()}-${file.name}`
      return ['tmp', day, name].join('/')
    },

    createFormData(key, file) {
      const data = new FormData()
      data.append('key', key)
      data.append('file', file)
      data.append('team_slug', this.$route.params.teamSlug)
      data.append('Content-Type', file.type)
      return data
    },
  },
}
</script>

<style lang="scss" scoped>
  .d-none {
    display: none;
  }

  .trix-wrapper {
    text-align: left;
    font-size: 17px;
    line-height: calc(1.8em + 0.5vh);
  }

  .full-height {
    .trix-editor {
      padding-bottom: 50vh;

      &:before {
        color: #c0c4cc!important;
      }
    }
  }
</style>
