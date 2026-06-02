import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import { quasar, transformAssetUrls } from '@quasar/vite-plugin'

const quasarVariables = fileURLToPath(
  new URL('./src/assets/quasar-variables.sass', import.meta.url),
)

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue({
      template: {
        transformAssetUrls,
      },
    }),

    vueDevTools(),

    quasar({
      sassVariables: quasarVariables,
    }),
  ],

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})