import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue'
import { useQuasar } from 'quasar'
import api from '@/api/axios'

export default defineComponent({
  name: 'CatalogoProductosView',

  setup() {
    const $q = useQuasar()

    const productos = ref([])
    const cargandoProductos = ref(false)
    const errorProductos = ref('')

    const opcionesCatalogo = ref({
      categorias: [],
      guarniciones: [],
    })

    const mostrarDialogoProducto = ref(false)
    const guardandoProducto = ref(false)
    const modoDialogoProducto = ref('CREAR')
    const tipoDialogoProducto = ref('PLATO')

    const formProducto = ref({
      id_producto: null,
      nombre: '',
      descripcion: '',
      precio: null,
      id_categoria_producto: null,

      modo_control: 'PLATO_CARNE',

      guarniciones: [],
      consumo_chancho: 0,
      consumo_pollo: 0,

      unidad_medida: 'UNIDAD',
      stock_inicial: 0,

      imagen: null,
      imagen_url: null,
      eliminar_imagen: false,
    })

    const modosControlPlato = [
      {
        label: 'Plato con producción diaria',
        value: 'PLATO_CARNE',
      },
      {
        label: 'Plato sin control de stock',
        value: 'PLATO_SIN_STOCK',
      },
    ]

    const esDialogoEdicion = computed(() => {
      return modoDialogoProducto.value === 'EDITAR'
    })

    const esDialogoPlato = computed(() => {
      return tipoDialogoProducto.value === 'PLATO'
    })

    const esDialogoBebida = computed(() => {
      return tipoDialogoProducto.value === 'BEBIDA'
    })

    const esPlatoConCarne = computed(() => {
      return formProducto.value.modo_control === 'PLATO_CARNE'
    })

    const esPlatoSinStock = computed(() => {
      return formProducto.value.modo_control === 'PLATO_SIN_STOCK'
    })

    const tituloDialogoProducto = computed(() => {
      if (esDialogoEdicion.value) {
        return esDialogoPlato.value
          ? 'Editar plato'
          : 'Editar bebida'
      }

      return esDialogoPlato.value
        ? 'Nuevo plato'
        : 'Nueva bebida'
    })

    const opcionesGuarnicionesCatalogo = computed(() => {
      return opcionesCatalogo.value.guarniciones.map((guarnicion) => ({
        label: guarnicion.nombre,
        value: guarnicion.id_guarnicion,
      }))
    })

    const normalizarNombre = (nombre) => {
      return String(nombre || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase()
        .trim()
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

    const cargarOpcionesCatalogo = async () => {
      try {
        const response = await api.get('/catalogo/productos/opciones')

        opcionesCatalogo.value = {
          categorias: Array.isArray(response.data?.categorias)
            ? response.data.categorias
            : [],
          guarniciones: Array.isArray(response.data?.guarniciones)
            ? response.data.guarniciones
            : [],
        }
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
          timeout: 3500,
        })
      }
    }

    const obtenerCategoriaPorNombre = (nombre) => {
      const buscado = normalizarNombre(nombre)

      return opcionesCatalogo.value.categorias.find(
        (categoria) => normalizarNombre(categoria.nombre) === buscado,
      )
    }

    const obtenerIdsTodasGuarniciones = () => {
      return opcionesCatalogo.value.guarniciones.map(
        (guarnicion) => guarnicion.id_guarnicion,
      )
    }

    const resetFormProducto = (tipoProducto) => {
      const categoriaPlatos = obtenerCategoriaPorNombre('Platos')
      const categoriaBebidas = obtenerCategoriaPorNombre('Bebidas')

      formProducto.value = {
        id_producto: null,
        nombre: '',
        descripcion: '',
        precio: null,
        id_categoria_producto:
          tipoProducto === 'PLATO'
            ? categoriaPlatos?.id_categoria_producto || null
            : categoriaBebidas?.id_categoria_producto || null,

        modo_control:
          tipoProducto === 'PLATO'
            ? 'PLATO_CARNE'
            : 'BEBIDA_INVENTARIO',

        guarniciones:
          tipoProducto === 'PLATO'
            ? obtenerIdsTodasGuarniciones()
            : [],

        consumo_chancho: 0,
        consumo_pollo: 0,

        unidad_medida: 'UNIDAD',
        stock_inicial: tipoProducto === 'BEBIDA' ? 0 : null,

        imagen: null,
        imagen_url: null,
        eliminar_imagen: false,
      }
    }

    const asegurarOpcionesCatalogo = async () => {
      if (
        opcionesCatalogo.value.categorias.length === 0 ||
        opcionesCatalogo.value.guarniciones.length === 0
      ) {
        await cargarOpcionesCatalogo()
      }
    }

    const abrirDialogoNuevoProducto = async (tipoProducto) => {
      modoDialogoProducto.value = 'CREAR'
      tipoDialogoProducto.value = tipoProducto

      await asegurarOpcionesCatalogo()

      resetFormProducto(tipoProducto)
      mostrarDialogoProducto.value = true
    }

    const detectarModoControlProducto = (producto) => {
      const tipoProducto = String(producto?.tipo_producto || '').toUpperCase()
      const prioridadStock = String(producto?.prioridad_stock || '').toUpperCase()

      if (tipoProducto === 'BEBIDA') {
        return 'BEBIDA_INVENTARIO'
      }

      if (
        prioridadStock === 'PRODUCCION_DIARIA' ||
        Boolean(producto?.consume_carne)
      ) {
        return 'PLATO_CARNE'
      }

      return 'PLATO_SIN_STOCK'
    }

    const abrirDialogoEditarProducto = async (producto) => {
      const tipoProducto = String(
        producto?.tipo_producto || 'PLATO',
      ).toUpperCase()

      modoDialogoProducto.value = 'EDITAR'
      tipoDialogoProducto.value = tipoProducto

      await asegurarOpcionesCatalogo()

      const consumosCarne = producto?.consumos_carne || {}

      formProducto.value = {
        id_producto: producto.id_producto,
        nombre: producto.nombre || '',
        descripcion: producto.descripcion || '',
        precio: Number(producto.precio || 0),
        id_categoria_producto: producto.id_categoria_producto,

        modo_control: detectarModoControlProducto(producto),

        guarniciones: Array.isArray(producto.guarniciones)
          ? producto.guarniciones.map(
            (guarnicion) => guarnicion.id_guarnicion,
          )
          : [],

        consumo_chancho: Number(consumosCarne.CHANCHO || 0),
        consumo_pollo: Number(consumosCarne.POLLO || 0),

        unidad_medida: producto.insumo?.unidad_medida || 'UNIDAD',
        stock_inicial: 0,

        imagen: null,
        imagen_url: producto.imagen_url || null,
        eliminar_imagen: false,
      }

      mostrarDialogoProducto.value = true
    }

    const cerrarDialogoProducto = () => {
      mostrarDialogoProducto.value = false
      resetFormProducto(tipoDialogoProducto.value)
    }

    const validarProducto = () => {
      const form = formProducto.value

      if (!form.nombre?.trim()) {
        return 'Debe indicar el nombre del producto.'
      }

      if (Number(form.precio || 0) <= 0) {
        return 'El precio debe ser mayor a cero.'
      }

      if (!form.id_categoria_producto) {
        return 'No se encontró la categoría del producto.'
      }

      if (esDialogoBebida.value) {
        if (!form.unidad_medida?.trim()) {
          return 'Debe indicar la unidad de medida de la bebida.'
        }

        if (!esDialogoEdicion.value && Number(form.stock_inicial || 0) < 0) {
          return 'El stock inicial no puede ser negativo.'
        }
      }

      if (esDialogoPlato.value && esPlatoConCarne.value) {
        const consumoChancho = Number(form.consumo_chancho || 0)
        const consumoPollo = Number(form.consumo_pollo || 0)

        if (consumoChancho <= 0 && consumoPollo <= 0) {
          return 'El plato con producción diaria debe consumir chancho, pollo o ambos.'
        }

        if (!Array.isArray(form.guarniciones) || form.guarniciones.length < 1) {
          return 'El plato con producción diaria debe tener mínimo 1 guarnición.'
        }
      }

      return null
    }

    const construirFormDataProducto = () => {
      const form = formProducto.value
      const formData = new FormData()

      formData.append('nombre', form.nombre.trim())
      formData.append('descripcion', form.descripcion?.trim() || '')
      formData.append('precio', Number(form.precio))
      formData.append('tipo_producto', tipoDialogoProducto.value)
      formData.append('id_categoria_producto', form.id_categoria_producto)

      if (form.imagen) {
        formData.append('imagen', form.imagen)
      }

      if (esDialogoEdicion.value) {
        formData.append(
          'eliminar_imagen',
          form.eliminar_imagen ? '1' : '0',
        )
      }

      if (esDialogoBebida.value) {
        formData.append('prioridad_stock', 'INVENTARIO')
        formData.append('unidad_medida', form.unidad_medida?.trim() || 'UNIDAD')

        if (!esDialogoEdicion.value) {
          formData.append('stock_inicial', Number(form.stock_inicial || 0))
        }
      }

      if (esDialogoPlato.value && esPlatoSinStock.value) {
        formData.append('prioridad_stock', 'SIN_STOCK')
      }

      if (esDialogoPlato.value && esPlatoConCarne.value) {
        formData.append('prioridad_stock', 'PRODUCCION_DIARIA')

        if (Number(form.consumo_chancho || 0) > 0) {
          formData.append(
            'consumos_carne[CHANCHO]',
            Number(form.consumo_chancho),
          )
        }

        if (Number(form.consumo_pollo || 0) > 0) {
          formData.append(
            'consumos_carne[POLLO]',
            Number(form.consumo_pollo),
          )
        }

        form.guarniciones.forEach((idGuarnicion) => {
          formData.append('guarniciones[]', idGuarnicion)
        })
      }

      return formData
    }

    const guardarProductoCatalogo = async () => {
      const errorValidacion = validarProducto()

      if (errorValidacion) {
        $q.notify({
          type: 'negative',
          message: errorValidacion,
          position: 'top',
          timeout: 3500,
        })

        return
      }

      guardandoProducto.value = true

      try {
        const formData = construirFormDataProducto()

        if (esDialogoEdicion.value) {
          formData.append('_method', 'PUT')

          await api.post(
            `/catalogo/productos/${formProducto.value.id_producto}`,
            formData,
            {
              headers: {
                'Content-Type': 'multipart/form-data',
              },
            },
          )
        } else {
          await api.post('/catalogo/productos', formData, {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          })
        }

        $q.notify({
          type: 'positive',
          message: esDialogoEdicion.value
            ? 'Producto actualizado correctamente.'
            : 'Producto creado correctamente.',
          position: 'top',
          timeout: 2500,
        })

        cerrarDialogoProducto()
        await cargarProductos()
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
          timeout: 4000,
        })
      } finally {
        guardandoProducto.value = false
      }
    }

    const confirmarEliminarProducto = (producto) => {
      $q.dialog({
        title: 'Eliminar producto',
        message:
          `¿Seguro que deseas eliminar "${producto.nombre}"? ` +
          'Solo debería eliminarse si no tiene ventas registradas.',
        cancel: {
          label: 'Cancelar',
          flat: true,
        },
        ok: {
          label: 'Eliminar',
          color: 'negative',
          unelevated: true,
        },
        persistent: true,
      }).onOk(() => eliminarProducto(producto))
    }

    const eliminarProducto = async (producto) => {
      try {
        await api.delete(`/catalogo/productos/${producto.id_producto}`)

        $q.notify({
          type: 'positive',
          message: 'Producto eliminado correctamente.',
          position: 'top',
          timeout: 2500,
        })

        await cargarProductos()
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
          timeout: 4000,
        })
      }
    }

    const esBebida = (producto) => {
      return String(producto?.tipo_producto || '').toUpperCase() === 'BEBIDA'
    }

    const usaInventario = (producto) => {
      return String(producto?.prioridad_stock || '').toUpperCase() === 'INVENTARIO'
    }

    const usaProduccionDiaria = (producto) => {
      const prioridadStock = String(
        producto?.prioridad_stock || '',
      ).toUpperCase()

      return prioridadStock === 'PRODUCCION_DIARIA' ||
        Boolean(producto?.consume_carne)
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

    onMounted(async () => {
      await Promise.all([
        cargarProductos(),
        cargarOpcionesCatalogo(),
      ])
    })

    return {
      productos,
      cargandoProductos,
      errorProductos,

      opcionesCatalogo,
      mostrarDialogoProducto,
      guardandoProducto,
      modoDialogoProducto,
      tipoDialogoProducto,
      formProducto,

      modosControlPlato,

      esDialogoEdicion,
      esDialogoPlato,
      esDialogoBebida,
      esPlatoConCarne,
      esPlatoSinStock,
      tituloDialogoProducto,
      opcionesGuarnicionesCatalogo,

      cargarProductos,
      cargarOpcionesCatalogo,

      abrirDialogoNuevoProducto,
      abrirDialogoEditarProducto,
      cerrarDialogoProducto,
      guardarProductoCatalogo,
      confirmarEliminarProducto,

      esBebida,
      usaInventario,
      usaProduccionDiaria,
      iconoProducto,
      formatoDinero,
      formatoCantidad,
    }
  },
})