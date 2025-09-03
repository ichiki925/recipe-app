<template>
  <div class="recipe-create-container">
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

    <div class="main-content">
      <div class="image-preview" @click="triggerImageInput">
        <div v-if="!imagePreview" class="no-image-placeholder">

          <div class="no-image-text">No Image</div>
        </div>
        <img
          v-if="imagePreview"
          :src="imagePreview"
          alt="プレビュー"
          class="preview-image"
          @error="handleImageError"
          @load="handleImageLoad"
        />
      </div>
      <input
        type="file"
        ref="imageInput"
        style="display: none"
        accept="image/*"
        @change="previewImage"
      />

      <form class="recipe-form" @submit.prevent="submitRecipe">
      <h2>New Recipe</h2>

      <div v-if="errors.length > 0" class="error-messages">
        <div v-for="error in errors" :key="error" class="error-message">
          {{ error }}
        </div>
      </div>

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

const handleImageError = (event) => {
  console.error('❌ 画像読み込みエラー:', event.target.src)
  imagePreview.value = ''
}

const handleImageLoad = (event) => {
  console.log('画像読み込み成功:', event.target.src)
}

const loadSavedRecipes = () => {
  try {
    const saved = localStorage.getItem('savedRecipes')
    if (saved) {
      savedRecipes.value = JSON.parse(saved)
    }
  } catch (error) {
    console.error('保存レシピの読み込みエラー:', error)
    savedRecipes.value = []
  }
}

const updateSavedRecipes = () => {
  try {
    localStorage.setItem('savedRecipes', JSON.stringify(savedRecipes.value))
  } catch (error) {
    console.error('保存レシピの更新エラー:', error)
  }
}

const formatDate = (dateString) => {
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('ja-JP', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    console.error('日付フォーマットエラー:', error)
    return '不明'
  }
}

const saveRecipe = () => {
  isSaving.value = true

  try {
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

    const existingIndex = savedRecipes.value.findIndex(r => r.id === recipeData.id)
    if (existingIndex !== -1) {
      savedRecipes.value[existingIndex] = recipeData
    } else {
      savedRecipes.value.unshift(recipeData)
    }

    if (savedRecipes.value.length > 10) {
      savedRecipes.value = savedRecipes.value.slice(0, 10)
    }

    updateSavedRecipes()

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

    successMessage.value = 'レシピを保存しました'
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } catch (error) {
    console.error('保存エラー:', error)
    errors.value.push('保存に失敗しました')
  } finally {
    isSaving.value = false
  }
}

const loadSavedRecipe = (savedRecipe) => {
  try {
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
  } catch (error) {
    console.error('読み込みエラー:', error)
    errors.value.push('レシピの読み込みに失敗しました')
  }
}

const deleteSavedRecipe = (id) => {
  if (confirm('このレシピを削除しますか？')) {
    try {
      savedRecipes.value = savedRecipes.value.filter(r => r.id !== id)
      updateSavedRecipes()

      if (currentEditingRecipe.value?.id === id) {
        currentEditingRecipe.value = null
      }
    } catch (error) {
      console.error('削除エラー:', error)
      errors.value.push('削除に失敗しました')
    }
  }
}

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

    const response = await fetch('http://localhost/api/admin/recipes', {
      method: 'POST',
      body: formData,
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })

    if (!response.ok) {
      const errorText = await response.text()
      console.error('❌ API Error Response:', errorText)
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }

    const data = await response.json()

    successMessage.value = 'レシピが投稿されました'

    const currentEditingId = currentEditingRecipe.value?.id

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

    if (currentEditingId) {
      savedRecipes.value = savedRecipes.value.filter(r => r.id !== currentEditingId)
      updateSavedRecipes()
    }

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

let autoSaveTimer = null
const startAutoSave = () => {
  if (autoSaveTimer) {
    clearInterval(autoSaveTimer)
  }

  autoSaveTimer = setInterval(() => {
    if (form.title || form.genre || form.instructions ||
        form.ingredients.some(ing => ing.name || ing.qty)) {
      saveRecipe()
    }
  }, 60000)
}

watch(form, () => {
  if (autoSaveTimer) {
    clearInterval(autoSaveTimer)
    startAutoSave()
  }
}, { deep: true })

onMounted(() => {
  loadSavedRecipes()
})
</script>

<style scoped>
@import '@/assets/css/common.css';

body {
    margin: 0;
    font-family: sans-serif;
}

.recipe-create-container {
    display: flex;
    gap: 30px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

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
    background: transparent;
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

.main-content {
    display: flex;
    gap: 40px;
    justify-content: center;
    align-items: flex-start;
    flex: 1;
}

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

.no-image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: #999;
}

.no-image-text {
    font-size: 18px;
    font-weight: 500;
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 12px;
}

.recipe-form {
    width: 400px;
}

.recipe-form h2 {
    margin-bottom: 20px;
    text-align: center;
    font-family: cursive;
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

.recipe-form .ingredient-row {
    display: flex;
    gap: 0px;
    margin-bottom: 10px;
}

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