<template>
  <div class="module-page reportes-page">
    <q-card class="module-card reportes-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">Reportes</div>
            <div class="text-grey-7">
              Reportes de ventas, stock, movimientos, empleados y actividad del sistema.
            </div>
          </div>

          <q-icon name="analytics" size="48px" color="primary" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-4">
            <q-card flat bordered class="reporte-mini-card">
              <q-card-section>
                <q-icon name="payments" color="green" size="32px" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Ventas</div>
                <div class="text-grey-7">
                  Resumen de ventas por día, semana, mes o rango personalizado.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="reporte-mini-card">
              <q-card-section>
                <q-icon name="inventory" color="orange" size="32px" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Stock</div>
                <div class="text-grey-7">
                  Control de insumos, movimientos y alertas de inventario.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="reporte-mini-card">
              <q-card-section>
                <q-icon name="manage_accounts" color="primary" size="32px" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Actividad</div>
                <div class="text-grey-7">
                  Seguimiento de acciones realizadas por usuarios del sistema.
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </div>
</template>

<script src="./ReportesView.js"></script>

<style src="./ReportesView.css" scoped></style>