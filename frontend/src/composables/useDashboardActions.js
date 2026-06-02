import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'

export function useDashboardActions() {
  const $q = useQuasar()
  const authStore = useAuthStore()

  const cambiarModoOscuro = () => {
    $q.dark.toggle()

    $q.notify({
      type: 'info',
      icon: $q.dark.isActive ? 'dark_mode' : 'light_mode',
      message: $q.dark.isActive ? 'Modo oscuro activado' : 'Modo claro activado',
      timeout: 1200,
    })
  }

  const cerrarSesion = () => {
    $q.notify({
      type: 'positive',
      icon: 'logout',
      message: 'Sesión cerrada correctamente',
      timeout: 1200,
    })

    setTimeout(() => {
      authStore.logout()
    }, 400)
  }

  return {
    $q,
    cambiarModoOscuro,
    cerrarSesion,
  }
}