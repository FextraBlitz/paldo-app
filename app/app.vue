<script setup lang="ts">

</script>

<template>
    <ClientOnly>
        <UApp :toaster="{ position: 'bottom-center' }">
            <NuxtPage />
        </UApp>
    </ClientOnly>
</template>