<template>
  <div class="cajero-module-page historial-pedidos-page">
    <q-card class="cajero-module-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">Historial de pedidos</div>
            <div class="text-grey-7">
              Consulta de pedidos registrados, pagados, entregados o anulados.
            </div>
          </div>

          <q-icon name="history" size="52px" color="primary" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-4">
            <q-card flat bordered class="cajero-option-card">
              <q-card-section>
                <q-icon name="search" size="34px" color="primary" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Búsqueda</div>
                <div class="text-grey-7">
                  Filtros por código, fecha, estado o cajero.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="cajero-option-card">
              <q-card-section>
                <q-icon name="fact_check" size="34px" color="green" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Estados</div>
                <div class="text-grey-7">
                  Seguimiento de pedidos PAGADOS, ENTREGADOS y anulados.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="cajero-option-card">
              <q-card-section>
                <q-icon name="table_chart" size="34px" color="orange" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Paginación</div>
                <div class="text-grey-7">
                  La lista deberá cargar por páginas para evitar vistas pesadas.
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <q-banner rounded class="bg-blue-1 text-blue-10 q-mt-md">
          Este historial se conectará después de crear el backend real de pedidos.
        </q-banner>
      </q-card-section>
    </q-card>
  </div>
</template>

<script src="./HistorialPedidosView.js"></script>

<style src="./HistorialPedidosView.css" scoped></style>