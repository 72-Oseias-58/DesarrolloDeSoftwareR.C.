import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue'

import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

export default defineComponent({
  name: 'MovimientosCarneView',

  setup() {
    const q = useQuasar()
    const authStore = useAuthStore()

    const loadingInicial = ref(false)
    const loadingMovimientos = ref(false)
    const guardando = ref(false)
    const mostrarDialogo = ref(false)

    const jornada = ref(null)
    const controlesCarne = ref([])
    const tiposCarne = ref([])
    const movimientos = ref([])

    const paginacion = ref({
      current_page: 1,
      last_page: 1,
      total: 0,
    })

    const filtros = ref({
      id_tipo_carne: null,
      tipo_movimiento: null,
      motivo: null,
    })

    const form = ref({
      id_tipo_carne: null,
      tipo_movimiento: 'ENTRADA',
      motivo: 'TIENDA_FAMILIAR',
      unidad_registrada: null,
      cantidad_registrada: null,
      cantidad_base_real: null,
      observacion: '',
    })

    const columnas = [
      {
        name: 'fecha',
        label: 'Fecha y hora',
        field: 'created_at',
        align: 'left',
      },
      {
        name: 'carne',
        label: 'Carne',
        field: 'tipo_carne',
        align: 'left',
      },
      {
        name: 'tipo_movimiento',
        label: 'Movimiento',
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
        field: 'cantidad_base',
        align: 'left',
      },
      {
        name: 'cambio',
        label: 'Cambio de stock',
        field: 'cantidad_nueva',
        align: 'left',
      },
      {
        name: 'usuario',
        label: 'Registrado por',
        field: 'usuario_creador',
        align: 'left',
      },
      {
        name: 'observacion',
        label: 'Observación',
        field: 'observacion',
        align: 'left',
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

    const opcionesMotivoRegistro = [
      {
        label: 'Tienda familiar',
        value: 'TIENDA_FAMILIAR',
      },
      {
        label: 'Ajuste',
        value: 'AJUSTE',
      },
      {
        label: 'Merma',
        value: 'MERMA',
      },
    ]

    const opcionesMotivoFiltro = [
      {
        label: 'Apertura de jornada',
        value: 'APERTURA',
      },
      {
        label: 'Tienda familiar',
        value: 'TIENDA_FAMILIAR',
      },
      {
        label: 'Venta',
        value: 'VENTA',
      },
      {
        label: 'Anulación de venta',
        value: 'ANULACION_VENTA',
      },
      {
        label: 'Ajuste',
        value: 'AJUSTE',
      },
      {
        label: 'Merma',
        value: 'MERMA',
      },
    ]

    const opcionesTipoCarne = computed(() => {
      return tiposCarne.value.map((tipo) => ({
        label: tipo.nombre,
        value: tipo.id_tipo_carne,
      }))
    })

    const tipoCarneSeleccionado = computed(() => {
      return tiposCarne.value.find(
        (tipo) =>
          Number(tipo.id_tipo_carne) ===
          Number(form.value.id_tipo_carne),
      )
    })

    const nombreTipoSeleccionado = computed(() => {
      return String(
        tipoCarneSeleccionado.value?.nombre || '',
      )
        .toUpperCase()
        .trim()
    })

    const opcionesUnidad = computed(() => {
      if (nombreTipoSeleccionado.value === 'CHANCHO') {
        return [
          {
            label: 'Cruz de chancho',
            value: 'CRUZ_CHANCHO',
          },
          {
            label: 'CostillaGrande',
            value: 'COSTILLA_GRANDE',
          },
          {
            label: 'MinCostilla',
            value: 'MIN_COSTILLA',
          },
        ]
      }

      if (nombreTipoSeleccionado.value === 'POLLO') {
        return [
          {
            label: 'Cruz de pollo',
            value: 'CRUZ_POLLO',
          },
          {
            label: 'Pollo',
            value: 'POLLO',
          },
        ]
      }

      return []
    })

    const mostrarCantidadReal = computed(() => {
      return (
        nombreTipoSeleccionado.value === 'CHANCHO' &&
        ['CRUZ_CHANCHO', 'COSTILLA_GRANDE'].includes(
          form.value.unidad_registrada,
        )
      )
    })

    const requiereObservacion = computed(() => {
      return ['AJUSTE', 'MERMA'].includes(form.value.motivo)
    })

    const textoConversion = computed(() => {
      const cantidad = Number(
        form.value.cantidad_registrada || 0,
      )

      if (!form.value.id_tipo_carne) {
        return 'Selecciona el tipo de carne para calcular la equivalencia.'
      }

      if (!form.value.unidad_registrada || cantidad <= 0) {
        return 'Selecciona la unidad e ingresa una cantidad.'
      }

      if (form.value.cantidad_base_real) {
        return `Cantidad base real: ${formatoCantidad(
          form.value.cantidad_base_real,
        )} MinCostillas.`
      }

      const conversiones = {
        CRUZ_CHANCHO: {
          factor: 24,
          unidad: 'MinCostillas',
        },
        COSTILLA_GRANDE: {
          factor: 12,
          unidad: 'MinCostillas',
        },
        MIN_COSTILLA: {
          factor: 1,
          unidad: 'MinCostillas',
        },
        CRUZ_POLLO: {
          factor: 2,
          unidad: 'pollos',
        },
        POLLO: {
          factor: 1,
          unidad: 'pollos',
        },
      }

      const conversion =
        conversiones[form.value.unidad_registrada]

      if (!conversion) {
        return 'No existe conversión para la unidad seleccionada.'
      }

      return `Equivalencia: ${formatoCantidad(
        cantidad * conversion.factor,
      )} ${conversion.unidad}.`
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

    const cargarTiposCarne = async () => {
      const response = await api.get('/tipos-carne')

      tiposCarne.value = Array.isArray(
        response.data?.tipos_carne,
      )
        ? response.data.tipos_carne
        : []
    }

    const cargarJornada = async () => {
      const response = await api.get('/jornadas/actual')

      jornada.value = response.data?.jornada || null

      controlesCarne.value =
        jornada.value?.control_carne ||
        jornada.value?.controlCarne ||
        []
    }

    const construirParametros = (pagina = 1) => {
      const parametros = {
        page: pagina,
      }

      if (filtros.value.id_tipo_carne) {
        parametros.id_tipo_carne =
          filtros.value.id_tipo_carne
      }

      if (filtros.value.tipo_movimiento) {
        parametros.tipo_movimiento =
          filtros.value.tipo_movimiento
      }

      if (filtros.value.motivo) {
        parametros.motivo = filtros.value.motivo
      }

      return parametros
    }

    const cargarMovimientos = async (pagina = 1) => {
      if (!jornada.value || jornada.value.estado !== 'ABIERTA') {
        movimientos.value = []
        return
      }

      loadingMovimientos.value = true

      try {
        const response = await api.get(
          '/admin/movimientos-carne',
          {
            params: construirParametros(pagina),
          },
        )

        const resultado = response.data?.movimientos

        movimientos.value = Array.isArray(resultado?.data)
          ? resultado.data
          : []

        paginacion.value = {
          current_page: Number(resultado?.current_page || 1),
          last_page: Number(resultado?.last_page || 1),
          total: Number(resultado?.total || 0),
        }
      } catch (error) {
        movimientos.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        loadingMovimientos.value = false
      }
    }

    const cargarDatos = async () => {
      loadingInicial.value = true

      try {
        await cargarTiposCarne()
        await cargarJornada()

        if (jornada.value?.estado === 'ABIERTA') {
          await cargarMovimientos(
            paginacion.value.current_page,
          )
        }
      } catch (error) {
        jornada.value = null
        controlesCarne.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        loadingInicial.value = false
      }
    }

    const limpiarFormulario = () => {
      form.value = {
        id_tipo_carne: null,
        tipo_movimiento: 'ENTRADA',
        motivo: 'TIENDA_FAMILIAR',
        unidad_registrada: null,
        cantidad_registrada: null,
        cantidad_base_real: null,
        observacion: '',
      }
    }

    const abrirDialogoMovimiento = () => {
      limpiarFormulario()
      mostrarDialogo.value = true
    }

    const cerrarDialogoMovimiento = () => {
      if (guardando.value) {
        return
      }

      mostrarDialogo.value = false
      limpiarFormulario()
    }

    const cambiarTipoCarne = () => {
      form.value.unidad_registrada = null
      form.value.cantidad_base_real = null
    }

    const ajustarMovimientoPorMotivo = () => {
      if (form.value.motivo === 'MERMA') {
        form.value.tipo_movimiento = 'SALIDA'
      }
    }

    const validarFormulario = () => {
      if (!form.value.id_tipo_carne) {
        return 'Debe seleccionar el tipo de carne.'
      }

      if (!form.value.tipo_movimiento) {
        return 'Debe seleccionar entrada o salida.'
      }

      if (!form.value.motivo) {
        return 'Debe seleccionar el motivo.'
      }

      if (!form.value.unidad_registrada) {
        return 'Debe seleccionar la unidad.'
      }

      if (
        Number(form.value.cantidad_registrada || 0) <= 0
      ) {
        return 'La cantidad debe ser mayor a cero.'
      }

      if (
        requiereObservacion.value &&
        !String(form.value.observacion || '').trim()
      ) {
        return 'Debe explicar el motivo del ajuste o la merma.'
      }

      return null
    }

    const construirPayload = () => {
      const payload = {
        id_tipo_carne: form.value.id_tipo_carne,
        tipo_movimiento: form.value.tipo_movimiento,
        motivo: form.value.motivo,
        unidad_registrada: form.value.unidad_registrada,
        cantidad_registrada: Number(
          form.value.cantidad_registrada,
        ),
        observacion:
          String(form.value.observacion || '').trim() ||
          null,
      }

      if (
        mostrarCantidadReal.value &&
        Number(form.value.cantidad_base_real || 0) > 0
      ) {
        payload.cantidad_base_real = Number(
          form.value.cantidad_base_real,
        )
      }

      return payload
    }

    const registrarMovimiento = async () => {
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
        const response = await api.post(
          '/admin/movimientos-carne',
          construirPayload(),
        )

        q.notify({
          type: 'positive',
          message:
            response.data?.message ||
            'Movimiento registrado correctamente.',
          position: 'top',
        })

        mostrarDialogo.value = false
        limpiarFormulario()

        await cargarJornada()
        await cargarMovimientos(1)
      } catch (error) {
        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
          timeout: 4000,
        })
      } finally {
        guardando.value = false
      }
    }

    const confirmarRegistro = () => {
      const errorValidacion = validarFormulario()

      if (errorValidacion) {
        q.notify({
          type: 'negative',
          message: errorValidacion,
          position: 'top',
        })

        return
      }

      q.dialog({
        title: 'Confirmar movimiento',
        message:
          `Se registrará una ${form.value.tipo_movimiento} de ` +
          `${formatoCantidad(form.value.cantidad_registrada)} ` +
          `${textoUnidad(form.value.unidad_registrada)}.`,
        cancel: true,
        persistent: true,
        ok: {
          label: 'Registrar',
          color: 'primary',
        },
        cancel: {
          label: 'Cancelar',
          color: 'grey',
          flat: true,
        },
      }).onOk(() => {
        registrarMovimiento()
      })
    }

    const nombreCarne = (control) => {
      return (
        control?.tipo_carne?.nombre ||
        control?.tipoCarne?.nombre ||
        'Carne'
      )
    }

    const esChancho = (control) => {
      return (
        String(nombreCarne(control))
          .toUpperCase()
          .trim() === 'CHANCHO'
      )
    }

    const formatoCantidad = (cantidad) => {
      const numero = Number(cantidad || 0)

      if (Number.isInteger(numero)) {
        return String(numero)
      }

      return numero.toFixed(2)
    }

    const textoUnidad = (unidad) => {
      const unidadNormalizada = String(unidad || '')
        .toUpperCase()
        .trim()

      const textos = {
        CRUZ_CHANCHO: 'cruz/ces de chancho',
        COSTILLA_GRANDE: 'CostillaGrande',
        MIN_COSTILLA: 'MinCostillas',
        CRUZ_POLLO: 'cruz/ces de pollo',
        POLLO: 'pollos',
      }

      return textos[unidadNormalizada] || unidadNormalizada
    }

    const textoMotivo = (motivo) => {
      const textos = {
        APERTURA: 'Apertura de jornada',
        TIENDA_FAMILIAR: 'Tienda familiar',
        VENTA: 'Venta',
        ANULACION_VENTA: 'Anulación de venta',
        AJUSTE: 'Ajuste',
        MERMA: 'Merma',
      }

      return textos[motivo] || motivo
    }

    const formatearFecha = (fecha) => {
      if (!fecha) {
        return 'No registrada'
      }

      return String(fecha).slice(0, 10)
    }

    const formatearFechaHora = (fecha) => {
      if (!fecha) {
        return 'No registrada'
      }

      return new Intl.DateTimeFormat('es-BO', {
        timeZone: 'America/La_Paz',
        dateStyle: 'short',
        timeStyle: 'short',
      }).format(new Date(fecha))
    }

    onMounted(async () => {
      await cargarDatos()
    })

    return {
      authStore,

      loadingInicial,
      loadingMovimientos,
      guardando,
      mostrarDialogo,

      jornada,
      controlesCarne,
      movimientos,
      filtros,
      paginacion,
      form,

      columnas,
      opcionesTipoMovimiento,
      opcionesMotivoRegistro,
      opcionesMotivoFiltro,
      opcionesTipoCarne,
      opcionesUnidad,

      mostrarCantidadReal,
      requiereObservacion,
      textoConversion,

      cargarDatos,
      cargarMovimientos,

      abrirDialogoMovimiento,
      cerrarDialogoMovimiento,
      cambiarTipoCarne,
      ajustarMovimientoPorMotivo,
      confirmarRegistro,

      nombreCarne,
      esChancho,
      formatoCantidad,
      textoUnidad,
      textoMotivo,
      formatearFecha,
      formatearFechaHora,
    }
  },
})