import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue'

import { useQuasar } from 'quasar'
import api from '@/api/axios'

export default defineComponent({
  name: 'ReportesView',

  setup() {
    const q = useQuasar()

    const cargando = ref(false)
    const reporteSeleccionado = ref(null)
    const mostrarDetalle = ref(false)

    const reportes = ref([])

    const filtros = ref({
      fecha_desde: '',
      fecha_hasta: '',
    })

    const paginacion = ref({
      current_page: 1,
      last_page: 1,
      total: 0,
    })

    const columnas = [
      {
        name: 'fecha',
        label: 'Fecha',
        field: 'fecha',
        align: 'left',
      },
      {
        name: 'ventas',
        label: 'Ventas',
        field: 'total_ventas',
        align: 'right',
      },
      {
        name: 'efectivo',
        label: 'Efectivo',
        field: 'total_efectivo',
        align: 'right',
      },
      {
        name: 'qr',
        label: 'QR',
        field: 'total_qr',
        align: 'right',
      },
      {
        name: 'gastos',
        label: 'Gastos',
        field: 'total_gastos_reales',
        align: 'right',
      },
      {
        name: 'resultado',
        label: 'Resultado',
        field: 'resultado_operativo',
        align: 'right',
      },
      {
        name: 'diferencia',
        label: 'Diferencia',
        field: 'diferencia_total',
        align: 'right',
      },
      {
        name: 'acciones',
        label: 'Acciones',
        field: 'acciones',
        align: 'center',
      },
    ]

    const totalVentas = computed(() => {
      return reportes.value.reduce(
        (total, reporte) =>
          total + Number(reporte.total_ventas || 0),
        0,
      )
    })

    const totalGastos = computed(() => {
      return reportes.value.reduce(
        (total, reporte) =>
          total +
          Number(reporte.total_gastos_reales || 0),
        0,
      )
    })

    const totalResultado = computed(() => {
      return reportes.value.reduce(
        (total, reporte) =>
          total +
          Number(reporte.resultado_operativo || 0),
        0,
      )
    })

    const diferenciaGeneral = computed(() => {
      return reportes.value.reduce(
        (total, reporte) =>
          total + Number(reporte.diferencia_total || 0),
        0,
      )
    })

    const obtenerMensajeError = (error) => {
      const errores = error.response?.data?.errors

      if (errores) {
        const primerError = Object.values(errores)[0]

        if (Array.isArray(primerError)) {
          return primerError[0]
        }
      }

      return (
        error.response?.data?.message ||
        'Ocurrió un error inesperado.'
      )
    }

    const construirParametros = (pagina = 1) => {
      const params = {
        page: pagina,
        per_page: 15,
      }

      if (filtros.value.fecha_desde) {
        params.fecha_desde =
          filtros.value.fecha_desde
      }

      if (filtros.value.fecha_hasta) {
        params.fecha_hasta =
          filtros.value.fecha_hasta
      }

      return params
    }

    const cargarReportes = async (pagina = 1) => {
      cargando.value = true

      try {
        const response = await api.get(
          '/admin/reportes-jornada',
          {
            params: construirParametros(pagina),
          },
        )

        const resultado =
          response.data?.reportes

        reportes.value = Array.isArray(
          resultado?.data,
        )
          ? resultado.data
          : []

        paginacion.value = {
          current_page: Number(
            resultado?.current_page || 1,
          ),
          last_page: Number(
            resultado?.last_page || 1,
          ),
          total: Number(resultado?.total || 0),
        }
      } catch (error) {
        reportes.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        cargando.value = false
      }
    }

    const abrirDetalle = async (reporte) => {
      try {
        const response = await api.get(
          `/admin/reportes-jornada/${reporte.id_reporte}`,
        )

        reporteSeleccionado.value =
          response.data?.reporte || reporte

        mostrarDetalle.value = true
      } catch (error) {
        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      }
    }

    const limpiarFiltros = async () => {
      filtros.value = {
        fecha_desde: '',
        fecha_hasta: '',
      }

      await cargarReportes(1)
    }

    const formatoDinero = (valor) => {
      return Number(valor || 0).toFixed(2)
    }

    const formatearFecha = (fecha) => {
      if (!fecha) {
        return 'No registrada'
      }

      return new Intl.DateTimeFormat('es-BO', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'America/La_Paz',
      }).format(new Date(fecha))
    }

    const colorDiferencia = (valor) => {
      const diferencia = Number(valor || 0)

      if (diferencia < -0.009) {
        return 'negative'
      }

      if (diferencia > 0.009) {
        return 'orange'
      }

      return 'positive'
    }

    const textoDiferencia = (valor) => {
      const diferencia = Number(valor || 0)

      if (diferencia < -0.009) {
        return 'FALTANTE'
      }

      if (diferencia > 0.009) {
        return 'SOBRANTE'
      }

      return 'CUADRA'
    }

    onMounted(async () => {
      await cargarReportes()
    })

    return {
      cargando,
      reportes,
      reporteSeleccionado,
      mostrarDetalle,
      filtros,
      paginacion,
      columnas,

      totalVentas,
      totalGastos,
      totalResultado,
      diferenciaGeneral,

      cargarReportes,
      abrirDetalle,
      limpiarFiltros,

      formatoDinero,
      formatearFecha,
      colorDiferencia,
      textoDiferencia,
    }
  },
})