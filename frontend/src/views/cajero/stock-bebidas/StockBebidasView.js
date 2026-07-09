import {
  computed,
  defineComponent,
  onMounted,
  reactive,
  ref,
} from 'vue'
import { useQuasar } from 'quasar'
import api from '@/api/axios'

export default defineComponent({
  name: 'StockBebidasView',

  setup() {
    const $q = useQuasar()

    const bebidas = ref([])
    const movimientos = ref([])
    const cargando = ref(false)
    const guardando = ref(false)
    const error = ref('')

    const form = reactive({
      id_insumo: null,
      tipo_movimiento: 'ENTRADA',
      motivo: 'REPOSICION',
      cantidad: null,
      observacion: '',
    })

    const tiposMovimiento = [
      {
        label: 'Entrada',
        value: 'ENTRADA',
      },
      {
        label: 'Salida',
        value: 'SALIDA',
      },
    ]

    const motivosEntrada = [
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

    const motivosSalida = [
      {
        label: 'Cortesía cliente',
        value: 'CORTESIA_CLIENTE',
      },
      {
        label: 'Consumo personal',
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

    const motivosDisponibles = computed(() => {
      return form.tipo_movimiento === 'ENTRADA'
        ? motivosEntrada
        : motivosSalida
    })

    const opcionesBebidas = computed(() => {
      return bebidas.value.map((bebida) => ({
        label: `${bebida.insumo?.nombre} - Stock: ${formatoCantidad(bebida.stock_actual)}`,
        value: bebida.id_insumo,
      }))
    })

    const obtenerMensajeError = (errorPeticion) => {
      const errores = errorPeticion.response?.data?.errors

      if (errores) {
        const primerError = Object.values(errores)[0]

        if (Array.isArray(primerError)) {
          return primerError[0]
        }
      }

      return (
        errorPeticion.response?.data?.message ||
        'Ocurrió un error inesperado.'
      )
    }

    const cargarBebidas = async () => {
      const response = await api.get('/inventario/bebidas')

      bebidas.value = Array.isArray(response.data?.bebidas)
        ? response.data.bebidas
        : []
    }

    const cargarMovimientos = async () => {
      const response = await api.get('/inventario/movimientos')

      movimientos.value = Array.isArray(response.data?.movimientos)
        ? response.data.movimientos
        : []
    }

    const cargarDatos = async () => {
      cargando.value = true
      error.value = ''

      try {
        await Promise.all([
          cargarBebidas(),
          cargarMovimientos(),
        ])
      } catch (errorPeticion) {
        error.value = obtenerMensajeError(errorPeticion)
      } finally {
        cargando.value = false
      }
    }

    const limpiarFormulario = () => {
      form.id_insumo = null
      form.tipo_movimiento = 'ENTRADA'
      form.motivo = 'REPOSICION'
      form.cantidad = null
      form.observacion = ''
    }

    const validarFormulario = () => {
      if (!form.id_insumo) {
        return 'Selecciona una bebida.'
      }

      if (!form.tipo_movimiento) {
        return 'Selecciona el tipo de movimiento.'
      }

      if (!form.motivo) {
        return 'Selecciona el motivo.'
      }

      if (!form.cantidad || Number(form.cantidad) <= 0) {
        return 'La cantidad debe ser mayor a cero.'
      }

      return null
    }

    const registrarMovimiento = async () => {
      const errorValidacion = validarFormulario()

      if (errorValidacion) {
        $q.notify({
          type: 'negative',
          message: errorValidacion,
          position: 'top',
          timeout: 3000,
        })

        return
      }

      guardando.value = true

      try {
        await api.post('/inventario/movimientos', {
          id_insumo: form.id_insumo,
          tipo_movimiento: form.tipo_movimiento,
          motivo: form.motivo,
          cantidad: Number(form.cantidad),
          observacion: form.observacion?.trim() || null,
        })

        $q.notify({
          type: 'positive',
          message: 'Movimiento registrado correctamente.',
          position: 'top',
          timeout: 2500,
        })

        limpiarFormulario()
        await cargarDatos()
      } catch (errorPeticion) {
        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(errorPeticion),
          position: 'top',
          timeout: 4000,
        })
      } finally {
        guardando.value = false
      }
    }

    const formatoCantidad = (cantidad) => {
      const numero = Number(cantidad || 0)

      if (Number.isInteger(numero)) {
        return String(numero)
      }

      return numero.toFixed(2)
    }

    onMounted(cargarDatos)

    return {
      bebidas,
      movimientos,
      cargando,
      guardando,
      error,
      form,
      tiposMovimiento,
      motivosDisponibles,
      opcionesBebidas,
      cargarDatos,
      registrarMovimiento,
      formatoCantidad,
    }
  },
})