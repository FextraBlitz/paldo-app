<template>
    <div class="min-h-screen bg-white pb-32">
        <Header />
        <div class="bg-white border-b-2 border-slate-500 py-2 text-center text-sm font-bold uppercase text-slate-700">
            All Categories
        </div>
        <div class="bg-white">
            <div class="divide-y">
                <div 
                    v-for="category in categories" 
                    :key="category.category_id"
                    class="flex items-center justify-between px-4 py-3"
                >
                    <div class="flex items-center gap-4">
                        <div 
                            class="flex items-center justify-center w-8 h-8 rounded-full"
                            :style="{ backgroundColor: category.c_color || 'slategray' }"
                        >
                            <UIcon :name="category.c_icon || 'i-game-icons-two-coins'" class="w-7.5 h-7.5 text-white"/>
                        </div>
                        <span class="font-medium text-slate-800"> {{ category.c_name }} </span>
                    </div>
                    <UDropdownMenu
                        :items="getDropdownItems(category)"
                        :ui="{
                            content: 'bg-white ring-0 border border-slate-300 shadow-lg rounded-md'
                        }"
                    >
                        <UButton variant="ghost" color="error" icon="i-lucide-more-horizontal" />
                    </UDropdownMenu>
                </div>

                <div v-if="categories.length === 0" class="px-4 py-8 text-sm text-slate-400 italic text-center">
                    No categories found.
                </div>
            </div>
        </div>
        <div class="px-4 mt-6 mb-20">
            <AddCategory @created="fetchCategories" />

            <EditCategory 
                v-model:open="isEditModalOpen" 
                :category="selectedCategory" 
                @updated="fetchCategories" 
            />
        </div>
        <!--div class="fixed bottom-16 w-full grid grid-cols-3 bg-white border-t border-black text-center text-[10px] font-bold py-2 uppercase">
            <div class="border-r border-black">
                <div class="text-slate-500">Lifetime Expenses</div>
                <div class="text-sm">₱ {{ (logData?.total_expense || 0).toFixed(2) }}</div>
            </div>
            <div class="border-r border-black">
                <div class="text-slate-500">Lifetime Income</div>
                <div class="text-sm">₱ {{ (logData?.total_income || 0).toFixed(2) }}</div>
            </div>
            <div>
                <div class="text-slate-500">Lifetime Total</div>
                <div class="text-sm" :class="(logData?.total_balance || 0) < 0 ? 'text-red-600' : 'text-black'">
                    ₱ {{ (logData?.total_balance || 0).toFixed(2) }}
                </div>
            </div>
        </div-->
        <Footer />
    </div>
</template>

<script setup lang="ts">
    import Header from '~/components/header.vue';
    import Footer from '~/components/footer.vue';
    import AddCategory from '~/components/add_category.vue';
    import EditCategory from '~/components/edit_category.vue';
    import { ref, onMounted } from 'vue';

    const supabase = useSupabaseClient<any>()
    const toast = useToast()
    const categories = ref<any[]>([])
    const isEditModalOpen = ref(false)
    const selectedCategory = ref<any>(null)
    //const logData = ref<any>(null)
    
    const getDropdownItems = (category: any) => [
        [{
            label: 'Edit',
            icon: 'i-lucide-pencil',
            color: 'info',
            onSelect: () => {
                selectedCategory.value = category
                isEditModalOpen.value = true
            }
        }, {
            label: 'Delete',
            icon: 'i-lucide-trash-2',
            color: 'error',
            onSelect: () => deleteCategory(category.category_id, category.c_name)
        }]
    ]

    onMounted(() => {
        fetchCategories()
    })

    async function fetchCategories() {
        const { data: { user: currentUser } } = await supabase.auth.getUser()
        if (!currentUser) return

        const { data: log } = await supabase
            .from('LOG')
            .select('*')
            .eq('user_id', currentUser.id)
            .single()

        if (log) {
            //logData.value = log
            const { data: cats } = await supabase
                .from('CATEGORY')
                .select('*')
                .eq('log_id', log.log_id)
                .order('c_name', { ascending: true })

            if (cats) {
                categories.value = cats
            }
        }
    }

    async function deleteCategory(id: string, name: string) {
        if (name === 'General') {
            toast.add({
                title: "Cannot Delete Category", 
                description: "You cannot delete the default category.",
                color: 'neutral',
                ui: {
                    root: 'bg-red-500 border-2 border-red-900',
                    description: 'text-white',
                },
                close: {class: 'text-white'}
            })
            return
        }

        const { error } = await supabase
            .from('CATEGORY')
            .delete()
            .eq('category_id', id)
            
        if (error) {
            console.error(error)
            toast.add({
                title: "Error",
                description: "Could not delete category. Make sure no entries are currently using it!",
                color: 'neutral',
                ui: {
                    root: 'bg-red-500 border-2 border-red-900',
                    description: 'text-white',
                },
                close: {class: 'text-white'}
            })
        }
        else {
            toast.add({
                title: "Category Deleted",
                description: `${name} has been deleted.`,
                color: 'neutral',
                ui: {
                    root: 'bg-blue-500 border-2 border-blue-900',
                    description: 'text-white',
                },
                close: {class: 'text-white'}
            })
            fetchCategories()
        }
    }
</script>

<style scoped>

</style>