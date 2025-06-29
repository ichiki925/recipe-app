<template>
  <div class="admin-login-page">
    <div class="form-container">
      <form class="login-form" @submit.prevent="handleLogin">
        <div class="logo">
          <img src="/images/rabbit-shape.svg" alt="Rabbit Logo" class="logo-image">
        </div>
        <h1 class="login-title">Admin Login</h1>
        <div class="form-group">
          <label class="form-label">メールアドレス</label>
          <input
            type="email"
            class="form-input"
            v-model="form.email"
            :class="{ 'error-input': errors.email }"
          >
          <div v-if="errors.email" class="error">{{ errors.email }}</div>
        </div>
        <div class="form-group">
          <label class="form-label">パスワード</label>
          <input
            type="password"
            class="form-input"
            v-model="form.password"
            :class="{ 'error-input': errors.password }"
          >
          <div v-if="errors.password" class="error">{{ errors.password }}</div>
        </div>
        <button type="submit" class="submit-button">ログイン</button>
        <div class="form-footer">
          <NuxtLink to="/auth/forgot-password" class="forgot-link">パスワードを忘れた場合はこちら</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: false
})

const form = ref({
  email: '',
  password: ''
})

const errors = ref({})

// ログイン処理メソッド
const handleLogin = async () => {
  // バリデーションリセット
  errors.value = {}

  // 簡単なバリデーション
  if (!form.value.email) {
    errors.value.email = 'メールアドレスを入力してください'
  }
  if (!form.value.password) {
    errors.value.password = 'パスワードを入力してください'
  }

  // エラーがある場合は送信しない
  if (Object.keys(errors.value).length > 0) {
    return
  }

  try {
    // TODO: 実際のAPI呼び出しをここに実装
    console.log('ログイン処理:', form.value)
    // 例: await $fetch('/api/auth/login', { method: 'POST', body: form.value })

    // 一時的にダッシュボードページにリダイレクト
    // await navigateTo('/dashboard')
  } catch (error) {
    console.error('ログインエラー:', error)
    errors.value.general = 'ログインに失敗しました'
  }
}
</script>
<style scoped>
/* 完全に独立したCSS - グローバルCSSの影響を受けない */


.form-container {
  width: 100% !important;
  max-width: 400px !important;
  padding: 20px !important;
  box-sizing: border-box !important;
}

.login-form {
  background: white !important;
  padding: 40px !important;
  border-radius: 8px !important;
  text-align: center !important;
  box-sizing: border-box !important;
}

.logo {
  margin-bottom: 20px !important;
}

.logo-image {
  width: 60px !important;
  height: 60px !important;
  object-fit: contain !important;
}

.login-title {
  font-size: 24px !important;
  font-weight: normal !important;
  color: #333 !important;
  margin: 0 0 30px 0 !important;
  font-family: cursive !important;
  font-style: italic !important;
}

.form-group {
  margin-bottom: 20px !important;
  text-align: left !important;
}

.form-label {
  display: block !important;
  font-size: 14px !important;
  font-weight: normal !important;
  color: #333 !important;
  margin-bottom: 8px !important;
}

.form-input {
  width: 100% !important;
  padding: 12px 16px !important;
  font-size: 16px !important;
  border: none;
  border-bottom: 1px solid #ddd !important;
  box-sizing: border-box !important;
  background: white !important;
  color: #333 !important;
}

.form-input:focus {
  outline: none !important;
  background-color: #d0d0d0;
  border-color: #999;
}

.error-input {
  border-color: #dc3545 !important;
}

.error {
  color: #dc3545 !important;
  font-size: 12px !important;
  margin-top: 4px !important;
}

.submit-button {
  width: 100% !important;
  padding: 12px !important;
  background: #ddd !important;
  color: #333 !important;
  border: none !important;
  border-radius: 4px !important;
  font-size: 16px !important;
  font-weight: normal !important;
  cursor: pointer !important;
  margin-bottom: 20px !important;
  box-sizing: border-box !important;
}

.submit-button:hover {
  background: #ccc !important;
}

.form-footer {
  text-align: center !important;
}

.form-footer a {
  display: block !important;
  color: #888 !important;
  text-decoration: underline !important;
  font-size: 14px !important;
  margin-bottom: 10px !important;
}

.form-footer a:hover {
  color: #666 !important;
}

.form-footer a:last-child {
  margin-bottom: 0 !important;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .recipe-page {
        flex-direction: column;
        padding: 15px;
    }

    .sidebar {
        width: 100%;
        order: 2;
    }

    .recipe-list {
        order: 1;
    }

    .recipe-grid {
        grid-template-columns: 1fr;
    }
}
</style>