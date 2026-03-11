<template>
    <div class="flex items-center py-40 h-screen bg-white flex-col gap-2">
        <div class="text-6xl pt-8 pb-5 font-bold">
            <img src="~/assets/logo.png" alt="Paldo Logo" width="200" height="200" />
        </div>

        <h1 class="text-2xl font-bold mt-4 mb-2 text-black">SIGN UP</h1>
        
        <ClientOnly>
            <UForm :state="state" class="px-8 w-full space-y-2" @submit="onRegister">
                <!--name-->
                <UFormField name="name">
                    <UInput
                        v-model="state.name"
                        placeholder="Name"
                        icon="i-lucide-user"
                        class="w-full"
                        :ui="{
                            base: 'bg-white text-black border-2 border-slate-300 h-12'
                        }"
                        color="info"
                    />
                </UFormField>

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

                <!--confirm password-->
                <UFormField name="confirm-password">
                    <UInput
                        v-model="state.confirm_password"
                        type="password"
                        placeholder="Confirm Password"
                        icon="i-lucide-lock"
                        class="w-full"
                        :ui="{
                            base: 'bg-white text-black border-2 border-slate-300 h-12'
                        }"
                        color="info"
                    />
                </UFormField>

                <UCheckbox 
                    v-model="state.terms" 
                    label="I have accepted the Terms and Conditions" 
                    class="py-2"
                    :ui="{ label: 'text-sm text-black font-medium' }"
                    color="info"
                />

                <!--submit button-->
                <UButton
                    type="submit"
                    block
                    size="xl"
                    label="Register"
                    class="bg-red-500 text-white border-4 border-red-900 font-semibold"
                    color="error"
                />
            </UForm>
        </ClientOnly>

        <div class="p-4">
            <p class="text-black">
                Already have an account? <NuxtLink href="/login" class="text-blue-500 underline font-bold">Login</NuxtLink>
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
    const toast = useToast()
    const state = reactive({
        email: '',
        password: '',
        name: '',
        confirm_password: '',
        terms: false
    })

    async function onRegister() {
        if (state.terms == false) {
            toast.add({
                title: 'Registration Error',
                description: 'You must accept the Terms and Conditions to register.',
                color: 'neutral',
                icon: 'i-lucide-circle-alert',
                ui: {
                    root: 'bg-red-700 border-2 border-red-900',
                    description: 'text-white',
                    close: 'text-white'
                }
            })
            return
        }
        
        if (state.password !== state.confirm_password) {
            toast.add({
                title: 'Registration Error',
                description: 'Your passwords do not match. Please try again.',
                color: 'neutral',
                icon: 'i-lucide-circle-alert',
                ui: {
                    root: 'bg-red-700 border-2 border-red-900',
                    description: 'text-white',
                    close: 'text-white'
                }
            })
            return
        }
        console.log('Registering user:', state.email)
        // Integration with Supabase auth logic here
    }
</script>

<style scoped>

</style>