// composables/useAuth.js
import {
    createUserWithEmailAndPassword,
    signInWithEmailAndPassword,
    signOut,
    onAuthStateChanged,
    getIdToken
} from 'firebase/auth'

export const useAuth = () => {
    const { $auth } = useNuxtApp()
    const config = useRuntimeConfig()
    const user = useState('auth.user', () => null)
    const loading = useState('auth.loading', () => false)

    // 設定からAPIベースURLを取得
    const API_BASE_URL = config.public.apiBaseUrl

    /**
     * 現在のユーザーを取得
     */
    const getCurrentUser = () => {
        return $auth.currentUser
    }

    /**
     * 認証状態の確立を待機
     */
    const waitForAuth = () => {
        return new Promise((resolve) => {
            if ($auth.currentUser) {
                resolve($auth.currentUser)
            } else {
                const unsubscribe = onAuthStateChanged($auth, (firebaseUser) => {
                    unsubscribe()
                    resolve(firebaseUser)
                })
            }
        })
    }

    /**
     * Firebase IDトークンを取得
     */
    const getFirebaseIdToken = async () => {
        const currentUser = $auth.currentUser
        if (!currentUser) {
            throw new Error('User not authenticated')
        }
        return await getIdToken(currentUser)
    }

    /**
     * 一般ユーザー登録
     */
    const register = async (userData) => {
        loading.value = true

        try {
            console.log('🚀 Firebase認証開始:', userData.email)

            // Firebase Authentication で新規ユーザー作成
            const { user: firebaseUser } = await createUserWithEmailAndPassword(
                $auth,
                userData.email,
                userData.password
            )

            console.log('✅ Firebase認証成功:', firebaseUser.uid)

            // Laravel API にユーザー情報を送信
            const response = await $fetch('/auth/register', {
                method: 'POST',
                baseURL: API_BASE_URL,
                body: {
                    firebase_uid: firebaseUser.uid,
                    name: userData.name,
                    email: userData.email
                }
            })

            console.log('✅ Laravel API登録成功:', response)

            if (response.success) {
                return response
            } else {
                throw new Error(response.error || 'ユーザー登録に失敗しました')
            }

        } catch (error) {
            console.error('❌ ユーザー登録エラー:', error)

            // エラーの詳細をログ出力
            if (error.data) {
                console.error('エラーレスポンス:', error.data)
            }
            if (error.status) {
                console.error('ステータスコード:', error.status)
            }

            // Firebase Authentication のユーザーが作成されている場合は削除
            if ($auth.currentUser) {
                try {
                    await $auth.currentUser.delete()
                    console.log('🗑️ Firebase認証ユーザーを削除しました')
                } catch (deleteError) {
                    console.error('❌ Firebase認証ユーザー削除エラー:', deleteError)
                }
            }

            throw error
        } finally {
            loading.value = false
        }
    }

    /**
     * 管理者登録
     */
    const registerAdmin = async (adminData) => {
        loading.value = true

        try {
            console.log('🚀 管理者Firebase認証開始:', adminData.email)

            // Firebase Authentication で新規ユーザー作成
            const { user: firebaseUser } = await createUserWithEmailAndPassword(
                $auth,
                adminData.email,
                adminData.password
            )

            console.log('✅ 管理者Firebase認証成功:', firebaseUser.uid)

            // Laravel API に管理者情報を送信
            const response = await $fetch('/admin/register', {
                method: 'POST',
                baseURL: API_BASE_URL,
                body: {
                    firebase_uid: firebaseUser.uid,
                    admin_code: adminData.adminCode,
                    name: adminData.name,
                    email: adminData.email
                }
            })

            console.log('✅ Laravel API管理者登録成功:', response)

            return response

        } catch (error) {
            console.error('❌ 管理者登録エラー:', error)

            // Firebase Authentication のユーザーが作成されている場合は削除
            if ($auth.currentUser) {
                try {
                    await $auth.currentUser.delete()
                    console.log('🗑️ Firebase認証ユーザーを削除しました')
                } catch (deleteError) {
                    console.error('❌ Firebase認証ユーザー削除エラー:', deleteError)
                }
            }

            throw error
        } finally {
            loading.value = false
        }
    }

    /**
     * ログイン（本番対応版）
     */
    const login = async (email, password) => {
        loading.value = true

        try {
            console.log('🚀 ログイン開始:', email)

            const { user: firebaseUser } = await signInWithEmailAndPassword($auth, email, password)
            console.log('✅ Firebase認証成功:', firebaseUser.uid)

            // Firebase IDトークンを取得
            const idToken = await firebaseUser.getIdToken()

            let userData
            
            // 📧 メールアドレスで管理者/一般ユーザーを判定
            const isAdminEmail = email.includes('admin') || email.endsWith('@admin.com')
            
            if (isAdminEmail) {
                // 管理者用メールアドレスの場合
                try {
                    console.log('🔍 管理者としてチェック中...')
                    userData = await $fetch('/admin/check', {
                        baseURL: API_BASE_URL,
                        headers: { Authorization: `Bearer ${idToken}` }
                    })
                    console.log('✅ 管理者認証成功')
                } catch (adminError) {
                    console.log('❌ 管理者認証失敗、一般ユーザーとして試行...')
                    userData = await $fetch('/auth/check', {
                        baseURL: API_BASE_URL,
                        headers: { Authorization: `Bearer ${idToken}` }
                    })
                    console.log('✅ 一般ユーザー認証成功')
                }
            } else {
                // 一般ユーザー用メールアドレスの場合
                try {
                    console.log('🔍 一般ユーザーとしてチェック中...')
                    userData = await $fetch('/auth/check', {
                        baseURL: API_BASE_URL,
                        headers: { Authorization: `Bearer ${idToken}` }
                    })
                    console.log('✅ 一般ユーザー認証成功')
                } catch (authError) {
                    console.log('❌ 一般ユーザー認証失敗、管理者として試行...')
                    userData = await $fetch('/admin/check', {
                        baseURL: API_BASE_URL,
                        headers: { Authorization: `Bearer ${idToken}` }
                    })
                    console.log('✅ 管理者認証成功')
                }
            }

            user.value = userData.user || userData.admin
            console.log('✅ ログイン完了:', user.value)

            return user.value

        } catch (error) {
            console.error('❌ ログインエラー:', error)
            throw error
        } finally {
            loading.value = false
        }
    }

    /**
     * ログアウト
     */
    const logout = async () => {
        try {
            // ログアウト前にユーザーの種類を記録（ログアウト後はuser.valueがnullになるため）
            const isAdminUser = user.value?.role === 'admin'

            await signOut($auth)
            user.value = null

            // ユーザー種類に応じて遷移先を分ける
            if (isAdminUser) {
                // 管理者 → ログインページ
                await navigateTo('/admin/login')
            } else {
                // 一般ユーザー → 公開ページ（トップページ）
                await navigateTo('/')
            }

            console.log('✅ ログアウト完了')

        } catch (error) {
            console.error('❌ ログアウトエラー:', error)
            throw error
        }
    }


    /**
     * 認証状態の監視
     */
    const initAuth = () => {
        return new Promise((resolve) => {
            const unsubscribe = onAuthStateChanged($auth, async (firebaseUser) => {
                if (firebaseUser) {
                    try {
                        const idToken = await firebaseUser.getIdToken()

                        // ユーザー情報を取得
                        const userData = await $fetch('/auth/check', {
                            baseURL: API_BASE_URL,
                            headers: {
                                Authorization: `Bearer ${idToken}`
                            }
                        }).catch(async () => {
                            // 一般ユーザーで失敗した場合、管理者として試行
                            return await $fetch('/admin/check', {
                                baseURL: API_BASE_URL,
                                headers: {
                                    Authorization: `Bearer ${idToken}`
                                }
                            })
                        })

                        user.value = userData.user || userData.admin
                        console.log('✅ 認証状態確認完了:', user.value)

                    } catch (error) {
                        console.error('❌ ユーザー情報取得エラー:', error)
                        user.value = null
                    }
                } else {
                    user.value = null
                }

                loading.value = false
                resolve()
                unsubscribe()
            })
        })
    }

    /**
     * 管理者権限チェック
     */
    const isAdmin = computed(() => {
        return user.value?.role === 'admin'
    })

    /**
     * ログイン状態チェック
     */
    const isLoggedIn = computed(() => {
        return !!user.value
    })

    return {
        user: readonly(user),
        loading: readonly(loading),
        isAdmin,
        isLoggedIn,
        getCurrentUser,
        waitForAuth,
        getIdToken: getFirebaseIdToken,
        register,
        registerAdmin,
        login,
        logout,
        initAuth
    }
}