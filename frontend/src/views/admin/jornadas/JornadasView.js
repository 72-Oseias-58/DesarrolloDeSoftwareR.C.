import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue'

import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'

import jornadaService from './jornadaService'
import { useCierreJornada } from './useCierreJornada'

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
      {
        name: 'reporte',
        label: 'Reporte',
        field: 'reporte',
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
      return (
        Number(
          formApertura.value.chancho_cruces || 0,
        ) * 24
      )
    })

    const basePollo = computed(() => {
      return (
        Number(
          formApertura.value.pollo_cruces || 0,
        ) * 2
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

    const normalizarNombre = (nombre) => {
      return String(nombre || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .trim()
    }

    const formatearFecha = (fecha) => {
      if (!fecha) {
        return 'No registrada'
      }

      return String(fecha).slice(0, 10)
    }

    const formatoCantidad = (cantidad) => {
      const numero = Number(cantidad || 0)

      if (Number.isInteger(numero)) {
        return String(numero)
      }

      return numero.toFixed(2)
    }

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

    const obtenerTipoCarnePorNombre = (nombre) => {
      const buscado = normalizarNombre(nombre)

      return tiposCarne.value.find((tipo) => {
        return (
          normalizarNombre(tipo.nombre) === buscado
        )
      })
    }

    const cargarTiposCarne = async () => {
      try {
        const data =
          await jornadaService.obtenerTiposCarne()

        tiposCarne.value = Array.isArray(
          data?.tipos_carne,
        )
          ? data.tipos_carne
          : []
      } catch (error) {
        tiposCarne.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      }
    }

    const cargarJornadaActual = async () => {
      loading.value = true

      try {
        const data =
          await jornadaService.obtenerActual()

        jornadaActual.value =
          data?.jornada || null
      } catch (error) {
        jornadaActual.value = null

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        loading.value = false
      }
    }

    const cargarHistorial = async () => {
      loadingHistorial.value = true

      try {
        const data =
          await jornadaService.obtenerHistorial()

        const resultado = data?.jornadas

        jornadas.value = Array.isArray(
          resultado?.data,
        )
          ? resultado.data
          : Array.isArray(resultado)
            ? resultado
            : []
      } catch (error) {
        jornadas.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
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
      const chancho = Number(
        formApertura.value.chancho_cruces || 0,
      )

      const pollo = Number(
        formApertura.value.pollo_cruces || 0,
      )

      if (!obtenerTipoCarnePorNombre('CHANCHO')) {
        return 'No existe el tipo CHANCHO.'
      }

      if (!obtenerTipoCarnePorNombre('POLLO')) {
        return 'No existe el tipo POLLO.'
      }

      if (chancho <= 0) {
        return 'Debes indicar las cruces de chancho.'
      }

      if (pollo <= 0) {
        return 'Debes indicar las cruces de pollo.'
      }

      return null
    }

    const construirPayloadApertura = () => {
      const tipoChancho =
        obtenerTipoCarnePorNombre('CHANCHO')

      const tipoPollo =
        obtenerTipoCarnePorNombre('POLLO')

      const crucesChancho = Number(
        formApertura.value.chancho_cruces || 0,
      )

      const crucesPollo = Number(
        formApertura.value.pollo_cruces || 0,
      )

      const observacion =
        formApertura.value.observacion?.trim() ||
        null

      return {
        carnes: [
          {
            id_tipo_carne:
              tipoChancho.id_tipo_carne,

            cantidad_cruces:
              crucesChancho,

            cantidad_base_inicial:
              crucesChancho * 24,

            unidad_base:
              'MIN_COSTILLA',

            platos_estimados:
              crucesChancho * 24,

            observacion,
          },
          {
            id_tipo_carne:
              tipoPollo.id_tipo_carne,

            cantidad_cruces:
              crucesPollo,

            cantidad_base_inicial:
              crucesPollo * 2,

            unidad_base:
              'POLLO',

            platos_estimados:
              crucesPollo * 4,

            observacion,
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
        })

        return
      }

      procesando.value = true

      try {
        const data = await jornadaService.abrir(
          construirPayloadApertura(),
        )

        jornadaActual.value =
          data?.jornada || null

        mostrarDialogoApertura.value = false
        limpiarFormularioApertura()

        q.notify({
          type: 'positive',
          message:
            data?.message ||
            'Jornada abierta correctamente.',
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

    const confirmarAbrirJornada = () => {
      const errorValidacion = validarApertura()

      if (errorValidacion) {
        q.notify({
          type: 'negative',
          message: errorValidacion,
          position: 'top',
        })

        return
      }

      q.dialog({
        title: 'Confirmar apertura',

        message:
          `Se abrirá la jornada con ` +
          `${formatoCantidad(
            formApertura.value.chancho_cruces,
          )} cruces de chancho y ` +
          `${formatoCantidad(
            formApertura.value.pollo_cruces,
          )} cruces de pollo.`,

        persistent: true,

        ok: {
          label: 'Abrir',
          color: 'green',
        },

        cancel: {
          label: 'Cancelar',
          flat: true,
        },
      }).onOk(abrirJornada)
    }

    const nombreTipoCarne = (control) => {
      return (
        control?.tipo_carne?.nombre ||
        control?.tipoCarne?.nombre ||
        'Carne'
      )
    }

    const unidadBaseCarne = (control) => {
      const unidad = String(
        control?.unidad_base || '',
      ).toUpperCase()

      if (unidad === 'MIN_COSTILLA') {
        return 'MinCostillas'
      }

      if (unidad === 'COSTILLA_GRANDE') {
        return 'CostillasGrandes'
      }

      if (unidad === 'POLLO') {
        return 'Pollos'
      }

      return unidad
    }

    const esChanchoControl = (control) => {
      return (
        normalizarNombre(
          control?.tipo_carne?.nombre ||
            control?.tipoCarne?.nombre,
        ) === 'CHANCHO'
      )
    }

    const costillasGrandesChancho = (
      control,
    ) => {
      return esChanchoControl(control)
        ? Number(control.cantidad_cruces || 0) * 2
        : 0
    }

    const rangoMinCostillasChancho = (
      control,
    ) => {
      const grandes =
        costillasGrandesChancho(control)

      return (
        `${grandes * 11} a ${grandes * 13} ` +
        `MinCostillas aprox.`
      )
    }

    const rangoPlatosChancho = (control) => {
      if (!esChanchoControl(control)) {
        return ''
      }

      const cruces = Number(
        control.cantidad_cruces || 0,
      )

      return (
        `${Math.round(cruces * 22)} a ` +
        `${Math.round(cruces * 26)} platos aprox.`
      )
    }

    const porcentajeRestanteCarne = (
      control,
    ) => {
      const inicial = Number(
        control?.cantidad_base_inicial || 0,
      )

      const actual = Number(
        control?.cantidad_base_actual || 0,
      )

      if (inicial <= 0) {
        return 0
      }

      return Math.max(
        0,
        Math.min(
          100,
          (actual / inicial) * 100,
        ),
      )
    }

    const cierre = useCierreJornada({
      jornadaActual,
      cargarJornadaActual,
      cargarHistorial,
    })

    onMounted(async () => {
      await Promise.all([
        cargarTiposCarne(),
        cargarJornadaActual(),
        cargarHistorial(),
      ])
    })

    return {
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

      cargarHistorial,

      abrirDialogoApertura,
      cerrarDialogoApertura,
      confirmarAbrirJornada,

      formatearFecha,
      formatoCantidad,

      obtenerControlCarneJornada,
      nombreTipoCarne,
      unidadBaseCarne,
      esChanchoControl,
      costillasGrandesChancho,
      rangoMinCostillasChancho,
      rangoPlatosChancho,
      porcentajeRestanteCarne,

      ...cierre,
    }
  },
})