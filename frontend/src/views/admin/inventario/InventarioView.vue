<template>
  <div class="module-page inventario-page">
    <q-card class="module-card inventario-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">Inventario</div>
            <div class="text-grey-7">
              Control de insumos, stock por sucursal, alertas y disponibilidad.
            </div>
          </div>

          <q-icon name="inventory_2" size="48px" color="primary" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-4">
            <q-card flat bordered class="inventario-mini-card">
              <q-card-section>
                <q-icon name="category" color="primary" size="32px" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Insumos</div>
                <div class="text-grey-7">
                  Verduras, platos, vasos, bebidas, carne y materiales de trabajo.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="inventario-mini-card">
              <q-card-section>
                <q-icon name="warning" color="orange" size="32px" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Alertas de stock</div>
                <div class="text-grey-7">
                  Avisos para productos con prioridad alta o cantidades bajas.
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat bordered class="inventario-mini-card">
              <q-card-section>
                <q-icon name="store" color="green" size="32px" />
                <div class="text-subtitle1 text-weight-bold q-mt-sm">Sucursal</div>
                <div class="text-grey-7">
                  El ADMIN gestionará únicamente el inventario de su sucursal.
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </div>
</template>

<script src="./InventarioView.js"></script>

<style src="./InventarioView.css" scoped></style>