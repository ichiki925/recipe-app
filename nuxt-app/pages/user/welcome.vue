<template>
    <div class="welcome-container">
        <img src="/images/rabbit-shape.svg" class="logo" alt="Rabbit" />
        <h1 class="title">Vanilla's Kitchen</h1>
        <p class="subtitle">日々のお料理レシピを記録</p>
        <div class="buttons">
            <NuxtLink to="/auth/register" class="btn">新規登録</NuxtLink>
            <NuxtLink to="/auth/login" class="btn ghost">ログイン</NuxtLink>
            <NuxtLink to="/" class="btn ghost">ゲストページ</NuxtLink>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
layout: false
})
</script>

<style>
@import "@/assets/css/user/welcome.css";
</style>
