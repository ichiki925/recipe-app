<template>
  <div class="forgot-password-page">
    <div class="form-container">
      <h1 class="title">パスワードをお忘れですか？</h1>
      <p class="description">
        アカウントにアクセスするには、<br>
        登録したメールアドレスを入力してください。
      </p>

      <!-- 成功メッセージ -->
      <div v-if="successMessage" class="success-message">
        パスワード再設定用のメールを送信しました。<br>
        ご確認ください。
      </div>

      <form @submit.prevent="handleSubmit" class="form">
        <div class="form-group">
          <label class="form-label">メールアドレス</label>
          <input
            type="email"
            v-model="form.email"
            class="form-input"
            :class="{ 'error-input': errors.email }"
            required
          >
          <div v-if="errors.email" class="error">{{ errors.email }}</div>
        </div>

        <button
          type="submit"
          class="submit-button"
          :disabled="isSubmitting"
        >
          {{ isSubmitting ? '送信中...' : '再設定リンクを送信' }}
        </button>
      </form>

      <nuxt-link to="/auth/login" class="login-link">
        ログイン画面に戻る
      </nuxt-link>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: false
})

import { ref } from 'vue'

const form = ref({ email: '' })
const errors = ref({})
const successMessage = ref(false)
const isSubmitting = ref(false)

const handleSubmit = async () => {
  errors.value = {}
  successMessage.value = false

  if (!form.value.email) {
    errors.value.email = 'メールアドレスを入力してください'
    return
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(form.value.email)) {
    errors.value.email = '正しいメールアドレスを入力してください'
    return
  }

  isSubmitting.value = true

  try {
    console.log('パスワード再設定リクエスト:', form.value.email)
    await new Promise(resolve => setTimeout(resolve, 1000))
    successMessage.value = true
    form.value.email = ''
  } catch (error) {
    console.error('パスワード再設定エラー:', error)
    errors.value.email = 'エラーが発生しました。もう一度お試しください。'
  } finally {
    isSubmitting.value = false
  }
}
</script>


<style>
@import "@/assets/css/auth/forgot-password.css";
</style>