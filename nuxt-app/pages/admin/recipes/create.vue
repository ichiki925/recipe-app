<template>
  <div class="recipe-create-container">
    <!-- 左サイドバー：保存中のレシピ一覧 -->
    <aside class="saved-recipes-sidebar">
      <h3>保存中のレシピ</h3>
      <div class="saved-recipes-list">
        <div
          v-for="savedRecipe in savedRecipes"
          :key="savedRecipe.id"
          class="saved-recipe-tag"
          @click="loadSavedRecipe(savedRecipe)"
        >
          <div class="saved-recipe-title">{{ savedRecipe.title || '無題のレシピ' }}</div>
          <div class="saved-recipe-date">{{ formatDate(savedRecipe.savedAt) }}</div>
          <button
            class="delete-saved-recipe"
            @click.stop="deleteSavedRecipe(savedRecipe.id)"
          >
            ×
          </button>
        </div>
        <div v-if="savedRecipes.length === 0" class="no-saved-recipes">
          保存中のレシピはありません
        </div>
      </div>
    </aside>

    <!-- メインコンテンツエリア -->
    <div class="main-content">
      <!-- 左側：画像プレビューエリア -->
      <div class="image-preview" @click="triggerImageInput">
        <span v-if="!imagePreview" id="preview-text">No Image</span>
        <img
          v-if="imagePreview"
          :src="imagePreview"
          alt="プレビュー"
          id="preview-image"
        />
      </div>
      <input
        type="file"
        ref="imageInput"
        style="display: none"
        accept="image/*"
        @change="previewImage"
      />

      <!-- 右側：入力フォーム -->
      <form class="recipe-form" @submit.prevent="submitRecipe">
      <h2>New Recipe</h2>

      <!-- 現在編集中のレシピ表示 -->
      <div v-if="currentEditingRecipe" class="editing-recipe-info">
        <span>編集中: {{ currentEditingRecipe.title || '無題のレシピ' }}</span>
        <button type="button" @click="clearCurrentRecipe" class="clear-editing">
          新規作成
        </button>
      </div>

      <!-- エラーメッセージ表示 -->
      <div v-if="errors.length > 0" class="error-messages">
        <div v-for="error in errors" :key="error" class="error-message">
          {{ error }}
        </div>
      </div>

      <!-- 成功メッセージ表示 -->
      <div v-if="successMessage" class="success-message">
        {{ successMessage }}
      </div>

      <label>料理名</label>
      <input type="text" v-model="form.title" class="recipe-title" required />

      <label>ジャンル</label>
      <input type="text" v-model="form.genre" class="recipe-title" />

      <label>人数</label>
      <select v-model="form.servings" class="servings-input" required>
        <option value="">選択してください</option>
        <option value="1人分">1人分</option>
        <option value="2人分">2人分</option>
        <option value="3人分">3人分</option>
        <option value="4人分">4人分</option>
        <option value="5人分以上">5人分以上</option>
      </select>

      <label>材料</label>
      <div id="ingredients">
        <div
          class="ingredient-row"
          v-for="(ingredient, index) in form.ingredients"
          :key="index"
        >
          <input
            type="text"
            v-model="ingredient.name"
            class="ingredient-name"
            placeholder="材料名"
          />
          <input
            type="text"
            v-model="ingredient.qty"
            class="ingredient-qty"
            placeholder="分量"
          />
        </div>
      </div>

      <label>作り方</label>
      <textarea
        v-model="form.instructions"
        class="auto-resize"
        @input="resizeTextarea"
        placeholder="作り方を入力してください"
        required
      ></textarea>

      <!-- 保存と投稿のボタン -->
      <div class="button-container">
        <button 
          type="button" 
          class="save-button"
          @click="saveRecipe"
          :disabled="isSaving"
        >
          {{ isSaving ? '保存中...' : '保存' }}
        </button>
        <button 
          type="submit" 
          class="submit-button"
          :disabled="isSubmitting"
        >
          {{ isSubmitting ? '投稿中...' : '投稿する' }}
        </button>
      </div>
    </form>
    </div>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin'
})
import { ref, reactive, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const form = reactive({
  title: '',
  genre: '',
  servings: '',
  ingredients: [{ name: '', qty: '' }],
  instructions: ''
})

const imageInput = ref(null)
const imagePreview = ref('')
const selectedFile = ref(null)
const errors = ref([])
const successMessage = ref('')
const isSubmitting = ref(false)
const isSaving = ref(false)
const savedRecipes = ref([])
const currentEditingRecipe = ref(null)

// 保存中のレシピを読み込み
const loadSavedRecipes = () => {
  const saved = localStorage.getItem('savedRecipes')
  if (saved) {
    savedRecipes.value = JSON.parse(saved)
  }
}

// 保存中のレシピを更新
const updateSavedRecipes = () => {
  localStorage.setItem('savedRecipes', JSON.stringify(savedRecipes.value))
}

// 日付フォーマット
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('ja-JP', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// 保存機能
const saveRecipe = () => {
  isSaving.value = true

  const recipeData = {
    id: currentEditingRecipe.value?.id || Date.now().toString(),
    title: form.title,
    genre: form.genre,
    servings: form.servings,
    ingredients: [...form.ingredients],
    instructions: form.instructions,
    imagePreview: imagePreview.value,
    savedAt: new Date().toISOString()
  }

  // 既存のレシピを更新または新規追加
  const existingIndex = savedRecipes.value.findIndex(r => r.id === recipeData.id)
  if (existingIndex !== -1) {
    savedRecipes.value[existingIndex] = recipeData
  } else {
    savedRecipes.value.unshift(recipeData)
  }

  // 最大10件まで保存
  if (savedRecipes.value.length > 10) {
    savedRecipes.value = savedRecipes.value.slice(0, 10)
  }

  updateSavedRecipes()
  currentEditingRecipe.value = recipeData

  successMessage.value = 'レシピを保存しました'
  setTimeout(() => {
    successMessage.value = ''
  }, 3000)

  isSaving.value = false
}

// 保存済みレシピを読み込み
const loadSavedRecipe = (savedRecipe) => {
  Object.assign(form, {
    title: savedRecipe.title,
    genre: savedRecipe.genre,
    servings: savedRecipe.servings,
    ingredients: savedRecipe.ingredients.length > 0 ? savedRecipe.ingredients : [{ name: '', qty: '' }],
    instructions: savedRecipe.instructions
  })

  imagePreview.value = savedRecipe.imagePreview || ''
  currentEditingRecipe.value = savedRecipe

  successMessage.value = 'レシピを読み込みました'
  setTimeout(() => {
    successMessage.value = ''
  }, 3000)
}

// 保存済みレシピを削除
const deleteSavedRecipe = (id) => {
  if (confirm('このレシピを削除しますか？')) {
    savedRecipes.value = savedRecipes.value.filter(r => r.id !== id)
    updateSavedRecipes()

    if (currentEditingRecipe.value?.id === id) {
      currentEditingRecipe.value = null
    }
  }
}

// 新規作成モードに切り替え
const clearCurrentRecipe = () => {
  if (confirm('現在の編集内容をクリアして新規作成しますか？')) {
    Object.assign(form, {
      title: '',
      genre: '',
      servings: '',
      ingredients: [{ name: '', qty: '' }],
      instructions: ''
    })

    imagePreview.value = ''
    selectedFile.value = null
    currentEditingRecipe.value = null
    errors.value = []
    successMessage.value = ''
  }
}

const triggerImageInput = () => {
  imageInput.value?.click()
}

const previewImage = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const removeIngredient = (index) => {
  if (form.ingredients.length > 1) {
    form.ingredients.splice(index, 1)
  }
}

// 材料入力時の動的追加
watch(
  () => form.ingredients,
  (newIngredients) => {
    const last = newIngredients[newIngredients.length - 1]
    if (last && (last.name || last.qty)) {
      form.ingredients.push({ name: '', qty: '' })
    }
  },
  { deep: true }
)

const resizeTextarea = (event) => {
  const textarea = event.target
  textarea.style.height = 'auto'
  textarea.style.height = Math.max(80, textarea.scrollHeight) + 'px'
}

// 材料を文字列形式に変換
const formatIngredients = () => {
  return form.ingredients
    .filter(ingredient => ingredient.name.trim() || ingredient.qty.trim())
    .map(ingredient => `${ingredient.name.trim()} ${ingredient.qty.trim()}`)
    .join('\n')
}

const submitRecipe = async () => {
  errors.value = []
  successMessage.value = ''
  isSubmitting.value = true

  try {
    const { $auth } = useNuxtApp()

    if (!$auth?.currentUser) {
      errors.value.push('認証が必要です。')
      isSubmitting.value = false
      return
    }

    const token = await $auth.currentUser.getIdToken()

    // バリデーション
    if (!form.title.trim()) {
      errors.value.push('料理名は必須です')
    }
    if (!form.servings) {
      errors.value.push('人数を選択してください')
    }
    if (!form.instructions.trim()) {
      errors.value.push('作り方は必須です')
    }

    const ingredientsText = formatIngredients()
    if (!ingredientsText) {
      errors.value.push('材料は必須です')
    }

    if (errors.value.length > 0) {
      isSubmitting.value = false
      return
    }

    const formData = new FormData()
    formData.append('title', form.title)
    formData.append('genre', form.genre || '')
    formData.append('servings', form.servings)
    formData.append('ingredients', ingredientsText)
    formData.append('instructions', form.instructions)

    if (selectedFile.value) {
      formData.append('image', selectedFile.value)
    }

    console.log('🚀 APIリクエストを送信中...')
    console.log('📝 送信データ:', {
      title: form.title,
      genre: form.genre,
      servings: form.servings,
      ingredients: ingredientsText,
      instructions: form.instructions,
      hasImage: !!selectedFile.value
    })


    // Docker環境用の絶対URL（ここが重要な修正箇所）
    const response = await fetch('http://localhost/api/admin/recipes', {
      method: 'POST',
      body: formData,
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })

    console.log('📊 レスポンス状況:', {
      status: response.status,
      statusText: response.statusText,
      ok: response.ok
    })


    if (!response.ok) {
      const errorText = await response.text()
      console.error('❌ API Error Response:', errorText)
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()
    console.log('✅ API response:', data)

    successMessage.value = 'レシピが投稿されました'

    // フォームリセット
    Object.assign(form, {
      title: '',
      genre: '',
      servings: '',
      ingredients: [{ name: '', qty: '' }],
      instructions: ''
    })

    imagePreview.value = ''
    selectedFile.value = null
    currentEditingRecipe.value = null

    // 保存中のレシピも削除（投稿成功時）
    if (currentEditingRecipe.value?.id) {
      savedRecipes.value = savedRecipes.value.filter(r => r.id !== currentEditingRecipe.value.id)
      updateSavedRecipes()
    }

    // リダイレクト
    if (data.data?.id) {
      setTimeout(() => {
        router.push(`/admin/recipes/show/${data.data.id}`)
      }, 1500)
    } else {
      setTimeout(() => {
        router.push('/admin/recipes')
      }, 1500)
    }

  } catch (error) {
    console.error('❌ API error:', error)
    errors.value = [`API呼び出しエラー: ${error.message}`]
  } finally {
    isSubmitting.value = false
  }
}


// 自動保存機能（オプション）
let autoSaveTimer = null
const startAutoSave = () => {
  if (autoSaveTimer) {
    clearInterval(autoSaveTimer)
  }

  autoSaveTimer = setInterval(() => {
    // 何かしらの入力があった場合のみ自動保存
    if (form.title || form.genre || form.instructions || 
        form.ingredients.some(ing => ing.name || ing.qty)) {
      saveRecipe()
    }
  }, 60000) // 1分間隔で自動保存
}

// フォーム変更時の自動保存設定
watch(form, () => {
  // 入力変更があったら自動保存タイマーをリセット
  if (autoSaveTimer) {
    clearInterval(autoSaveTimer)
    startAutoSave()
  }
}, { deep: true })

onMounted(() => {
  loadSavedRecipes()
  // startAutoSave() // 自動保存を有効にする場合はコメントアウト
})
</script>

<style scoped>
@import '@/assets/css/common.css';

body {
    margin: 0;
    font-family: sans-serif;
}

/* 全体のレイアウト */
.recipe-create-container {
    display: flex;
    gap: 30px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* 左サイドバー：保存中のレシピエリア */
.saved-recipes-sidebar {
    width: 300px;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    height: fit-content;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}

.saved-recipes-sidebar h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
    color: #333;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 8px;
}

.saved-recipes-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 600px;
    overflow-y: auto;
}

.saved-recipe-tag {
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 12px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.saved-recipe-tag:hover {
    background-color: #f0f0f0;
    border-color: #ccc;
}

.saved-recipe-title {
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 4px;
    color: #333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: calc(100% - 25px);
}

.saved-recipe-date {
    font-size: 12px;
    color: #666;
}

.delete-saved-recipe {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    border: none;
    color: #555;
    border-radius: 4px;
    cursor: pointer;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    line-height: 1;
    font-family: system-ui, sans-serif;
}



.delete-saved-recipe:hover {
    background-color: #c5414182;
}

.no-saved-recipes {
    text-align: center;
    color: #999;
    font-size: 14px;
    padding: 20px;
}

/* メインコンテンツエリア（画像とフォーム） */
.main-content {
    display: flex;
    gap: 40px;
    justify-content: center;
    align-items: flex-start;
    flex: 1;
}

/* 中央：画像プレビューエリア */
.image-preview {
    width: 300px;
    height: 300px;
    background-color: #f0f0f0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 80px;
    cursor: pointer;
    overflow: hidden;
    position: relative;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    color: #999;
    font-size: 18px;
}

/* 右側：入力フォーム */
.recipe-form {
    width: 400px;
}

.recipe-form h2 {
    margin-bottom: 20px;
    text-align: center;
    font-family: cursive;
}

/* 編集中のレシピ情報 */
.editing-recipe-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #e7f3ff;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 14px;
}

.clear-editing {
    background: #666;
    color: white;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.clear-editing:hover {
    background: #555;
}

.recipe-form label {
    display: block;
    font-weight: bold;
    margin-top: 15px;
    margin-bottom: 5px;
}

.recipe-title {
    width: 100%;
    padding: 10px;
    font-size: 16px;
    border: 1px solid #aaa;
    border-radius: 6px;
    box-sizing: border-box;
}

.recipe-title:focus {
    outline: none;
    border: 1px solid #aaa;
}

/* 材料名と分量を横並び */
.recipe-form .ingredient-row {
    display: flex;
    gap: 0px;
    margin-bottom: 10px;
}

/* 材料名入力欄 */
.recipe-form .ingredient-name {
    flex: 2;
    border: 1px solid #aaa !important;
    border-right: none !important;
    border-radius: 6px 0 0 6px !important;
    padding: 10px !important;
    box-sizing: border-box;
}

.recipe-form .ingredient-name:focus {
    outline: none;
    border: 1px solid #aaa !important;
    border-right: none !important;
}

/* 分量入力欄 */
.recipe-form .ingredient-qty {
    flex: 1;
    border: 1px solid #aaa !important;
    border-left: none !important;
    border-radius: 0 6px 6px 0 !important;
    padding: 10px !important;
    box-sizing: border-box;
}

.recipe-form .ingredient-qty:focus {
    outline: none;
    border: 1px solid #aaa !important;
    border-left: none !important;
}

.servings-input {
    width: 150px;
    padding: 8px;
    font-size: 14px;
    margin-bottom: 10px;
    border: 1px solid #aaa;
    border-radius: 6px;
}

.servings-input:focus {
    outline: none;
    border: 1px solid #aaa;
}

.auto-resize {
    width: 100%;
    min-height: 80px;
    resize: none;
    padding: 10px;
    border: 1px solid #aaa;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
    overflow: auto;
}

.auto-resize:focus {
    outline: none;
    border: 1px solid #aaa;
}

/* ボタンコンテナ */
.button-container {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.save-button {
    flex: 1;
    padding: 12px;
    background-color: #59d4d4fc;
    color: white;
    border: none;
    font-weight: bold;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.save-button:hover:not(:disabled) {
    background-color: #59b9d4fc;
}

.save-button:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(89, 212, 212, 0.3);
}

.save-button:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.submit-button {
    flex: 1;
    padding: 12px;
    background-color: #ffbf00;
    color: white;
    border: none;
    font-weight: bold;
    cursor: pointer;
    border-radius: 6px;
    transition: background-color 0.2s;
}

.submit-button:hover:not(:disabled) {
    background-color: #ff9d00;
}

.submit-button:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}

/* エラーメッセージ */
.error-messages {
    margin-bottom: 20px;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 6px;
    border: 1px solid #f5c6cb;
}

/* 成功メッセージ */
.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 6px;
    border: 1px solid #c3e6cb;
}

input::placeholder,
textarea::placeholder {
    color: #ccc !important;
    opacity: 1 !important;
}

/* レスポンシブデザイン */
@media screen and (max-width: 1024px) {
    .recipe-create-container {
        flex-direction: column;
        gap: 20px;
    }

    .saved-recipes-sidebar {
        width: 100%;
        order: 1;
    }

    .saved-recipes-list {
        flex-direction: row;
        flex-wrap: wrap;
        max-height: 200px;
    }

    .saved-recipe-tag {
        min-width: 150px;
        flex: 1;
        max-width: 200px;
    }

    .main-content {
        flex-direction: column;
        align-items: center;
        gap: 20px;
        order: 2;
    }

    .image-preview {
        width: 100%;
        max-width: 300px;
        margin-top: 0;
    }

    .recipe-form {
        width: 100%;
        max-width: 400px;
    }
}

@media screen and (max-width: 768px) {
    .recipe-create-container {
        padding: 20px;
        gap: 20px;
    }

    .saved-recipes-sidebar {
        padding: 15px;
    }

    .saved-recipes-list {
        flex-direction: column;
        gap: 8px;
        max-height: 150px;
    }

    .saved-recipe-tag {
        min-width: auto;
        max-width: none;
    }

    .image-preview {
        width: 100%;
        max-width: 280px;
        height: 280px;
    }

    .ingredient-row {
        flex-direction: column;
        gap: 8px;
    }

    .recipe-form .ingredient-name,
    .recipe-form .ingredient-qty {
        border: 1px solid #aaa !important;
        border-radius: 6px !important;
        flex: none;
        margin-bottom: 8px;
    }

    .recipe-form .ingredient-name:focus,
    .recipe-form .ingredient-qty:focus {
        outline: none;
        border: 1px solid #aaa !important;
    }

    .recipe-form .ingredient-qty {
        margin-bottom: 0;
    }

    .servings-input {
        width: 100%;
    }

    .auto-resize {
        min-height: 100px;
    }

    .button-container {
        flex-direction: column;
        gap: 8px;
    }
}
</style>