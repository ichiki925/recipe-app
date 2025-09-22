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

  // 開発専用プロキシ（本番では使わない）
  // nitro: {
  //   devProxy: {
  //     '/api': {
  //       target: 'http://nginx:80',
  //       changeOrigin: true,
  //     }
  //   }
  // },

  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.API_BASE_URL || '/api',

      firebaseApiKey: process.env.NUXT_PUBLIC_FIREBASE_API_KEY,
      firebaseAuthDomain: process.env.NUXT_PUBLIC_FIREBASE_AUTH_DOMAIN,
      firebaseProjectId: process.env.NUXT_PUBLIC_FIREBASE_PROJECT_ID,
      firebaseStorageBucket: process.env.NUXT_PUBLIC_FIREBASE_STORAGE_BUCKET,
      firebaseMessagingSenderId: process.env.NUXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID,
      firebaseAppId: process.env.NUXT_PUBLIC_FIREBASE_APP_ID,
    }
  }
})