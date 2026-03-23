<script setup lang="ts">

</script>

<template>
    <ClientOnly>
        <UApp :toaster="{ position: 'bottom-center' }">
            <div class="min-h-screen flex flex-col items-center bg-white">
                <Header />
                <div class="mx-auto w-full lg:w-[50%] relative min-h-screen border-x border-slate-300">
                    <NuxtPage />
                </div>
                <Footer />
            </div>
        </UApp>
    </ClientOnly>
</template>