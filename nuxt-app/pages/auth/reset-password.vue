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

      <nuxt-link to="/auth/login" class="login-link">
        ログイン画面に戻る
      </nuxt-link>
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
        password: '',
        passwordConfirmation: ''
      },
      errors: {},
      isSubmitting: false,
      token: null
    }
  },
  mounted() {
    // URLからトークンを取得
    this.token = this.$route.query.token || this.$route.params.token
    
    // URLからメールアドレスを取得（あれば）
    if (this.$route.query.email) {
      this.form.email = this.$route.query.email
    }
    
    // トークンがない場合はログインページにリダイレクト
    /*if (!this.token) {
      this.$router.push('/auth/login')
    }*/
  },
  methods: {
    async handleSubmit() {
      // バリデーションリセット
      this.errors = {}
      
      // バリデーション
      if (!this.validateForm()) {
        return
      }
      
      this.isSubmitting = true
      
      try {
        // TODO: 実際のAPI呼び出しをここに実装
        console.log('パスワード再設定データ:', {
          token: this.token,
          email: this.form.email,
          password: this.form.password,
          passwordConfirmation: this.form.passwordConfirmation
        })
        
        // 仮の成功処理（実際のAPIレスポンスに応じて修正）
        await new Promise(resolve => setTimeout(resolve, 1000)) // 1秒待機（デモ用）
        
        // 成功時はログインページにリダイレクト
        alert('パスワードが正常に変更されました。')
        this.$router.push('/auth/login')
        
      } catch (error) {
        console.error('パスワード再設定エラー:', error)
        this.errors.general = 'エラーが発生しました。もう一度お試しください。'
      } finally {
        this.isSubmitting = false
      }
    },
    
    validateForm() {
      let isValid = true
      
      // メールアドレスチェック
      if (!this.form.email) {
        this.errors.email = 'メールアドレスを入力してください'
        isValid = false
      } else {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        if (!emailRegex.test(this.form.email)) {
          this.errors.email = '正しいメールアドレスを入力してください'
          isValid = false
        }
      }
      
      // パスワードチェック
      if (!this.form.password) {
        this.errors.password = 'パスワードを入力してください'
        isValid = false
      } else if (this.form.password.length < 8) {
        this.errors.password = 'パスワードは8文字以上で入力してください'
        isValid = false
      }
      
      // パスワード確認チェック
      if (!this.form.passwordConfirmation) {
        this.errors.passwordConfirmation = 'パスワード確認を入力してください'
        isValid = false
      } else if (this.form.password !== this.form.passwordConfirmation) {
        this.errors.passwordConfirmation = 'パスワードが一致しません'
        isValid = false
      }
      
      return isValid
    }
  }
}
</script>

<style>
@import "@/assets/css/auth/reset-password.css";
</style>