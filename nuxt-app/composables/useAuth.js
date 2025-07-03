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
const loading = ref(false)
const authInitialized = ref(false)

export const useAuth = () => {

    // ログイン機能
    const login = async (email, password) => {
        try {
            loading.value = true
            const { $auth } = useNuxtApp()

            const userCredential = await signInWithEmailAndPassword($auth, email, password)
            user.value = userCredential.user

            return { success: true, user: userCredential.user }
        } catch (error) {
            console.error('Login error:', error)
            throw new Error(getAuthErrorMessage(error.code))
        } finally {
            loading.value = false
        }
    }

    // 一般ユーザー登録機能
    const register = async (email, password, name) => {
        try {
            loading.value = true
            const { $auth } = useNuxtApp()

            const userCredential = await createUserWithEmailAndPassword($auth, email, password)

            // メール認証送信
            await sendEmailVerification(userCredential.user)

            // Laravel側にユーザー情報保存
            // await $fetch('/api/users', {
            // method: 'POST',
            // body: {
            //     firebase_uid: userCredential.user.uid,
            //     name,
            //     email,
            //     role: 'user'
            // }
            // })

            user.value = userCredential.user
            return { success: true, user: userCredential.user }
        } catch (error) {
            console.error('Registration error:', error)
            throw new Error(getAuthErrorMessage(error.code))
        } finally {
            loading.value = false
        }
    }

    // 管理者登録機能
    const registerAdmin = async (formData) => {
        try {
            loading.value = true

            // 1. 管理者コード検証
            // const codeVerification = await $fetch('/api/admin/verify-code', {
            // method: 'POST',
            // body: { code: formData.adminCode }
            // })

            // if (!codeVerification.valid) {
            // throw new Error('無効な管理者コードです')
            // }

            const { $auth } = useNuxtApp()

            // 2. Firebase Authentication
            const userCredential = await createUserWithEmailAndPassword(
            $auth,
            formData.email,
            formData.password
            )

            // 3. メール認証送信
            await sendEmailVerification(userCredential.user)

            // 4. Laravel側にユーザー情報保存
            // await $fetch('/api/admin/register', {
            // method: 'POST',
            // body: {
            //     firebase_uid: userCredential.user.uid,
            //     name: formData.name,
            //     email: formData.email,
            //     role: 'admin'
            // }
            // })

            user.value = userCredential.user
            return { success: true, user: userCredential.user }
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

    // ログアウト機能
    const logout = async () => {
        try {
            loading.value = true
            const { $auth } = useNuxtApp()

            await signOut($auth)
            user.value = null

            // ログイン画面にリダイレクト
            await navigateTo('/auth/login')

            return { success: true }
        } catch (error) {
            console.error('Logout error:', error)
            throw new Error('ログアウトに失敗しました')
        } finally {
            loading.value = false
        }
    }

    // 認証状態の監視（自動初期化）
    const initAuth = () => {
        if (authInitialized.value) return

        console.log('🔧 認証状態の監視を開始...')

        const { $auth } = useNuxtApp()

        onAuthStateChanged($auth, (firebaseUser) => {
            console.log('🔄 認証状態変更:', firebaseUser ? firebaseUser.email : 'null')
            user.value = firebaseUser
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

    // ユーザー情報取得
    const getCurrentUser = () => {
        return user.value
    }

    // 管理者判定
    const isAdmin = computed(() => {
      // TODO: Laravel APIでroleを確認
        return false
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
        user: readonly(user),
        loading: readonly(loading),
        login,
        register,
        registerAdmin,
        resetPassword,
        logout,
        initAuth,
        waitForAuth,
        getCurrentUser,
        isAdmin,
        isLoggedIn
    }
}