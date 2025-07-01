// plugins/firebase.client.js - 新しいプロジェクト設定
import { initializeApp } from 'firebase/app'
import { getAuth } from 'firebase/auth'

export default defineNuxtPlugin(() => {
    // 新しいFirebaseプロジェクトの設定
    const firebaseConfig = {
        apiKey: "AIzaSyD58aQi1pH7507kCD-o35cHCVqv5wqwXoU",
        authDomain: "recipe-app-new-6d490.firebaseapp.com",
        projectId: "recipe-app-new-6d490",
        storageBucket: "recipe-app-new-6d490.firebasestorage.app",
        messagingSenderId: "412610584515",
        appId: "1:412610584515:web:8b64b750c653390ae22776"
    }

    try {
        console.log('🚀 NEW Firebase project initialization starting...')
        console.log('📋 NEW Config:', {
            projectId: firebaseConfig.projectId,
            authDomain: firebaseConfig.authDomain
        })

        const app = initializeApp(firebaseConfig)
        const auth = getAuth(app)
        
        console.log('✅ NEW Firebase project initialized successfully!')
        console.log('🔥 New App name:', app.name)
        console.log('🔐 New Auth instance:', !!auth)
        
        return {
            provide: {
                firebase: app,
                auth
            }
        }
    } catch (error) {
        console.error('❌ NEW Firebase initialization failed:', error)
        throw new Error(`New Firebase initialization failed: ${error.message}`)
    }
})