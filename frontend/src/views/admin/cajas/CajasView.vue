<template>
  <div class="admin-module-page cajas-page">
    <q-card class="admin-module-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">Cajas</div>
            <div class="text-grey-7">
              Control de cajas por jornada, cajeros asignados, montos y diferencias.
            </div>
          </div>

          <q-icon name="point_of_sale" size="52px" color="primary" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-4">
            <q-card flat bordered class="admin-option-card">
              <q-card-section>
                <q-icon name="person_pin" size="34px" color="primary" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Cajero asignado</div>
                <div class="text-grey-7">
                  Cada caja estará relacionada con un cajero de la sucursal.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="admin-option-card">
              <q-card-section>
                <q-icon name="payments" size="34px" color="green" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Montos</div>
                <div class="text-grey-7">
                  Control de monto inicial, monto final, efectivo, QR y total vendido.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="admin-option-card">
              <q-card-section>
                <q-icon name="sync_problem" size="34px" color="orange" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Diferencias</div>
                <div class="text-grey-7">
                  Verificación de diferencias entre lo registrado y lo declarado al cierre.
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <q-banner rounded class="bg-green-1 text-green-10 q-mt-md">
          Este módulo se conectará después de abrir jornadas y antes de registrar pedidos reales.
        </q-banner>
      </q-card-section>
    </q-card>
  </div>
</template>

<script src="./CajasView.js"></script>

<style src="./CajasView.css" scoped></style>