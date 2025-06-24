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

<script>
export default {
  layout: false, // レイアウトを使用しない
  data() {
    return {
      form: {
        email: ''
      },
      errors: {},
      successMessage: false,
      isSubmitting: false
    }
  },
  methods: {
    async handleSubmit() {
      // バリデーションリセット
      this.errors = {}
      this.successMessage = false

      // 簡単なバリデーション
      if (!this.form.email) {
        this.errors.email = 'メールアドレスを入力してください'
        return
      }

      // メールアドレスの形式チェック
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(this.form.email)) {
        this.errors.email = '正しいメールアドレスを入力してください'
        return
      }

      this.isSubmitting = true

      try {
        // TODO: 実際のAPI呼び出しをここに実装
        console.log('パスワード再設定リクエスト:', this.form.email)

        // 仮の成功処理（実際のAPIレスポンスに応じて修正）
        await new Promise(resolve => setTimeout(resolve, 1000)) // 1秒待機（デモ用）

        this.successMessage = true
        this.form.email = '' // フォームをリセット

      } catch (error) {
        console.error('パスワード再設定エラー:', error)
        this.errors.email = 'エラーが発生しました。もう一度お試しください。'
      } finally {
        this.isSubmitting = false
      }
    }
  }
}
</script>

<style>
@import "@/assets/css/auth/forgot-password.css";
</style>