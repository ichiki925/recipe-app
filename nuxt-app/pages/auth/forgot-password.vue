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

<style scoped>
.forgot-password-page {
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
    max-width: 450px;
    width: 100%;
    padding: 2.5rem;
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

.description {
    font-size: 0.95rem;
    color: #555;
    font-weight: 300;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    text-align: center;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 0.75rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    text-align: center;
    font-size: 0.9rem;
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
    .forgot-password-page {
        background-color: #ffffff;
        padding: 15px;
    }

    .form-container {
        box-shadow: none;
        border-radius: 0;
        max-width: 100%;
        padding: 1.5rem;
    }

    .description {
        font-size: 0.9rem;
    }
}
</style>