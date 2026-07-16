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
  name: 'ComprasInternasView',

  setup() {
    const q = useQuasar()
    const authStore = useAuthStore()

    const loadingInicial = ref(false)
    const loadingCompras = ref(false)
    const guardando = ref(false)

    const jornada = ref(null)
    const cajas = ref([])
    const empleados = ref([])
    const compras = ref([])

    const compraSeleccionada = ref(null)

    const mostrarDialogoNuevaCompra = ref(false)
    const mostrarDialogoDinero = ref(false)
    const mostrarDialogoFinalizar = ref(false)
    const mostrarDialogoAnular = ref(false)
    const mostrarDialogoDetalle = ref(false)

    const paginacion = ref({
      current_page: 1,
      last_page: 1,
      total: 0,
    })

    const filtros = ref({
      estado: null,
      categoria: null,
    })

    const obtenerFechaHoraActual = () => {
      const partes = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'America/La_Paz',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
      }).formatToParts(new Date())

      const valores = {}

      partes.forEach((parte) => {
        valores[parte.type] = parte.value
      })

      return (
        `${valores.year}-${valores.month}-${valores.day}` +
        `T${valores.hour}:${valores.minute}`
      )
    }

    const crearFormularioNuevaCompra = () => ({
      id_caja: null,
      id_empleado_comprador: null,
      motivo: '',
      categoria: 'COCINA',
      monto_entregado_inicial: null,
      fecha_hora_salida: obtenerFechaHoraActual(),
      observacion: '',
    })

    const crearFormularioFinalizar = () => ({
      productos: [
        {
          producto: '',
          cantidad: 1,
          precio_unitario: null,
        },
      ],
      fecha_hora_regreso: obtenerFechaHoraActual(),
      observacion: '',
    })

    const formNuevaCompra = ref(
      crearFormularioNuevaCompra(),
    )

    const formDineroAdicional = ref({
      monto: null,
      observacion: '',
    })

    const formFinalizar = ref(
      crearFormularioFinalizar(),
    )

    const formAnular = ref({
      observacion: '',
    })

    const columnas = [
      {
        name: 'salida',
        label: 'Fecha y hora',
        field: 'fecha_hora_salida',
        align: 'left',
      },
      {
        name: 'empleado',
        label: 'Empleado comprador',
        field: 'empleado_comprador',
        align: 'left',
      },
      {
        name: 'caja',
        label: 'Caja',
        field: 'caja',
        align: 'left',
      },
      {
        name: 'categoria',
        label: 'Categoría',
        field: 'categoria',
        align: 'left',
      },
      {
        name: 'motivo',
        label: 'Motivo',
        field: 'motivo',
        align: 'left',
      },
      {
        name: 'dinero',
        label: 'Dinero entregado',
        field: 'total_entregado',
        align: 'left',
      },
      {
        name: 'resultado',
        label: 'Resultado',
        field: 'total_gastado',
        align: 'left',
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

    const opcionesEstado = [
      {
        label: 'Pendiente',
        value: 'PENDIENTE',
      },
      {
        label: 'Finalizada',
        value: 'FINALIZADA',
      },
      {
        label: 'Anulada',
        value: 'ANULADA',
      },
    ]

    const opcionesCategoria = [
      {
        label: 'Gas',
        value: 'GAS',
      },
      {
        label: 'Limpieza',
        value: 'LIMPIEZA',
      },
      {
        label: 'Cocina',
        value: 'COCINA',
      },
      {
        label: 'Transporte',
        value: 'TRANSPORTE',
      },
      {
        label: 'Mantenimiento',
        value: 'MANTENIMIENTO',
      },
      {
        label: 'Emergencia',
        value: 'EMERGENCIA',
      },
      {
        label: 'Otros',
        value: 'OTROS',
      },
    ]

    const opcionesCaja = computed(() => {
      return cajas.value.map((caja) => ({
        label:
          `Caja #${caja.id_caja} — ` +
          `${caja.empleado?.nombre || 'Cajero'}`,
        value: caja.id_caja,
      }))
    })

    const opcionesEmpleado = computed(() => {
      return empleados.value
        .filter((empleado) => {
          return empleado.estado === 'ACTIVO'
        })
        .map((empleado) => ({
          label: `${empleado.nombre} — ${empleado.cargo}`,
          value: empleado.id_empleado,
        }))
    })

    const totalProductos = computed(() => {
      return formFinalizar.value.productos.reduce(
        (total, producto) => {
          return (
            total +
            Number(producto.cantidad || 0) *
              Number(producto.precio_unitario || 0)
          )
        },
        0,
      )
    })

    const cambioCalculado = computed(() => {
      return (
        Number(
          compraSeleccionada.value?.total_entregado || 0,
        ) - totalProductos.value
      )
    })

    const resumen = computed(() => {
      const pendientes = compras.value.filter(
        (compra) => compra.estado === 'PENDIENTE',
      )

      const finalizadas = compras.value.filter(
        (compra) => compra.estado === 'FINALIZADA',
      )

      return {
        cantidadPendientes: pendientes.length,

        totalEntregado: compras.value.reduce(
          (total, compra) =>
            total + Number(compra.total_entregado || 0),
          0,
        ),

        totalGastado: finalizadas.reduce(
          (total, compra) =>
            total + Number(compra.total_gastado || 0),
          0,
        ),

        totalCambio: compras.value.reduce(
          (total, compra) =>
            total + Number(compra.cambio_devuelto || 0),
          0,
        ),
      }
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

    const construirParametros = (pagina = 1) => {
      const parametros = {
        page: pagina,
      }

      if (filtros.value.estado) {
        parametros.estado = filtros.value.estado
      }

      if (filtros.value.categoria) {
        parametros.categoria = filtros.value.categoria
      }

      return parametros
    }

    const cargarOpciones = async () => {
      const [respuestaOpciones, respuestaEmpleados] =
        await Promise.all([
          api.get('/admin/compras-internas/opciones'),
          api.get('/empleados'),
        ])

      jornada.value =
        respuestaOpciones.data?.jornada || null

      cajas.value = Array.isArray(
        respuestaOpciones.data?.cajas,
      )
        ? respuestaOpciones.data.cajas
        : []

      empleados.value = Array.isArray(
        respuestaEmpleados.data?.empleados,
      )
        ? respuestaEmpleados.data.empleados
        : []
    }

    const cargarCompras = async (pagina = 1) => {
      if (!jornada.value) {
        compras.value = []
        return
      }

      loadingCompras.value = true

      try {
        const response = await api.get(
          '/admin/compras-internas',
          {
            params: construirParametros(pagina),
          },
        )

        const resultado = response.data?.compras

        compras.value = Array.isArray(resultado?.data)
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
        compras.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        loadingCompras.value = false
      }
    }

    const cargarDatos = async () => {
      loadingInicial.value = true

      try {
        await cargarOpciones()

        if (jornada.value) {
          await cargarCompras(
            paginacion.value.current_page,
          )
        }
      } catch (error) {
        jornada.value = null
        cajas.value = []
        empleados.value = []
        compras.value = []

        q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
        })
      } finally {
        loadingInicial.value = false
      }
    }

    const abrirDialogoNuevaCompra = () => {
      formNuevaCompra.value =
        crearFormularioNuevaCompra()

      if (cajas.value.length === 1) {
        formNuevaCompra.value.id_caja =
          cajas.value[0].id_caja
      }

      mostrarDialogoNuevaCompra.value = true
    }

    const cerrarDialogoNuevaCompra = () => {
      if (guardando.value) {
        return
      }

      mostrarDialogoNuevaCompra.value = false
      formNuevaCompra.value =
        crearFormularioNuevaCompra()
    }

    const validarNuevaCompra = () => {
      const form = formNuevaCompra.value

      if (!form.id_caja) {
        return 'Debe seleccionar la caja.'
      }

      if (!form.id_empleado_comprador) {
        return 'Debe seleccionar al empleado comprador.'
      }

      if (!form.categoria) {
        return 'Debe seleccionar una categoría.'
      }

      if (!String(form.motivo || '').trim()) {
        return 'Debe indicar el motivo de la compra.'
      }

      if (
        Number(form.monto_entregado_inicial || 0) <= 0
      ) {
        return 'El dinero entregado debe ser mayor a cero.'
      }

      return null
    }

    const registrarNuevaCompra = async () => {
      const errorValidacion = validarNuevaCompra()

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
          ...formNuevaCompra.value,

          motivo: String(
            formNuevaCompra.value.motivo,
          ).trim(),

          monto_entregado_inicial: Number(
            formNuevaCompra.value
              .monto_entregado_inicial,
          ),

          fecha_hora_salida: String(
            formNuevaCompra.value.fecha_hora_salida,
          ).replace('T', ' '),

          observacion:
            String(
              formNuevaCompra.value.observacion || '',
            ).trim() || null,
        }

        const response = await api.post(
          '/admin/compras-internas',
          payload,
        )

        q.notify({
          type: 'positive',
          message:
            response.data?.message ||
            'Compra interna registrada correctamente.',
          position: 'top',
        })

        mostrarDialogoNuevaCompra.value = false

        await cargarCompras(1)
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

    const abrirDialogoDineroAdicional = (compra) => {
      compraSeleccionada.value = compra

      formDineroAdicional.value = {
        monto: null,
        observacion: '',
      }

      mostrarDialogoDinero.value = true
    }

    const cerrarDialogoDineroAdicional = () => {
      if (guardando.value) {
        return
      }

      mostrarDialogoDinero.value = false
      compraSeleccionada.value = null
    }

    const registrarDineroAdicional = async () => {
      if (
        Number(formDineroAdicional.value.monto || 0) <= 0
      ) {
        q.notify({
          type: 'negative',
          message: 'El monto adicional debe ser mayor a cero.',
          position: 'top',
        })

        return
      }

      guardando.value = true

      try {
        const response = await api.post(
          `/admin/compras-internas/${compraSeleccionada.value.id_compra_interna}/dinero-adicional`,
          {
            monto: Number(
              formDineroAdicional.value.monto,
            ),

            observacion:
              String(
                formDineroAdicional.value
                  .observacion || '',
              ).trim() || null,
          },
        )

        q.notify({
          type: 'positive',
          message:
            response.data?.message ||
            'Dinero adicional registrado.',
          position: 'top',
        })

        mostrarDialogoDinero.value = false
        compraSeleccionada.value = null

        await cargarCompras(1)
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

    const abrirDialogoFinalizar = (compra) => {
      compraSeleccionada.value = compra
      formFinalizar.value =
        crearFormularioFinalizar()

      mostrarDialogoFinalizar.value = true
    }

    const cerrarDialogoFinalizar = () => {
      if (guardando.value) {
        return
      }

      mostrarDialogoFinalizar.value = false
      compraSeleccionada.value = null
      formFinalizar.value =
        crearFormularioFinalizar()
    }

    const agregarProducto = () => {
      formFinalizar.value.productos.push({
        producto: '',
        cantidad: 1,
        precio_unitario: null,
      })
    }

    const eliminarProducto = (index) => {
      if (
        formFinalizar.value.productos.length <= 1
      ) {
        return
      }

      formFinalizar.value.productos.splice(
        index,
        1,
      )
    }

    const subtotalProducto = (producto) => {
      return formatoDinero(
        Number(producto.cantidad || 0) *
          Number(producto.precio_unitario || 0),
      )
    }

    const validarFinalizacion = () => {
      for (
        let index = 0;
        index < formFinalizar.value.productos.length;
        index += 1
      ) {
        const producto =
          formFinalizar.value.productos[index]

        if (!String(producto.producto || '').trim()) {
          return `Debe indicar el producto de la fila ${index + 1}.`
        }

        if (Number(producto.cantidad || 0) <= 0) {
          return `La cantidad de la fila ${index + 1} debe ser mayor a cero.`
        }

        if (
          Number(producto.precio_unitario || 0) < 0
        ) {
          return `El precio de la fila ${index + 1} no es válido.`
        }
      }

      if (totalProductos.value <= 0) {
        return 'El total gastado debe ser mayor a cero.'
      }

      if (cambioCalculado.value < 0) {
        return (
          `Faltan Bs ${formatoDinero(
            Math.abs(cambioCalculado.value),
          )}. Registra dinero adicional.`
        )
      }

      return null
    }

    const finalizarCompra = async () => {
      const errorValidacion =
        validarFinalizacion()

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
          productos:
            formFinalizar.value.productos.map(
              (producto) => ({
                producto: String(
                  producto.producto,
                ).trim(),

                cantidad: Number(
                  producto.cantidad,
                ),

                precio_unitario: Number(
                  producto.precio_unitario,
                ),
              }),
            ),

          total_gastado: Number(
            totalProductos.value.toFixed(2),
          ),

          fecha_hora_regreso: String(
            formFinalizar.value.fecha_hora_regreso,
          ).replace('T', ' '),

          observacion:
            String(
              formFinalizar.value.observacion || '',
            ).trim() || null,
        }

        const response = await api.patch(
          `/admin/compras-internas/${compraSeleccionada.value.id_compra_interna}/finalizar`,
          payload,
        )

        q.notify({
          type: 'positive',
          message:
            response.data?.message ||
            'Compra finalizada correctamente.',
          position: 'top',
        })

        mostrarDialogoFinalizar.value = false
        compraSeleccionada.value = null

        await cargarCompras(1)
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

    const abrirDialogoAnular = (compra) => {
      compraSeleccionada.value = compra

      formAnular.value = {
        observacion: '',
      }

      mostrarDialogoAnular.value = true
    }

    const cerrarDialogoAnular = () => {
      if (guardando.value) {
        return
      }

      mostrarDialogoAnular.value = false
      compraSeleccionada.value = null
    }

    const anularCompra = async () => {
      if (
        !String(
          formAnular.value.observacion || '',
        ).trim()
      ) {
        q.notify({
          type: 'negative',
          message: 'Debe indicar el motivo de la anulación.',
          position: 'top',
        })

        return
      }

      guardando.value = true

      try {
        const response = await api.patch(
          `/admin/compras-internas/${compraSeleccionada.value.id_compra_interna}/anular`,
          {
            observacion: String(
              formAnular.value.observacion,
            ).trim(),
          },
        )

        q.notify({
          type: 'positive',
          message:
            response.data?.message ||
            'Compra anulada correctamente.',
          position: 'top',
        })

        mostrarDialogoAnular.value = false
        compraSeleccionada.value = null

        await cargarCompras(1)
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

    const abrirDetalle = (compra) => {
      compraSeleccionada.value = compra
      mostrarDialogoDetalle.value = true
    }

    const formatoDinero = (valor) => {
      return Number(valor || 0).toFixed(2)
    }

    const formatoCantidad = (valor) => {
      const numero = Number(valor || 0)

      if (Number.isInteger(numero)) {
        return String(numero)
      }

      return numero.toFixed(2)
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

    const textoCategoria = (categoria) => {
      const textos = {
        GAS: 'Gas',
        LIMPIEZA: 'Limpieza',
        COCINA: 'Cocina',
        TRANSPORTE: 'Transporte',
        MANTENIMIENTO: 'Mantenimiento',
        EMERGENCIA: 'Emergencia',
        OTROS: 'Otros',
      }

      return textos[categoria] || categoria
    }

    const colorEstado = (estado) => {
      const colores = {
        PENDIENTE: 'orange',
        FINALIZADA: 'green',
        ANULADA: 'red',
      }

      return colores[estado] || 'grey'
    }

    onMounted(async () => {
      await cargarDatos()
    })

    return {
      authStore,

      loadingInicial,
      loadingCompras,
      guardando,

      jornada,
      compras,
      compraSeleccionada,
      filtros,
      paginacion,

      mostrarDialogoNuevaCompra,
      mostrarDialogoDinero,
      mostrarDialogoFinalizar,
      mostrarDialogoAnular,
      mostrarDialogoDetalle,

      formNuevaCompra,
      formDineroAdicional,
      formFinalizar,
      formAnular,

      columnas,
      opcionesEstado,
      opcionesCategoria,
      opcionesCaja,
      opcionesEmpleado,

      totalProductos,
      cambioCalculado,
      resumen,

      cargarDatos,
      cargarCompras,

      abrirDialogoNuevaCompra,
      cerrarDialogoNuevaCompra,
      registrarNuevaCompra,

      abrirDialogoDineroAdicional,
      cerrarDialogoDineroAdicional,
      registrarDineroAdicional,

      abrirDialogoFinalizar,
      cerrarDialogoFinalizar,
      agregarProducto,
      eliminarProducto,
      subtotalProducto,
      finalizarCompra,

      abrirDialogoAnular,
      cerrarDialogoAnular,
      anularCompra,

      abrirDetalle,

      formatoDinero,
      formatoCantidad,
      formatearFecha,
      formatearFechaHora,
      textoCategoria,
      colorEstado,
    }
  },
})