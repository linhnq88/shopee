/* global flatsome_gutenberg, wp */
(function () {
  'use strict'

  var FlatsomeGutenberg = {
    headerToolbar: null,
    editButton: null,
    init: function () {
      if (!flatsome_gutenberg.edit_button.enabled) {
        return
      }

      this.buttonText = flatsome_gutenberg.edit_button.text
      this.editUrl = flatsome_gutenberg.edit_button.url

      this.addEditButton()
      this.bindEvents()
    },
    addEditButton: function () {
      this.headerToolbar = document.querySelector('.gutenberg .edit-post-header-toolbar')

      if (!this.headerToolbar) return
      this.headerToolbar.insertAdjacentHTML('beforeend',
        '<button id="uxbuilder-edit-button" class="components-button is-button is-primary is-large">' + this.buttonText + '</button>')

      this.editButton = this.headerToolbar.querySelector('#uxbuilder-edit-button')
    },
    bindEvents: function () {
      var self = this

      if (!this.editButton) return
      this.editButton.addEventListener('click', function (e) {
        e.preventDefault()

        this.classList.add('is-busy')
        this.blur()

        wp.data.dispatch('core/editor').savePost()
        self.redirectToBuilder()
      }, false)
    },
    redirectToBuilder: function () {
      var self = this

      setTimeout(function () {
        if (wp.data.select('core/editor').isSavingPost()) {
          self.redirectToBuilder()
        } else if (wp.data.select('core/editor').didPostSaveRequestSucceed()) {
          location.href = self.editUrl
          self.editButton.textContent += '...'
        } else {
          self.editButton.classList.remove('is-busy')
        }
      }, 500)
    }

  }

  document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
      FlatsomeGutenberg.init()
    }, 10)
  })
}())
