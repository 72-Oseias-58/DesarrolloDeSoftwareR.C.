<template>
  <div class="cajero-module-page registrar-pago-page">
    <div class="registrar-pago-header q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">
          Registrar pago
        </div>

        <div class="text-grey-7">
          Cobra pedidos pendientes en efectivo y genera el ticket
          del cliente y la ficha del mesero.
        </div>
      </div>

      <q-btn
        color="primary"
        icon="refresh"
        label="Actualizar"
        unelevated
        :loading="cargandoPedidos"
        @click="cargarPedidosPendientes"
      />
    </div>

    <div class="row q-col-gutter-lg">
      <div class="col-12 col-lg-5">
        <q-card class="cajero-module-card">
          <q-card-section>
            <div class="text-h6 text-weight-bold">
              Pedidos pendientes
            </div>

            <div class="text-grey-7">
              Selecciona un pedido pendiente para cobrarlo.
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div
              v-if="cargandoPedidos"
              class="estado-pedidos"
            >
              <q-spinner
                color="primary"
                size="46px"
              />

              <div class="q-mt-md text-grey-7">
                Cargando pedidos pendientes...
              </div>
            </div>

            <q-banner
              v-else-if="errorPedidos"
              rounded
              class="bg-red-1 text-negative"
            >
              {{ errorPedidos }}
            </q-banner>

            <div
              v-else-if="pedidosPendientes.length === 0"
              class="estado-pedidos"
            >
              <q-icon
                name="receipt_long"
                size="54px"
                color="grey-5"
              />

              <div class="q-mt-md text-grey-7">
                No hay pedidos pendientes de pago.
              </div>
            </div>

            <div
              v-else
              class="column q-gutter-md"
            >
              <q-card
                v-for="pedido in pedidosPendientes"
                :key="pedido.id_pedido"
                flat
                bordered
                class="pedido-pendiente-card cursor-pointer"
                :class="
                  pedidoSeleccionado?.id_pedido === pedido.id_pedido
                    ? 'pedido-seleccionado'
                    : ''
                "
                @click="seleccionarPedido(pedido)"
              >
                <q-card-section>
                  <div class="row items-start justify-between no-wrap">
                    <div>
                      <div class="text-h6 text-weight-bold">
                        {{ pedido.codigo_pedido }}
                      </div>

                      <div class="text-grey-7">
                        {{ formatearFechaHora(pedido.fecha) }}
                      </div>

                      <div class="text-grey-7">
                        {{ cantidadPlatosPedido(pedido) }} plato(s)
                        ·
                        {{ cantidadBebidasPedido(pedido) }} bebida(s)
                      </div>
                    </div>

                    <div class="text-right">
                      <q-chip
                        dense
                        color="orange-1"
                        text-color="orange-10"
                      >
                        {{ pedido.estado }}
                      </q-chip>

                      <div class="pedido-total-mini q-mt-sm">
                        Bs {{ formatoDinero(pedido.total) }}
                      </div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-lg-7">
        <q-card class="cajero-module-card">
          <q-card-section>
            <div class="text-h6 text-weight-bold">
              Detalle y cobro
            </div>

            <div class="text-grey-7">
              El pago se registra actualmente en efectivo.
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div
              v-if="!pedidoSeleccionado"
              class="estado-pedidos"
            >
              <q-icon
                name="point_of_sale"
                size="58px"
                color="grey-5"
              />

              <div class="q-mt-md text-grey-7">
                Selecciona un pedido pendiente.
              </div>
            </div>

            <template v-else>
              <div class="row q-col-gutter-md q-mb-md">
                <div class="col-12 col-md-4">
                  <q-card flat bordered>
                    <q-card-section>
                      <div class="detalle-label">
                        Pedido
                      </div>

                      <div class="detalle-value">
                        {{ pedidoSeleccionado.codigo_pedido }}
                      </div>
                    </q-card-section>
                  </q-card>
                </div>

                <div class="col-12 col-md-4">
                  <q-card flat bordered>
                    <q-card-section>
                      <div class="detalle-label">
                        Estado
                      </div>

                      <div class="detalle-value">
                        {{ pedidoSeleccionado.estado }}
                      </div>
                    </q-card-section>
                  </q-card>
                </div>

                <div class="col-12 col-md-4">
                  <q-card flat bordered>
                    <q-card-section>
                      <div class="detalle-label">
                        Total
                      </div>

                      <div class="detalle-value text-primary">
                        Bs {{ formatoDinero(pedidoSeleccionado.total) }}
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>

              <q-card
                flat
                bordered
                class="q-mb-md"
              >
                <q-card-section>
                  <div class="text-subtitle1 text-weight-bold q-mb-md">
                    Productos del pedido
                  </div>

                  <div
                    v-for="detalle in pedidoSeleccionado.detalles || []"
                    :key="detalle.id_detalle"
                    class="detalle-producto q-mb-md"
                  >
                    <div class="row items-start justify-between no-wrap">
                      <div>
                        <div class="text-weight-bold">
                          {{ nombreDetalle(detalle) }}
                        </div>

                        <div class="text-grey-7">
                          Cantidad: {{ detalle.cantidad }}
                        </div>

                        <div
                          v-if="detalle.guarniciones?.length"
                          class="text-grey-7"
                        >
                          Guarniciones:
                          {{
                            detalle.guarniciones
                              .map((guarnicion) => guarnicion.nombre)
                              .join(' | ')
                          }}
                        </div>

                        <div
                          v-if="detalle.observacion"
                          class="text-grey-7"
                        >
                          Observación: {{ detalle.observacion }}
                        </div>
                      </div>

                      <div class="text-right text-weight-bold">
                        Bs {{ formatoDinero(detalle.subtotal) }}
                      </div>
                    </div>

                    <q-separator class="q-mt-md" />
                  </div>
                </q-card-section>
              </q-card>

              <q-card flat bordered>
                <q-card-section>
                  <div class="text-subtitle1 text-weight-bold q-mb-md">
                    Pago en efectivo
                  </div>

                  <q-input
                    v-model.number="montoEfectivo"
                    outlined
                    type="number"
                    min="0.01"
                    step="0.01"
                    label="Dinero recibido"
                    prefix="Bs"
                    :disable="registrandoPago"
                  />

                  <div class="row q-col-gutter-md q-mt-md">
                    <div class="col-12 col-md-4">
                      <div class="detalle-label">
                        Total pedido
                      </div>

                      <div class="detalle-value">
                        Bs {{ formatoDinero(totalPedidoSeleccionado) }}
                      </div>
                    </div>

                    <div class="col-12 col-md-4">
                      <div class="detalle-label">
                        Recibido
                      </div>

                      <div class="detalle-value">
                        Bs {{ formatoDinero(montoEfectivo) }}
                      </div>
                    </div>

                    <div class="col-12 col-md-4">
                      <div class="detalle-label">
                        Cambio
                      </div>

                      <div
                        class="detalle-value"
                        :class="
                          cambio < 0
                            ? 'text-negative'
                            : 'text-positive'
                        "
                      >
                        Bs {{ formatoDinero(cambio < 0 ? 0 : cambio) }}
                      </div>
                    </div>
                  </div>

                  <q-banner
                    v-if="
                      montoEfectivo > 0 &&
                      montoEfectivo < totalPedidoSeleccionado
                    "
                    rounded
                    class="bg-red-1 text-negative q-mt-md"
                  >
                    El efectivo recibido no cubre el total del pedido.
                  </q-banner>

                  <q-btn
                    color="primary"
                    icon="payments"
                    label="Registrar pago y generar ticket"
                    class="full-width q-mt-md"
                    unelevated
                    size="lg"
                    :loading="registrandoPago"
                    :disable="!puedeRegistrarPago"
                    @click="confirmarPago"
                  />
                </q-card-section>
              </q-card>
            </template>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-dialog
      v-model="mostrarTicket"
      persistent
      maximized
    >
      <q-card class="ticket-dialog-card">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6 text-weight-bold">
              Ticket generado
            </div>

            <div class="text-grey-7">
              Imprime el ticket del cliente y la ficha del mesero.
            </div>
          </div>

          <q-btn
            flat
            round
            dense
            icon="close"
            color="grey-8"
            @click="cerrarTicket"
          />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="row q-col-gutter-lg">
            <div class="col-12 col-lg-7">
              <q-card flat bordered>
                <q-card-section>
                  <div class="row items-center justify-between q-mb-md">
                    <div class="text-subtitle1 text-weight-bold">
                      Ticket detallado del cliente
                    </div>

                    <q-btn
                      color="primary"
                      icon="print"
                      label="Imprimir ticket"
                      unelevated
                      @click="imprimirTicketCliente"
                    />
                  </div>

                  <div
                    id="ticket-cliente-print"
                    class="ticket-termico ticket-cliente"
                  >
                    <div class="ticket-center ticket-title">
                      {{ ticketCliente.restaurante }}
                    </div>

                    <div class="ticket-center">
                      Sucursal: {{ ticketCliente.sucursal }}
                    </div>

                    <div class="ticket-separator"></div>

                    <div class="ticket-center ticket-code">
                      PEDIDO: {{ ticketCliente.codigo_pedido }}
                    </div>

                    <div>
                      Fecha: {{ ticketCliente.fecha_hora }}
                    </div>

                    <div>
                      Cajero: {{ ticketCliente.cajero }}
                    </div>

                    <div class="ticket-separator"></div>

                    <template v-if="ticketCliente.platos?.length">
                      <div class="ticket-section-title">
                        PLATOS
                      </div>

                      <div
                        v-for="plato in ticketCliente.platos"
                        :key="plato.numero"
                        class="ticket-item"
                      >
                        <div class="ticket-row">
                          <span>
                            [{{ plato.numero }}]
                            {{ plato.cantidad }}
                            {{ plato.nombre }}
                          </span>

                          <span>
                            Bs {{ formatoDinero(plato.subtotal) }}
                          </span>
                        </div>

                        <div v-if="plato.preparacion">
                          Guarniciones: {{ plato.preparacion }}
                        </div>

                        <div v-else-if="plato.guarniciones_texto">
                          Guarniciones:
                          {{ plato.guarniciones_texto }}
                        </div>

                        <div v-if="plato.observacion">
                          Observación:

                          <div class="ticket-indent">
                            - {{ plato.observacion }}
                          </div>
                        </div>
                      </div>

                      <div class="ticket-separator"></div>
                    </template>

                    <template v-if="ticketCliente.pura_carne?.length">
                      <div class="ticket-section-title">
                        PURA CARNE
                      </div>

                      <div
                        v-for="carne in ticketCliente.pura_carne"
                        :key="carne.numero"
                        class="ticket-item"
                      >
                        <div class="ticket-row">
                          <span>
                            [{{ carne.numero }}]
                            {{ carne.cantidad }}
                            {{ carne.nombre }}
                          </span>

                          <span>
                            Bs {{ formatoDinero(carne.subtotal) }}
                          </span>
                        </div>

                        <div
                          v-if="
                            carne.cantidad_carne_manual &&
                            carne.unidad_carne_manual
                          "
                        >
                          Venta:
                          {{ formatoCantidad(carne.cantidad_carne_manual) }}
                          {{ textoUnidadCarne(carne.unidad_carne_manual) }}
                        </div>

                        <div v-if="carne.observacion">
                          Observación:

                          <div class="ticket-indent">
                            - {{ carne.observacion }}
                          </div>
                        </div>
                      </div>

                      <div class="ticket-separator"></div>
                    </template>

                    <template v-if="ticketCliente.bebidas?.length">
                      <div class="ticket-section-title">
                        BEBIDAS
                      </div>

                      <div
                        v-for="bebida in ticketCliente.bebidas"
                        :key="
                          bebida.nombre +
                          bebida.precio_unitario +
                          (bebida.observacion || '')
                        "
                        class="ticket-item"
                      >
                        <div class="ticket-row">
                          <span>
                            {{ bebida.cantidad }}
                            {{ bebida.nombre }}
                          </span>

                          <span>
                            Bs {{ formatoDinero(bebida.subtotal) }}
                          </span>
                        </div>

                        <div v-if="bebida.observacion">
                          Observación:

                          <div class="ticket-indent">
                            - {{ bebida.observacion }}
                          </div>
                        </div>
                      </div>

                      <div class="ticket-separator"></div>
                    </template>

                    <div class="ticket-row ticket-total">
                      <span>TOTAL:</span>

                      <span>
                        Bs
                        {{ formatoDinero(ticketCliente.subtotal_productos) }}
                      </span>
                    </div>

                    <div class="ticket-row">
                      <span>PAGO:</span>

                      <span>
                        {{ ticketCliente.metodo_pago }}
                      </span>
                    </div>

                    <div class="ticket-row">
                      <span>RECIBIDO:</span>

                      <span>
                        Bs {{ formatoDinero(ticketCliente.dinero_recibido) }}
                      </span>
                    </div>

                    <div class="ticket-row">
                      <span>CAMBIO:</span>

                      <span>
                        Bs {{ formatoDinero(ticketCliente.cambio) }}
                      </span>
                    </div>

                    <div class="ticket-separator"></div>

                    <div class="ticket-footer">
                      <div class="ticket-message ticket-thanks">
                        {{ ticketCliente.mensaje_gracias }}
                      </div>

                      <div class="ticket-message">
                        {{ ticketCliente.mensaje_contacto }}
                      </div>

                      <div class="ticket-message">
                        {{ ticketCliente.mensaje_publicidad }}
                      </div>

                      <div class="ticket-message ticket-slogan">
                        {{ ticketCliente.mensaje_slogan }}
                      </div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-lg-5">
              <q-card flat bordered>
                <q-card-section>
                  <div class="row items-center justify-between q-mb-md">
                    <div class="text-subtitle1 text-weight-bold">
                      Ficha pequeña del mesero
                    </div>

                    <q-btn
                      color="deep-orange"
                      icon="print"
                      label="Imprimir ficha"
                      unelevated
                      @click="imprimirFichaMesero"
                    />
                  </div>

                  <div
                    id="ficha-mesero-print"
                    class="ticket-termico ficha-mesero"
                  >
                    <div class="ticket-center ticket-title">
                      {{ fichaMesero.restaurante }}
                    </div>

                    <div class="ticket-center ficha-code">
                      {{ fichaMesero.codigo_pedido }}
                    </div>

                    <div
                      v-if="fichaMesero.cantidad_platos > 0"
                      class="ticket-center ficha-count"
                    >
                      {{ fichaMesero.cantidad_platos }}
                      PLATOS
                    </div>

                    <div
                      v-if="fichaMesero.cantidad_pura_carne > 0"
                      class="ticket-center ficha-count"
                    >
                      {{ fichaMesero.cantidad_pura_carne }}
                      PURA CARNE
                    </div>

                    <div
                      v-if="fichaMesero.cantidad_bebidas > 0"
                      class="ticket-center ficha-count"
                    >
                      {{ fichaMesero.cantidad_bebidas }}
                      BEBIDAS
                    </div>
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script src="./RegistrarPagoView.js"></script>

<style src="./RegistrarPagoView.css" scoped></style>