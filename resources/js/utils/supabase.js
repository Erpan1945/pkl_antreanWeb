import { createClient } from '@supabase/supabase-js'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

console.log("Supabase URL:", supabaseUrl); // CEK DI KONSOL BROWSER (F12)

export const supabase = createClient(supabaseUrl, supabaseAnonKey)



