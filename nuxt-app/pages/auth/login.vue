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

<script>
export default {
  layout: false, // レイアウトを使用しない
  data() {
    return {
      form: {
        email: '',
        password: ''
      },
      errors: {}
    }
  },
  methods: {
    async handleLogin() {
      // バリデーションリセット
      this.errors = {}
      
      // 簡単なバリデーション
      if (!this.form.email) {
        this.errors.email = 'メールアドレスを入力してください'
      }
      if (!this.form.password) {
        this.errors.password = 'パスワードを入力してください'
      }
      
      // エラーがある場合は送信しない
      if (Object.keys(this.errors).length > 0) {
        return
      }
      
      try {
        // TODO: 実際のAPI呼び出しをここに実装
        console.log('ログイン処理:', this.form)
        // 例: await this.$auth.loginWith('local', { data: this.form })
        
        // 一時的にダッシュボードページにリダイレクト
        // this.$router.push('/dashboard')
      } catch (error) {
        console.error('ログインエラー:', error)
        this.errors.general = 'ログインに失敗しました'
      }
    }
  }
}
</script>

<style>
@import "@/assets/css/auth/login.css";
</style>