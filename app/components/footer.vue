<template>
  <div class="fixed bottom-0 w-full z-30 flex flex-col">
    <nav class="grid grid-cols-5 bg-red-500 border-t border-black h-16 text-white">
        <button class="flex flex-col items-center justify-center gap-1 hover:bg-gray-50 transition-colors">
            <UIcon name="i-lucide-file-text" class="w-6 h-6 stroke-[1.5px]" />
            <span class="text-[12px]">Records</span>
        </button>

        <button class="flex flex-col items-center justify-center gap-1 hover:bg-gray-300 transition-colors">
            <UIcon name="i-lucide-pie-chart" class="w-6 h-6 stroke-[1.5px]" />
            <span class="text-[12px]">Analysis</span>
        </button>

        <button class="flex flex-col items-center justify-center gap-1 hover:bg-gray-300 transition-colors">
            <UIcon name="i-lucide-calculator" class="w-6 h-6 stroke-[1.5px]" />
            <span class="text-[12px]">Budgets</span>
        </button>

        <button class="flex flex-col items-center justify-center gap-1 hover:bg-gray-300 transition-colors">
            <UIcon name="i-lucide-tag" class="w-6 h-6 stroke-[1.5px]" />
            <span class="text-[12px]">Categories</span>
        </button>

        <button class="flex flex-col items-center justify-center gap-1 hover:bg-gray-300 transition-colors">
            <UIcon name="i-lucide-user" class="w-6 h-6 stroke-[1.5px]" />
            <span class="text-[12px]">Profile</span>
        </button>
    </nav>
  </div>
</template>

<script setup lang="ts">
</script>