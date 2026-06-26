import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue'
import { useQuasar } from 'quasar'
import api from '@/api/axios'

export default defineComponent({
  name: 'NuevoPedidoView',

  setup() {
    const $q = useQuasar()

    const productos = ref([])
    const detalles = ref([])
    const tipoConsumo = ref('PARA_LLEVAR')
    const cargandoProductos = ref(false)
    const registrandoPedido = ref(false)
    const errorProductos = ref('')

    let siguienteUid = 1

    const tiposConsumo = [
      {
        label: 'Para llevar',
        value: 'PARA_LLEVAR',
      },
      {
        label: 'Consumo en el local',
        value: 'EN_LOCAL',
      },
    ]

    const opcionesPreparacion = [
      {
        label: 'Mixto completo',
        value: 'MIXTO',
      },
      {
        label: 'Arroz completo',
        value: 'ARROZ',
      },
      {
        label: 'Mote completo',
        value: 'MOTE',
      },
      {
        label: 'Personalizado',
        value: 'PERSONALIZADO',
      },
    ]

    const cantidadProductos = computed(() => {
      return detalles.value.reduce(
        (total, detalle) => total + detalle.cantidad,
        0,
      )
    })

    const totalPedido = computed(() => {
      return detalles.value.reduce(
        (total, detalle) => total + subtotalDetalle(detalle),
        0,
      )
    })

    const normalizarNombre = (nombre) => {
      return String(nombre || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .trim()
    }

    const tieneGuarniciones = (producto) => {
      return Array.isArray(producto.guarniciones) &&
        producto.guarniciones.length > 0
    }

    const obtenerIdsPreparacion = (producto, tipo) => {
      const guarniciones = producto.guarniciones || []

      if (tipo === 'ARROZ') {
        return guarniciones
          .filter(
            (guarnicion) =>
              normalizarNombre(guarnicion.nombre) !== 'MOTE',
          )
          .map((guarnicion) => guarnicion.id_guarnicion)
      }

      if (tipo === 'MOTE') {
        return guarniciones
          .filter(
            (guarnicion) =>
              normalizarNombre(guarnicion.nombre) !== 'ARROZ',
          )
          .map((guarnicion) => guarnicion.id_guarnicion)
      }

      return guarniciones.map(
        (guarnicion) => guarnicion.id_guarnicion,
      )
    }

    const aplicarPreparacion = (detalle) => {
      if (detalle.tipoPreparacion === 'PERSONALIZADO') {
        return
      }

      detalle.guarniciones = obtenerIdsPreparacion(
        detalle.producto,
        detalle.tipoPreparacion,
      )
    }

    const crearDetalle = (producto) => {
      const detalle = {
        uid: siguienteUid++,
        producto,
        cantidad: 1,
        tipoPreparacion: tieneGuarniciones(producto)
          ? 'MIXTO'
          : null,
        guarniciones: [],
        observacion: '',
      }

      if (tieneGuarniciones(producto)) {
        aplicarPreparacion(detalle)
      }

      return detalle
    }

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

    const cargarProductos = async () => {
      cargandoProductos.value = true
      errorProductos.value = ''

      try {
        const response = await api.get('/productos-venta')

        productos.value = Array.isArray(response.data?.productos)
          ? response.data.productos
          : []
      } catch (error) {
        errorProductos.value = obtenerMensajeError(error)
        productos.value = []
      } finally {
        cargandoProductos.value = false
      }
    }

    const agregarProducto = (producto) => {
      detalles.value.push(crearDetalle(producto))

      $q.notify({
        type: 'positive',
        message: `${producto.nombre} agregado.`,
        position: 'top',
        timeout: 1200,
      })
    }

    const duplicarCombinacion = (detalleOriginal) => {
      const nuevoDetalle = crearDetalle(detalleOriginal.producto)

      nuevoDetalle.tipoPreparacion = 'PERSONALIZADO'
      nuevoDetalle.guarniciones = [
        ...detalleOriginal.guarniciones,
      ]

      detalles.value.push(nuevoDetalle)

      $q.notify({
        type: 'info',
        message: 'Nueva combinación agregada.',
        position: 'top',
        timeout: 1400,
      })
    }

    const eliminarDetalle = (indice) => {
      detalles.value.splice(indice, 1)
    }

    const aumentarCantidad = (detalle) => {
      if (detalle.cantidad < 100) {
        detalle.cantidad++
      }
    }

    const disminuirCantidad = (detalle) => {
      if (detalle.cantidad > 1) {
        detalle.cantidad--
      }
    }

    const subtotalDetalle = (detalle) => {
      return (
        Number(detalle.producto.precio) *
        Number(detalle.cantidad)
      )
    }

    const opcionesGuarniciones = (producto) => {
      return (producto.guarniciones || []).map(
        (guarnicion) => ({
          label: guarnicion.nombre,
          value: guarnicion.id_guarnicion,
        }),
      )
    }

    const obtenerGuarnicionesSeleccionadas = (detalle) => {
      return (detalle.producto.guarniciones || []).filter(
        (guarnicion) =>
          detalle.guarniciones.includes(
            guarnicion.id_guarnicion,
          ),
      )
    }

    const iconoProducto = (producto) => {
      return producto.tipo_producto === 'BEBIDA'
        ? 'local_drink'
        : 'restaurant'
    }

    const formatoDinero = (monto) => {
      return Number(monto || 0).toFixed(2)
    }

    const registrarPedido = async () => {
      registrandoPedido.value = true

      try {
        const payload = {
          tipo_consumo: tipoConsumo.value,
          detalles: detalles.value.map((detalle) => ({
            id_producto: detalle.producto.id_producto,
            cantidad: detalle.cantidad,
            observacion:
              detalle.observacion?.trim() || null,
            guarniciones: detalle.guarniciones,
          })),
        }

        const response = await api.post('/pedidos', payload)
        const pedido = response.data?.pedido

        $q.notify({
          type: 'positive',
          message:
            `${pedido?.codigo_pedido || 'Pedido'} registrado ` +
            `por Bs ${formatoDinero(pedido?.total)}.`,
          position: 'top',
          timeout: 3000,
        })

        detalles.value = []
        tipoConsumo.value = 'PARA_LLEVAR'
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
          timeout: 4000,
        })
      } finally {
        registrandoPedido.value = false
      }
    }

    const confirmarPedido = () => {
      if (detalles.value.length === 0) {
        return
      }

      $q.dialog({
        title: 'Confirmar pedido',
        message:
          `Se registrarán ${cantidadProductos.value} productos ` +
          `por Bs ${formatoDinero(totalPedido.value)}.`,
        cancel: {
          label: 'Cancelar',
          flat: true,
        },
        ok: {
          label: 'Registrar',
          color: 'primary',
          unelevated: true,
        },
        persistent: true,
      }).onOk(registrarPedido)
    }

    onMounted(cargarProductos)

    return {
      productos,
      detalles,
      tipoConsumo,
      tiposConsumo,
      opcionesPreparacion,
      cargandoProductos,
      registrandoPedido,
      errorProductos,
      cantidadProductos,
      totalPedido,
      cargarProductos,
      agregarProducto,
      duplicarCombinacion,
      eliminarDetalle,
      aumentarCantidad,
      disminuirCantidad,
      subtotalDetalle,
      aplicarPreparacion,
      opcionesGuarniciones,
      obtenerGuarnicionesSeleccionadas,
      tieneGuarniciones,
      iconoProducto,
      formatoDinero,
      confirmarPedido,
    }
  },
})