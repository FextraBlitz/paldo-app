<script setup lang="ts">
  import { ref, onMounted } from 'vue'
  import SplashScreen from '~/components/splash.vue'
  const isSplashLoading = ref(true)

  onMounted(() => {
    setTimeout(() => {
      isSplashLoading.value = false
    }, 3000)
  })
</script>

<template>
  <ClientOnly>
    <UApp :toaster="{ position: 'bottom-center' }">
      <SplashScreen v-if="isSplashLoading" />
      <NuxtPage v-else />
    </UApp>
  </ClientOnly>
</template>