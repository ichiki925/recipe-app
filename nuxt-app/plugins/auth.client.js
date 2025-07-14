// plugins/auth.client.js - 認証状態監視用
export default defineNuxtPlugin(async () => {
    const { initAuth } = useAuth()

    console.log('🔍 認証プラグイン開始')

    // Firebase認証状態の監視を開始
    await initAuth()

    console.log('✅ 認証状態監視開始完了')
})