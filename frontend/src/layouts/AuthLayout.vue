<template>
  <main class="auth-layout">
    <section class="auth-container">
      <aside class="auth-visual-panel">
        <AuthMascot :estado="mascotEstado" />
      </aside>

      <section class="auth-form-panel">
        <slot />
      </section>
    </section>
  </main>
</template>

<script setup>
import AuthMascot from '@/components/auth/AuthMascot.vue'

defineProps({
  mascotEstado: {
    type: String,
    default: 'normal',
  },
})
</script>