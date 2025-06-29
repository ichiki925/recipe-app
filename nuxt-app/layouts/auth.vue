<script setup>
import { useRoute } from 'vue-router'
const route = useRoute()

// 特定のパスだけヘッダーを非表示にする
const hideHeader = ['/auth/forgot-password', '/auth/reset-password'].includes(route.path)
</script>

<template>
<div>
    <!-- この Header を一部ページでは表示しない -->
    <Header v-if="!hideHeader" />

    <NuxtPage />
</div>
</template>