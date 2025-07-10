// composables/useAuth.js
import {
    createUserWithEmailAndPassword,
    signInWithEmailAndPassword,
    signOut,
    onAuthStateChanged,
    sendEmailVerification,
    sendPasswordResetEmail
} from 'firebase/auth'

// グローバルな状態管理（全てのコンポーネントで共有）
const user = ref(null)
const admin = ref(null)
const loading = ref(false)
const authInitialized = ref(false)

export const useAuth = () => {

    // ログイン機能（一般ユーザー）
    const login = async (email, password) => {
        try {
            loading.value = true
            const { $auth } = useNuxtApp()

            const userCredential = await signInWithEmailAndPassword($auth, email, password)
            const token = await userCredential.user.getIdToken()

            // Laravel APIでユーザー情報を取得・確認
            const config = useRuntimeConfig()
            const response = await $fetch('/auth/user', {
                baseURL: config.public.apiBaseUrl,
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            })

            user.value = {
                ...userCredential.user,
                profile: response.user
            }

            console.log('🔥 ログイン成功! ID Token:', token)
            return { success: true, user: userCredential.user, profile: response.user }
        } catch (error) {
            console.error('Login error:', error)
            // Firebase認証失敗時はサインアウト
            const { $auth } = useNuxtApp()
            await signOut($auth)
            throw new Error(getAuthErrorMessage(error.code) || error.message)
        } finally {
            loading.value = false
        }
    }

    // 管理者ログイン機能
    const adminLogin = async (email, password) => {
        try {
            loading.value = true
            const { $auth } = useNuxtApp()

            // 1. Firebase認証でログイン
            const userCredential = await signInWithEmailAndPassword($auth, email, password)
            const token = await userCredential.user.getIdToken()
            
            // 2. Laravel APIで管理者権限を確認
            const config = useRuntimeConfig()
            const response = await $fetch('/admin', {
                baseURL: config.public.apiBaseUrl,
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            })

            if (!response.admin || response.admin.role !== 'admin') {
                // 管理者権限がない場合
                await signOut($auth)
                throw new Error('管理者権限がありません')
            }

            // 3. 管理者情報をセット
            admin.value = {
                uid: userCredential.user.uid,
                email: userCredential.user.email,
                displayName: response.admin.name || userCredential.user.displayName,
                role: response.admin.role,
                createdAt: response.admin.created_at
            }

            user.value = {
                ...userCredential.user,
                profile: response.admin
            }

            console.log('🔥 管理者ログイン成功!', response.admin)
            return { success: true, user: userCredential.user, admin: admin.value }
        } catch (error) {
            console.error('管理者ログインエラー:', error)
            // エラー時はサインアウト
            const { $auth } = useNuxtApp()
            await signOut($auth)
            throw new Error(error.message || getAuthErrorMessage(error.code))
        } finally {
            loading.value = false
        }
    }

    // 一般ユーザー登録機能
    const register = async (email, password, name) => {
        try {
            loading.value = true
            const { $auth } = useNuxtApp()

            // 1. Firebase Authentication
            const userCredential = await createUserWithEmailAndPassword($auth, email, password)
            const token = await userCredential.user.getIdToken()

            // 2. Laravel APIにユーザー情報を保存
            const config = useRuntimeConfig()
            const response = await $fetch('/auth/register', {
                method: 'POST',
                baseURL: config.public.apiBaseUrl,
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: {
                    firebase_uid: userCredential.user.uid,
                    name,
                    email,
                    role: 'user'
                }
            })

            // 3. メール認証送信
            await sendEmailVerification(userCredential.user)

            user.value = {
                ...userCredential.user,
                profile: response.user
            }

            return { success: true, user: userCredential.user, profile: response.user }
        } catch (error) {
            console.error('Registration error:', error)
            // エラー時はFirebaseユーザーを削除
            throw new Error(error.message || getAuthErrorMessage(error.code))
        } finally {
            loading.value = false
        }
    }

    // 管理者登録機能
    const registerAdmin = async (formData) => {
        try {
            loading.value = true
            const { $auth } = useNuxtApp()

            // 1. Firebase Authentication
            const userCredential = await createUserWithEmailAndPassword(
                $auth,
                formData.email,
                formData.password
            )
            const token = await userCredential.user.getIdToken()

            // 2. Laravel APIで管理者登録（管理者コード検証含む）
            const config = useRuntimeConfig()
            const response = await $fetch('/admin/register', {
                method: 'POST',
                baseURL: config.public.apiBaseUrl,
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                },
                body: {
                    firebase_uid: userCredential.user.uid,
                    name: formData.name,
                    email: formData.email,
                    admin_code: formData.adminCode,
                    role: 'admin'
                }
            })

            if (!response.admin) {
                throw new Error('管理者登録に失敗しました')
            }

            // 3. メール認証送信
            await sendEmailVerification(userCredential.user)

            // 4. 管理者情報をセット
            admin.value = {
                uid: userCredential.user.uid,
                email: userCredential.user.email,
                displayName: response.admin.name,
                role: response.admin.role,
                createdAt: response.admin.created_at
            }

            user.value = {
                ...userCredential.user,
                profile: response.admin
            }

            return { success: true, user: userCredential.user, admin: admin.value }
        } catch (error) {
            console.error('Admin registration error:', error)
            throw new Error(error.message || '管理者登録に失敗しました')
        } finally {
            loading.value = false
        }
    }

    // パスワードリセット機能
    const resetPassword = async (email) => {
        try {
            loading.value = true
            const { $auth } = useNuxtApp()

            await sendPasswordResetEmail($auth, email)
            return { success: true }
        } catch (error) {
            console.error('Password reset error:', error)
            throw new Error(getAuthErrorMessage(error.code))
        } finally {
            loading.value = false
        }
    }

    // 一般ユーザーログアウト
    const logout = async () => {
        try {
            loading.value = true
            const { $auth } = useNuxtApp()

            await signOut($auth)
            user.value = null
            admin.value = null
            await navigateTo('/auth/login')

            return { success: true }
        } catch (error) {
            console.error('Logout error:', error)
            throw new Error('ログアウトに失敗しました')
        } finally {
            loading.value = false
        }
    }

    // 管理者ログアウト
    const adminLogout = async () => {
        try {
            loading.value = true
            const { $auth } = useNuxtApp()

            await signOut($auth)
            user.value = null
            admin.value = null
            await navigateTo('/admin/login')

            return { success: true }
        } catch (error) {
            console.error('管理者ログアウトエラー:', error)
            throw new Error('管理者ログアウトに失敗しました')
        } finally {
            loading.value = false
        }
    }

    // 管理者情報をセット（ログイン時に使用）
    const setAdmin = (adminData) => {
        admin.value = adminData
    }

    // IDトークン取得機能
    const getIdToken = async () => {
        try {
            if (user.value) {
                const token = await user.value.getIdToken()
                console.log('🔥 Firebase ID Token取得成功:', token)
                return token
            } else {
                console.log('❌ ユーザーがログインしていません')
                return null
            }
        } catch (error) {
            console.error('🚨 Token取得エラー:', error)
            return null
        }
    }

    // Laravel APIテスト機能
    const testLaravelAPI = async () => {
        try {
            const token = await getIdToken()
            if (!token) {
                console.log('❌ トークンが取得できません')
                return
            }

            console.log('📤 Laravel APIテスト開始...')
            console.log('🔑 使用するトークン:', token)
            
            // 実際のAPIリクエスト
            const config = useRuntimeConfig()
            const response = await $fetch('/auth/user', {
                baseURL: config.public.apiBaseUrl,
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            })
            
            console.log('✅ Laravel API Response:', response)
            return response
            
        } catch (error) {
            console.error('🚨 Laravel API エラー:', error)
            throw error
        }
    }

    // 認証状態の監視（自動初期化）
    const initAuth = () => {
        if (authInitialized.value) return

        console.log('🔧 認証状態の監視を開始...')

        const { $auth } = useNuxtApp()

        onAuthStateChanged($auth, async (firebaseUser) => {
            console.log('🔄 認証状態変更:', firebaseUser ? firebaseUser.email : 'null')
            
            if (firebaseUser) {
                try {
                    const token = await firebaseUser.getIdToken()
                    
                    // Laravel APIでユーザー情報と権限を確認
                    const config = useRuntimeConfig()
                    const response = await $fetch('/auth/user', {
                        baseURL: config.public.apiBaseUrl,
                        headers: {
                            'Authorization': `Bearer ${token}`
                        }
                    })

                    user.value = {
                        ...firebaseUser,
                        profile: response.user
                    }

                    // 管理者権限チェック
                    if (response.user && response.user.role === 'admin') {
                        admin.value = {
                            uid: firebaseUser.uid,
                            email: firebaseUser.email,
                            displayName: response.user.name || firebaseUser.displayName,
                            role: response.user.role,
                            createdAt: response.user.created_at
                        }
                    } else {
                        admin.value = null
                    }
                } catch (error) {
                    console.error('Laravel API エラー:', error)
                    // APIエラーの場合はFirebase認証のみ保持
                    user.value = firebaseUser
                    admin.value = null
                }
            } else {
                user.value = null
                admin.value = null
            }
        })

        authInitialized.value = true
    }

    // 認証状態の確立を待機
    const waitForAuth = () => {
        return new Promise((resolve) => {
            if (authInitialized.value && user.value !== undefined) {
                resolve(user.value)
                return
            }

            const { $auth } = useNuxtApp()
            const unsubscribe = onAuthStateChanged($auth, (firebaseUser) => {
                unsubscribe()
                user.value = firebaseUser
                resolve(firebaseUser)
            })
        })
    }

    // 管理者権限チェック
    const requireAdmin = async () => {
        if (!admin.value) {
            await navigateTo('/admin/login')
            return false
        }
        return true
    }

    // 認証チェック
    const requireAuth = async () => {
        if (!user.value) {
            await navigateTo('/auth/login')
            return false
        }
        return true
    }

    // ユーザー情報取得
    const getCurrentUser = () => {
        return user.value
    }

    // 管理者情報取得
    const getCurrentAdmin = () => {
        return admin.value
    }

    // 管理者判定
    const isAdmin = computed(() => {
        return !!admin.value && admin.value.role === 'admin'
    })

    // ログイン状態判定
    const isLoggedIn = computed(() => {
        return !!user.value
    })

    // エラーメッセージの変換
    const getAuthErrorMessage = (errorCode) => {
        const errorMessages = {
            'auth/user-not-found': 'ユーザーが見つかりません',
            'auth/wrong-password': 'パスワードが間違っています',
            'auth/email-already-in-use': 'このメールアドレスは既に使用されています',
            'auth/weak-password': 'パスワードが弱すぎます',
            'auth/invalid-email': 'メールアドレスの形式が正しくありません',
            'auth/too-many-requests': 'リクエストが多すぎます。しばらく待ってから再試行してください',
            'auth/network-request-failed': 'ネットワークエラーが発生しました'
        }

        return errorMessages[errorCode] || '認証エラーが発生しました'
    }

    // 自動的に認証状態の監視を開始
    if (process.client) {
        initAuth()
    }

    return {
        // 状態
        user: readonly(user),
        admin: readonly(admin),
        loading: readonly(loading),
        
        // 計算プロパティ
        isAdmin,
        isLoggedIn,
        
        // 一般ユーザー機能
        login,
        register,
        logout,
        requireAuth,
        
        // 管理者機能
        adminLogin,
        registerAdmin,
        adminLogout,
        setAdmin,
        requireAdmin,
        getCurrentAdmin,
        
        // 共通機能
        resetPassword,
        initAuth,
        waitForAuth,
        getCurrentUser,
        getIdToken,
        testLaravelAPI
    }
}