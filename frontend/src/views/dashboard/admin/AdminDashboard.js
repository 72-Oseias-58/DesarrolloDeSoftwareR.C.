import { computed, defineComponent, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import VueApexCharts from 'vue3-apexcharts'
import { useDashboardActions } from '@/composables/useDashboardActions'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

export default defineComponent({
  name: 'AdminDashboard',

  components: {
    ApexChart: VueApexCharts,
  },

  setup() {
    const router = useRouter()
    const authStore = useAuthStore()

    const {
      $q: q,
      cambiarModoOscuro,
      cerrarSesion,
    } = useDashboardActions()

    const loadingEstadisticas = ref(false)

    const periodo = ref('hoy')
    const fechaDesde = ref('')
    const fechaHasta = ref('')

    const periodos = [
      { label: 'Hoy', value: 'hoy' },
      { label: 'Semana', value: 'semana' },
      { label: 'Mes', value: 'mes' },
      { label: 'Año', value: 'anio' },
      { label: 'Personalizado', value: 'personalizado' },
    ]

    const metricas = ref({
      total_ventas: 0,
      cantidad_pedidos: 0,
      ticket_promedio: 0,
      total_efectivo: 0,
      total_qr: 0,
    })

    const categorias = ref([])
    const series = ref([])

    const modulos = [
      {
        label: 'Empleados',
        descripcion: 'Registrar, editar y administrar cajeros y personal de la sucursal.',
        icon: 'groups',
        color: 'secondary',
        to: '/admin/empleados',
        permiso: 'ver_empleados',
      },
      {
        label: 'Inventario',
        descripcion: 'Controlar insumos, productos, stock y alertas de disponibilidad.',
        icon: 'inventory_2',
        color: 'primary',
        to: '/admin/inventario',
        permiso: 'ver_inventario',
      },
      {
        label: 'Jornadas',
        descripcion: 'Abrir, revisar y cerrar la jornada diaria de la sucursal.',
        icon: 'event_available',
        color: 'green',
        to: '/admin/jornadas',
        permiso: 'ver_jornadas',
      },
      {
        label: 'Cajas',
        descripcion: 'Supervisar cajas, cajeros asignados, montos y diferencias.',
        icon: 'point_of_sale',
        color: 'orange',
        to: '/admin/cajas',
        permiso: 'ver_cajas',
      },
      {
        label: 'Reportes',
        descripcion: 'Generar reportes de ventas, stock, movimientos y actividad.',
        icon: 'bar_chart',
        color: 'accent',
        to: '/admin/reportes',
        permiso: 'crear_reportes',
      },
      {
        label: 'Solicitudes',
        descripcion: 'Crear solicitudes administrativas, técnicas o de inventario.',
        icon: 'outgoing_mail',
        color: 'purple',
        to: '/admin/solicitudes',
        permiso: 'crear_solicitudes',
      },
    ]

    const modulosFiltrados = computed(() => {
      return modulos.filter((modulo) => {
        if (!modulo.permiso) return true
        return authStore.tienePermiso(modulo.permiso)
      })
    })

    const chartSeries = computed(() => {
      if (!Array.isArray(series.value) || series.value.length === 0) {
        return [
          {
            name: 'Ventas',
            data: [],
          },
        ]
      }

      return series.value.map((serie) => ({
        name: serie.name || serie.nombre || 'Ventas',
        data: Array.isArray(serie.data) ? serie.data : [],
      }))
    })

    const chartOptions = computed(() => ({
      chart: {
        toolbar: {
          show: true,
        },
        zoom: {
          enabled: true,
        },
      },
      stroke: {
        curve: 'smooth',
        width: 3,
      },
      dataLabels: {
        enabled: false,
      },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.35,
          opacityTo: 0.05,
          stops: [0, 90, 100],
        },
      },
      xaxis: {
        categories: categorias.value,
        labels: {
          rotate: -35,
        },
      },
      yaxis: {
        labels: {
          formatter(value) {
            return `Bs ${Number(value || 0).toFixed(2)}`
          },
        },
      },
      tooltip: {
        y: {
          formatter(value) {
            return `Bs ${Number(value || 0).toFixed(2)}`
          },
        },
      },
      legend: {
        position: 'top',
      },
      noData: {
        text: 'Sin datos de ventas',
      },
    }))

    const sinVentas = computed(() => {
      const total = Number(metricas.value.total_ventas || 0)

      if (total > 0) return false

      return chartSeries.value.every((serie) => {
        return serie.data.every((valor) => Number(valor || 0) === 0)
      })
    })

    const formatoDinero = (valor) => {
      return Number(valor || 0).toFixed(2)
    }

    const normalizarMetricas = (data) => {
      const origen = data?.metricas || data?.data?.metricas || {}

      return {
        total_ventas: Number(origen.total_ventas || origen.ventas_total || 0),
        cantidad_pedidos: Number(origen.cantidad_pedidos || origen.pedidos_pagados || 0),
        ticket_promedio: Number(origen.ticket_promedio || 0),
        total_efectivo: Number(origen.total_efectivo || origen.efectivo || 0),
        total_qr: Number(origen.total_qr || origen.qr || 0),
      }
    }

    const cargarEstadisticas = async () => {
      loadingEstadisticas.value = true

      try {
        const params = {
          periodo: periodo.value,
        }

        if (periodo.value === 'personalizado') {
          params.fecha_desde = fechaDesde.value
          params.fecha_hasta = fechaHasta.value
        }

        const response = await api.get('/admin/estadisticas/ventas', {
          params,
        })

        const data = response.data || {}

        metricas.value = normalizarMetricas(data)
        categorias.value = data.categorias || data.data?.categorias || []
        series.value = data.series || data.data?.series || []
      } catch (error) {
        q.notify({
          type: 'negative',
          message: error.response?.data?.message || 'Error al cargar estadísticas del ADMIN.',
          position: 'top',
        })

        metricas.value = {
          total_ventas: 0,
          cantidad_pedidos: 0,
          ticket_promedio: 0,
          total_efectivo: 0,
          total_qr: 0,
        }

        categorias.value = []
        series.value = []
      } finally {
        loadingEstadisticas.value = false
      }
    }

    const irA = (ruta) => {
      router.push(ruta)
    }

    onMounted(() => {
      cargarEstadisticas()
    })

    return {
      q,
      authStore,
      loadingEstadisticas,
      periodo,
      fechaDesde,
      fechaHasta,
      periodos,
      metricas,
      categorias,
      series,
      modulosFiltrados,
      chartSeries,
      chartOptions,
      sinVentas,
      formatoDinero,
      cargarEstadisticas,
      irA,
      cambiarModoOscuro,
      cerrarSesion,
    }
  },
})