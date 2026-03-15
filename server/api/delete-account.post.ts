import { createClient } from '@supabase/supabase-js'
import { serverSupabaseClient } from '#supabase/server'

export default defineEventHandler(async (event) => {
  // 1. Get the user's session from the request
  const client = await serverSupabaseClient(event)
  const { data: { user } } = await client.auth.getUser()

  if (!user) {
    throw createError({ statusCode: 401, message: 'Unauthorized' })
  }

  // 2. Create an Admin Client using the Service Role Key
  // This client can bypass RLS and delete users
  const config = useRuntimeConfig()
  const supabaseAdmin = createClient(
    config.public.supabase.url,
    process.env.SUPABASE_SERVICE_ROLE_KEY!
  )

  // 3. Delete the user
  const { error } = await supabaseAdmin.auth.admin.deleteUser(user.id)

  if (error) {
    throw createError({ statusCode: 400, message: error.message })
  }

  return { message: 'Account and all associated data deleted successfully' }
})