<script setup>
const supabase = useSupabaseClient()
const router = useRouter()

// A reactive state to show a loading spinner if you want
const isDeleting = ref(false)

async function handleDeleteAccount() {
  const confirmed = confirm("Are you absolutely sure? This will delete all your Paldo data.")
  if (!confirmed) return

  isDeleting.value = true

  try {
    // 1. Call our custom server route
    // This removes the user from Supabase Auth and triggers your database cascades
    await $fetch('/api/delete-account', { method: 'POST' })

    // 2. Clear the local session
    // This wipes the cookies and local storage so the UI knows they are logged out
    await supabase.auth.signOut()

    // 3. Redirect to the landing page or login
    router.push('/')
  } catch (error) {
    alert("Error deleting account: " + error.message)
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <button 
    @click="handleDeleteAccount" 
    :disabled="isDeleting"
    class="bg-red-500 text-white p-2 rounded"
  >
    {{ isDeleting ? 'Deleting...' : 'Delete My Account' }}
  </button>
</template>