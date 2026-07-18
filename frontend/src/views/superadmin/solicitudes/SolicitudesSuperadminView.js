import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue'

import { useQuasar } from 'quasar'
import api from '@/api/axios'

export default defineComponent({
  name: 'SolicitudesSuperadminView',

  setup() {
    const q = useQuasar()

    const cargando = ref(false)
    const cargandoDetalle = ref(false)

    const solicitudes = ref([])
    const sucursales = ref([])

    const mostrarDetalle = ref(false)
    const solicitudSeleccionada = ref(null)

    const filtros = ref({
      buscar: '',
      id_sucursal: null,
      visto: null,
      tipo: null,
    })

    const paginacion = ref({
      current_page: 1,
      last_page: 1,
      total: 0,
    })

    const columnas = [
      {
        name: 'lectura',
        label: 'Lectura',
        field: 'visto',
        align: 'center',
      },
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
        name: 'tipo',
        label: 'Tipo',
        field: 'tipo',
        align: 'left',
      },
      {
        name: 'asunto',
        label: 'Asunto',
        field: 'asunto',
        align: 'left',
      },
      {
        name: 'insumos',
        label: 'Insumos',
        field: 'detalles_inventario',
        align: 'center',
      },
      {
        name: 'solicitante',
        label: 'Solicitante',
        field: 'solicitante',
        align: 'left',
      },
      {
        name: 'acciones',
        label: 'Acciones',
        field: 'acciones',
        align: 'center',
      },
    ]

    const opcionesVisto = [
      {
        label: 'No vistas',
        value: '0',
      },
      {
        label: 'Vistas',
        value: '1',
      },
    ]

    const opcionesTipo = [
      {
        label: 'Reposición de inventario',
        value: 'REPOSICION_INVENTARIO',
      },
      {
        label: 'Creación de recurso',
        value: 'CREACION_RECURSO',
      },
      {
        label: 'Modificación de recurso',
        value: 'MODIFICACION_RECURSO',
      },
      {
        label: 'Otro requerimiento',
        value: 'OTRO_REQUERIMIENTO',
      },
    ]

    const opcionesSucursales = computed(() => {
      return sucursales.value.map((sucursal) => ({
        label: sucursal.nombre,
        value: sucursal.id_sucursal,
      }))
    })

    const cantidadNoVistas = computed(() => {
      return solicitudes.value.filter(
        (solicitud) => !solicitud.visto,
      ).length
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
        error.response?.data?.message
        || 'Ocurrió un error inesperado.'
      )
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

    const formatoCantidad = (valor) => {
      return Number(valor || 0).toFixed(2)
    }

    const mostrarUnidad = (unidad) => {
      const unidades = {
        KG: 'kg',
        L: 'L',
        BALON: 'balón',
        PAQUETE: 'paquete',
        UNIDAD: 'unidad',
      }

      return unidades[
        String(unidad || '').toUpperCase()
      ] || unidad || ''
    }

    const textoTipo = (tipo) => {
      const tipos = {
        REPOSICION_INVENTARIO:
          'Reposición de inventario',
        CREACION_RECURSO:
          'Creación de recurso',
        MODIFICACION_RECURSO:
          'Modificación de recurso',
        OTRO_REQUERIMIENTO:
          'Otro requerimiento',
      }

      return tipos[tipo] || tipo
    }

    const colorTipo = (tipo) => {
      const colores = {
        REPOSICION_INVENTARIO: 'orange',
        CREACION_RECURSO: 'green',
        MODIFICACION_RECURSO: 'primary',
        OTRO_REQUERIMIENTO: 'purple',
      }

      return colores[tipo] || 'grey'
    }

    const cantidadInsumos = (solicitud) => {
      if (
        !Array.isArray(
          solicitud?.detalles_inventario,
        )
      ) {
        return 0
      }

      return solicitud.detalles_inventario.length
    }

    const cargarSucursales = async () => {
      try {
        const response = await api.get('/sucursales')

        sucursales.value = Array.isArray(
          response.data?.sucursales,
        )
          ? response.data.sucursales
          : []
      } catch {
        sucursales.value = []
      }
    }

    const cargarSolicitudes = async (pagina = 1) => {
      cargando.value = true

      try {
        const params = {
          page: pagina,
          per_page: 15,
        }

        if (filtros.value.buscar.trim()) {
          params.buscar = filtros.value.buscar.trim()
        }

        if (filtros.value.id_sucursal) {
          params.id_sucursal =
            filtros.value.id_sucursal
        }

        if (filtros.value.visto !== null) {
          params.visto = filtros.value.visto
        }

        if (filtros.value.tipo) {
          params.tipo = filtros.value.tipo
        }

        const response = await api.get(
          '/superadmin/solicitudes',
          { params },
        )

        const resultado =
          response.data?.solicitudes

        solicitudes.value = Array.isArray(
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
        solicitudes.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        cargando.value = false
      }
    }

    const abrirDetalle = async (solicitud) => {
      cargandoDetalle.value = true
      solicitudSeleccionada.value = solicitud

      try {
        const response = await api.get(
          `/superadmin/solicitudes/${solicitud.id_solicitud}`,
        )

        solicitudSeleccionada.value =
          response.data?.solicitud || solicitud

        mostrarDetalle.value = true

        await cargarSolicitudes(
          paginacion.value.current_page,
        )
      } catch (error) {
        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        cargandoDetalle.value = false
      }
    }

    const limpiarFiltros = async () => {
      filtros.value = {
        buscar: '',
        id_sucursal: null,
        visto: null,
        tipo: null,
      }

      await cargarSolicitudes(1)
    }

    onMounted(async () => {
      await Promise.all([
        cargarSucursales(),
        cargarSolicitudes(),
      ])
    })

    return {
      cargando,
      cargandoDetalle,

      solicitudes,
      filtros,
      paginacion,
      columnas,

      mostrarDetalle,
      solicitudSeleccionada,

      opcionesVisto,
      opcionesTipo,
      opcionesSucursales,
      cantidadNoVistas,

      cargarSolicitudes,
      abrirDetalle,
      limpiarFiltros,

      formatearFecha,
      formatoCantidad,
      mostrarUnidad,
      textoTipo,
      colorTipo,
      cantidadInsumos,
    }
  },
})