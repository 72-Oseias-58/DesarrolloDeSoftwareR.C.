import { defineComponent } from 'vue'
import { useDashboardActions } from '@/composables/useDashboardActions'

export default defineComponent({
  name: 'CajeroDashboard',

  setup() {
  const {
    $q: q,
    cambiarModoOscuro,
    cerrarSesion,
  } = useDashboardActions()

  return {
    q,
    cambiarModoOscuro,
    cerrarSesion,
  }
},
})