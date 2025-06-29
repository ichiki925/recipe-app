<template>
  <div class="recipe-create-container">
    <!-- プレビューエリア -->
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

    <form class="recipe-form" @submit.prevent="submitRecipe">
      <h2>New Recipe</h2>

      <label>料理名</label>
      <input type="text" v-model="form.title" class="recipe-title" />

      <label>ジャンル</label>
      <input type="text" v-model="form.genre" class="recipe-title" />

      <label>人数</label>
      <select v-model="form.servings" class="servings-input">
        <option value="">選択してください</option>
        <option value="1人分">1人分</option>
        <option value="2人分">2人分</option>
        <option value="3人分">3人分</option>
        <option value="4人分">4人分</option>
        <option value="5人以上">5人以上</option>
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
        v-model="form.body"
        class="auto-resize"
        @input="resizeTextarea"
      ></textarea>

      <button type="submit">投稿する</button>
    </form>
  </div>
</template>

<script setup>
definePageMeta({
  layout: 'admin'
})
import { ref, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const form = reactive({
  title: '',
  genre: '',
  servings: '',
  ingredients: [{ name: '', qty: '' }],
  body: '',
})

const imageInput = ref(null)
const imagePreview = ref('')

const triggerImageInput = () => {
  imageInput.value?.click()
}

const previewImage = (event) => {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

watch(
  () => form.ingredients,
  (newIngredients) => {
    const last = newIngredients[newIngredients.length - 1]
    if (last.name || last.qty) {
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

const submitRecipe = () => {
  console.log('投稿データ:', form)
  // 実際の送信処理（APIなど）をここに書く
}
</script>

<style scoped>
@import '@/assets/css/common.css';
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
</style>
