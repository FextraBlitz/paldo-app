<script setup lang="ts">
  import SplashScreen from '~/components/splash.vue'
  const isSplashLoading = ref(true)

  onMounted(() => {
    setTimeout(() => {
      isSplashLoading.value = false
    }, 3000)
  })
</script>

<template>
  <SplashScreen v-if="isSplashLoading" />
  <NuxtPage v-else />
</template>