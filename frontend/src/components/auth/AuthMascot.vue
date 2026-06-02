<template>
  <div class="mascot-scene">
    <div class="mascot-decoration decoration-one"></div>
    <div class="mascot-decoration decoration-two"></div>

    <div
      class="mascot-group"
      :class="{
        'mascot-shake': estado === 'error',
        'mascot-loading': estado === 'loading',
      }"
    >
      <!-- CERDITO GRANDE -->
      <div class="pig pig-main">
        <div class="pig-ear pig-ear-left"></div>
        <div class="pig-ear pig-ear-right"></div>

        <div class="eye pig-eye pig-eye-left">
          <span :style="eyeStyle"></span>
        </div>
        <div class="eye pig-eye pig-eye-right">
          <span :style="eyeStyle"></span>
        </div>

        <div class="pig-nose">
          <span></span>
          <span></span>
        </div>

        <div class="pig-mouth" :class="{ 'mouth-sad': estado === 'error' }"></div>
      </div>

      <!-- CERDITO PEQUEÑO -->
      <div class="pig pig-small">
        <div class="pig-ear pig-ear-left"></div>
        <div class="pig-ear pig-ear-right"></div>

        <div class="eye pig-eye pig-eye-left">
          <span :style="eyeStyle"></span>
        </div>
        <div class="eye pig-eye pig-eye-right">
          <span :style="eyeStyle"></span>
        </div>

        <div class="pig-nose">
          <span></span>
          <span></span>
        </div>

        <div class="pig-mouth pig-mouth-small" :class="{ 'mouth-sad': estado === 'error' }"></div>
      </div>

      <!-- POLLITO GRANDE -->
      <div class="chick chick-main">
        <div class="eye chick-eye chick-eye-left">
          <span :style="eyeStyle"></span>
        </div>
        <div class="eye chick-eye chick-eye-right">
          <span :style="eyeStyle"></span>
        </div>

        <div class="chick-beak"></div>
        <div class="chick-mouth-line"></div>
        <div class="chick-wing"></div>
      </div>

      <!-- POLLITO PEQUEÑO -->
      <div class="chick chick-small">
        <div class="eye chick-eye chick-eye-left">
          <span :style="eyeStyle"></span>
        </div>
        <div class="eye chick-eye chick-eye-right">
          <span :style="eyeStyle"></span>
        </div>

        <div class="chick-beak"></div>
        <div class="chick-mouth-line chick-mouth-line-small"></div>
      </div>
    </div>

    <div class="mascot-message">
      <h2>Sistema Rincón Chaqueño</h2>
      <p>Control de pedidos, caja, ventas y stock para Rincón Chaqueño.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
  estado: {
    type: String,
    default: 'normal',
  },
})

const eyeX = ref(0)
const eyeY = ref(0)

const LIMIT_X = 2.8
const LIMIT_Y = 2.8

const clamp = (value, min, max) => Math.min(max, Math.max(min, value))

const eyeStyle = computed(() => ({
  transform: `translate(${eyeX.value}px, ${eyeY.value}px)`,
}))

const mirarA = (x, y) => {
  eyeX.value = clamp(x, -LIMIT_X, LIMIT_X)
  eyeY.value = clamp(y, -LIMIT_Y, LIMIT_Y)
}

const seguirCursorGlobal = (event) => {
  if (props.estado !== 'normal') return

  const centroX = window.innerWidth / 2
  const centroY = window.innerHeight / 2

  const dx = (event.clientX - centroX) / 95
  const dy = (event.clientY - centroY) / 95

  mirarA(dx, dy)
}

watch(
  () => props.estado,
  (nuevoEstado) => {
    switch (nuevoEstado) {
      case 'usuario':
        // mira hacia el campo usuario (a la derecha y un poco arriba)
        mirarA(2.6, -0.2)
        break

      case 'password':
        // mira hacia el campo contraseña (a la derecha y un poco abajo)
        mirarA(2.6, 1.1)
        break

      case 'verPassword':
        // mira más de lado
        mirarA(2.8, 0)
        break

      case 'loading':
        mirarA(0, 0)
        break

      case 'error':
        mirarA(-1.8, 0)
        break

      default:
        mirarA(0, 0)
        break
    }
  },
  { immediate: true },
)

onMounted(() => {
  window.addEventListener('mousemove', seguirCursorGlobal)
})

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', seguirCursorGlobal)
})
</script>