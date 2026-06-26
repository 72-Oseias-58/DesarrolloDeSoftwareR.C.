import {
  computed,
  defineComponent,
  onMounted,
  reactive,
  ref,
  watch,
} from 'vue'

import {
  useRoute,
  useRouter,
} from 'vue-router'

import { useQuasar } from 'quasar'

import estadisticasService from '@/services/estadisticasService'

import GraficoVentas from '@/components/estadisticas/GraficoVentas.vue'
import TarjetasMetricas from '@/components/estadisticas/TarjetasMetricas.vue'

const crearMetricasIniciales = () => ({
  total_ventas: 0,
  cantidad_pedidos: 0,
  ticket_promedio: 0,
  total_efectivo: 0,
  total_qr: 0,
})

const crearPeriodoInicial = () => ({
  tipo: 'hoy',
  desde: null,
  hasta: null,
  agrupacion: 'hora',
})

export default defineComponent({
  name: 'EstadisticasSucursalView',

  components: {
    GraficoVentas,
    TarjetasMetricas,
  },

  setup() {
    const route = useRoute()
    const router = useRouter()
    const $q = useQuasar()

    const cargando = ref(false)

    const filtros = reactive({
      periodo: 'hoy',
      fechaDesde: '',
      fechaHasta: '',
    })

    const estadisticas = reactive({
      periodo: crearPeriodoInicial(),
      metricas: crearMetricasIniciales(),
      categorias: [],
      series: [],
    })

    const opcionesPeriodo = [
      {
        label: 'Hoy',
        value: 'hoy',
      },
      {
        label: 'Esta semana',
        value: 'semana',
      },
      {
        label: 'Este mes',
        value: 'mes',
      },
      {
        label: 'Este año',
        value: 'anio',
      },
      {
        label: 'Rango personalizado',
        value: 'personalizado',
      },
    ]

    const idSucursal = computed(() => {
      return Number(route.params.id)
    })

    const nombreSucursal = computed(() => {
      return estadisticas.series?.[0]?.nombre || 'Sucursal'
    })

    const filtrosValidos = computed(() => {
      if (filtros.periodo !== 'personalizado') {
        return true
      }

      if (!filtros.fechaDesde || !filtros.fechaHasta) {
        return false
      }

      return filtros.fechaHasta >= filtros.fechaDesde
    })

    const formatearFecha = (fecha) => {
      if (!fecha) {
        return ''
      }

      return new Intl.DateTimeFormat('es-BO', {
        dateStyle: 'medium',
      }).format(
        new Date(
          fecha.replace(' ', 'T'),
        ),
      )
    }

    const subtituloGrafico = computed(() => {
      const periodo = estadisticas.periodo

      if (!periodo.desde || !periodo.hasta) {
        return 'Ventas de la sucursal seleccionada'
      }

      return `Desde ${formatearFecha(periodo.desde)} hasta ${formatearFecha(periodo.hasta)}`
    })

    const aplicarEstadisticas = (datos) => {
      estadisticas.periodo =
        datos?.periodo ?? crearPeriodoInicial()

      estadisticas.metricas =
        datos?.metricas ?? crearMetricasIniciales()

      estadisticas.categorias =
        Array.isArray(datos?.categorias)
          ? datos.categorias
          : []

      estadisticas.series =
        Array.isArray(datos?.series)
          ? datos.series
          : []
    }

    const notificarError = (error) => {
      console.error(
        'Error al cargar estadísticas de sucursal:',
        error,
      )

      $q.notify({
        type: 'negative',
        message:
          error.response?.data?.message ||
          'No se pudieron cargar las estadísticas.',
        position: 'top',
      })
    }

    const cargarEstadisticas = async () => {
      if (
        !Number.isInteger(idSucursal.value) ||
        idSucursal.value <= 0
      ) {
        $q.notify({
          type: 'negative',
          message: 'La sucursal seleccionada no es válida.',
          position: 'top',
        })

        return
      }

      if (!filtrosValidos.value) {
        $q.notify({
          type: 'warning',
          message: 'Selecciona un rango de fechas válido.',
          position: 'top',
        })

        return
      }

      cargando.value = true

      try {
        const datos =
          await estadisticasService.obtenerEstadisticasSucursal(
            idSucursal.value,
            filtros,
          )

        aplicarEstadisticas(datos)
      } catch (error) {
        notificarError(error)
      } finally {
        cargando.value = false
      }
    }

    const manejarCambioPeriodo = () => {
      if (filtros.periodo === 'personalizado') {
        return
      }

      filtros.fechaDesde = ''
      filtros.fechaHasta = ''

      cargarEstadisticas()
    }

    const volverDashboard = () => {
      router.push('/superadmin/dashboard')
    }

    watch(
      () => route.params.id,
      async (nuevoId, idAnterior) => {
        if (nuevoId === idAnterior) {
          return
        }

        await cargarEstadisticas()
      },
    )

    onMounted(async () => {
      await cargarEstadisticas()
    })

    return {
      cargando,
      filtros,
      estadisticas,
      opcionesPeriodo,
      nombreSucursal,
      filtrosValidos,
      subtituloGrafico,
      cargarEstadisticas,
      manejarCambioPeriodo,
      volverDashboard,
    }
  },
})