<script setup lang="ts">
      import { ref, onMounted } from 'vue'
      import { useRouter } from 'vue-router'
      import SplashScreen from '~/components/splash.vue'
      
      const router = useRouter()
      const isSplashLoading = ref(false)

    //   onMounted(() => {
    //       const hasVisitedBefore = localStorage.getItem('hasVisited')
    //       const isLoggedIn = sessionStorage.getItem('isLoggedIn')

    //       if (hasVisitedBefore) {
    //           setTimeout(() => {
    //               isSplashLoading.value = false
    //               router.push('/summary')
    //           }, 3000)
    //       }
    //       else {
    //           localStorage.setItem('hasVisited', 'true')
    //           setTimeout(() => {
    //               isSplashLoading.value = false
    //               router.push('/login')
    //           }, 3000)
    //       }
    //     })
</script>

<template>
    <ClientOnly>
        <UApp :toaster="{ position: 'bottom-center' }">
            <!--SplashScreen v-if="isSplashLoading" />
            <NuxtPage v-else /-->
            <NuxtPage />
        </UApp>
    </ClientOnly>
</template>