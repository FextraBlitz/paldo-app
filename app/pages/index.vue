<<template>
    <div class="flex items-center py-40 min-h-screen bg-white flex-col gap-2">
        <div class="text-6xl p-20 font-bold">
            <img src="~/assets/logo.png" alt="Paldo Logo" width="200" height="200" />
        </div>
        <NuxtLink to="/register">
            <div class="text-xl bg-red-500 px-16 py-2 text-white border-4 border-red-900 rounded-md">
                Get Started
            </div>
        </NuxtLink>
        <div class="p-4">
            <p class="text-black">
                Already have an account? <NuxtLink href="/login" class="text-blue-500 underline">Login</NuxtLink>
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">

</script>

<style scoped>

</style>>