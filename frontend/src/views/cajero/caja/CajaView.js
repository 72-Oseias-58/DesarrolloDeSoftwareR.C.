import { computed, defineComponent, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

export default defineComponent({
  name: 'CajaView',

  setup() {
    const q = useQuasar()
    const authStore = useAuthStore()

    const loading = ref(false)
    const procesando = ref(false)

    const cajaActual = ref(null)

    const formAbrir = ref({
      monto_inicial: 0,
    })

    const formCerrar = ref({
      monto_final: null,
    })

    const textoCajaActual = computed(() => {
      if (!cajaActual.value) {
        return 'No existe una caja abierta para este cajero.'
      }

      if (cajaActual.value.estado === 'ABIERTA') {
        return 'Tienes una caja abierta actualmente.'
      }

      return 'La caja fue cerrada.'
    })

    const formatoDinero = (valor) => {
      return Number(valor || 0).toFixed(2)
    }

    const cargarCajaActual = async () => {
      loading.value = true

      try {
        const response = await api.get('/cajero/caja/actual')
        cajaActual.value = response.data?.caja || null
      } catch (error) {
        q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Error al cargar caja actual.',
          position: 'top',
        })

        cajaActual.value = null
      } finally {
        loading.value = false
      }
    }

    const abrirCaja = async () => {
      procesando.value = true

      try {
        const response = await api.post('/cajero/caja/abrir', {
          monto_inicial: formAbrir.value.monto_inicial || 0,
        })

        cajaActual.value = response.data?.caja || null

        q.notify({
          type: 'positive',
          message: response.data?.message || 'Caja abierta correctamente.',
          position: 'top',
        })
      } catch (error) {
        q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Error al abrir caja.',
          position: 'top',
        })
      } finally {
        procesando.value = false
      }
    }

    const cerrarCaja = async () => {
      if (formCerrar.value.monto_final === null || formCerrar.value.monto_final === '') {
        q.notify({
          type: 'negative',
          message: 'Debe ingresar el monto final para cerrar caja.',
          position: 'top',
        })
        return
      }

      procesando.value = true

      try {
        const response = await api.patch('/cajero/caja/cerrar', {
          monto_final: formCerrar.value.monto_final,
        })

        cajaActual.value = response.data?.caja || null

        q.notify({
          type: 'positive',
          message: response.data?.message || 'Caja cerrada correctamente.',
          position: 'top',
        })

        formCerrar.value.monto_final = null
      } catch (error) {
        q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Error al cerrar caja.',
          position: 'top',
        })
      } finally {
        procesando.value = false
      }
    }

    const confirmarAbrirCaja = () => {
      q.dialog({
        title: 'Abrir caja',
        message: `¿Deseas abrir caja con Bs ${formatoDinero(formAbrir.value.monto_inicial)}?`,
        cancel: true,
        persistent: true,
        ok: {
          label: 'Abrir',
          color: 'green',
        },
        cancel: {
          label: 'Cancelar',
          color: 'grey',
          flat: true,
        },
      }).onOk(() => {
        abrirCaja()
      })
    }

    const confirmarCerrarCaja = () => {
      q.dialog({
        title: 'Cerrar caja',
        message: `¿Deseas cerrar caja con monto final Bs ${formatoDinero(formCerrar.value.monto_final)}?`,
        cancel: true,
        persistent: true,
        ok: {
          label: 'Cerrar',
          color: 'red',
        },
        cancel: {
          label: 'Cancelar',
          color: 'grey',
          flat: true,
        },
      }).onOk(() => {
        cerrarCaja()
      })
    }

    onMounted(() => {
      cargarCajaActual()
    })

    return {
      q,
      authStore,
      loading,
      procesando,
      cajaActual,
      formAbrir,
      formCerrar,
      textoCajaActual,
      formatoDinero,
      cargarCajaActual,
      abrirCaja,
      cerrarCaja,
      confirmarAbrirCaja,
      confirmarCerrarCaja,
    }
  },
})