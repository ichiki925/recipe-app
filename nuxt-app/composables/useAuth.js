import {
    createUserWithEmailAndPassword,
    signInWithEmailAndPassword,
    signOut,
    onAuthStateChanged,
    getIdToken
} from 'firebase/auth'

import { computed, readonly } from 'vue'

export const useAuth = () => {
    const { $auth } = useNuxtApp()
    const config = useRuntimeConfig()
    const user = useState('auth.user', () => null)
    const loading = useState('auth.loading', () => false)

    const API_BASE_URL = config.public.apiBaseUrl

    const getCurrentUser = () => $auth.currentUser
    const waitForAuth = () =>
        new Promise((resolve) => {
            if ($auth.currentUser) return resolve($auth.currentUser)
            const unsubscribe = onAuthStateChanged($auth, (firebaseUser) => {
                unsubscribe()
                resolve(firebaseUser)
            })
        })

    const getFirebaseIdToken = async () => {
        const currentUser = $auth.currentUser
        if (!currentUser) throw new Error('User not authenticated')
        return await getIdToken(currentUser)
    }

    const cleanupFirebaseUser = async () => {
        if ($auth.currentUser) {
            try {
                await $auth.currentUser.delete()
            } catch (err) {
                console.error('Firebase認証ユーザー削除エラー:', err)
            }
        }
    }

    const registerUser = async (userData, endpoint) => {
        loading.value = true
        try {
            // 1) Firebase アカウント作成
            const { user: firebaseUser } = await createUserWithEmailAndPassword(
                $auth,
                userData.email,
                userData.password
            )

            // 2) Laravel に登録（パスワードは送らない）
            const response = await $fetch(endpoint, {
                method: 'POST',
                baseURL: API_BASE_URL,
                body: {
                    firebase_uid: firebaseUser.uid,
                    name: userData.name,
                    email: userData.email,
                    ...(userData.admin_code ? { admin_code: userData.admin_code } : {})
                },
                credentials: 'omit',   // ← 追加
                redirect: 'error'
            })

            if (!response?.success) {
                throw new Error(response?.error || 'ユーザー登録に失敗しました')
            }
            return response
        } catch (error) {
            await cleanupFirebaseUser()
            throw error
        } finally {
            loading.value = false
        }
    }

    const authenticateUser = async (idToken) => {
        const endpoints = ['api/admin/check', 'api/auth/check']
        const pickUser = (r) => r?.admin || r?.user || r?.data?.admin || r?.data?.user || r?.data || null

        for (const endpoint of endpoints) {
            try {
                const response = await $fetch(endpoint, {
                    baseURL: API_BASE_URL,
                    headers: {
                        'Authorization': `Bearer ${idToken}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'omit',   // Cookie送らない（CORSでBearer運用）
                    redirect: 'error'      // 302を即検知
                })
                const userData = pickUser(response)
                if (userData) return userData
                throw new Error('Invalid auth response')
            } catch (e) {
                // 最後まで失敗したら投げる
                if (endpoint === endpoints[endpoints.length - 1]) throw e
            }
        }
    }

    const register = (userData) =>
        registerUser(
            { name: userData.name, email: userData.email, password: userData.password },
            'api/auth/register'
        )

    const registerAdmin = (adminData) =>
        registerUser(
            {
                name: adminData.name,
                email: adminData.email,
                password: adminData.password,
                admin_code: adminData.adminCode
            },
            'api/admin/register'
        )

    const login = async (email, password) => {
        loading.value = true
        try {
            const { user: firebaseUser } = await signInWithEmailAndPassword($auth, email, password)
            const idToken = await firebaseUser.getIdToken()
            user.value = await authenticateUser(idToken)
            return user.value
        } catch (error) {
            console.error('❌ ログインエラー:', error)
            throw error
        } finally {
            loading.value = false
        }
    }

    const logout = async () => {
        try {
            const isAdminUser = user.value?.role === 'admin'
            await signOut($auth)
            user.value = null
            await navigateTo(isAdminUser ? '/admin/login' : '/')
        } catch (error) {
            console.error('ログアウトエラー:', error)
            throw error
        }
    }

    const initAuth = () =>
        new Promise((resolve) => {
            const unsubscribe = onAuthStateChanged($auth, async (firebaseUser) => {
                try {
                    loading.value = true
                    if (firebaseUser) {
                        const idToken = await firebaseUser.getIdToken()
                        user.value = await authenticateUser(idToken)
                    } else {
                        user.value = null
                    }
                } catch (error) {
                    console.error('❌ 認証初期化エラー:', error)
                    user.value = null
                } finally {
                    loading.value = false
                    resolve()
                    unsubscribe()
                }
            })
        })

    const isAdmin = computed(() => user.value?.role === 'admin')
    const isLoggedIn = computed(() => !!user.value)

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