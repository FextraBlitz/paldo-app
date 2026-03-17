export const useCategories = () => {
  const supabase = useSupabaseClient()
  const user = useSupabaseUser()

  return useAsyncData('user-categories', async () => {
    // Handling the 'sub' field confirmed in your logs
    const userId = user.value?.id || (user.value as any)?.sub
    
    if (!userId) {
      console.log("No user session found for categories")
      return []
    }

    const { data, error } = await supabase
      .from('CATEGORY')
      .select(`
        *,
        LOG!inner (
          user_id
        )
      `)
      .eq('LOG.user_id', userId)

    if (error) {
      console.error('Error fetching categories:', error.message)
      throw error
    }

    return data || []
  }, {
    // Re-fetch if the user logs in or out
    watch: [() => user.value]
  })
}