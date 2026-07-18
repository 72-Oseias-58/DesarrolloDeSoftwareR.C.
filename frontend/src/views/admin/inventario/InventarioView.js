import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue'

import { useQuasar } from 'quasar'
import api from '@/api/axios'

export default defineComponent({
  name: 'InventarioView',

  setup() {
    const q = useQuasar()

    const cargando = ref(false)
    const guardando = ref(false)
    const cargandoMovimientos = ref(false)

    const inventarios = ref([])
    const movimientos = ref([])

    const mostrarDialogoNuevo = ref(false)
    const mostrarDialogoMovimiento = ref(false)
    const mostrarDialogoHistorial = ref(false)

    const inventarioSeleccionado = ref(null)

    const filtros = ref({
      buscar: '',
      alerta: null,
      categoria: '',
    })

    const resumen = ref({
      total: 0,
      agotados: 0,
      stock_bajo: 0,
      normales: 0,
    })

    const formNuevo = ref({
      nombre: '',
      unidad_medida: '',
      categoria: '',
      prioridad_stock: '',
      stock_actual: null,
      stock_minimo: null,
      observacion: '',
    })

    const formMovimiento = ref({
      id_insumo: null,
      tipo_movimiento: null,
      motivo: null,
      cantidad: null,
      observacion: '',
    })

    const columnas = [
      {
        name: 'insumo',
        label: 'Insumo',
        field: 'insumo',
        align: 'left',
      },
      {
        name: 'categoria',
        label: 'Categoría',
        field: 'categoria',
        align: 'left',
      },
      {
        name: 'unidad',
        label: 'Unidad',
        field: 'unidad',
        align: 'center',
      },
      {
        name: 'prioridad',
        label: 'Prioridad',
        field: 'prioridad',
        align: 'center',
      },
      {
        name: 'stock_actual',
        label: 'Stock actual',
        field: 'stock_actual',
        align: 'right',
      },
      {
        name: 'stock_minimo',
        label: 'Stock mínimo',
        field: 'stock_minimo',
        align: 'right',
      },
      {
        name: 'estado',
        label: 'Estado',
        field: 'estado',
        align: 'center',
      },
      {
        name: 'acciones',
        label: 'Acciones',
        field: 'acciones',
        align: 'center',
      },
    ]

    const columnasMovimientos = [
      {
        name: 'fecha',
        label: 'Fecha',
        field: 'created_at',
        align: 'left',
      },
      {
        name: 'insumo',
        label: 'Insumo',
        field: 'insumo',
        align: 'left',
      },
      {
        name: 'tipo',
        label: 'Tipo',
        field: 'tipo_movimiento',
        align: 'center',
      },
      {
        name: 'motivo',
        label: 'Motivo',
        field: 'motivo',
        align: 'left',
      },
      {
        name: 'cantidad',
        label: 'Cantidad',
        field: 'cantidad',
        align: 'right',
      },
      {
        name: 'stock',
        label: 'Cambio',
        field: 'stock',
        align: 'right',
      },
      {
        name: 'usuario',
        label: 'Registrado por',
        field: 'usuario',
        align: 'left',
      },
    ]

    const opcionesAlerta = [
      {
        label: 'Agotado',
        value: 'AGOTADO',
      },
      {
        label: 'Stock bajo',
        value: 'STOCK_BAJO',
      },
      {
        label: 'Normal',
        value: 'NORMAL',
      },
    ]

    const opcionesTipoMovimiento = [
      {
        label: 'Entrada',
        value: 'ENTRADA',
      },
      {
        label: 'Salida',
        value: 'SALIDA',
      },
    ]

    const motivosMovimiento = computed(() => {
      if (
        formMovimiento.value.tipo_movimiento
        === 'ENTRADA'
      ) {
        return [
          {
            label: 'Reposición',
            value: 'REPOSICION',
          },
          {
            label: 'Compra',
            value: 'COMPRA',
          },
          {
            label: 'Ajuste positivo',
            value: 'AJUSTE_POSITIVO',
          },
        ]
      }

      if (
        formMovimiento.value.tipo_movimiento
        === 'SALIDA'
      ) {
        return [
          {
            label: 'Cortesía al cliente',
            value: 'CORTESIA_CLIENTE',
          },
          {
            label: 'Consumo del personal',
            value: 'CONSUMO_PERSONAL',
          },
          {
            label: 'Merma',
            value: 'MERMA',
          },
          {
            label: 'Ajuste negativo',
            value: 'AJUSTE_NEGATIVO',
          },
        ]
      }

      return []
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

    const formatoCantidad = (valor) => {
      return Number(valor || 0).toFixed(2)
    }

    const formatearFecha = (fecha) => {
      if (!fecha) {
        return 'No registrada'
      }

      return new Intl.DateTimeFormat('es-BO', {
        dateStyle: 'short',
        timeStyle: 'short',
        timeZone: 'America/La_Paz',
      }).format(new Date(fecha))
    }

    const mostrarUnidad = (unidad) => {
      const unidades = {
        KG: 'kg',
        L: 'L',
        BALON: 'balón',
        PAQUETE: 'paquete',
        UNIDAD: 'unidad',
      }

      const valor = String(unidad || '').toUpperCase()

      return unidades[valor] || unidad || ''
    }

    const obtenerEstado = (inventario) => {
      if (inventario.estado_stock) {
        return inventario.estado_stock
      }

      const actual = Number(
        inventario.stock_actual || 0,
      )

      const minimo = Number(
        inventario.stock_minimo || 0,
      )

      if (actual <= 0) {
        return 'AGOTADO'
      }

      if (actual <= minimo) {
        return 'STOCK_BAJO'
      }

      return 'NORMAL'
    }

    const textoEstado = (inventario) => {
      const estado = obtenerEstado(inventario)

      if (estado === 'STOCK_BAJO') {
        return 'STOCK BAJO'
      }

      return estado
    }

    const colorEstado = (inventario) => {
      const estado = obtenerEstado(inventario)

      if (estado === 'AGOTADO') {
        return 'negative'
      }

      if (estado === 'STOCK_BAJO') {
        return 'orange'
      }

      return 'positive'
    }

    const colorPrioridad = (prioridad) => {
      const valor = String(
        prioridad || '',
      ).toUpperCase()

      if (valor === 'ALTA') {
        return 'negative'
      }

      if (valor === 'MEDIA') {
        return 'orange'
      }

      if (valor === 'BAJA') {
        return 'positive'
      }

      if (valor === 'INVENTARIO') {
        return 'primary'
      }

      return 'grey'
    }

    const colorMovimiento = (tipo) => {
      return tipo === 'ENTRADA'
        ? 'positive'
        : 'negative'
    }

    const limpiarNuevo = () => {
      formNuevo.value = {
        nombre: '',
        unidad_medida: '',
        categoria: '',
        prioridad_stock: '',
        stock_actual: null,
        stock_minimo: null,
        observacion: '',
      }
    }

    const limpiarMovimiento = () => {
      formMovimiento.value = {
        id_insumo: null,
        tipo_movimiento: null,
        motivo: null,
        cantidad: null,
        observacion: '',
      }
    }

    const cargarInventario = async () => {
      cargando.value = true

      try {
        const params = {}

        if (filtros.value.buscar.trim()) {
          params.buscar = filtros.value.buscar.trim()
        }

        if (filtros.value.alerta) {
          params.alerta = filtros.value.alerta
        }

        if (filtros.value.categoria.trim()) {
          params.categoria =
            filtros.value.categoria.trim()
        }

        const response = await api.get(
          '/admin/inventario',
          { params },
        )

        inventarios.value = Array.isArray(
          response.data?.inventarios,
        )
          ? response.data.inventarios
          : []

        resumen.value = {
          total: Number(
            response.data?.resumen?.total || 0,
          ),
          agotados: Number(
            response.data?.resumen?.agotados || 0,
          ),
          stock_bajo: Number(
            response.data?.resumen?.stock_bajo || 0,
          ),
          normales: Number(
            response.data?.resumen?.normales || 0,
          ),
        }
      } catch (error) {
        inventarios.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        cargando.value = false
      }
    }

    const cargarMovimientos = async () => {
      cargandoMovimientos.value = true

      try {
        const response = await api.get(
          '/admin/inventario/movimientos',
          {
            params: {
              per_page: 50,
            },
          },
        )

        const resultado =
          response.data?.movimientos

        movimientos.value = Array.isArray(
          resultado?.data,
        )
          ? resultado.data
          : Array.isArray(resultado)
            ? resultado
            : []
      } catch (error) {
        movimientos.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        cargandoMovimientos.value = false
      }
    }

    const abrirNuevo = () => {
      limpiarNuevo()
      mostrarDialogoNuevo.value = true
    }

    const abrirMovimiento = (inventario) => {
      inventarioSeleccionado.value = inventario

      limpiarMovimiento()

      formMovimiento.value.id_insumo =
        inventario.id_insumo

      mostrarDialogoMovimiento.value = true
    }

    const abrirHistorial = async () => {
      await cargarMovimientos()
      mostrarDialogoHistorial.value = true
    }

    const cambiarTipoMovimiento = () => {
      formMovimiento.value.motivo = null
    }

    const validarFormularioNuevo = () => {
      if (!formNuevo.value.nombre.trim()) {
        return 'Escribe el nombre del insumo.'
      }

      if (!formNuevo.value.unidad_medida.trim()) {
        return 'Escribe la unidad de medida.'
      }

      if (!formNuevo.value.categoria.trim()) {
        return 'Escribe la categoría.'
      }

      if (!formNuevo.value.prioridad_stock.trim()) {
        return 'Escribe la prioridad.'
      }

      if (
        formNuevo.value.stock_actual === null
        || Number(formNuevo.value.stock_actual) < 0
      ) {
        return 'El stock actual no puede ser negativo.'
      }

      if (
        formNuevo.value.stock_minimo === null
        || Number(formNuevo.value.stock_minimo) < 0
      ) {
        return 'El stock mínimo no puede ser negativo.'
      }

      return null
    }

    const guardarNuevo = async () => {
      const errorValidacion =
        validarFormularioNuevo()

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
          nombre:
            formNuevo.value.nombre.trim(),

          unidad_medida:
            formNuevo.value.unidad_medida.trim(),

          categoria:
            formNuevo.value.categoria.trim(),

          prioridad_stock:
            formNuevo.value.prioridad_stock.trim(),

          stock_actual: Number(
            formNuevo.value.stock_actual,
          ),

          stock_minimo: Number(
            formNuevo.value.stock_minimo,
          ),

          observacion:
            formNuevo.value.observacion.trim()
            || null,
        }

        const response = await api.post(
          '/admin/inventario',
          payload,
        )

        q.notify({
          type: 'positive',
          message:
            response.data?.message
            || 'Insumo agregado correctamente.',
          position: 'top',
        })

        mostrarDialogoNuevo.value = false

        await cargarInventario()
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

    const guardarMovimiento = async () => {
      if (
        !formMovimiento.value.tipo_movimiento
        || !formMovimiento.value.motivo
      ) {
        q.notify({
          type: 'negative',
          message:
            'Selecciona el tipo y motivo del movimiento.',
          position: 'top',
        })

        return
      }

      if (
        !formMovimiento.value.cantidad
        || Number(formMovimiento.value.cantidad) <= 0
      ) {
        q.notify({
          type: 'negative',
          message:
            'La cantidad debe ser mayor a cero.',
          position: 'top',
        })

        return
      }

      guardando.value = true

      try {
        const response = await api.post(
          '/admin/inventario/movimientos',
          {
            id_insumo:
              formMovimiento.value.id_insumo,

            tipo_movimiento:
              formMovimiento.value.tipo_movimiento,

            motivo:
              formMovimiento.value.motivo,

            cantidad: Number(
              formMovimiento.value.cantidad,
            ),

            observacion:
              formMovimiento.value.observacion.trim()
              || null,
          },
        )

        q.notify({
          type: 'positive',
          message:
            response.data?.message
            || 'Movimiento registrado.',
          position: 'top',
        })

        mostrarDialogoMovimiento.value = false

        await cargarInventario()
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

    const limpiarFiltros = async () => {
      filtros.value = {
        buscar: '',
        alerta: null,
        categoria: '',
      }

      await cargarInventario()
    }

    onMounted(cargarInventario)

    return {
  cargando,
  guardando,
  cargandoMovimientos,

  inventarios,
  movimientos,

  mostrarDialogoNuevo,
  mostrarDialogoMovimiento,
  mostrarDialogoHistorial,

  inventarioSeleccionado,

  filtros,
  resumen,
  formNuevo,
  formMovimiento,

  columnas,
  columnasMovimientos,
  opcionesAlerta,
  opcionesTipoMovimiento,
  motivosMovimiento,

  cargarInventario,
  cargarMovimientos,

  abrirNuevo,
  abrirMovimiento,
  abrirHistorial,

  cambiarTipoMovimiento,
  guardarNuevo,
  guardarMovimiento,
  limpiarFiltros,

  formatoCantidad,
  formatearFecha,
  mostrarUnidad,
  textoEstado,
  colorEstado,
  colorPrioridad,
  colorMovimiento,
}
  },
})