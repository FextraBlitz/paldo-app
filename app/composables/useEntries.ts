import type { Ref } from 'vue'

export const useEntries = (startDate?: Ref<string | null>, endDate?: Ref<string | null>) => {
  const supabase = useSupabaseClient()
  const user = useSupabaseUser()

  return useAsyncData(`user-entries-${startDate?.value}-${endDate?.value}`, async () => {
    // Handling the 'sub' vs 'id' quirk confirmed in your logs
    const userId = user.value?.id || (user.value as any)?.sub
    if (!userId) return []

    let query = supabase
      .from('ENTRY')
      .select(`
        entry_id,
        e_type,
        e_amount,
        e_date,
        log_id,
        CATEGORY ( c_name ),
        LOG!inner ( user_id )
      `)
      // Filter by the user_id inside the LOG table
      .eq('LOG.user_id', userId)
      .order('e_date', { ascending: true })

    // Apply date range filters if they exist
    if (startDate?.value) query = query.gte('e_date', startDate.value)
    if (endDate?.value) query = query.lte('e_date', endDate.value)
    console.log('se', startDate?.value, endDate?.value)
    const { data, error } = await query

    if (error) {
      console.error('Error fetching entries:', error.message)
      throw error
    }

    interface returnType {
      entry_id: number
      e_type: string
      e_amount: number
      e_date: string
      log_id: number
      CATEGORY: {
        c_name: string
      }
      LOG: {
        user_id:string
      }
    }

    return data as returnType[] || []
  }, {
    // Watchers using getter functions to avoid TypeScript "WatchSource" errors
    watch: [
      () => user.value,
      () => startDate?.value,
      () => endDate?.value
    ]
  })
}