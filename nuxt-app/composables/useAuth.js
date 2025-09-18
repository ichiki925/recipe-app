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
                    // 必要なフィールドだけ明示的に
                    name: userData.name,
                    email: userData.email,
                    ...(userData.admin_code ? { admin_code: userData.admin_code } : {})
                }
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
        const endpoints = ['/api/auth/check', '/api/admin/check']
        for (let i = 0; i < endpoints.length; i++) {
            try {
                const res = await $fetch(endpoints[i], {
                    baseURL: API_BASE_URL,
                    headers: { Authorization: `Bearer ${idToken}` }
                })
                return res.user || res.admin
            } catch (e) {
                if (i === endpoints.length - 1) throw e
            }
        }
    }


    const register = (userData) =>
        registerUser(
            { name: userData.name, email: userData.email, password: userData.password },
            '/api/auth/register'
        )

    const registerAdmin = (adminData) =>
        registerUser(
            {
                name: adminData.name,
                email: adminData.email,
                password: adminData.password,
                admin_code: adminData.adminCode
            },
            '/api/admin/register'
        )

    const login = async (email, password) => {
        loading.value = true
        try {
            const { user: firebaseUser } = await signInWithEmailAndPassword($auth, email, password)
            const idToken = await firebaseUser.getIdToken()
            user.value = await authenticateUser(idToken)
            return user.value
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
                    if (firebaseUser) {
                        const idToken = await firebaseUser.getIdToken()
                        user.value = await authenticateUser(idToken)
                    } else {
                        user.value = null
                    }
                } catch (e) {
                    console.error('ユーザー情報取得エラー:', e)
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