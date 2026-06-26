<template>
  <div class="cajero-module-page reimprimir-ticket-page">
    <q-card class="cajero-module-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">Reimprimir ticket</div>
            <div class="text-grey-7">
              Reimpresión controlada de tickets según permiso asignado al cajero.
            </div>
          </div>

          <q-icon name="print" size="52px" color="primary" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-4">
            <q-card flat bordered class="cajero-option-card">
              <q-card-section>
                <q-icon name="pin" size="34px" color="primary" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Código</div>
                <div class="text-grey-7">
                  Búsqueda por código único de pedido.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="cajero-option-card">
              <q-card-section>
                <q-icon name="verified_user" size="34px" color="green" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Permiso</div>
                <div class="text-grey-7">
                  Acción visible solo si el usuario posee reimprimir_ticket.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="cajero-option-card">
              <q-card-section>
                <q-icon name="receipt" size="34px" color="orange" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Ticket</div>
                <div class="text-grey-7">
                  Generación del comprobante para impresión o reimpresión.
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <q-banner rounded class="bg-purple-1 text-purple-10 q-mt-md">
          Esta vista quedará conectada después del módulo de pedidos, pagos y tickets.
        </q-banner>
      </q-card-section>
    </q-card>
  </div>
</template>

<script src="./ReimprimirTicketView.js"></script>

<style src="./ReimprimirTicketView.css" scoped></style>