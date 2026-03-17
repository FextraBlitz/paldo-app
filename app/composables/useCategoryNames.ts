export const useCategoryNames = () => {
  // 1. Grab the existing categories data (Nuxt will reuse the cache)
  const { data: categories, status } = useCategories()

  // 2. Perform the extraction reactively
  const data = computed(() => {
    if (!categories.value) return []
    
    return categories.value.map((category: any) => {
      // Logic from your function: return name or empty string
      return category.c_name ?? ''
    })
  })

  return {
    data,
    // We export the status so the UI knows if it's still loading the source data
    pending: computed(() => status.value === 'pending')
  }
}