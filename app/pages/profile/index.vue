<template>
    <div class="min-h-screen bg-slate-50 pb-32">
        <div class="flex flex-col items-center justify-center pt-20 pb-12 bg-white">
            <div class="w-32 h-32 rounded-full border-2 border-slate-200 flex items-center justify-center bg-slate-100 overflow-hidden mb-6 shadow-sm">
                <img v-if="avatarUrl" :src="avatarUrl" class="w-full h-full object-cover" />
                <UIcon v-else name="i-lucide-user" class="w-16 h-16 text-slate-400" />
            </div>
            
            <h1 class="text-2xl font-bold text-slate-900">{{ profileName }}</h1>
            <p class="text-slate-500 font-medium mt-1">{{ profileEmail }}</p>
        </div>

        <div class="bg-white border-t border-b border-gray-200 mt-6 divide-y divide-gray-200">
            
            <NuxtLink to="/edit_profile" class="w-full flex items-center justify-between p-5 hover:bg-slate-50 transition-colors">
                <div class="flex items-center gap-4">
                    <UIcon name="i-lucide-settings" class="w-6 h-6 text-slate-700" />
                    <span class="text-lg font-medium text-slate-800">Edit Profile</span>
                </div>
                <UIcon name="i-lucide-chevron-right" class="w-6 h-6 text-slate-400" />
            </NuxtLink>
            
            <button @click="handleLogout" class="w-full flex items-center p-5 hover:bg-red-50 transition-colors">
                <div class="flex items-center gap-4">
                    <UIcon name="i-lucide-log-out" class="w-6 h-6 text-red-600" />
                    <span class="text-lg font-medium text-red-600">Log Out</span>
                </div>
            </button>

        </div>
    </div>
</template>

<script setup lang="ts">
    import { ref, onMounted } from 'vue'

    const supabase = useSupabaseClient<any>()
    
    const profileName = ref('Loading...')
    const profileEmail = ref('...')
    const avatarUrl = ref('')

    onMounted(async () => {
        const { data: { user } } = await supabase.auth.getUser()
        
        if (user) {
            const { data: userData, error } = await supabase
                .from('USER')
                .select('u_name, u_email, u_avatar')
                .eq('user_id', user.id)
                .single()

            if (error) {
                console.error("Error fetching user data:", error)
                profileName.value = 'Paldo User'
                profileEmail.value = user.email || 'Error loading email'
            }
            else if (userData) {
                profileName.value = userData.u_name || 'New User'
                profileEmail.value = userData.u_email || ''
                avatarUrl.value = userData.u_avatar || ''
            }
        }
        else {
            profileName.value = 'Guest'
            profileEmail.value = 'Not logged in'
        }
    })

    async function handleLogout() {
        const isConfirmed = confirm("Are you sure you want to log out?")
        if (!isConfirmed) return

        const { error } = await supabase.auth.signOut()
        
        if (error) {
            console.error('Logout Error:', error.message)
            alert('Failed to log out. Please try again.')
        } else {
            navigateTo('/login') 
        }
    }
</script>