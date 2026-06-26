<template>
  <div class="cajero-module-page registrar-pago-page">
    <q-card class="cajero-module-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">Registrar pago</div>
            <div class="text-grey-7">
              Confirmación de pagos en efectivo, QR BNB o modalidad mixta.
            </div>
          </div>

          <q-icon name="payments" size="52px" color="primary" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-4">
            <q-card flat bordered class="cajero-option-card">
              <q-card-section>
                <q-icon name="attach_money" size="34px" color="green" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Efectivo</div>
                <div class="text-grey-7">
                  Registro del monto recibido en efectivo.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="cajero-option-card">
              <q-card-section>
                <q-icon name="qr_code_2" size="34px" color="primary" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">QR BNB</div>
                <div class="text-grey-7">
                  Registro del monto pagado mediante QR.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="cajero-option-card">
              <q-card-section>
                <q-icon name="sync_alt" size="34px" color="orange" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Pago mixto</div>
                <div class="text-grey-7">
                  Validación de efectivo + QR contra el total exacto del pedido.
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <q-banner rounded class="bg-green-1 text-green-10 q-mt-md">
          Este módulo usará transacciones para guardar el pago y cambiar el pedido a PAGADO sin dejar datos a medias.
        </q-banner>
      </q-card-section>
    </q-card>
  </div>
</template>

<script src="./RegistrarPagoView.js"></script>

<style src="./RegistrarPagoView.css" scoped></style>