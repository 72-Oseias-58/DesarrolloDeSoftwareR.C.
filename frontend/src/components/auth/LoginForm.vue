<template>
  <form class="login-form" @submit.prevent="iniciarSesion">
    <div class="login-header">
      <span class="login-icon">🔐</span>
      <h2>Bienvenido</h2>
      <p>Ingresa tus credenciales para continuar</p>
    </div>

    <div class="form-group">
      <label for="usuario">Usuario</label>

      <div class="input-wrapper">
        <span class="input-icon">👤</span>
        <input
          id="usuario"
          v-model.trim="form.usuario"
          type="text"
          placeholder="Ej: jhonatan"
          autocomplete="username"
          :disabled="cargando"
          @focus="emit('focus-usuario')"
          @input="emit('focus-usuario')"
          @blur="emit('login-normal')"
        />
      </div>
    </div>

    <div class="form-group">
      <label for="password">Contraseña</label>

      <div class="input-wrapper">
        <span class="input-icon">🔑</span>
        <input
          id="password"
          v-model="form.password"
          :type="mostrarPassword ? 'text' : 'password'"
          placeholder="Ingrese su contraseña"
          autocomplete="current-password"
          :disabled="cargando"
          @focus="emit('focus-password')"
          @input="emit('focus-password')"
          @blur="emit('login-normal')"
        />
        

        <button
          class="password-toggle"
          type="button"
          @click="alternarPassword"
          :disabled="cargando"
        >
          {{ mostrarPassword ? 'Ocultar' : 'Ver' }}
        </button>
      </div>
    </div>

    <Transition name="fade">
      <p v-if="error" class="login-error">
        {{ error }}
      </p>
    </Transition>

    <button class="login-button" type="submit" :disabled="cargando">
      <span v-if="cargando" class="loader"></span>
      <span>{{ cargando ? 'Verificando...' : 'Ingresar al sistema' }}</span>
    </button>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const emit = defineEmits([
  'login-error',
  'login-loading',
  'login-normal',
  'focus-usuario',
  'focus-password',
  'toggle-password',
])

const form = reactive({
  usuario: '',
  password: '',
})

const error = ref('')
const cargando = ref(false)
const mostrarPassword = ref(false)

const alternarPassword = () => {
  mostrarPassword.value = !mostrarPassword.value
  emit('toggle-password')
}
const iniciarSesion = async () => {
  error.value = ''

  if (!form.usuario || !form.password) {
    error.value = 'Debe ingresar usuario y contraseña.'
    emit('login-error')
    return
  }

  cargando.value = true
  emit('login-loading')

  try {
    await authStore.login({
      usuario: form.usuario,
      password: form.password,
    })
  } catch (e) {
    emit('login-error')

    if (e.response?.status === 401) {
      error.value = 'Usuario o contraseña incorrectos.'
    } else if (e.response?.data?.message) {
      error.value = e.response.data.message
    }  else if (e.message) {
  error.value = e.message
} else {
  error.value = 'No se pudo conectar con el servidor.'
}
  } finally {
    cargando.value = false

    setTimeout(() => {
      emit('login-normal')
    }, 700)
  }
}
</script>