import {
  defineComponent,
  onMounted,
  ref,
} from 'vue'

import { useQuasar } from 'quasar'
import api from '@/api/axios'

export default defineComponent({
  name: 'ReportesSucursalesView',

  setup() {
    const q = useQuasar()

    const cargando = ref(false)
    const sucursales = ref([])
    const reportes = ref([])

    const mostrarDetalle = ref(false)
    const reporteSeleccionado = ref(null)

    const filtros = ref({
      id_sucursal: null,
      fecha_desde: '',
      fecha_hasta: '',
    })

    const paginacion = ref({
      current_page: 1,
      last_page: 1,
    })

    const columnas = [
      {
        name: 'fecha',
        label: 'Fecha',
        field: 'fecha',
        align: 'left',
      },
      {
        name: 'sucursal',
        label: 'Sucursal',
        field: 'sucursal',
        align: 'left',
      },
      {
        name: 'ventas',
        label: 'Ventas',
        field: 'total_ventas',
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

    const opcionesSucursales = () => {
      return sucursales.value.map((sucursal) => ({
        label: sucursal.nombre,
        value: sucursal.id_sucursal,
      }))
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

    const obtenerMensajeError = (error) => {
      return (
        error.response?.data?.message ||
        'Ocurrió un error inesperado.'
      )
    }

    const cargarSucursales = async () => {
      const response = await api.get('/sucursales')

      sucursales.value = Array.isArray(
        response.data?.sucursales,
      )
        ? response.data.sucursales
        : []
    }

    const cargarReportes = async (pagina = 1) => {
      cargando.value = true

      try {
        const params = {
          page: pagina,
          per_page: 15,
        }

        if (filtros.value.id_sucursal) {
          params.id_sucursal =
            filtros.value.id_sucursal
        }

        if (filtros.value.fecha_desde) {
          params.fecha_desde =
            filtros.value.fecha_desde
        }

        if (filtros.value.fecha_hasta) {
          params.fecha_hasta =
            filtros.value.fecha_hasta
        }

        const response = await api.get(
          '/superadmin/reportes-jornada',
          { params },
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
        }
      } catch (error) {
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
          `/superadmin/reportes-jornada/${reporte.id_reporte}`,
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

    onMounted(async () => {
      await Promise.all([
        cargarSucursales(),
        cargarReportes(),
      ])
    })

    return {
      cargando,
      sucursales,
      reportes,
      filtros,
      paginacion,
      columnas,
      mostrarDetalle,
      reporteSeleccionado,

      opcionesSucursales,
      cargarReportes,
      abrirDetalle,

      formatoDinero,
      formatearFecha,
      colorDiferencia,
    }
  },
})