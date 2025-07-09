<template>
  <div class="recipe-create-container">
    <!-- プレビューエリア -->
    <div class="image-preview" @click="selectImage">
      <span v-if="!previewUrl" id="preview-text">No Image</span>
      <img
        id="preview-image"
        v-if="previewUrl"
        :src="previewUrl"
        alt="プレビュー"
      />
    </div>

    <!-- 非表示のファイル入力 -->
    <input
      type="file"
      id="imageInput"
      ref="imageInput"
      accept="image/*"
      style="display: none"
      @change="handleImageChange"
    />

    <!-- 編集フォーム -->
    <form class="recipe-form" @submit.prevent="submit">
      <h2>Edit Recipe</h2>

      <label>料理名</label>
      <input v-model="form.title" type="text" class="recipe-title" />

      <label>ジャンル</label>
      <input v-model="form.genre" type="text" class="recipe-title" />

      <label>人数</label>
      <select v-model="form.servings" class="servings-input">
        <option value="">選択してください</option>
        <option value="1人分">1人分</option>
        <option value="2人分">2人分</option>
        <option value="3人分">3人分</option>
        <option value="4人分">4人分</option>
        <option value="5人以上">5人分以上</option>
      </select>

      <label>材料</label>
      <div id="ingredients">
        <div
          class="ingredient-row"
          v-for="(ingredient, index) in form.ingredients"
          :key="index"
        >
          <input
            v-model="ingredient.name"
            type="text"
            class="ingredient-name"
            placeholder="材料名"
          />
          <input
            v-model="ingredient.qty"
            type="text"
            class="ingredient-qty"
            placeholder="分量"
          />
        </div>
      </div>

      <label>作り方</label>
      <textarea
        v-model="form.body"
        class="auto-resize"
        ref="textarea"
        @input="autoResize"
      ></textarea>

      <button type="submit" :disabled="isLoading">
        {{ isLoading ? '更新中...' : '投稿する' }}
      </button>
    </form>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin'
})
import { ref, reactive, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

// 初期値
const form = reactive({
  title: '',
  genre: '',
  servings: '',
  ingredients: [
    { name: '', qty: '' },

  ],
  instructions: '',
})

// ローディング状態を管理
const isLoading = ref(true)
const previewUrl = ref(null) 

// レシピデータを取得
onMounted(async () => {
  try {
    const response = await $fetch(`/api/admin/recipes/${route.params.id}`)
    const recipe = response.data

    // フォームにデータを設定
    form.title = recipe.title
    form.genre = recipe.genre || ''
    form.servings = recipe.servings
    form.instructions = recipe.instructions

    // 材料を配列形式に変換（バックエンドの形式による）
    form.ingredients = recipe.ingredients_array || [{ name: '', qty: '' }]

    // 画像URLを設定
    previewUrl.value = recipe.image_url

    isLoading.value = false
  } catch (error) {
    console.error('レシピ取得エラー:', error)
    isLoading.value = false
  }
})

const imageInput = ref(null)
const textarea = ref(null)

// 画像クリック → input起動
const selectImage = () => {
  imageInput.value?.click()
}

// プレビュー
const handleImageChange = (event) => {
  const file = event.target.files[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrl.value = e.target.result
  }
  reader.readAsDataURL(file)
}

// 材料の自動追加
watch(
  () => form.ingredients,
  () => {
    const last = form.ingredients[form.ingredients.length - 1]
    if (last.name || last.qty) {
      form.ingredients.push({ name: '', qty: '' })
    }
  },
  { deep: true }
)

// 作り方textareaの自動リサイズ
const autoResize = () => {
  if (!textarea.value) return
  textarea.value.style.height = 'auto'
  textarea.value.style.height = Math.max(textarea.value.scrollHeight, 80) + 'px'
}

onMounted(() => {
  autoResize()
})

// フォーム送信（仮処理）
const submit = async () => {
  try {
    isLoading.value = true

    // FormDataを作成（画像アップロード対応）
    const formData = new FormData()
    formData.append('title', form.title)
    formData.append('genre', form.genre)
    formData.append('servings', form.servings)
    formData.append('instructions', form.instructions)

    // 材料を文字列形式に変換
    const ingredientsText = form.ingredients
      .filter(item => item.name.trim() || item.qty.trim())
      .map(item => `${item.name}:${item.qty}`)
      .join('\n')
    formData.append('ingredients', ingredientsText)

    // 画像ファイルがある場合
    const imageFile = imageInput.value?.files[0]
    if (imageFile) {
      formData.append('image', imageFile)
    }

    const response = await $fetch(`/api/admin/recipes/${route.params.id}`, {
      method: 'PUT',
      body: formData
    })

    alert('レシピが更新されました')
    // 必要に応じてリダイレクト
    // router.push('/admin/recipes')

  } catch (error) {
    console.error('更新エラー:', error)
    alert('更新に失敗しました')
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
body {
    margin: 0;
    font-family: sans-serif;
    /* background: radial-gradient(circle at center, #fde2e4, #fdf6e3); */
}



/* 全体の2カラムレイアウト */
.recipe-create-container {
    display: flex;
    padding: 40px;
    gap: 40px;
    justify-content: center;
    align-items: flex-start;
}

/* 左側の画像部分 */
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

css コピーする 編集する

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    color: #999;
    font-size: 18px;
}

/* 右側の入力フォーム */
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
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
}


/* 材料名と分量を横並び */
.ingredient-row {
    display: flex;
    gap: 0px;
    margin-bottom: 10px;
}


/* 材料名入力欄 */
.ingredient-name {
    flex: 2;
    border: 1px solid #ccc;
    border-right: none;
    border-radius: 6px 0 0 6px;
    padding: 10px;
    box-sizing: border-box;
}

/* 分量入力欄 */
.ingredient-qty {
    flex: 1;
    border: 1px solid #ccc;
    border-left: none;
    /* 中央の線を消す */
    border-radius: 0 6px 6px 0;
    padding: 10px;
    box-sizing: border-box;
}



.recipe-form input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    /* font-size: 14px; */
    box-sizing: border-box;
}

.servings-input {
    width: 150px;
    padding: 8px;
    font-size: 14px;
    margin-bottom: 10px;
}

.auto-resize {
    width: 100%;
    min-height: 80px;
    resize: none;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
    overflow: auto;
}

.auto-resize:focus {
    outline: none;
    border: 1px solid #ccc;
}

input::placeholder,
textarea::placeholder,
input::-webkit-input-placeholder,
textarea::-webkit-input-placeholder {
    color: #ccc !important;
    opacity: 1 !important;
    /* Safariで効かない対策 */
}




.recipe-form button {
    margin-top: 20px;
    width: 100%;
    padding: 12px;
    background-color: #eee;
    border: none;
    font-weight: bold;
    cursor: pointer;
    border-radius: 6px;
}

.recipe-form button:hover {
    background-color: #ddd;
}

@media screen and (max-width: 768px) {
    .recipe-create-container {
        flex-direction: column;
        align-items: center;
        padding: 20px;
        gap: 20px;
    }

    .image-preview {
        width: 100%;
        max-width: 280px;
        height: 280px;
        margin: 40px auto 0;
    }

    .recipe-form {
        width: 100%;
        max-width: 400px;
    }

    .ingredient-row {
        flex-direction: column;
        gap: 10px;
    }

    .ingredient-name,
    .ingredient-qty {
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    .ingredient-name {
        border-right: 1px solid #ccc;
    }

    .ingredient-qty {
        border-left: 1px solid #ccc;
    }

    .servings-input {
        width: 100%;
    }

    .auto-resize {
        min-height: 100px;
    }
}

/* フォーカス時の青枠を消す */
input:focus,
select:focus,
textarea:focus {
  outline: none;
  box-shadow: none;
  border-color: #ccc;
}
</style>
