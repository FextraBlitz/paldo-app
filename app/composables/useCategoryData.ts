 export const useCategorySums = (startDate?: Ref<string | null>, endDate?: Ref<string | null>) => {
  // 1. Reuse the main fetcher (Nuxt reuses the cache automatically)
  const { data: entries, status } = useEntries(startDate, endDate)

  // 2. Process the data reactively
  const data = computed(() => {
    const result = {
      totals: {} as Record<string, number>,
      expenses: {} as Record<string, number>,
      income: {} as Record<string, number>,
    }
    
    if (!entries.value) return result

    let entry_type;
    for (const entry of entries.value) {
      const catName = entry.CATEGORY?.c_name || 'Uncategorized'
      const amount = Number(entry.e_amount) || 0

      if (!result.totals[catName]) result.totals[catName] = 0;
      
      entry_type = entry.e_type?.toLowerCase()
      if (entry_type === 'e') {
        if (!result.expenses[catName]) result.expenses[catName] = 0;
        result.expenses[catName] += amount
        result.totals[catName] -= amount
      }
      else if (entry_type === 'i') {
        if (!result.income[catName]) result.income[catName] = 0;
        result.income[catName] += amount
        result.totals[catName] += amount
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