<template>
  <div class="login-page">
    <div class="form-container">
      <form class="login-form" @submit.prevent="handleLogin">
        <div class="logo">
          <img src="/images/rabbit-shape.svg" alt="Rabbit Logo" class="logo-image">
        </div>
        <h1 class="login-title">Login</h1>
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
          <NuxtLink to="/auth/forgot-password">パスワードを忘れた方はこちら</NuxtLink>
        </div>
        <div class="form-footer">
          <NuxtLink to="/auth/register">アカウントをお持ちでない方はこちら</NuxtLink>
        </div>
        <div class="form-footer">
          <NuxtLink to="/">トップページに戻る</NuxtLink>
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
.login-page {
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
    padding: 0;
}

.form-container {
    max-width: 400px;
    width: 90%;
    padding: 2rem;
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.login-form {
    text-align: center;
}

.logo {
    text-align: center;
    margin-bottom: 1rem;
    width: 100%;
}

.logo-image {
    width: 60px;
    height: auto;
    opacity: 0.9;
    display: block;
    margin: 0 auto;
}

.login-title {
    color: #555;
    font-size: 1.8rem;
    font-family: cursive;
    margin-bottom: 2rem;
    margin-top: 0.5rem;
    font-weight: 300;
    text-align: center;
}

.form-group {
    margin-bottom: 1.5rem;
    text-align: left;
}

.form-label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.95rem;
    color: #333;
    font-weight: 400;
}

.form-input {
    width: 100%;
    padding: 0.75rem;
    border: none;
    border-bottom: 1px solid #dcdcdc;
    background-color: #fff;
    font-size: 1rem;
    font-weight: 400;
    color: #555;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    background-color: #d0d0d0;
    border-color: #999;
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
    padding: 0.75rem;
    background-color: #dcdcdc;
    color: #555;
    border: none;
    font-size: 1rem;
    font-weight: 400;
    cursor: pointer;
    border-radius: 4px;
}

.submit-button:hover {
    background-color: #cfcfcf;
}

.form-footer {
    text-align: center;
    margin-top: 1rem;
    font-size: 0.95rem;
}

.form-footer a {
    color: #888;
    text-decoration: underline;
}

.form-footer a:hover {
    color: #666;
}

@media screen and (max-width: 768px) {
    .login-page {
        background-color: #ffffff;
    }

    .form-container {
        box-shadow: none;
        border-radius: 0;
        margin: 30px;
        max-width: 100%;
        padding: 1rem;
    }
}
</style>