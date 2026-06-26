import './assets/css/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import {
  Quasar,
  Notify,
  Dark,
  Dialog,
} from 'quasar'

import '@quasar/extras/material-icons/material-icons.css'
import 'quasar/src/css/index.sass'

import App from './App.vue'
import router from './router'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.use(Quasar, {
  plugins: {
    Notify,
    Dark,
    Dialog,
  },

  config: {
    brand: {
      primary: '#D92243',
      secondary: '#F69D39',
      accent: '#E0C375',
      dark: '#3F1F1B',
    },

    notify: {
      position: 'top-right',
      timeout: 2500,
      progress: true,
      actions: [
        {
          icon: 'close',
          color: 'white',
        },
      ],
    },
  },
})

app.mount('#app')