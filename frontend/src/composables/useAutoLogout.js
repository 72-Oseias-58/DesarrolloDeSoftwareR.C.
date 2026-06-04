import { onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'

export function useAutoLogout() {
  const router = useRouter()
  const $q = useQuasar()
  const authStore = useAuthStore()

  
  const TIEMPO_INACTIVIDAD = 60 * 1000

  let temporizador = null

  const cerrarPorInactividad = () => {
    authStore.logout()

    $q.notify({
      type: 'warning',
      message: 'Sesión cerrada por inactividad',
      position: 'top',
      timeout: 3000
    })
    router.push('/login')
  }
  const reiniciarTemporizador = () => {
    clearTimeout(temporizador)
    temporizador = setTimeout(() => {
      cerrarPorInactividad()
    }, TIEMPO_INACTIVIDAD)
  }

  const eventos = [
    'mousemove',
    'keydown',
    'click',
    'scroll',
    'touchstart'
  ]

  onMounted(() => {
    eventos.forEach((evento) => {
      window.addEventListener(evento, reiniciarTemporizador)
    })

    reiniciarTemporizador()
  })

  onBeforeUnmount(() => {
    clearTimeout(temporizador)

    eventos.forEach((evento) => {
      window.removeEventListener(evento, reiniciarTemporizador)
    })
  })
}