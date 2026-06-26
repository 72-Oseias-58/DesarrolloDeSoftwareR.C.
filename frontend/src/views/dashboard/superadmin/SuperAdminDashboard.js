import {
  computed,
  defineComponent,
  onMounted,
  reactive,
  ref,
} from 'vue'

import { useQuasar } from 'quasar'
import api from '@/api/axios'
import estadisticasService from '@/services/estadisticasService'
import GraficoVentas from '@/components/estadisticas/GraficoVentas.vue'
import TarjetasMetricas from '@/components/estadisticas/TarjetasMetricas.vue'

export default defineComponent({
  name: 'SuperAdminDashboard',

  components: {
    GraficoVentas,
    TarjetasMetricas,
  },

  setup() {
    const $q = useQuasar()

    const cargando = ref(false)
    const sucursales = ref([])
    const administradores = ref([])

    const filtros = reactive({
      periodo: 'hoy',
      fechaDesde: '',
      fechaHasta: '',
    })

    const estadisticas = reactive({
      periodo: {
        tipo: 'hoy',
        desde: null,
        hasta: null,
        agrupacion: 'hora',
      },

      metricas: {
        total_ventas: 0,
        cantidad_pedidos: 0,
        ticket_promedio: 0,
        total_efectivo: 0,
        total_qr: 0,
      },

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

    const totalSucursales = computed(() => {
      return sucursales.value.length
    })

    const sucursalesActivas = computed(() => {
      return sucursales.value.filter(
        (sucursal) => sucursal.estado === 'ACTIVA',
      ).length
    })

    const totalAdministradores = computed(() => {
      return administradores.value.length
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
      if (!fecha) return ''

      return new Intl.DateTimeFormat('es-BO', {
        dateStyle: 'medium',
      }).format(
        new Date(fecha.replace(' ', 'T')),
      )
    }

    const subtituloGrafico = computed(() => {
      const periodo = estadisticas.periodo

      if (!periodo.desde || !periodo.hasta) {
        return 'Comparación de ventas entre sucursales'
      }

      return `Desde ${formatearFecha(periodo.desde)} hasta ${formatearFecha(periodo.hasta)}`
    })

    const aplicarEstadisticas = (datos) => {
      estadisticas.periodo =
        datos?.periodo ?? estadisticas.periodo

      estadisticas.metricas =
        datos?.metricas ?? {
          total_ventas: 0,
          cantidad_pedidos: 0,
          ticket_promedio: 0,
          total_efectivo: 0,
          total_qr: 0,
        }

      estadisticas.categorias =
        Array.isArray(datos?.categorias)
          ? datos.categorias
          : []

      estadisticas.series =
        Array.isArray(datos?.series)
          ? datos.series
          : []
    }

    const cargarEstadisticas = async () => {
      if (!filtrosValidos.value) {
        $q.notify({
          type: 'warning',
          message:
            'Selecciona un rango de fechas válido.',
          position: 'top',
        })

        return
      }

      cargando.value = true

      try {
        const datos =
          await estadisticasService
            .obtenerEstadisticasGlobales(
              filtros,
            )

        aplicarEstadisticas(datos)
      } catch (error) {
        console.error(
          'Error al cargar estadísticas globales:',
          error,
        )

        $q.notify({
          type: 'negative',
          message:
            error.response?.data?.message ||
            'No se pudieron cargar las estadísticas.',
          position: 'top',
        })
      } finally {
        cargando.value = false
      }
    }

    const manejarCambioPeriodo = () => {
      if (filtros.periodo !== 'personalizado') {
        filtros.fechaDesde = ''
        filtros.fechaHasta = ''

        cargarEstadisticas()
      }
    }

    const cargarResumenSistema = async () => {
      try {
        const [
          respuestaSucursales,
          respuestaAdministradores,
        ] = await Promise.all([
          api.get('/sucursales'),
          api.get('/administradores'),
        ])

        sucursales.value =
          respuestaSucursales.data.sucursales || []

        administradores.value =
          respuestaAdministradores.data
            .administradores || []
      } catch (error) {
        console.error(
          'Error al cargar resumen del dashboard:',
          error,
        )
      }
    }

    onMounted(async () => {
      await Promise.all([
        cargarEstadisticas(),
        cargarResumenSistema(),
      ])
    })

    return {
      cargando,
      sucursales,
      administradores,
      filtros,
      estadisticas,
      opcionesPeriodo,
      totalSucursales,
      sucursalesActivas,
      totalAdministradores,
      filtrosValidos,
      subtituloGrafico,
      manejarCambioPeriodo,
      cargarEstadisticas,
    }
  },
})