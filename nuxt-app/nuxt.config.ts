export default defineNuxtConfig({
  compatibilityDate: '2025-05-15',

  css: [
    '@/assets/css/sanitize.css',
  ],

  modules: [
    '@nuxt/eslint',
    '@nuxt/icon',
    '@nuxt/image',
    '@nuxt/ui'
  ],

  plugins: [
    '~/plugins/firebase.client.js'
  ],

  devServer: {
    host: '0.0.0.0',
    port: 3000
  },

  nitro: {
    devProxy: {
      '/api': {
        target: 'http://nginx',
        changeOrigin: true,
        prependPath: true
      }
    }
  },

  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.API_BASE_URL || 'http://localhost/api',
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost',
      firebaseApiKey: process.env.FIREBASE_API_KEY,
      firebaseAuthDomain: process.env.FIREBASE_AUTH_DOMAIN,
      firebaseProjectId: process.env.FIREBASE_PROJECT_ID,
      firebaseStorageBucket: process.env.FIREBASE_STORAGE_BUCKET,
      firebaseMessagingSenderId: process.env.FIREBASE_MESSAGING_SENDER_ID,
      firebaseAppId: process.env.FIREBASE_APP_ID
    }
  }
})