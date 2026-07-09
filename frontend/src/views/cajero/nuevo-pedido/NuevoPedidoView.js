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

    const mostrarDialogoPuraCarne = ref(false)

    const formPuraCarne = ref({
      tipo_carne_manual: 'CHANCHO',
      cantidad_carne_manual: null,
      unidad_carne_manual: 'COSTILLA_HUESO',
      precio_unitario: null,
      observacion: '',
    })

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

    const tiposCarneManual = [
      {
        label: 'Chancho',
        value: 'CHANCHO',
      },
      {
        label: 'Pollo',
        value: 'POLLO',
      },
    ]

    const unidadesCarneManualPorTipo = {
      CHANCHO: [
        {
          label: 'Costilla / hueso',
          value: 'COSTILLA_HUESO',
        },
        {
          label: 'Media costilla',
          value: 'MEDIA_COSTILLA',
        },
        {
          label: 'Costilla entera',
          value: 'COSTILLA_ENTERA',
        },
        {
          label: 'Cruz entera de chancho',
          value: 'CRUZ_CHANCHO',
        },
        {
          label: 'Porción de chancho',
          value: 'PORCION_CHANCHO',
        },
      ],

      POLLO: [
        {
          label: 'Cuarto de pollo',
          value: 'CUARTO_POLLO',
        },
        {
          label: 'Medio pollo',
          value: 'MEDIO_POLLO',
        },
        {
          label: 'Pollo entero',
          value: 'POLLO_ENTERO',
        },
        {
          label: 'Cruz entera de pollo',
          value: 'CRUZ_POLLO',
        },
        {
          label: 'Porción de pollo',
          value: 'PORCION_POLLO',
        },
      ],
    }

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
      return detalles.value.reduce((total, detalle) => {
        if (detalle.esPuraCarne) {
          return total + 1
        }

        return total + Number(detalle.cantidad || 0)
      }, 0)
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

    const esBebida = (producto) => {
      return String(producto?.tipo_producto || '').toUpperCase() === 'BEBIDA'
    }

    const usaInventario = (producto) => {
      const prioridadStock = String(
        producto?.prioridad_stock || '',
      ).toUpperCase()

      return prioridadStock === 'INVENTARIO'
    }

    const usaProduccionDiaria = (producto) => {
      const prioridadStock = String(
        producto?.prioridad_stock || '',
      ).toUpperCase()

      return prioridadStock === 'PRODUCCION_DIARIA' ||
        Boolean(producto?.consume_carne)
    }

    const stockDisponible = (producto) => {
      if (!usaInventario(producto)) {
        return null
      }

      return Number(producto?.stock_actual || 0)
    }

    const estaAgotado = (producto) => {
      if (!usaInventario(producto)) {
        return false
      }

      return Boolean(producto?.agotado) || stockDisponible(producto) <= 0
    }

    const cantidadEnPedidoProducto = (idProducto) => {
      return detalles.value
        .filter((detalle) => {
          if (detalle.esPuraCarne || !detalle.producto) {
            return false
          }

          return Number(detalle.producto.id_producto) === Number(idProducto)
        })
        .reduce(
          (total, detalle) => total + Number(detalle.cantidad || 0),
          0,
        )
    }

    const stockRestanteProducto = (producto) => {
      if (!usaInventario(producto)) {
        return null
      }

      return stockDisponible(producto) -
        cantidadEnPedidoProducto(producto.id_producto)
    }

    const stockRestanteDetalle = (detalle) => {
      if (detalle.esPuraCarne || !detalle.producto) {
        return null
      }

      if (!usaInventario(detalle.producto)) {
        return null
      }

      return stockDisponible(detalle.producto) -
        cantidadEnPedidoProducto(detalle.producto.id_producto)
    }

    const puedeAgregarProducto = (producto) => {
      if (!usaInventario(producto)) {
        return true
      }

      return stockRestanteProducto(producto) > 0
    }

    const puedeAumentarCantidad = (detalle) => {
      if (detalle.esPuraCarne) {
        return false
      }

      if (!usaInventario(detalle.producto)) {
        return detalle.cantidad < 100
      }

      return stockRestanteDetalle(detalle) > 0
    }

    const textoStockProducto = (producto) => {
      if (!usaInventario(producto)) {
        return ''
      }

      const stock = stockDisponible(producto)
      const enPedido = cantidadEnPedidoProducto(producto.id_producto)
      const restante = stock - enPedido

      if (stock <= 0) {
        return 'Agotado'
      }

      if (enPedido > 0) {
        return `Stock: ${formatoCantidad(stock)} | Restante: ${formatoCantidad(restante)}`
      }

      return `Stock: ${formatoCantidad(stock)}`
    }

    const tieneGuarniciones = (producto) => {
      return Array.isArray(producto?.guarniciones) &&
        producto.guarniciones.length > 0
    }

    const obtenerIdsPreparacion = (producto, tipo) => {
      const guarniciones = producto?.guarniciones || []

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
      if (detalle.esPuraCarne) {
        return
      }

      if (detalle.tipoPreparacion === 'PERSONALIZADO') {
        return
      }

      detalle.guarniciones = obtenerIdsPreparacion(
        detalle.producto,
        detalle.tipoPreparacion,
      )
    }

    const opcionesUnidadCarne = (tipoCarne) => {
      const tipo = String(tipoCarne || 'CHANCHO').toUpperCase()

      return unidadesCarneManualPorTipo[tipo] ||
        unidadesCarneManualPorTipo.CHANCHO
    }

    const obtenerUnidadDefaultPorTipo = (tipoCarne) => {
      return opcionesUnidadCarne(tipoCarne)[0]?.value || null
    }

    const unidadPerteneceAlTipo = (tipoCarne, unidad) => {
      const unidadNormalizada = String(unidad || '').toUpperCase()

      return opcionesUnidadCarne(tipoCarne).some(
        (opcion) => opcion.value === unidadNormalizada,
      )
    }

    const cambiarTipoCarneForm = () => {
      const tipoCarne = String(
        formPuraCarne.value.tipo_carne_manual || 'CHANCHO',
      ).toUpperCase()

      if (
        !unidadPerteneceAlTipo(
          tipoCarne,
          formPuraCarne.value.unidad_carne_manual,
        )
      ) {
        formPuraCarne.value.unidad_carne_manual =
          obtenerUnidadDefaultPorTipo(tipoCarne)
      }
    }

    const cambiarTipoCarneDetalle = (detalle) => {
      const tipoCarne = String(
        detalle.tipo_carne_manual || 'CHANCHO',
      ).toUpperCase()

      if (!unidadPerteneceAlTipo(tipoCarne, detalle.unidad_carne_manual)) {
        detalle.unidad_carne_manual = obtenerUnidadDefaultPorTipo(tipoCarne)
      }
    }

    const etiquetaTipoCarne = (tipoCarne) => {
      const tipo = String(tipoCarne || '').toUpperCase()

      if (tipo === 'CHANCHO') {
        return 'Chancho'
      }

      if (tipo === 'POLLO') {
        return 'Pollo'
      }

      return tipo || 'Sin tipo'
    }

    const etiquetaUnidadCarne = (tipoCarne, unidad) => {
      const unidadNormalizada = String(unidad || '').toUpperCase()

      const opcion = opcionesUnidadCarne(tipoCarne).find(
        (item) => item.value === unidadNormalizada,
      )

      return opcion?.label || unidadNormalizada
    }

    const crearDetalle = (producto) => {
      const detalle = {
        uid: siguienteUid++,
        esPuraCarne: false,
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

    const crearDetallePuraCarne = () => {
      return {
        uid: siguienteUid++,
        esPuraCarne: true,
        producto: null,
        cantidad: 1,
        tipo_carne_manual: String(
          formPuraCarne.value.tipo_carne_manual || 'CHANCHO',
        ).toUpperCase(),
        cantidad_carne_manual: Number(
          formPuraCarne.value.cantidad_carne_manual || 0,
        ),
        unidad_carne_manual: String(
          formPuraCarne.value.unidad_carne_manual || '',
        ).toUpperCase(),
        precio_unitario: Number(
          formPuraCarne.value.precio_unitario || 0,
        ),
        observacion:
          formPuraCarne.value.observacion?.trim() || '',
        guarniciones: [],
      }
    }

    const resetFormPuraCarne = () => {
      formPuraCarne.value = {
        tipo_carne_manual: 'CHANCHO',
        cantidad_carne_manual: null,
        unidad_carne_manual: obtenerUnidadDefaultPorTipo('CHANCHO'),
        precio_unitario: null,
        observacion: '',
      }
    }

    const abrirDialogoPuraCarne = () => {
      resetFormPuraCarne()
      mostrarDialogoPuraCarne.value = true
    }

    const cerrarDialogoPuraCarne = () => {
      mostrarDialogoPuraCarne.value = false
      resetFormPuraCarne()
    }

    const validarFormPuraCarne = () => {
      const tipoCarne = String(
        formPuraCarne.value.tipo_carne_manual || '',
      ).toUpperCase()

      const unidad = String(
        formPuraCarne.value.unidad_carne_manual || '',
      ).toUpperCase()

      const cantidad = Number(
        formPuraCarne.value.cantidad_carne_manual || 0,
      )

      const precio = Number(
        formPuraCarne.value.precio_unitario || 0,
      )

      if (!['CHANCHO', 'POLLO'].includes(tipoCarne)) {
        return 'Debe seleccionar CHANCHO o POLLO.'
      }

      if (!unidad) {
        return 'Debe seleccionar la unidad de carne.'
      }

      if (!unidadPerteneceAlTipo(tipoCarne, unidad)) {
        return `La unidad seleccionada no corresponde a ${tipoCarne}.`
      }

      if (cantidad <= 0) {
        return 'La cantidad de carne debe ser mayor a cero.'
      }

      if (precio <= 0) {
        return 'El precio de venta debe ser mayor a cero.'
      }

      return null
    }

    const agregarPuraCarne = () => {
      const errorValidacion = validarFormPuraCarne()

      if (errorValidacion) {
        $q.notify({
          type: 'negative',
          message: errorValidacion,
          position: 'top',
          timeout: 3000,
        })

        return
      }

      detalles.value.push(crearDetallePuraCarne())

      $q.notify({
        type: 'positive',
        message: 'Pura carne agregada al pedido.',
        position: 'top',
        timeout: 1400,
      })

      cerrarDialogoPuraCarne()
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
      if (!puedeAgregarProducto(producto)) {
        $q.notify({
          type: 'warning',
          message: `${producto.nombre} no tiene stock disponible.`,
          position: 'top',
          timeout: 2500,
        })

        return
      }

      detalles.value.push(crearDetalle(producto))

      $q.notify({
        type: 'positive',
        message: `${producto.nombre} agregado.`,
        position: 'top',
        timeout: 1200,
      })
    }

    const duplicarCombinacion = (detalleOriginal) => {
      if (detalleOriginal.esPuraCarne) {
        return
      }

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
      if (detalle.esPuraCarne) {
        return
      }

      if (!puedeAumentarCantidad(detalle)) {
        if (usaInventario(detalle.producto)) {
          $q.notify({
            type: 'warning',
            message:
              `No hay más stock disponible para ${detalle.producto.nombre}.`,
            position: 'top',
            timeout: 2500,
          })
        }

        return
      }

      detalle.cantidad++
    }

    const disminuirCantidad = (detalle) => {
      if (detalle.esPuraCarne) {
        return
      }

      if (detalle.cantidad > 1) {
        detalle.cantidad--
      }
    }

    const subtotalDetalle = (detalle) => {
      if (detalle.esPuraCarne) {
        return Number(detalle.precio_unitario || 0)
      }

      return (
        Number(detalle.producto?.precio || 0) *
        Number(detalle.cantidad || 0)
      )
    }

    const opcionesGuarniciones = (producto) => {
      return (producto?.guarniciones || []).map(
        (guarnicion) => ({
          label: guarnicion.nombre,
          value: guarnicion.id_guarnicion,
        }),
      )
    }

    const obtenerGuarnicionesSeleccionadas = (detalle) => {
      if (detalle.esPuraCarne) {
        return []
      }

      return (detalle.producto?.guarniciones || []).filter(
        (guarnicion) =>
          detalle.guarniciones.includes(
            guarnicion.id_guarnicion,
          ),
      )
    }

    const iconoProducto = (producto) => {
      if (esBebida(producto)) {
        return 'local_drink'
      }

      if (usaProduccionDiaria(producto)) {
        return 'local_fire_department'
      }

      return 'restaurant'
    }

    const formatoDinero = (monto) => {
      return Number(monto || 0).toFixed(2)
    }

    const formatoCantidad = (cantidad) => {
      const numero = Number(cantidad || 0)

      if (Number.isInteger(numero)) {
        return String(numero)
      }

      return numero.toFixed(2)
    }

    const validarStockInventario = () => {
      const productosInventario = new Map()

      for (const detalle of detalles.value) {
        if (detalle.esPuraCarne || !usaInventario(detalle.producto)) {
          continue
        }

        const idProducto = detalle.producto.id_producto

        if (!productosInventario.has(idProducto)) {
          productosInventario.set(idProducto, {
            producto: detalle.producto,
            cantidad: 0,
          })
        }

        productosInventario.get(idProducto).cantidad += Number(
          detalle.cantidad || 0,
        )
      }

      for (const item of productosInventario.values()) {
        const stock = stockDisponible(item.producto)

        if (stock <= 0) {
          return `${item.producto.nombre} está agotado.`
        }

        if (item.cantidad > stock) {
          return `${item.producto.nombre} solo tiene ${formatoCantidad(stock)} unidad(es) disponibles.`
        }
      }

      return null
    }

    const validarDetallePuraCarne = (detalle) => {
      const tipoCarne = String(
        detalle.tipo_carne_manual || '',
      ).toUpperCase()

      const unidad = String(
        detalle.unidad_carne_manual || '',
      ).toUpperCase()

      const cantidad = Number(
        detalle.cantidad_carne_manual || 0,
      )

      const precio = Number(
        detalle.precio_unitario || 0,
      )

      if (!['CHANCHO', 'POLLO'].includes(tipoCarne)) {
        return 'La pura carne debe ser CHANCHO o POLLO.'
      }

      if (!unidad) {
        return 'La pura carne debe tener unidad.'
      }

      if (!unidadPerteneceAlTipo(tipoCarne, unidad)) {
        return `La unidad de pura carne no corresponde a ${tipoCarne}.`
      }

      if (cantidad <= 0) {
        return 'La cantidad de pura carne debe ser mayor a cero.'
      }

      if (precio <= 0) {
        return 'El precio de pura carne debe ser mayor a cero.'
      }

      return null
    }

    const validarPedido = () => {
      const errorStock = validarStockInventario()

      if (errorStock) {
        return errorStock
      }

      for (const detalle of detalles.value) {
        if (detalle.esPuraCarne) {
          const errorPuraCarne = validarDetallePuraCarne(detalle)

          if (errorPuraCarne) {
            return errorPuraCarne
          }

          continue
        }

        if (
  tieneGuarniciones(detalle.producto) &&
  detalle.guarniciones.length < 1
) {
  return `${detalle.producto.nombre} debe tener mínimo 1 guarnición.`
}

        if (Number(detalle.producto?.precio || 0) <= 0) {
          return `${detalle.producto.nombre} no tiene precio válido.`
        }
      }

      return null
    }

    const prepararDetallePayload = (detalle) => {
      if (detalle.esPuraCarne) {
        return {
          es_pura_carne: true,
          tipo_carne_manual: String(
            detalle.tipo_carne_manual || '',
          ).toUpperCase(),
          cantidad_carne_manual: Number(
            detalle.cantidad_carne_manual || 0,
          ),
          unidad_carne_manual: String(
            detalle.unidad_carne_manual || '',
          ).toUpperCase(),
          precio_unitario: Number(detalle.precio_unitario || 0),
          observacion:
            detalle.observacion?.trim() || null,
        }
      }

      return {
        id_producto: detalle.producto.id_producto,
        cantidad: detalle.cantidad,
        observacion:
          detalle.observacion?.trim() || null,
        guarniciones: detalle.guarniciones,
      }
    }

    const registrarPedido = async () => {
      const errorValidacion = validarPedido()

      if (errorValidacion) {
        $q.notify({
          type: 'negative',
          message: errorValidacion,
          position: 'top',
          timeout: 3500,
        })

        return
      }

      registrandoPedido.value = true

      try {
        const payload = {
          tipo_consumo: tipoConsumo.value,
          detalles: detalles.value.map(prepararDetallePayload),
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

        await cargarProductos()
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
          timeout: 4000,
        })

        await cargarProductos()
      } finally {
        registrandoPedido.value = false
      }
    }

    const confirmarPedido = () => {
      if (detalles.value.length === 0) {
        return
      }

      const errorValidacion = validarPedido()

      if (errorValidacion) {
        $q.notify({
          type: 'negative',
          message: errorValidacion,
          position: 'top',
          timeout: 3500,
        })

        return
      }

      $q.dialog({
        title: 'Confirmar pedido',
        message:
          `Se registrarán ${cantidadProductos.value} item(s) ` +
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

    onMounted(async () => {
      await cargarProductos()
    })

    return {
      productos,
      detalles,
      tipoConsumo,
      tiposConsumo,
      tiposCarneManual,
      opcionesPreparacion,

      cargandoProductos,
      registrandoPedido,
      errorProductos,

      mostrarDialogoPuraCarne,
      formPuraCarne,

      cantidadProductos,
      totalPedido,

      cargarProductos,

      abrirDialogoPuraCarne,
      cerrarDialogoPuraCarne,
      agregarPuraCarne,

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
      esBebida,
      usaInventario,
      usaProduccionDiaria,
      estaAgotado,
      stockDisponible,
      stockRestanteProducto,
      stockRestanteDetalle,
      puedeAgregarProducto,
      puedeAumentarCantidad,
      textoStockProducto,

      opcionesUnidadCarne,
      cambiarTipoCarneForm,
      cambiarTipoCarneDetalle,
      etiquetaTipoCarne,
      etiquetaUnidadCarne,

      iconoProducto,
      formatoDinero,
      formatoCantidad,
      confirmarPedido,
    }
  },
})