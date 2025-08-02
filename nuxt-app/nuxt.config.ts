// https://nuxt.com/docs/api/configuration/nuxt-config
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

  // プラグイン設定を追加
  plugins: [
    '~/plugins/firebase.client.js'
  ],

  // サーバー設定（Docker用）
  devServer: {
    host: '0.0.0.0',
    port: 3000
  },

  nitro: {
    devProxy: {
      '/api': {
        target: 'http://nginx', // nginxコンテナ名を使用
        changeOrigin: true,
        prependPath: true
      }
    }
  },

  // API設定（Laravel連携用とFirebase設定を統合）
  runtimeConfig: {
    public: {
      
      apiBaseUrl: process.env.API_BASE_URL || 'http://localhost/api',
      
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost',

      // Firebase設定
      firebaseApiKey: process.env.FIREBASE_API_KEY,
      firebaseAuthDomain: process.env.FIREBASE_AUTH_DOMAIN,
      firebaseProjectId: process.env.FIREBASE_PROJECT_ID,
      firebaseStorageBucket: process.env.FIREBASE_STORAGE_BUCKET,
      firebaseMessagingSenderId: process.env.FIREBASE_MESSAGING_SENDER_ID,
      firebaseAppId: process.env.FIREBASE_APP_ID
    }
  }
})