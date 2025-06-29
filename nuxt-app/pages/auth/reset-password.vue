<template>
  <div class="reset-password-page">
    <div class="form-container">
      <h1 class="title">新しいパスワードの設定</h1>

      <form @submit.prevent="handleSubmit" class="form">
        <div class="form-group">
          <label class="form-label">メールアドレス</label>
          <input
            type="email"
            v-model="form.email"
            class="form-input"
            required
          >
          <div v-if="errors.email" class="error">{{ errors.email }}</div>
        </div>

        <div class="form-group">
          <label class="form-label">新しいパスワード</label>
          <input
            type="password"
            v-model="form.password"
            class="form-input"
            required
          >
          <div v-if="errors.password" class="error">{{ errors.password }}</div>
        </div>

        <div class="form-group">
          <label class="form-label">パスワード確認</label>
          <input
            type="password"
            v-model="form.passwordConfirmation"
            class="form-input"
            required
          >
          <div v-if="errors.passwordConfirmation" class="error">{{ errors.passwordConfirmation }}</div>
        </div>

        <button
          type="submit"
          class="submit-button"
          :disabled="isSubmitting"
        >
          {{ isSubmitting ? '変更中...' : 'パスワードを変更' }}
        </button>
      </form>

      <NuxtLink to="/auth/login" class="login-link">
        ログイン画面に戻る
      </NuxtLink>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'auth'
})

import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const form = ref({
  email: '',
  password: '',
  passwordConfirmation: ''
})
const errors = ref({})
const isSubmitting = ref(false)
const token = ref(null)

onMounted(() => {
  token.value = route.query.token || route.params.token || null

  if (route.query.email) {
    form.value.email = route.query.email
  }

  // トークンがない場合はリダイレクト（必要なら）
  // if (!token.value) {
  //   router.push('/auth/login')
  // }
})

const handleSubmit = async () => {
  errors.value = {}

  if (!validateForm()) return

  isSubmitting.value = true

  try {
    console.log('パスワード再設定データ:', {
      token: token.value,
      email: form.value.email,
      password: form.value.password,
      passwordConfirmation: form.value.passwordConfirmation
    })

    await new Promise(resolve => setTimeout(resolve, 1000)) // 仮のAPI遅延

    alert('パスワードが正常に変更されました。')
    router.push('/auth/login')

  } catch (error) {
    console.error('エラー:', error)
    errors.value.general = 'エラーが発生しました。'
  } finally {
    isSubmitting.value = false
  }
}

const validateForm = () => {
  let isValid = true
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

  if (!form.value.email) {
    errors.value.email = 'メールアドレスを入力してください'
    isValid = false
  } else if (!emailRegex.test(form.value.email)) {
    errors.value.email = '正しいメールアドレスを入力してください'
    isValid = false
  }

  if (!form.value.password) {
    errors.value.password = 'パスワードを入力してください'
    isValid = false
  } else if (form.value.password.length < 8) {
    errors.value.password = 'パスワードは8文字以上で入力してください'
    isValid = false
  }

  if (!form.value.passwordConfirmation) {
    errors.value.passwordConfirmation = 'パスワード確認を入力してください'
    isValid = false
  } else if (form.value.password !== form.value.passwordConfirmation) {
    errors.value.passwordConfirmation = 'パスワードが一致しません'
    isValid = false
  }

  return isValid
}
</script>

<style>
@import "@/assets/css/auth/reset-password.css";
</style>
