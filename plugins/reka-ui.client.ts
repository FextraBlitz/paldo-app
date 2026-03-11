import { defineNuxtPlugin } from '#app'
import Reka from 'reka-ui'

export default defineNuxtPlugin((nuxtApp) => {
  // the plugin will install ConfigProvider, UApp, UMain, etc.
  nuxtApp.vueApp.use(Reka)
})