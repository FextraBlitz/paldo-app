<template>
    <div class="min-h-screen bg-slate-50 pb-32">
        <div class="bg-white flex items-center justify-between px-4 py-4 border-b border-gray-200 sticky top-0 z-10">
            <UButton icon="i-lucide-arrow-left" color="info" variant="ghost" @click="navigateTo('/profile')" />
            <h1 class="text-xl font-bold text-slate-800 absolute left-1/2 -translate-x-1/2">Edit Account</h1>
            <div class="w-8"></div>
        </div>
        
        <div class="flex flex-col items-center justify-center pt-10 pb-6">
            <div class="relative w-32 h-32 rounded-full border border-slate-300 flex items-center justify-center bg-white shadow-sm overflow-hidden">
                
                <img v-if="avatarUrl" :src="avatarUrl" class="w-full h-full object-cover" />
                <UIcon v-else name="i-lucide-user" class="w-16 h-16 text-slate-300" />
                
                <input 
                    type="file" 
                    ref="fileInput" 
                    accept="image/*" 
                    class="hidden" 
                    @change="uploadAvatar" 
                />
            </div>

            <button 
                @click="triggerFileInput" 
                :disabled="uploadingAvatar"
                class="absolute mt-24 ml-24 bg-white rounded-full p-2.5 border border-gray-200 shadow-md hover:bg-gray-50 transition-colors z-10"
            >
                <UIcon v-if="uploadingAvatar" name="i-lucide-loader-2" class="w-5 h-5 text-slate-700 animate-spin" />
                <UIcon v-else name="i-lucide-camera" class="w-5 h-5 text-slate-700" />
            </button>
        </div>

        <div class="px-6 py-4">
            <UForm @submit.prevent="saveChanges" class="space-y-5">
                
                <UFormField>
                    <UInput v-model="editForm.name" color="info" size="xl" placeholder="Name" class="w-full bg-white font-medium">
                        <template #leading>
                            <UIcon name="i-lucide-user" class="text-slate-500 w-5 h-5" />
                        </template>
                    </UInput>
                </UFormField>

                <UFormField>
                    <UInput v-model="editForm.email" color="info" type="email" size="xl" placeholder="Email" class="w-full bg-white font-medium">
                        <template #leading>
                            <UIcon name="i-lucide-mail" class="text-slate-500 w-5 h-5" />
                        </template>
                    </UInput>
                </UFormField>

                <UFormField>
                    <UInput v-model="editForm.password" color="info" type="password" size="xl" placeholder="Change Password" class="w-full bg-white font-medium">
                        <template #leading>
                            <UIcon name="i-lucide-lock" class="text-slate-500 w-5 h-5" />
                        </template>
                    </UInput>
                </UFormField>

                <UButton type="submit" color="error" block variant="solid" size="xl" :loading="loading" class="mt-8 font-bold bg-red-500 hover:bg-red-300 border border-gray-300 uppercase shadow-sm text-white">
                    Save Changes
                </UButton>

            </UForm>
        </div>

        <Footer />
    </div>
</template>

<script setup lang="ts">
    import { ref, onMounted } from 'vue'
    import Footer from '~/components/footer.vue'

    const supabase = useSupabaseClient<any>()
    const toast = useToast()
    const loading = ref(false)

    const fileInput = ref<HTMLInputElement | null>(null)
    const avatarUrl = ref('')
    const uploadingAvatar = ref(false)

    const editForm = ref({
        name: '',
        email: '',
        password: ''
    })
    const originalEmail = ref('')

    onMounted(async () => {
        const { data: { user } } = await supabase.auth.getUser()
        if (!user) return

        const { data: userData } = await supabase
            .from('USER')
            .select('u_name, u_email, u_avatar')
            .eq('user_id', user.id)
            .single()

        if (userData) {
            editForm.value.name = userData.u_name || ''
            editForm.value.email = userData.u_email || user.email || ''
            avatarUrl.value = userData.u_avatar || ''
            originalEmail.value = editForm.value.email
        }
    })

    function triggerFileInput() {
        if (fileInput.value) { fileInput.value.click() }
    }

    async function uploadAvatar(event: Event) {
        const target = event.target as HTMLInputElement
        const file = target.files?.[0]
        
        if (!file) return

        uploadingAvatar.value = true

        const { data: { user } } = await supabase.auth.getUser()
        if (!user) return

        const fileExt = file.name.split('.').pop() || 'png'
        const filePath = `${user.id}/avatar.${fileExt}`

        const { error: uploadError } = await supabase.storage
            .from('avatars')
            .upload(filePath, file, { upsert: true, cacheControl: '3600' })

        if (uploadError) {
            console.error(uploadError)
            toast.add({
                title: 'Upload Failed',
                description: uploadError.message,
                color: 'neutral',
                ui: { root: 'bg-red-500 border-2 border-red-900', description: 'text-white' },
                close: { class: 'text-white' }
            })
            uploadingAvatar.value = false
            return
        }

        const { data: { publicUrl } } = supabase.storage
            .from('avatars')
            .getPublicUrl(filePath)

        const timestamp = new Date().getTime()
        const freshUrl = `${publicUrl}?t=${timestamp}`

        const { error: dbError } = await supabase
            .from('USER')
            .update({ u_avatar: freshUrl })
            .eq('user_id', user.id)

        uploadingAvatar.value = false

        if (dbError) {
            console.error(dbError)
            toast.add({
                title: 'Database Error',
                description: 'Image uploaded, but could not link to profile.',
                color: 'neutral',
                ui: { root: 'bg-red-500 border-2 border-red-900', description: 'text-white' },
                close: { class: 'text-white' }
            })
        }
        else {
            avatarUrl.value = freshUrl
            toast.add({
                title: 'Success',
                description: 'Profile picture updated!',
                color: 'neutral',
                ui: { root: 'bg-blue-500 border-2 border-blue-900', description: 'text-white' },
                close: { class: 'text-white' }
            })
        }
    }

    async function saveChanges() {
        loading.value = true
        let hasError = false

        const { data: { user } } = await supabase.auth.getUser()
        if (!user) {
            loading.value = false
            return
        }

        const authUpdates: { email?: string, password?: string } = {}
        
        if (editForm.value.email && editForm.value.email !== originalEmail.value) {
            authUpdates.email = editForm.value.email
        }
        
        if (editForm.value.password) {
            authUpdates.password = editForm.value.password
        }

        if (Object.keys(authUpdates).length > 0) {
            const { error: authError } = await supabase.auth.updateUser(authUpdates)
            if (authError) {
                console.error("Auth Update Error:", authError)
                toast.add({
                    title: 'Authentication Error',
                    description: authError.message,
                    color: 'neutral',
                    ui: { root: 'bg-red-500 border-2 border-red-900', description: 'text-white' },
                    close: { class: 'text-white' }
                })
                hasError = true
            }
        }

        if (!hasError) {
            const { error: dbError } = await supabase
                .from('USER')
                .update({
                    u_name: editForm.value.name,
                    u_email: editForm.value.email
                })
                .eq('user_id', user.id)

            if (dbError) {
                console.error("Database Update Error:", dbError)
                toast.add({
                    title: 'Database Error',
                    description: 'Could not update profile information.',
                    color: 'neutral',
                    ui: { root: 'bg-red-500 border-2 border-red-900', description: 'text-white' },
                    close: { class: 'text-white' }
                })
                hasError = true
            }
        }

        loading.value = false

        if (!hasError) {
            toast.add({
                title: 'Success',
                description: 'Profile updated successfully!',
                color: 'neutral',
                ui: { root: 'bg-blue-500 border-2 border-blue-900', description: 'text-white' },
                close: { class: 'text-white' }
            })
            editForm.value.password = ''
            originalEmail.value = editForm.value.email
        }
    }
</script>