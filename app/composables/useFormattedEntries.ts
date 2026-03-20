export const useFormattedEntries = (startDate?: Ref<string | null>, endDate?: Ref<string | null>) => {
  // 1. Reuse the main fetcher (Nuxt reuses the cache automatically)
  const { data: entries, status } = useEntries(startDate, endDate)

  // 2. Process the data reactively
  const data = computed(() => {
    const result = {
      totals: [] as FormattedEntry[],
      expenses: [] as FormattedEntry[],
      income: [] as FormattedEntry[],
    }
    
    if (!entries.value) return result

    let entry_type;
    for (const entry of entries.value) {
      const entry_type = entry.e_type?.toLowerCase()
      const lastTotal = result.totals.length > 0 ? result.totals.at(-1) : null
      const fallbackId = lastTotal ? lastTotal.id + 1 : 0
      const entry_data: FormattedEntry = {
        id: Number(entry.entry_id) || fallbackId,
        catName: entry.CATEGORY?.c_name || 'Uncategorized',
        amount: Number(entry.e_amount) || 0,
        date: new Date(entry.e_date),
        type: entry_type,
      }
      
      if (entry_type === 'e') {
        result.totals.push({...entry_data, amount: (result.totals.at(-1)?.amount ?? 0) - entry_data.amount})
        result.expenses[result.totals.length-1] = entry_data
      }
      else if (entry_type === 'i') {
        result.totals.push({...entry_data, amount: (result.totals.at(-1)?.amount ?? 0) + entry_data.amount})
        result.income[result.totals.length-1] = entry_data
      }
    }
    return result
  })

  // 3. Convert to an array format if needed for charts (e.g., [ ['Food', 500], ['Rent', 1200] ])
  // const asArray = computed(() => Object.entries(data.value))

  return {
    data,
    // asArray,
    pending: computed(() => status.value === 'pending')
  }
}