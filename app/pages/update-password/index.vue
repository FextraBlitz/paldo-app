<template>
	<div class="flex items-center py-40 h-screen bg-white flex-col gap-2">
		<div class="text-6xl pt-12 pb-5 font-bold">
			<img src="~/assets/logo.png" alt="Paldo Logo" width="200" height="200" />
		</div>

		<h1 class="text-2xl font-bold mt-4 mb-2 text-black uppercase">Set New Password</h1>
        <p class="text-sm text-gray-500 mb-4 px-10 text-center">
            Please enter your new password below.
        </p>
		
		<ClientOnly>
			<UForm :state="state" class="px-8 w-full space-y-4" @submit="onSubmit">
				<UFormField name="password">
					<UInput
						v-model="state.password"
                        type="password"
						placeholder="New Password"
						icon="i-lucide-lock"
						class="w-full"
						:ui="{
							base: 'bg-white text-black border-2 border-slate-300 h-12'
						}"
						color="info"
                        required
					/>
				</UFormField>

                <UFormField name="confirmPassword">
					<UInput
						v-model="state.confirmPassword"
                        type="password"
						placeholder="Confirm New Password"
						icon="i-lucide-lock"
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
					label="Update Password"
					class="bg-red-500 text-white border-4 border-red-900 font-semibold uppercase mt-2"
					color="error"
				/>
			</UForm>
		</ClientOnly>
	</div>
</template>

<script setup lang="ts">
	import { reactive, ref } from 'vue'

	const supabase = useSupabaseClient()
	const toast = useToast()
    const loading = ref(false)
    
	const state = reactive({
		password: '',
        confirmPassword: ''
	})

	async function onSubmit() {
        if (state.password !== state.confirmPassword) {
            toast.add({
				title: 'Wait!',
				description: 'Your passwords do not match.',
				color: 'neutral',
				icon: 'i-lucide-x-circle',
				ui: {root: 'bg-red-500 border-2 border-red-900', description: 'text-white'},
				close: {class: 'text-white'}
			})
            return
        }

        loading.value = true
		try {
			const { error } = await supabase.auth.updateUser({
                password: state.password
            })
            
			if (error) throw error
            
			toast.add({
				title: 'Password Updated!',
				description: 'Your password has been successfully changed.',
				color: 'neutral',
				icon: 'i-lucide-check-circle',
				ui: {root: 'bg-blue-500 border-2 border-blue-900', description: 'text-white'},
				close: {class: 'text-white'}
			})
            await navigateTo('/')

		}
		catch (error: any) {
			toast.add({
				title: 'Update Failed',
				description: error.message || 'Could not update your password. The link may have expired.',
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