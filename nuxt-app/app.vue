<template>
    <div>
        <NuxtLayout>
            <NuxtPage />
        </NuxtLayout>
    </div>
</template>

<script setup>
const { initAuth } = useAuth()

onMounted(async () => {
    await initAuth()
})
</script>