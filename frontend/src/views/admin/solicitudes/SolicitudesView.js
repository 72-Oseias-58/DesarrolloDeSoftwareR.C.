import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue'

import { useQuasar } from 'quasar'
import api from '@/api/axios'

export default defineComponent({
  name: 'SolicitudesView',

  setup() {
    const q = useQuasar()

    const cargando = ref(false)
    const guardando = ref(false)
    const cargandoOpciones = ref(false)

    const solicitudes = ref([])
    const tipos = ref([])
    const inventarios = ref([])
    const opcionesInventarioFiltradas = ref([])

    const mostrarDialogoNueva = ref(false)
    const mostrarDialogoDetalle = ref(false)

    const solicitudSeleccionada = ref(null)

    const paginacion = ref({
      current_page: 1,
      last_page: 1,
      total: 0,
    })

    const form = ref({
      tipo: null,
      asunto: '',
      descripcion: '',
      inventarios_seleccionados: [],
      detalles_inventario: [],
    })

    const columnas = [
      {
        name: 'fecha',
        label: 'Fecha',
        field: 'fecha',
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
        align: 'left',
      },
      {
        name: 'lectura',
        label: 'Lectura',
        field: 'visto',
        align: 'center',
      },
      {
        name: 'acciones',
        label: 'Acciones',
        field: 'acciones',
        align: 'center',
      },
    ]

    const esReposicion = computed(() => {
      return form.value.tipo === 'REPOSICION_INVENTARIO'
    })

    const opcionesInventario = computed(() => {
      return inventarios.value.map((inventario) => ({
        label:
          `${inventario.nombre} — `
          + `${mostrarUnidad(inventario.unidad_medida)} — `
          + `Stock: ${formatoCantidad(inventario.stock_actual)}`,

        value: inventario.id_insumo,

        id_insumo: inventario.id_insumo,
        nombre: inventario.nombre,
        unidad_medida: inventario.unidad_medida,
        categoria: inventario.categoria,
        stock_actual: Number(inventario.stock_actual || 0),
        stock_minimo: Number(inventario.stock_minimo || 0),
        cantidad_sugerida: Number(
          inventario.cantidad_sugerida || 0,
        ),
        requiere_reposicion:
          Boolean(inventario.requiere_reposicion),
      }))
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

    const formatoCantidad = (valor) => {
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

    const textoTipo = (tipo) => {
      const tiposSolicitud = {
        REPOSICION_INVENTARIO:
          'Reposición de inventario',
        CREACION_RECURSO:
          'Creación de recurso',
        MODIFICACION_RECURSO:
          'Modificación de recurso',
        OTRO_REQUERIMIENTO:
          'Otro requerimiento',
      }

      return tiposSolicitud[tipo] || tipo
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

    const limpiarFormulario = () => {
      form.value = {
        tipo: null,
        asunto: '',
        descripcion: '',
        inventarios_seleccionados: [],
        detalles_inventario: [],
      }
    }

    const cargarSolicitudes = async (pagina = 1) => {
      cargando.value = true

      try {
        const response = await api.get(
          '/admin/solicitudes',
          {
            params: {
              page: pagina,
            },
          },
        )

        const resultado = response.data?.solicitudes

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

    const cargarOpciones = async () => {
      cargandoOpciones.value = true

      try {
        const response = await api.get(
          '/admin/solicitudes/opciones',
        )

        tipos.value = Array.isArray(
          response.data?.tipos,
        )
          ? response.data.tipos
          : []

        inventarios.value = Array.isArray(
          response.data?.inventarios,
        )
          ? response.data.inventarios
          : []

        opcionesInventarioFiltradas.value = [
          ...opcionesInventario.value,
        ]
      } catch (error) {
        tipos.value = []
        inventarios.value = []
        opcionesInventarioFiltradas.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        cargandoOpciones.value = false
      }
    }

    const abrirNueva = async () => {
      limpiarFormulario()
      await cargarOpciones()
      mostrarDialogoNueva.value = true
    }

    const abrirDetalle = (solicitud) => {
      solicitudSeleccionada.value = solicitud
      mostrarDialogoDetalle.value = true
    }

    const cambiarTipo = () => {
      if (!esReposicion.value) {
        form.value.inventarios_seleccionados = []
        form.value.detalles_inventario = []
      }
    }

    const filtrarInsumos = (
      valor,
      actualizar,
    ) => {
      actualizar(() => {
        const texto = String(valor || '')
          .trim()
          .toLowerCase()

        if (!texto) {
          opcionesInventarioFiltradas.value = [
            ...opcionesInventario.value,
          ]

          return
        }

        opcionesInventarioFiltradas.value =
          opcionesInventario.value.filter((opcion) => {
            const contenido = [
              opcion.nombre,
              opcion.categoria,
              opcion.unidad_medida,
            ]
              .join(' ')
              .toLowerCase()

            return contenido.includes(texto)
          })
      })
    }

    const sincronizarDetalles = () => {
      const seleccionados =
        form.value.inventarios_seleccionados || []

      const detallesAnteriores =
        form.value.detalles_inventario || []

      form.value.detalles_inventario =
        seleccionados.map((seleccionado) => {
          const detalleAnterior =
            detallesAnteriores.find(
              (detalle) =>
                detalle.id_insumo
                === seleccionado.id_insumo,
            )

          let cantidadInicial =
            seleccionado.cantidad_sugerida

          if (cantidadInicial <= 0) {
            cantidadInicial = 1
          }

          return {
            id_insumo:
              seleccionado.id_insumo,

            nombre:
              seleccionado.nombre,

            unidad_medida:
              seleccionado.unidad_medida,

            categoria:
              seleccionado.categoria,

            stock_actual:
              seleccionado.stock_actual,

            stock_minimo:
              seleccionado.stock_minimo,

            cantidad_solicitada:
              detalleAnterior
                ? detalleAnterior.cantidad_solicitada
                : cantidadInicial,
          }
        })
    }

    const eliminarDetalle = (idInsumo) => {
      form.value.inventarios_seleccionados =
        form.value.inventarios_seleccionados.filter(
          (item) => item.id_insumo !== idInsumo,
        )

      sincronizarDetalles()
    }

    const validarFormulario = () => {
      if (!form.value.tipo) {
        return 'Selecciona el tipo de solicitud.'
      }

      if (!form.value.asunto.trim()) {
        return 'Escribe el asunto de la solicitud.'
      }

      if (
        esReposicion.value
        && form.value.detalles_inventario.length === 0
      ) {
        return 'Selecciona al menos un insumo.'
      }

      if (esReposicion.value) {
        const cantidadInvalida =
          form.value.detalles_inventario.some(
            (detalle) =>
              Number(detalle.cantidad_solicitada) <= 0,
          )

        if (cantidadInvalida) {
          return 'Todas las cantidades deben ser mayores a cero.'
        }
      }

      return null
    }

    const guardarSolicitud = async () => {
      const errorValidacion = validarFormulario()

      if (errorValidacion) {
        q.notify({
          type: 'negative',
          message: errorValidacion,
          position: 'top',
        })

        return
      }

      guardando.value = true

      try {
        const payload = {
          tipo: form.value.tipo,

          asunto:
            form.value.asunto.trim(),

          descripcion:
            form.value.descripcion.trim() || null,

          detalles_inventario:
            esReposicion.value
              ? form.value.detalles_inventario.map(
                (detalle) => ({
                  id_insumo:
                    detalle.id_insumo,

                  cantidad_solicitada:
                    Number(
                      detalle.cantidad_solicitada,
                    ),
                }),
              )
              : null,
        }

        const response = await api.post(
          '/admin/solicitudes',
          payload,
        )

        q.notify({
          type: 'positive',
          message:
            response.data?.message
            || 'Solicitud enviada correctamente.',
          position: 'top',
        })

        mostrarDialogoNueva.value = false
        await cargarSolicitudes(1)
      } catch (error) {
        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        guardando.value = false
      }
    }

    onMounted(cargarSolicitudes)

    return {
      cargando,
      guardando,
      cargandoOpciones,

      solicitudes,
      tipos,
      inventarios,
      opcionesInventarioFiltradas,

      mostrarDialogoNueva,
      mostrarDialogoDetalle,

      solicitudSeleccionada,
      paginacion,
      form,
      columnas,

      esReposicion,

      cargarSolicitudes,
      abrirNueva,
      abrirDetalle,
      cambiarTipo,
      filtrarInsumos,
      sincronizarDetalles,
      eliminarDetalle,
      guardarSolicitud,

      mostrarUnidad,
      formatoCantidad,
      formatearFecha,
      textoTipo,
      colorTipo,
    }
  },
})