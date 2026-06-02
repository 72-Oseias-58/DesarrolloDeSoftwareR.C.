<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const mensaje = ref('Cargando...')

onMounted(async () => {
  try {
    const res = await axios.get('http://127.0.0.1:8000/prueba')
    mensaje.value = res.data.mensaje
  } catch (error) {
    mensaje.value = 'Error conectando con backend'
  }
})
</script>

<template>
  <h1>Rinco Chaqueño 🐷</h1>
  <p>{{ mensaje }}</p>
</template>