<template>
    <div class="recipe-detail">
        <!-- ローディング表示 -->
        <div v-if="loading" class="loading">
            レシピを読み込み中...
        </div>
    
        <!-- エラー表示 -->
        <div v-else-if="error" class="error">
            {{ error }}
        </div>
    
        <!-- レシピ詳細（ユーザー画面と同じレイアウト + 管理者サイドバー） -->
        <div v-else-if="recipe" class="recipe-page">
            <!-- 左サイドバー（管理者専用） -->
            <aside class="admin-sidebar">
                <div class="admin-actions">
                    <button @click="router.back()" class="action-btn back-btn">
                    <i class="fas fa-arrow-left"></i>
                    戻る
                    </button>
                    <button @click="editRecipe" class="action-btn edit-btn">
                    <i class="fas fa-edit"></i>
                    編集
                    </button>
                    <button @click="confirmDelete" class="action-btn delete-btn">
                    <i class="fas fa-trash"></i>
                    削除
                    </button>
                </div>
            
                <div class="admin-info">
                <h3>管理者情報</h3>
                    <div class="info-item">
                        <span class="label">レシピID:</span>
                        <span class="value">{{ recipe.id }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">いいね数:</span>
                        <span class="value">{{ recipe.likes_count || 0 }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">コメント数:</span>
                        <span class="value">{{ recipe.comments?.length || 0 }}</span>
                    </div>
                </div>
            </aside>

            <!-- メインコンテンツエリア -->
            <div class="recipe-create-container">
            <!-- 左カラム -->
            <div class="left-column">
                <h2 class="recipe-title-heading">{{ recipe.title }}</h2>

            <div class="image-preview">
                <span v-if="!recipe.image_url" id="preview-text">No Image</span>
                <img
                    v-else
                    :src="getImageUrl(recipe.image_url)"
                    :alt="recipe.title"
                    id="preview-image"
                    @error="handleImageError($event, recipe)"
                    @load="handleImageLoad($event, recipe)"
                />
            </div>

            <div class="comment-section">
                <ul id="comment-list">
                    <li
                    v-for="comment in displayedComments"
                    :key="comment.id"
                    class="comment-item"
                    >
                    <i class="fas fa-user comment-avatar-icon"></i>
                    <span class="username" :title="comment.user?.name">
                        {{ truncateUsername(comment.user?.name || 'ゲスト') }}
                    </span>
                    <span class="comment-body">{{ comment.content || comment.body }}</span>
                    </li>
                </ul>

                <!-- もっと見る/折りたたみボタン -->
                <div v-if="hasMoreComments" class="comment-toggle-section">
                    <button 
                    v-if="!showAllComments" 
                    @click="showAllComments = true"
                    class="comment-toggle-btn"
                    >
                    もっと見る ({{ remainingCount }}件)
                    </button>
                    <button 
                    v-else 
                    @click="showAllComments = false"
                    class="comment-toggle-btn"
                    >
                    表示を折りたたむ
                    </button>
                </div>

                <!-- 管理者メモ：コメント投稿は無効 -->
                <div class="admin-note">
                    <i class="fas fa-info-circle"></i>
                    管理者はコメント表示のみです
                </div>

                <div class="action-buttons">
                    <!-- いいね表示のみ（クリック不可） -->
                    <div class="like-display">
                        <i class="far fa-heart heart-icon"></i>
                        <span class="like-count">{{ recipe.likes_count || 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

            <!-- 右カラム -->
            <div class="right-column">
                <div class="recipe-form">
                <label>ジャンル</label>
                <div class="recipe-info">{{ recipe.genre || '未設定' }}</div>
    
                <label>材料（{{ recipe.servings || '人数未設定' }}）</label>
                <div id="ingredients">
                    <div
                    v-for="(ingredient, index) in recipe.ingredients_array"
                    :key="index"
                    class="ingredient-row"
                    >
                    <div class="ingredient-name">{{ ingredient.name }}</div>
                    <div class="ingredient-qty">{{ ingredient.amount }}</div>
                    </div>
                </div>
    
                <label>作り方</label>
                <div class="recipe-body">
                    <p v-if="typeof recipe.instructions === 'string'">{{ recipe.instructions }}</p>
                    <ol v-else-if="Array.isArray(recipe.instructions_array)">
                        <li 
                            v-for="(step, index) in recipe.instructions_array" 
                            :key="index"
                            class="instruction-step"
                        >
                            {{ step }}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- 削除確認モーダル -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="showDeleteModal = false">
        <div class="modal-content" @click.stop>
        <h3>レシピの削除</h3>
        <p>「{{ recipe?.title }}」を削除しますか？</p>
        <p class="warning">この操作は取り消すことができません。</p>
            <div class="modal-actions">
                <button @click="showDeleteModal = false" class="cancel-btn">キャンセル</button>
                <button @click="deleteRecipe" class="confirm-delete-btn" :disabled="deleting">
                    {{ deleting ? '削除中...' : '削除する' }}
                </button>
            </div>
            </div>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    layout: 'admin'
})

import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter, useHead } from '#app'

useHead({
    link: [
        {
            rel: 'stylesheet',
            href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'
        }
        ]
    })

const route = useRoute()
const router = useRouter()

// 画像URL処理関数
const getImageUrl = (imageUrl) => {
    console.log('🖼️ Original image URL:', imageUrl)
    
    if (!imageUrl) {
        return '/images/no-image.png'
    }
    
    // 相対URLの場合、絶対URLに変換
    if (imageUrl.startsWith('/storage/')) {
        const fullUrl = `http://localhost${imageUrl}`
        console.log('🔗 Converted to full URL:', fullUrl)
        return fullUrl
    }
    
    return imageUrl
    }

    // 画像読み込みエラーハンドリング
    const handleImageError = (event, recipe) => {
    console.error('❌ Image load failed:', {
        recipe_id: recipe.id,
        recipe_title: recipe.title,
        image_url: recipe.image_url,
        attempted_src: event.target.src
    })
    
    // エラー時はデフォルト画像に変更
    event.target.src = '/images/no-image.png'
    }

    // 画像読み込み成功時
    const handleImageLoad = (event, recipe) => {
    console.log('✅ Image loaded successfully:', {
        recipe_id: recipe.id,
        recipe_title: recipe.title,
        loaded_src: event.target.src
    })
}

// データ定義
const recipe = ref(null)
const loading = ref(true)
const error = ref('')
const showDeleteModal = ref(false)
const deleting = ref(false)
const showAllComments = ref(false)

// レシピID取得
const recipeId = route.params.id

// 表示するコメントを制御
const displayedComments = computed(() => {
    if (!recipe.value?.comments) return []

    if (showAllComments.value) {
        return [...recipe.value.comments].reverse()
        } else {
        return [...recipe.value.comments].reverse().slice(0, 3)
    }
})

// 残りのコメント数
const remainingCount = computed(() => {
    if (!recipe.value?.comments) return 0
    return Math.max(0, recipe.value.comments.length - 3)
})

// もっと見るボタンの表示判定
const hasMoreComments = computed(() => {
    if (!recipe.value?.comments) return false
    return recipe.value.comments.length > 3
})

// ユーザー名の省略処理
const truncateUsername = (username) => {
    if (!username) return 'ゲスト'
    return username.length > 10 ? username.substring(0, 10) + '...' : username
}

// 初期化
onMounted(() => {
    fetchRecipe()
})

// レシピデータ取得
const fetchRecipe = async () => {
    loading.value = true
    error.value = ''
    recipe.value = null
    
    try {
        console.log('🔍 レシピ取得開始:', {
            recipeId,
            timestamp: new Date().toISOString()
        })

        // 認証確認
        const { $auth } = useNuxtApp()
        
        if (!$auth?.currentUser) {
            throw new Error('認証が必要です')
        }

        console.log('🔑 認証ユーザー確認:', {
            uid: $auth.currentUser.uid,
            email: $auth.currentUser.email
        })

        const token = await $auth.currentUser.getIdToken()
        
        if (!token) {
            throw new Error('認証トークンの取得に失敗しました')
        }

        console.log('🎫 認証トークン取得成功')

        // APIリクエスト
        const apiUrl = `http://localhost/api/admin/recipes/${recipeId}`
        console.log('📡 APIリクエスト送信:', apiUrl)

        const response = await fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })

        console.log('📊 APIレスポンス受信:', {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok,
            contentType: response.headers.get('content-type')
        })

        // レスポンステキストを取得
        const responseText = await response.text()
        console.log('📄 レスポンステキスト:', responseText.substring(0, 500) + (responseText.length > 500 ? '...' : ''))

        if (!response.ok) {
            console.error('❌ HTTPエラー:', {
                status: response.status,
                statusText: response.statusText,
                responseText
            })
            
            // エラーレスポンスの解析を試行
            try {
                const errorData = JSON.parse(responseText)
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`)
            } catch (parseError) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`)
            }
        }

        // JSONパース
        let responseData
        try {
            responseData = JSON.parse(responseText)
        } catch (parseError) {
            console.error('❌ JSONパースエラー:', parseError)
            throw new Error('サーバーからの応答が不正です')
        }

        console.log('✅ レスポンスデータパース成功:', {
            status: responseData.status,
            hasData: !!responseData.data,
            dataKeys: responseData.data ? Object.keys(responseData.data) : []
        })

        // データ構造の確認と設定
        if (responseData.status === 'success' && responseData.data) {
            recipe.value = responseData.data
            
            console.log('✅ レシピデータ設定完了:', {
                id: recipe.value.id,
                title: recipe.value.title,
                hasIngredients: !!recipe.value.ingredients,
                hasInstructions: !!recipe.value.instructions,
                ingredientsArrayLength: recipe.value.ingredients_array?.length || 0,
                instructionsArrayLength: recipe.value.instructions_array?.length || 0,
                commentsCount: recipe.value.comments?.length || 0,
                hasAdmin: !!recipe.value.admin,
                imageUrl: recipe.value.image_url
            })
            
        } else {
            console.error('❌ 予期しないレスポンス構造:', responseData)
            throw new Error('レスポンスデータの形式が不正です')
        }
        
    } catch (err) {
        console.error('❌ レシピ取得エラー:', {
            error: err.message,
            stack: err.stack,
            recipeId
        })
        
        // エラーメッセージの設定
        if (err.message.includes('401') || err.message.includes('認証')) {
            error.value = '認証が無効です。再ログインしてください。'
        } else if (err.message.includes('403')) {
            error.value = 'このレシピを表示する権限がありません。'
        } else if (err.message.includes('404')) {
            error.value = 'レシピが見つかりません。'
        } else if (err.message.includes('500')) {
            error.value = 'サーバーエラーが発生しました。しばらく時間をおいて再度お試しください。'
        } else if (err.message.includes('NetworkError') || err.message.includes('fetch')) {
            error.value = 'ネットワークエラーが発生しました。接続を確認してください。'
        } else {
            error.value = `レシピの取得に失敗しました: ${err.message}`
        }
        
    } finally {
        loading.value = false
        console.log('🏁 レシピ取得処理完了:', {
            loading: loading.value,
            hasError: !!error.value,
            hasRecipe: !!recipe.value,
            recipeId
        })
    }
}

// デバッグ用の追加メソッドも定義してください
const debugInfo = () => {
    console.log('🐛 デバッグ情報:', {
        route: route.params,
        recipeId,
        loading: loading.value,
        error: error.value,
        hasRecipe: !!recipe.value,
        recipeTitle: recipe.value?.title,
        currentUser: useNuxtApp().$auth?.currentUser?.uid
    })
}

// 材料文字列を配列に変換
const parseIngredients = (ingredientsStr) => {
    if (!ingredientsStr) return []

    return ingredientsStr.split('\n').map(line => {
        const parts = line.trim().split(/\s+/)
        return {
            name: parts[0] || '',
            amount: parts.slice(1).join(' ') || ''
        }
    }).filter(ingredient => ingredient.name)
}

// 作り方文字列を配列に変換
const parseInstructions = (instructionsStr) => {
    if (!instructionsStr) return []

    return instructionsStr.split('\n')
        .map(line => line.trim())
        .filter(line => line.length > 0)
}

// 編集画面へ遷移
const editRecipe = () => {
    router.push(`/admin/recipes/edit/${recipeId}`)
}

// 削除確認モーダル表示
const confirmDelete = () => {
    showDeleteModal.value = true
}

// レシピ削除実行
const deleteRecipe = async () => {
    deleting.value = true
    
    try {
        await $fetch(`/api/admin/recipes/${recipeId}`, {
            method: 'DELETE'
        })
        
        // 削除成功 → 一覧画面に戻る
        alert('レシピが削除されました')
        await router.push('/admin/recipes')
        
    } catch (err) {
        console.error('削除エラー:', err)
        
        // より詳細なエラー処理
        let errorMessage = '削除に失敗しました。もう一度お試しください。'
        
        if (err.status === 404) {
            errorMessage = 'レシピが見つかりません。'
        } else if (err.status === 403) {
            errorMessage = 'このレシピを削除する権限がありません。'
        } else if (err.status === 500) {
            errorMessage = 'サーバーエラーが発生しました。しばらく時間をおいて再度お試しください。'
        }
        
        alert(errorMessage)
    } finally {
        deleting.value = false
        showDeleteModal.value = false
    }
}
</script>

<style scoped>
    @import '@/assets/css/common.css';

    .recipe-detail {
        padding: 20px;
    }
    
    .recipe-page {
        display: flex;
        gap: 30px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    /* 左サイドバー（管理者専用） */
    .admin-sidebar {
        width: 300px;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        height: fit-content;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        flex-shrink: 0;
    }
    
    .admin-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 30px;
    }
    
    .action-btn {
        width: 100%;
        padding: 12px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.2s;
        font-size: 14px;
    }
    
    .back-btn {
        background-color: #ddd;
        color: #000;
    }
    
    .back-btn:hover {
        background-color: #ccc;
    }
    
    .edit-btn {
        background-color: #fbc559f6;
        color: white;
    }
    
    .edit-btn:hover {
        background-color: #f6ad1af6;
    }
    
    .delete-btn {
        background-color: #ec8892f5;
        color: white;
    }
    
    .delete-btn:hover {
        background-color: #c82333;
    }
    
    .admin-info {
        border-top: 1px solid #e9ecef;
        padding-top: 20px;
    }
    
    .admin-info h3 {
        font-size: 16px;
        margin-bottom: 15px;
        color: #333;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .info-item .label {
        color: #666;
    }
    
    .info-item .value {
        font-weight: 500;
        color: #333;
    }
    
    .loading, .error {
        text-align: center;
        padding: 40px;
        font-size: 16px;
        color: #666;
    }
    
    .error {
        color: #dc3545;
    }
    
    /* メインコンテンツエリア */
    .recipe-create-container {
        display: flex;
        gap: 40px;
        justify-content: center;
        align-items: flex-start;
        flex: 1;
    }
    
    /* 左カラム */
    .left-column {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 300px;
        min-width: 300px;
        flex-shrink: 0;
        gap: 30px;
    }
    
    .recipe-title-heading {
        font-size: 20px;
        font-weight: 500;
        margin-bottom: 10px;
        text-align: center;
    }
    
    .image-preview {
        width: 100%;
        aspect-ratio: 1 / 1;
        background-color: #f0f0f0;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        height: 300px;
    }
    
    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .comment-section {
        width: 100%;
    }
    
    .comment-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .comment-avatar-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        margin: 8px;
        font-size: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #eee;
        color: #666;
    }
    
    .username {
        margin-right: 2px;
        font-size: 10px;
        white-space: nowrap;
        max-width: 80px;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 600;
        color: #333;
        cursor: default;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
    }
    
    .comment-body {
        flex: 1;
        font-size: 12px;
        line-height: 1.4;
        word-wrap: break-word;
    }
    
    .comment-toggle-section {
        margin-top: 10px;
        margin-bottom: 10px;
        text-align: center;
    }
    
    .comment-toggle-btn {
        background: none;
        border: 1px solid #bbb;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 11px;
        color: #333;
        cursor: pointer;
        transition: all 0.2s ease;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
    }
    
    .comment-toggle-btn:hover {
        background-color: #f5f5f5;
    }
    
    .admin-note {
        background-color: #e3f2fd;
        color: #1976d2;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 12px;
        text-align: center;
        margin: 15px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .like-display {
        display: flex;
        align-items: center;
        gap: 4px;
        color: #333;
        justify-content: center;
    }
    
    .heart-icon {
        font-size: 18px;
        color: #dc3545;
    }
    
    .like-count {
        font-size: 12px;
        color: #333;
        font-family: cursive;
    }
    
    /* 右カラム */
    .right-column {
        width: 400px;
        min-height: 100%;
    }
    
    .recipe-form {
        width: 100%;
    }
    
    .recipe-form label {
        display: block;
        font-weight: bold;
        margin-top: 25px;
        margin-bottom: 10px;
    }
    
    .recipe-info {
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
        margin-bottom: 15px;
    }
    
    .ingredient-row {
        display: flex;
        gap: 0px;
        margin-bottom: 10px;
    }
    
    .ingredient-name,
    .ingredient-qty {
        width: 100%;
        padding: 10px;
        font-size: 14px;
        box-sizing: border-box;
        background-color: transparent;
        border: none;
        border-bottom: 1px solid #ccc;
        border-radius: 0;
    }
    
    .ingredient-name {
        flex: 2;
    }
    
    .ingredient-qty {
        flex: 1;
    }
    
    .recipe-body {
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 4px;
        white-space: pre-wrap;
        line-height: 1.6;
    }
    
    .instruction-step {
        margin-bottom: 8px;
        line-height: 1.5;
    }
    
    /* モーダル */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    
    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 8px;
        max-width: 400px;
        width: 90%;
        text-align: center;
    }
    
    .modal-content h3 {
        margin-top: 0;
        color: #dc3545;
    }
    
    .warning {
        color: #856404;
        font-size: 14px;
    }
    
    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }
    
    .cancel-btn {
        padding: 8px 20px;
        background-color: #6c757d;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .confirm-delete-btn {
        padding: 8px 20px;
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .confirm-delete-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* レスポンシブ */
    @media (max-width: 768px) {
    .recipe-page {
        flex-direction: column;
        gap: 20px;
    }
    
    .admin-sidebar {
        width: 100%;
        order: 1;
    }
    
    .recipe-create-container {
        flex-direction: column;
        gap: 30px;
        order: 2;
    }
    
    .left-column {
        width: 100%;
        min-width: auto;
        gap: 20px;
    }
    
    .right-column {
        width: 100%;
    }
    
    .admin-actions {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .action-btn {
        flex: 1;
        min-width: 120px;
    }
    
    .recipe-title-heading {
        font-size: 18px;
    }
    
    .image-preview {
        max-width: 280px;
        max-height: 280px;
    }
}
</style>