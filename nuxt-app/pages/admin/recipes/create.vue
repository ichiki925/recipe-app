<template>
  <div class="recipe-create-container">
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
import { ref, reactive, watch, onMounted, nextTick } from 'vue'
import { useRouter, useRoute  } from 'vue-router'
import { getStorage, ref as storageRef, uploadBytes, getDownloadURL, deleteObject } from 'firebase/storage'

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

const uploadTempImage = async (file) => {
  try {
    const { $auth } = useNuxtApp()
    const currentUser = $auth.currentUser
    if (!currentUser) {
      throw new Error('認証が必要です')
    }

    const storage = getStorage()
    const fileName = `${Date.now()}_${file.name}`
    const tempPath = `temp/${currentUser.uid}/${fileName}`
    const imageRef = storageRef(storage, tempPath)

    console.log('Firebase Storageに一時保存中:', tempPath)

    const snapshot = await uploadBytes(imageRef, file)
    const downloadURL = await getDownloadURL(snapshot.ref)

    console.log('一時保存完了:', downloadURL)
    return {
      url: downloadURL,
      path: tempPath
    }
  } catch (error) {
    console.error('Firebase Storage一時保存エラー:', error)
    throw error
  }
}

const deleteTempImage = async (tempPath) => {
  try {
    const storage = getStorage()
    const imageRef = storageRef(storage, tempPath)
    await deleteObject(imageRef)
    console.log('一時保存画像を削除:', tempPath)
  } catch (error) {
    console.error('一時保存画像削除エラー:', error)
  }
}

const handleImageError = (event) => {
  console.error('❌ 画像読み込みエラー:', event.target.src)
  imagePreview.value = ''
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

const saveRecipe = async () => {
  isSaving.value = true

  try {
    const recipeData = {
      id: currentEditingRecipe.value?.id || Date.now().toString(),
      title: form.title,
      genre: form.genre,
      servings: form.servings,
      ingredients: [...form.ingredients],
      instructions: form.instructions,
      hasImage: !!selectedFile.value,
      savedAt: new Date().toISOString(),
      isEditDraft: false
    }

    // 画像がある場合はFirebase Storageに一時保存
    if (selectedFile.value?.file) {
      // 新規選択画像の場合
      try {
        const tempImageData = await uploadTempImage(selectedFile.value.file)
        recipeData.tempImageUrl = tempImageData.url
        recipeData.tempImagePath = tempImageData.path
        console.log('画像を一時保存:', selectedFile.value.file.name)
      } catch (error) {
        console.error('画像一時保存エラー:', error)
        recipeData.hasImage = false
      }
    } else if (selectedFile.value?.isTemp) {
      // 既に一時保存済みの画像の場合
      recipeData.tempImageUrl = selectedFile.value.tempImageUrl
      recipeData.tempImagePath = selectedFile.value.tempImagePath
      console.log('既存の一時保存画像を再利用')
    }

    // 既存のレシピを更新する場合、古い一時画像を削除
    const existingIndex = savedRecipes.value.findIndex(r => r.id === recipeData.id)
    if (existingIndex !== -1) {
      const oldRecipe = savedRecipes.value[existingIndex]
      if (oldRecipe.tempImagePath && oldRecipe.tempImagePath !== recipeData.tempImagePath) {
        await deleteTempImage(oldRecipe.tempImagePath)
      }
      savedRecipes.value[existingIndex] = recipeData
    } else {
      savedRecipes.value.unshift(recipeData)
    }

    if (savedRecipes.value.length > 10) {
      const removedRecipes = savedRecipes.value.slice(10)
      for (const recipe of removedRecipes) {
        if (recipe.tempImagePath) {
          await deleteTempImage(recipe.tempImagePath)
        }
      }
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

    console.log('レシピ保存完了')

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
    console.log('🔍 loadSavedRecipe開始:', savedRecipe.id)
    console.log('🔍 savedRecipe.hasImage:', savedRecipe.hasImage)
    console.log('🔍 savedRecipe.tempImageUrl:', savedRecipe.tempImageUrl)
    console.log('🔍 savedRecipe.tempImagePath:', savedRecipe.tempImagePath)

    Object.assign(form, {
      title: savedRecipe.title,
      genre: savedRecipe.genre,
      servings: savedRecipe.servings,
      ingredients: savedRecipe.ingredients.length > 0 ? savedRecipe.ingredients : [{ name: '', qty: '' }],
      instructions: savedRecipe.instructions
    })

    currentEditingRecipe.value = savedRecipe

    if (savedRecipe.hasImage && savedRecipe.tempImageUrl) {
      console.log('✅ 画像復元処理開始')
      imagePreview.value = savedRecipe.tempImageUrl
      selectedFile.value = {
        tempImageUrl: savedRecipe.tempImageUrl,
        tempImagePath: savedRecipe.tempImagePath,
        isTemp: true
      }
      console.log('✅ imagePreview設定完了:', imagePreview.value)
    } else {
      console.log('❌ 画像復元スキップ - hasImage:', savedRecipe.hasImage, 'tempImageUrl:', !!savedRecipe.tempImageUrl)
      imagePreview.value = ''
      selectedFile.value = null
    }
  } catch (error) {
    console.error('読み込みエラー:', error)
    errors.value.push('レシピの読み込みに失敗しました')
  }
}

const triggerImageInput = () => {
  imageInput.value?.click()
}

const previewImage = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  console.log('画像ファイル選択:', file.name, file.type, file.size)

  if (file.size > 5 * 1024 * 1024) {
    errors.value.push('ファイルサイズは5MB以下にしてください')
    return
  }

  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    errors.value.push('JPEG、PNG、WebP形式の画像のみアップロード可能です')
    return
  }

  selectedFile.value = {
    file,
    serverUpload: true
  }

  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target.result
    console.log('画像プレビュー表示完了')
  }
  reader.onerror = () => {
    console.error('画像読み込みエラー')
    errors.value.push('画像の読み込みに失敗しました')
  }
  reader.readAsDataURL(file)
}

const submitRecipe = async () => {
  errors.value = []
  successMessage.value = ''
  isSubmitting.value = true

  try {
    let currentUser = null
    let authToken = null

    try {
      const { $auth } = useNuxtApp()
      if ($auth?.currentUser) {
        currentUser = $auth.currentUser
        authToken = await currentUser.getIdToken()
      }
    } catch (nuxtError) {
      console.log('認証取得エラー:', nuxtError.message)
    }

    if (!currentUser) {
      errors.value.push('認証が必要です。ページをリロードしてログインしてください。')
      isSubmitting.value = false
      return
    }

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
    formData.append('title', form.title.trim())
    formData.append('genre', form.genre || '')
    formData.append('servings', form.servings.toString())
    formData.append('ingredients', ingredientsText)
    formData.append('instructions', form.instructions.trim())

    if (selectedFile.value?.isTemp) {
      formData.append('temp_image_url', selectedFile.value.tempImageUrl)
      console.log('一時保存画像URLをサーバーに送信:', selectedFile.value.tempImageUrl)
    } else if (selectedFile.value?.file instanceof File) {
      formData.append('image', selectedFile.value.file)
      console.log('画像ファイルをFormDataに追加:', selectedFile.value.file.name, selectedFile.value.file.size)
    }

    const response = await fetch('http://localhost/api/admin/recipes', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authToken}`
      },
      body: formData
    })

    if (!response.ok) {
      let errorMessage = `HTTP ${response.status}: ${response.statusText}`
      try {
        const errorData = await response.json()
        if (errorData.message) {
          errorMessage = errorData.message
        } else if (errorData.errors) {
          errorMessage = Object.values(errorData.errors).flat().join(', ')
        }
      } catch {
        const errorText = await response.text()
        if (errorText) errorMessage = errorText
      }
      throw new Error(errorMessage)
    }

    const data = await response.json()
    console.log('API成功:', data)

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
    console.error('API error:', error)
    errors.value = [`API呼び出しエラー: ${error.message}`]
  } finally {
    isSubmitting.value = false
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

onMounted(async () => {
  loadSavedRecipes()

  try {
    // URLパラメータから下書きIDを取得
    const route = useRoute()
    const draftId = route.query?.draft
    console.log('Draft ID from URL:', draftId)
    
    if (draftId) {
      await nextTick()
      const savedRecipe = savedRecipes.value.find(r => r.id === draftId)
      console.log('Found saved recipe:', savedRecipe)
      
      if (savedRecipe) {
        loadSavedRecipe(savedRecipe)
        console.log('Auto-loaded recipe with image:', savedRecipe.tempImageUrl)
      }
    }
  } catch (error) {
    console.error('Auto-load error:', error)
  }

  const config = useRuntimeConfig()
  console.log('Firebase Config:', {
    apiKey: config.public.firebaseApiKey,
    authDomain: config.public.firebaseAuthDomain,
    projectId: config.public.firebaseProjectId,
    storageBucket: config.public.firebaseStorageBucket,
    messagingSenderId: config.public.firebaseMessagingSenderId,
    appId: config.public.firebaseAppId
  })

  const { $auth } = useNuxtApp()
  console.log('Current User:', $auth.currentUser)
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

@media screen and (max-width: 768px) {
    .main-content {
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .image-preview {
        width: 100%;
        max-width: 280px;
        height: 280px;
        margin-top: 0;
    }

    .recipe-form {
        width: 100%;
        max-width: 400px;
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