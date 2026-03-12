<template>
    <div class="flex items-center py-40 h-screen bg-white flex-col gap-2">
        <div class="text-6xl pt-12 pb-5 font-bold">
            <img src="~/assets/logo.png" alt="Paldo Logo" width="200" height="200" />
        </div>

        <h1 class="text-2xl font-bold mt-4 mb-2 text-black">LOGIN</h1>
        
        <ClientOnly>
            <UForm :state="state" class="px-8 w-full space-y-2" @submit="onSubmit">
                <!--email-->
                <UFormField name="email">
                    <UInput
                        v-model="state.email"
                        placeholder="Email"
                        icon="i-lucide-mail"
                        class="w-full"
                        :ui="{
                            base: 'bg-white text-black border-2 border-slate-300 h-12'
                        }"
                        color="info"
                    />
                </UFormField>

                <!--password-->
                <UFormField name="password">
                    <UInput
                        v-model="state.password"
                        type="password"
                        placeholder="Password"
                        icon="i-lucide-lock"
                        class="w-full"
                        :ui="{
                            base: 'bg-white text-black border-2 border-slate-300 h-12'
                        }"
                        color="info"
                    />
                </UFormField>

                <div class="flex justify-end">
                    <NuxtLink to="/forgot-password" class="text-sm text-black underline">
                        Forgot Password?
                    </NuxtLink>
                </div>

                <!--submit button-->
                <UButton
                    type="submit"
                    block
                    size="xl"
                    label="Login"
                    class="bg-red-500 text-white border-4 border-red-900 font-semibold"
                    color="error"
                />
            </UForm>
        </ClientOnly>
        <div class="py-4">
            <p class="text-black">
                Don't have an Account? <NuxtLink to="/register" class="font-bold underline text-blue-500">Sign Up</NuxtLink>
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
    import { reactive } from 'vue'

    const supabase = useSupabaseClient()
    const toast = useToast()
    const state = reactive({
        email: '',
        password: ''
    })

    async function onSubmit() {
        try {
            const { data, error } = await supabase.auth.signInWithPassword({
                email: state.email,
                password: state.password
            })
            if (error) throw error
            toast.add({
                title: 'Welcome Back!',
                description: 'Login successful. Redirecting...',
                color: 'neutral',
                icon: 'i-lucide-check-circle',
                ui: {
                    root: 'bg-blue-500 border-2 border-blue-900',
                    description: 'text-white',
                    close: 'text-white'
                }
            })
            await navigateTo('/summary')

        }
        catch (error: any) {
            toast.add({
                title: 'Login Failed',
                description: error.message || 'Invalid email or password.',
                color: 'neutral',
                icon: 'i-lucide-x-circle',
                ui: {
                    root: 'bg-red-500 border-2 border-red-900',
                    description: 'text-white',
                }
            })
        }
    }
</script>

<style scoped>

</style>