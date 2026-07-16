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
    const tiposCarne = ref([])

    const mostrarDialogoApertura = ref(false)

    const formApertura = ref({
      chancho_cruces: null,
      pollo_cruces: null,
      observacion: '',
    })
    const obtenerControlCarneJornada = () => {
      if (!jornadaActual.value) {
        return []
      }

      return (
        jornadaActual.value.control_carne ||
        jornadaActual.value.controlCarne ||
        jornadaActual.value.controles_carne ||
        jornadaActual.value.controlesCarne ||
        []
      )
    }

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

    const baseChancho = computed(() => {
  return Number(formApertura.value.chancho_cruces || 0) * 24
})

    const basePollo = computed(() => {
      return Number(formApertura.value.pollo_cruces || 0) * 2
    })

    const obtenerMensajeError = (error) => {
      const errores = error.response?.data?.errors

      if (errores) {
        const primerError = Object.values(errores)[0]

        if (Array.isArray(primerError)) {
          return primerError[0]
        }
      }

      return error.response?.data?.message || 'Ocurrió un error inesperado.'
    }

    const normalizarNombre = (nombre) => {
      return String(nombre || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .trim()
    }

    const obtenerTipoCarnePorNombre = (nombre) => {
      const nombreBuscado = normalizarNombre(nombre)

      return tiposCarne.value.find((tipo) => {
        return normalizarNombre(tipo.nombre) === nombreBuscado
      })
    }

    const cargarTiposCarne = async () => {
      try {
        const response = await api.get('/tipos-carne')

        tiposCarne.value = Array.isArray(response.data?.tipos_carne)
          ? response.data.tipos_carne
          : []
      } catch (error) {
        tiposCarne.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
          timeout: 3500,
        })
      }
    }

    const cargarJornadaActual = async () => {
      loading.value = true

      try {
        const response = await api.get('/jornadas/actual')
        jornadaActual.value = response.data?.jornada || null
      } catch (error) {
        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
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

        jornadas.value = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
      } catch (error) {
        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })

        jornadas.value = []
      } finally {
        loadingHistorial.value = false
      }
    }

    const limpiarFormularioApertura = () => {
      formApertura.value = {
        chancho_cruces: null,
        pollo_cruces: null,
        observacion: '',
      }
    }

    const abrirDialogoApertura = async () => {
      if (tiposCarne.value.length === 0) {
        await cargarTiposCarne()
      }

      limpiarFormularioApertura()
      mostrarDialogoApertura.value = true
    }

    const cerrarDialogoApertura = () => {
      if (procesando.value) {
        return
      }

      mostrarDialogoApertura.value = false
      limpiarFormularioApertura()
    }

    const validarApertura = () => {
      const chancho = Number(formApertura.value.chancho_cruces || 0)
      const pollo = Number(formApertura.value.pollo_cruces || 0)

      if (tiposCarne.value.length === 0) {
        return 'No se cargaron los tipos de carne.'
      }

      if (!obtenerTipoCarnePorNombre('CHANCHO')) {
        return 'No existe el tipo de carne CHANCHO en la base de datos.'
      }

      if (!obtenerTipoCarnePorNombre('POLLO')) {
        return 'No existe el tipo de carne POLLO en la base de datos.'
      }

      if (chancho <= 0) {
        return 'Debes indicar cuántas cruces de chancho hay para vender.'
      }

      if (pollo <= 0) {
        return 'Debes indicar cuántas cruces de pollo hay para vender.'
      }

      return null
    }

    const construirPayloadApertura = () => {
      const tipoChancho = obtenerTipoCarnePorNombre('CHANCHO')
      const tipoPollo = obtenerTipoCarnePorNombre('POLLO')

      const crucesChancho = Number(formApertura.value.chancho_cruces || 0)
      const crucesPollo = Number(formApertura.value.pollo_cruces || 0)

      const observacionGeneral = formApertura.value.observacion?.trim() || null

      return {
        carnes: [
          {
            id_tipo_carne: tipoChancho.id_tipo_carne,
            cantidad_cruces: crucesChancho,
            cantidad_base_inicial: crucesChancho * 24,
            unidad_base: 'MIN_COSTILLA',
            platos_estimados: crucesChancho * 24,
            observacion: observacionGeneral,
          },
          {
            id_tipo_carne: tipoPollo.id_tipo_carne,
            cantidad_cruces: crucesPollo,

            // 1 cruz de pollo = 2 pollos.
            cantidad_base_inicial: crucesPollo * 2,
            unidad_base: 'POLLO',

            // 1 pollo entero = 2 platos de pollo de 1/2 pollo.
            platos_estimados: crucesPollo * 4,

            observacion: observacionGeneral,
          },
        ],
      }
    }

    const abrirJornada = async () => {
      const errorValidacion = validarApertura()

      if (errorValidacion) {
        q.notify({
          type: 'negative',
          message: errorValidacion,
          position: 'top',
          timeout: 3500,
        })

        return
      }

      procesando.value = true

      try {
        const payload = construirPayloadApertura()
        const response = await api.post('/jornadas/abrir', payload)

        jornadaActual.value = response.data?.jornada || null

        q.notify({
          type: 'positive',
          message: response.data?.message || 'Jornada abierta correctamente.',
          position: 'top',
        })

        mostrarDialogoApertura.value = false
        limpiarFormularioApertura()

        await cargarHistorial()
      } catch (error) {
        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
          timeout: 4000,
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
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        procesando.value = false
      }
    }
    const rangoPlatosChancho = (control) => {
      const nombre = normalizarNombre(
        control?.tipo_carne?.nombre || control?.tipoCarne?.nombre || '',
      )

      if (nombre !== 'CHANCHO') {
        return ''
      }

      const cruces = Number(control?.cantidad_cruces || 0)

      if (cruces <= 0) {
        return ''
      }

      const minimo = Math.round(cruces * 22)
      const maximo = Math.round(cruces * 26)

      return `${minimo} a ${maximo} platos aprox.`
    }

    const confirmarAbrirJornada = () => {
      const errorValidacion = validarApertura()

      if (errorValidacion) {
        q.notify({
          type: 'negative',
          message: errorValidacion,
          position: 'top',
          timeout: 3500,
        })

        return
      }

      q.dialog({
        title: 'Confirmar apertura',
        message:
          `Se abrirá la jornada con ${formatoCantidad(formApertura.value.chancho_cruces)} cruz/ces de chancho ` +
          `y ${formatoCantidad(formApertura.value.pollo_cruces)} cruz/ces de pollo.`,
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
        message:
          '¿Deseas cerrar la jornada actual? Después no se podrá abrir otra jornada para la misma fecha.',
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

    const formatoCantidad = (cantidad) => {
      const numero = Number(cantidad || 0)

      if (Number.isInteger(numero)) {
        return String(numero)
      }

      return numero.toFixed(2)
    }

    onMounted(async () => {
      await Promise.all([cargarTiposCarne(), cargarJornadaActual(), cargarHistorial()])
    })

    const nombreTipoCarne = (control) => {
      return control?.tipo_carne?.nombre || control?.tipoCarne?.nombre || 'Carne'
    }

    const unidadBaseCarne = (control) => {
      const unidad = String(control?.unidad_base || '').toUpperCase()

      if (unidad === 'MIN_COSTILLA') {
        return 'MinCostillas'
      }

      if (unidad === 'COSTILLA_GRANDE') {
        return 'CostillasGrandes'
      }

      if (unidad === 'POLLO') {
        return 'Pollos'
      }

      return unidad || ''
    }
    const esChanchoControl = (control) => {
      const nombre = normalizarNombre(
        control?.tipo_carne?.nombre || control?.tipoCarne?.nombre || '',
      )

      return nombre === 'CHANCHO'
    }

    const costillasGrandesChancho = (control) => {
      if (!esChanchoControl(control)) {
        return ''
      }

      const cruces = Number(control?.cantidad_cruces || 0)

      return cruces * 2
    }

    const rangoMinCostillasChancho = (control) => {
      if (!esChanchoControl(control)) {
        return ''
      }

      const costillasGrandes = costillasGrandesChancho(control)

      const minimo = costillasGrandes * 11
      const maximo = costillasGrandes * 13

      return `${minimo} a ${maximo} MinCostillas aprox.`
    }

    const porcentajeRestanteCarne = (control) => {
      const inicial = Number(control?.cantidad_base_inicial || 0)
      const actual = Number(control?.cantidad_base_actual || 0)

      if (inicial <= 0) {
        return 0
      }

      return Math.max(0, Math.min(100, (actual / inicial) * 100))
    }

    return {
      q,
      authStore,

      loading,
      loadingHistorial,
      procesando,

      jornadaActual,
      jornadas,
      tiposCarne,

      mostrarDialogoApertura,
      formApertura,

      columns,
      textoJornadaActual,
      baseChancho,
      basePollo,

      cargarTiposCarne,
      cargarJornadaActual,
      cargarHistorial,

      abrirDialogoApertura,
      cerrarDialogoApertura,
      abrirJornada,
      cerrarJornada,
      confirmarAbrirJornada,
      confirmarCerrarJornada,

      formatearFecha,
      formatoCantidad,

      obtenerControlCarneJornada,
      nombreTipoCarne,
      unidadBaseCarne,
      porcentajeRestanteCarne,

      rangoPlatosChancho,
      esChanchoControl,
      costillasGrandesChancho,
      rangoMinCostillasChancho,
    }
  },
})
