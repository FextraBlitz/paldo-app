<template>
	<div class="flex items-center py-40 h-screen bg-white flex-col gap-2">
		<div class="text-6xl pt-12 pb-5 font-bold">
			<img src="~/assets/logo.png" alt="Paldo Logo" width="200" height="200" />
		</div>

		<h1 class="text-2xl font-bold mt-4 mb-2 text-black uppercase">Reset Password</h1>
        <p class="text-sm text-gray-500 mb-4 px-10 text-center">
            Enter your email address and we'll send you a link to reset your password.
        </p>
		
		<ClientOnly>
			<UForm :state="state" class="px-8 w-full space-y-4" @submit="onSubmit">
				<UFormField name="email">
					<UInput
						v-model="state.email"
                        type="email"
						placeholder="Email"
						icon="i-lucide-mail"
						class="w-full"
						:ui="{
							base: 'bg-white text-black border-2 border-slate-300 h-12'
						}"
						color="info"
                        required
					/>
				</UFormField>

				<UButton
					type="submit"
					block
					size="xl"
                    :loading="loading"
					label="Send Reset Link"
					class="bg-red-500 text-white border-4 border-red-900 font-semibold uppercase mt-2"
					color="error"
				/>
			</UForm>
		</ClientOnly>
		
        <div class="py-4 mt-2">
			<p class="text-black">
				Remember your password? <NuxtLink to="/" class="font-bold underline text-blue-500">Log In</NuxtLink>
			</p>
		</div>
	</div>
</template>

<script setup lang="ts">
	import { reactive, ref } from 'vue'

	const supabase = useSupabaseClient()
	const toast = useToast()
    const loading = ref(false)
    
	const state = reactive({
		email: ''
	})

	async function onSubmit() {
        if (!state.email) return

        loading.value = true
		try {
			const { error } = await supabase.auth.resetPasswordForEmail(state.email, {
                redirectTo: `${window.location.origin}/update-password`,
            })
            
			if (error) throw error
            
			toast.add({
				title: 'Email Sent!',
				description: 'Check your inbox for the password reset link.',
				color: 'neutral',
				icon: 'i-lucide-check-circle',
				ui: {root: 'bg-blue-500 border-2 border-blue-900', description: 'text-white'},
				close: {class: 'text-white'}
			})
            
            state.email = ''

		}
		catch (error: any) {
			toast.add({
				title: 'Request Failed',
				description: error.message || 'Could not send reset email.',
				color: 'neutral',
				icon: 'i-lucide-x-circle',
				ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
				close: {class: 'text-white'}
			})
		} finally {
            loading.value = false
        }
	}
</script>

<style scoped>

</style>