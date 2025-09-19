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
            :class="{ 'error-input': errors.email }"
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
            :class="{ 'error-input': errors.password }"
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
            :class="{ 'error-input': errors.passwordConfirmation }"
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
  layout: false
})

const route = useRoute()

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
})

const handleSubmit = async () => {
  errors.value = {}
  if (!validateForm()) return
  isSubmitting.value = true

  try {
    const config = useRuntimeConfig()

    const response = await $fetch('/api/auth/reset-password', {
      baseURL: config.public.apiBaseUrl,
      method: 'POST',
      body: {
        token: token.value,
        email: form.value.email,
        password: form.value.password,
        password_confirmation: form.value.passwordConfirmation
      }
    })

    if (response.success) {
      alert('パスワードが正常に変更されました。')
      await navigateTo('/auth/login')
    } else {
      throw new Error(response.message || 'パスワード変更に失敗しました')
    }

  } catch (error) {
    console.error('エラー:', error)
    errors.value.general = error.message || 'エラーが発生しました。'
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

<style scoped>
.reset-password-page {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    overflow: hidden;

    background-color: #f2f2f2;
    font-family: 'Noto Sans JP', sans-serif;
    color: #555;
    font-weight: 300;

    display: flex;
    align-items: center;
    justify-content: center;

    margin: 0;
    padding: 20px;
}

.form-container {
    max-width: 360px;
    width: 100%;
    padding: 2rem;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 3px 5px rgba(0, 0, 0, 0.1);
}

.title {
    text-align: center;
    font-size: 1.3rem;
    font-family: sans-serif;
    margin-bottom: 2rem;
    font-weight: 300;
    color: #555;
}

.form-group {
    margin-bottom: 1.5rem;
    text-align: left;
}

.form-label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.9rem;
    font-weight: 300;
    color: #333;
}

.form-input {
    width: 100%;
    padding: 0.6rem 0.8rem;
    border: none;
    border-bottom: 1px solid #dcdcdc;
    background-color: #fff;
    font-size: 1rem;
    font-weight: 300;
    outline: none;
    box-sizing: border-box;
}

.form-input:focus {
    border-bottom-color: #555;
}

.form-input.error-input {
    border-bottom-color: #d9534f;
}

.error {
    font-size: 0.85rem;
    color: #d9534f;
    margin-top: 0.3rem;
}

.submit-button {
    width: 100%;
    margin-top: 2rem;
    padding: 0.8rem;
    background-color: #dcdcdc;
    color: #555;
    border: none;
    font-size: 1rem;
    font-weight: 300;
    cursor: pointer;
    border-radius: 4px;
}

.submit-button:hover {
    background-color: #cfcfcf;
}

.submit-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.login-link {
    display: block;
    text-align: center;
    margin-top: 1.2rem;
    font-size: 0.85rem;
    color: #555;
    text-decoration: underline;
    font-weight: 300;
}

.login-link:hover {
    color: #9f9b9b;
}

@media screen and (max-width: 480px) {
    .reset-password-page {
        background-color: #ffffff;
        padding: 15px;
    }

    .form-container {
        box-shadow: none;
        border-radius: 0;
        max-width: 100%;
        padding: 1.5rem;
    }
}
</style>