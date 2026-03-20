export const useCategoryStyles = () => {
  // 1. Reuse existing composables to maintain the "Chain of Cohesion"
  const { data: categories, refresh, status } = useCategories()
  const { data: names } = useCategoryNames()

  // 2. Map styles to names, preserving the order from useCategoryNames
  const styles = computed(() => {
    if (!categories.value || !names.value.length) return {}

    const styleMap: Record<string, { icon: string; color: string }> = {}

    // We iterate through the 'names' list to ensure key alignment
    names.value.forEach((name) => {
      // Find the full category object that matches this name
      const category = categories.value?.find((c: any) => c.c_name === name)

      styleMap[name] = {
        // Fallback to defaults if data is missing
        icon: category?.c_icon ?? 'mdi-help-circle', 
        color: category?.c_color ?? 'gray'
      }
    })

    return styleMap
  })

  return {
    styles,
    refresh,
    pending: computed(() => status.value === 'pending')
  }
}