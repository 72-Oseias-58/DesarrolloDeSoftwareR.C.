<template>
  <AuthLayout :mascot-estado="mascotEstado">
    <LoginForm
      @login-error="activarErrorMascota"
      @login-loading="activarCargaMascota"
      @login-normal="activarNormalMascota"
      @focus-usuario="activarUsuarioMascota"
      @focus-password="activarPasswordMascota"
      @toggle-password="activarVerPasswordMascota"
    />
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import LoginForm from '@/components/auth/LoginForm.vue'

const mascotEstado = ref('normal')

const activarNormalMascota = () => {
  mascotEstado.value = 'normal'
}

const activarUsuarioMascota = () => {
  mascotEstado.value = 'usuario'
}

const activarPasswordMascota = () => {
  mascotEstado.value = 'password'
}

const activarVerPasswordMascota = () => {
  mascotEstado.value = 'verPassword'
}

const activarCargaMascota = () => {
  mascotEstado.value = 'loading'
}

const activarErrorMascota = () => {
  mascotEstado.value = 'error'

  setTimeout(() => {
    mascotEstado.value = 'normal'
  }, 650)
}
</script>