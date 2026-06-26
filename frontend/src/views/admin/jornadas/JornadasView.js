import { computed, defineComponent, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

export default defineComponent({
  name: 'JornadasView',

  setup() {
    const q = useQuasar()
    const authStore = useAuthStore()

    const loading = ref(false)
    const loadingHistorial = ref(false)
    const procesando = ref(false)

    const jornadaActual = ref(null)
    const jornadas = ref([])

    const columns = [
      {
        name: 'fecha',
        label: 'Fecha',
        field: 'fecha',
        align: 'left',
      },
      {
        name: 'hora_inicio',
        label: 'Hora inicio',
        field: 'hora_inicio',
        align: 'center',
      },
      {
        name: 'hora_fin',
        label: 'Hora cierre',
        field: 'hora_fin',
        align: 'center',
        format: (value) => value || 'Pendiente',
      },
      {
        name: 'estado',
        label: 'Estado',
        field: 'estado',
        align: 'center',
      },
    ]

    const textoJornadaActual = computed(() => {
      if (!jornadaActual.value) {
        return 'No existe jornada registrada para hoy.'
      }

      if (jornadaActual.value.estado === 'ABIERTA') {
        return 'La jornada del día se encuentra abierta.'
      }

      return 'La jornada del día ya fue cerrada.'
    })

    const cargarJornadaActual = async () => {
      loading.value = true

      try {
        const response = await api.get('/jornadas/actual')
        jornadaActual.value = response.data?.jornada || null
      } catch (error) {
        q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Error al cargar jornada actual.',
          position: 'top',
        })

        jornadaActual.value = null
      } finally {
        loading.value = false
      }
    }

    const cargarHistorial = async () => {
      loadingHistorial.value = true

      try {
        const response = await api.get('/jornadas')

        const data = response.data?.jornadas

        jornadas.value = Array.isArray(data?.data)
          ? data.data
          : Array.isArray(data)
            ? data
            : []
      } catch (error) {
        q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Error al cargar historial de jornadas.',
          position: 'top',
        })

        jornadas.value = []
      } finally {
        loadingHistorial.value = false
      }
    }

    const abrirJornada = async () => {
      procesando.value = true

      try {
        const response = await api.post('/jornadas/abrir')

        jornadaActual.value = response.data?.jornada || null

        q.notify({
          type: 'positive',
          message: response.data?.message || 'Jornada abierta correctamente.',
          position: 'top',
        })

        await cargarHistorial()
      } catch (error) {
        q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Error al abrir jornada.',
          position: 'top',
        })
      } finally {
        procesando.value = false
      }
    }

    const cerrarJornada = async () => {
      procesando.value = true

      try {
        const response = await api.patch('/jornadas/cerrar')

        jornadaActual.value = response.data?.jornada || null

        q.notify({
          type: 'positive',
          message: response.data?.message || 'Jornada cerrada correctamente.',
          position: 'top',
        })

        await cargarHistorial()
      } catch (error) {
        q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Error al cerrar jornada.',
          position: 'top',
        })
      } finally {
        procesando.value = false
      }
    }

    const confirmarAbrirJornada = () => {
      q.dialog({
        title: 'Abrir jornada',
        message: '¿Deseas abrir la jornada de hoy para tu sucursal?',
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
        abrirJornada()
      })
    }

    const confirmarCerrarJornada = () => {
      q.dialog({
        title: 'Cerrar jornada',
        message: '¿Deseas cerrar la jornada actual? Después no se podrá abrir otra jornada para la misma fecha.',
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
        cerrarJornada()
      })
    }

    const formatearFecha = (fecha) => {
      if (!fecha) return 'No registrada'

      return String(fecha).slice(0, 10)
    }

    onMounted(async () => {
      await cargarJornadaActual()
      await cargarHistorial()
    })

    return {
      q,
      authStore,
      loading,
      loadingHistorial,
      procesando,
      jornadaActual,
      jornadas,
      columns,
      textoJornadaActual,
      cargarJornadaActual,
      cargarHistorial,
      abrirJornada,
      cerrarJornada,
      confirmarAbrirJornada,
      confirmarCerrarJornada,
      formatearFecha,
    }
  },
})