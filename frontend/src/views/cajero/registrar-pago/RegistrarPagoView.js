import {
  computed,
  defineComponent,
  onMounted,
  ref,
} from 'vue'

import { useRoute } from 'vue-router'
import { useQuasar } from 'quasar'
import api from '@/api/axios'

export default defineComponent({
  name: 'RegistrarPagoView',

  setup() {
    const $q = useQuasar()
    const route = useRoute()

    const pedidosPendientes = ref([])
    const pedidoSeleccionado = ref(null)

    const cargandoPedidos = ref(false)
    const registrandoPago = ref(false)
    const errorPedidos = ref('')

    const montoEfectivo = ref(null)

    const mostrarTicket = ref(false)
    const ticketCliente = ref({})
    const fichaMesero = ref({})

    const totalPedidoSeleccionado = computed(() => {
      return Number(
        pedidoSeleccionado.value?.total || 0,
      )
    })

    const cambio = computed(() => {
      return (
        Number(montoEfectivo.value || 0) -
        totalPedidoSeleccionado.value
      )
    })

    const puedeRegistrarPago = computed(() => {
      if (!pedidoSeleccionado.value) {
        return false
      }

      if (registrandoPago.value) {
        return false
      }

      return (
        Number(montoEfectivo.value || 0) >=
        totalPedidoSeleccionado.value
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

    const cargarPedidosPendientes = async () => {
      cargandoPedidos.value = true
      errorPedidos.value = ''

      try {
        const response = await api.get(
          '/pagos/pedidos-pendientes',
        )

        pedidosPendientes.value = Array.isArray(
          response.data?.pedidos,
        )
          ? response.data.pedidos
          : []

        const idPedidoQuery = Number(
          route.query?.pedido || 0,
        )

        if (
          idPedidoQuery > 0 &&
          !pedidoSeleccionado.value
        ) {
          const pedidoEncontrado =
            pedidosPendientes.value.find((pedido) => {
              return (
                Number(pedido.id_pedido) ===
                idPedidoQuery
              )
            })

          if (pedidoEncontrado) {
            seleccionarPedido(pedidoEncontrado)
          }
        }

        if (pedidoSeleccionado.value) {
          const siguePendiente =
            pedidosPendientes.value.find((pedido) => {
              return (
                Number(pedido.id_pedido) ===
                Number(
                  pedidoSeleccionado.value.id_pedido,
                )
              )
            })

          if (!siguePendiente) {
            pedidoSeleccionado.value = null
            montoEfectivo.value = null
          }
        }
      } catch (error) {
        errorPedidos.value =
          obtenerMensajeError(error)

        pedidosPendientes.value = []
      } finally {
        cargandoPedidos.value = false
      }
    }

    const seleccionarPedido = (pedido) => {
      pedidoSeleccionado.value = pedido
      montoEfectivo.value = Number(
        pedido.total || 0,
      )
    }

    const confirmarPago = () => {
      if (!pedidoSeleccionado.value) {
        return
      }

      if (!puedeRegistrarPago.value) {
        $q.notify({
          type: 'negative',
          message:
            'El efectivo recibido no cubre el total del pedido.',
          position: 'top',
          timeout: 3000,
        })

        return
      }

      $q.dialog({
        title: 'Confirmar pago',
        message:
          `Se registrará el pago del pedido ` +
          `${pedidoSeleccionado.value.codigo_pedido} ` +
          `por Bs ${formatoDinero(
            totalPedidoSeleccionado.value,
          )}. ` +
          `Cambio: Bs ${formatoDinero(cambio.value)}.`,
        cancel: {
          label: 'Cancelar',
          flat: true,
        },
        ok: {
          label: 'Registrar pago',
          color: 'primary',
          unelevated: true,
        },
        persistent: true,
      }).onOk(() => {
        registrarPago()
      })
    }

    const registrarPago = async () => {
      if (!pedidoSeleccionado.value) {
        return
      }

      registrandoPago.value = true

      try {
        const response = await api.post(
          `/pedidos/${pedidoSeleccionado.value.id_pedido}/pagar`,
          {
            monto_efectivo: Number(
              montoEfectivo.value || 0,
            ),
          },
        )

        ticketCliente.value =
          response.data?.ticket_cliente || {}

        fichaMesero.value =
          response.data?.ficha_mesero || {}

        $q.notify({
          type: 'positive',
          message:
            response.data?.message ||
            'Pago registrado correctamente.',
          position: 'top',
          timeout: 2500,
        })

        mostrarTicket.value = true

        pedidoSeleccionado.value = null
        montoEfectivo.value = null

        await cargarPedidosPendientes()
      } catch (error) {
        $q.notify({
          type: 'negative',
          message: obtenerMensajeError(error),
          position: 'top',
          timeout: 4000,
        })
      } finally {
        registrandoPago.value = false
      }
    }

    const cerrarTicket = () => {
      mostrarTicket.value = false
      ticketCliente.value = {}
      fichaMesero.value = {}
    }

    const imprimirElemento = (
      idElemento,
      titulo = 'Ticket',
    ) => {
      const elemento =
        document.getElementById(idElemento)

      if (!elemento) {
        $q.notify({
          type: 'negative',
          message:
            'No se encontró el contenido para imprimir.',
          position: 'top',
          timeout: 2500,
        })

        return
      }

      const ventana = window.open(
        '',
        '_blank',
        'width=420,height=700',
      )

      if (!ventana) {
        $q.notify({
          type: 'negative',
          message:
            'El navegador bloqueó la ventana de impresión.',
          position: 'top',
          timeout: 3000,
        })

        return
      }

      ventana.document.write(`
        <!DOCTYPE html>
        <html>
          <head>
            <meta charset="UTF-8">

            <title>${titulo}</title>

            <style>
              @page {
                size: 80mm auto;
                margin: 0;
              }

              * {
                box-sizing: border-box;
              }

              body {
                margin: 0;
                padding: 0;
                background: #ffffff;
                color: #000000;
                font-family: Arial, sans-serif;
              }

              .ticket-termico {
                width: 80mm;
                min-height: auto;
                padding: 8px;
                background: #ffffff;
                color: #000000;
                font-size: 12px;
                line-height: 1.35;
              }

              .ticket-center {
                text-align: center;
              }

              .ticket-title {
                margin-bottom: 4px;
                font-size: 16px;
                font-weight: bold;
              }

              .ticket-code {
                margin: 6px 0;
                font-size: 15px;
                font-weight: bold;
              }

              .ticket-separator {
                margin: 8px 0;
                border-top: 1px dashed #000000;
              }

              .ticket-section-title {
                margin: 6px 0;
                text-align: center;
                font-weight: bold;
              }

              .ticket-item {
                margin-bottom: 8px;
              }

              .ticket-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 8px;
              }

              .ticket-row span:first-child {
                flex: 1;
                min-width: 0;
              }

              .ticket-row span:last-child {
                flex-shrink: 0;
                text-align: right;
                white-space: nowrap;
              }

              .ticket-total {
                font-size: 14px;
                font-weight: bold;
              }

              .ticket-indent {
                padding-left: 10px;
              }

              .ticket-footer {
                margin-top: 10px;
                text-align: center;
              }

              .ticket-message {
                margin-top: 5px;
                text-align: center;
              }

              .ticket-thanks {
                font-size: 13px;
                font-weight: bold;
              }

              .ticket-slogan {
                margin-top: 8px;
                font-size: 14px;
                font-weight: bold;
                text-transform: uppercase;
              }

              .ficha-mesero {
                padding-top: 12px;
                text-align: center;
              }

              .ficha-code {
                margin: 18px 0;
                font-size: 34px;
                font-weight: bold;
              }

              .ficha-count {
                margin: 8px 0;
                font-size: 20px;
                font-weight: bold;
              }
            </style>
          </head>

          <body>
            ${elemento.outerHTML}

            <script>
              window.onload = function () {
                window.print()

                window.onafterprint = function () {
                  window.close()
                }
              }
            <\/script>
          </body>
        </html>
      `)

      ventana.document.close()
    }

    const imprimirTicketCliente = () => {
      imprimirElemento(
        'ticket-cliente-print',
        'Ticket cliente',
      )
    }

    const imprimirFichaMesero = () => {
      imprimirElemento(
        'ficha-mesero-print',
        'Ficha mesero',
      )
    }

    const nombreDetalle = (detalle) => {
      if (detalle.es_pura_carne) {
        const tipo = String(
          detalle.tipo_carne_manual || '',
        )
          .toUpperCase()
          .trim()

        return tipo === 'POLLO'
          ? 'Pura carne de pollo'
          : 'Pura carne de chancho'
      }

      return detalle.producto?.nombre || 'Producto'
    }

    const esBebidaDetalle = (detalle) => {
      return (
        String(
          detalle.producto?.tipo_producto || '',
        )
          .toUpperCase()
          .trim() === 'BEBIDA'
      )
    }

    const esPuraCarneDetalle = (detalle) => {
      return Boolean(detalle.es_pura_carne)
    }

    const cantidadPlatosPedido = (pedido) => {
      return (pedido.detalles || []).reduce(
        (total, detalle) => {
          if (
            esBebidaDetalle(detalle) ||
            esPuraCarneDetalle(detalle)
          ) {
            return total
          }

          return (
            total +
            Math.max(
              1,
              Number(detalle.cantidad || 0),
            )
          )
        },
        0,
      )
    }

    const cantidadBebidasPedido = (pedido) => {
      return (pedido.detalles || []).reduce(
        (total, detalle) => {
          if (!esBebidaDetalle(detalle)) {
            return total
          }

          return (
            total +
            Number(detalle.cantidad || 0)
          )
        },
        0,
      )
    }

    const textoUnidadCarne = (unidad) => {
      const unidadNormalizada = String(
        unidad || '',
      )
        .toUpperCase()
        .trim()

      const unidades = {
        MIN_COSTILLA: 'MinCostilla(s)',
        COSTILLA_GRANDE: 'CostillaGrande(s)',
        CRUZ_CHANCHO: 'cruz/ces de chancho',

        CUARTO_POLLO: '1/4 de pollo',
        MEDIO_POLLO: '1/2 pollo',
        TRES_CUARTOS_POLLO: '3/4 de pollo',
        POLLO_ENTERO: 'pollo(s) entero(s)',
        CRUZ_POLLO: 'cruz/ces de pollo',
      }

      return (
        unidades[unidadNormalizada] ||
        unidadNormalizada
      )
    }

    const formatearFechaHora = (fecha) => {
      if (!fecha) {
        return 'Fecha no registrada'
      }

      const date = new Date(fecha)

      if (Number.isNaN(date.getTime())) {
        return String(fecha)
      }

      return date.toLocaleString('es-BO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
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
      await cargarPedidosPendientes()
    })

    return {
      pedidosPendientes,
      pedidoSeleccionado,

      cargandoPedidos,
      registrandoPago,
      errorPedidos,

      montoEfectivo,

      mostrarTicket,
      ticketCliente,
      fichaMesero,

      totalPedidoSeleccionado,
      cambio,
      puedeRegistrarPago,

      cargarPedidosPendientes,
      seleccionarPedido,
      confirmarPago,
      registrarPago,

      cerrarTicket,
      imprimirTicketCliente,
      imprimirFichaMesero,

      nombreDetalle,
      cantidadPlatosPedido,
      cantidadBebidasPedido,
      textoUnidadCarne,

      formatearFechaHora,
      formatoDinero,
      formatoCantidad,

      esPuraCarneDetalle,
    }
  },
})