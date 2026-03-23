<template>
    <div class="fixed bottom-0 inset-x-0 mx-auto max-w-[inherit] w-full z-30 flex flex-col">
        <nav class="grid grid-cols-5 bg-blue-500 h-16 text-white shadow-lg">
            <NuxtLink 
                to="/summary" 
                active-class="bg-blue-700 font-bold" 
                class="flex flex-col items-center justify-center gap-1 hover:bg-blue-300 transition-colors"
            >
                <UIcon name="i-lucide-file-text" class="size-6" />
                <span class="text-[12px]">Records</span>
            </NuxtLink>

            <NuxtLink 
                to="/analysis" 
                active-class="bg-blue-700 font-bold" 
                class="flex flex-col items-center justify-center gap-1 hover:bg-blue-300 transition-colors"
            >
                <UIcon name="i-lucide-pie-chart" class="size-6" />
                <span class="text-[12px]">Analysis</span>
            </NuxtLink>

            <NuxtLink 
                to="/goals" 
                active-class="bg-blue-700 font-bold" 
                class="flex flex-col items-center justify-center gap-1 hover:bg-blue-300 transition-colors"
            >
                <UIcon name="i-lucide-notebook-pen" class="size-6" />
                <span class="text-[12px]">Goals</span>
            </NuxtLink>

            <NuxtLink 
                to="/categories" 
                active-class="bg-blue-700 font-bold" 
                class="flex flex-col items-center justify-center gap-1 hover:bg-blue-300 transition-colors"
            >
                <UIcon name="i-lucide-tag" class="size-6" />
                <span class="text-[12px]">Categories</span>
            </NuxtLink>

            <NuxtLink 
                to="/profile" 
                active-class="bg-blue-700 font-bold" 
                class="flex flex-col items-center justify-center gap-1 hover:bg-blue-300 transition-colors"
            >
                <UIcon name="i-lucide-user" class="size-6" />
                <span class="text-[12px]">Profile</span>
            </NuxtLink>
        </nav>
    </div>
</template>

<script setup lang="ts">
</script>