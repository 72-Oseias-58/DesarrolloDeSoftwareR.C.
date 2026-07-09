<template>
  <div class="cajero-module-page stock-bebidas-page">
    <div class="stock-header q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Stock de bebidas</div>
        <div class="text-grey-7">
          Control de entradas, salidas y movimientos de bebidas.
        </div>
      </div>

      <q-btn
        color="primary"
        icon="refresh"
        label="Actualizar"
        unelevated
        :loading="cargando"
        @click="cargarDatos"
      />
    </div>

    <div class="row q-col-gutter-lg">
      <div class="col-12 col-lg-5">
        <q-card class="cajero-module-card">
          <q-card-section>
            <div class="text-h6 text-weight-bold">
              Registrar movimiento
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <q-select
              v-model="form.id_insumo"
              outlined
              emit-value
              map-options
              label="Bebida"
              :options="opcionesBebidas"
              class="q-mb-md"
            />

            <q-select
              v-model="form.tipo_movimiento"
              outlined
              emit-value
              map-options
              label="Tipo de movimiento"
              :options="tiposMovimiento"
              class="q-mb-md"
              @update:model-value="form.motivo = null"
            />

            <q-select
              v-model="form.motivo"
              outlined
              emit-value
              map-options
              label="Motivo"
              :options="motivosDisponibles"
              class="q-mb-md"
            />

            <q-input
              v-model.number="form.cantidad"
              outlined
              type="number"
              min="0.01"
              step="0.01"
              label="Cantidad"
              class="q-mb-md"
            />

            <q-input
              v-model="form.observacion"
              outlined
              autogrow
              maxlength="255"
              label="Observación"
              class="q-mb-md"
            />

            <q-btn
              color="primary"
              icon="save"
              label="Registrar movimiento"
              class="full-width"
              unelevated
              :loading="guardando"
              @click="registrarMovimiento"
            />
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-lg-7">
        <q-card class="cajero-module-card">
          <q-card-section>
            <div class="text-h6 text-weight-bold">
              Bebidas disponibles
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div v-if="cargando" class="estado-vacio">
              <q-spinner color="primary" size="42px" />
              <div class="q-mt-md text-grey-7">Cargando stock...</div>
            </div>

            <q-banner
              v-else-if="error"
              rounded
              class="bg-red-1 text-negative"
            >
              {{ error }}
            </q-banner>

            <div v-else class="row q-col-gutter-md">
              <div
                v-for="bebida in bebidas"
                :key="bebida.id_inventario"
                class="col-12 col-sm-6"
              >
                <q-card flat bordered class="bebida-card">
                  <q-card-section>
                    <div class="row items-start no-wrap">
                      <q-icon
                        name="local_drink"
                        size="36px"
                        color="primary"
                        class="q-mr-md"
                      />

                      <div class="col">
                        <div class="text-subtitle1 text-weight-bold">
                          {{ bebida.insumo?.nombre }}
                        </div>

                        <div class="text-grey-7">
                          {{ bebida.insumo?.unidad_medida }}
                        </div>

                        <div class="stock-numero q-mt-sm">
                          Stock: {{ formatoCantidad(bebida.stock_actual) }}
                        </div>

                        <q-chip
                          dense
                          class="q-mt-sm"
                          :color="Number(bebida.stock_actual) <= 0 ? 'red-1' : 'green-1'"
                          :text-color="Number(bebida.stock_actual) <= 0 ? 'negative' : 'positive'"
                          :icon="Number(bebida.stock_actual) <= 0 ? 'block' : 'check_circle'"
                        >
                          {{ Number(bebida.stock_actual) <= 0 ? 'Agotado' : 'Disponible' }}
                        </q-chip>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-card class="cajero-module-card q-mt-lg">
          <q-card-section>
            <div class="text-h6 text-weight-bold">
              Últimos movimientos
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div v-if="movimientos.length === 0" class="estado-vacio">
              <q-icon name="history" size="46px" color="grey-5" />
              <div class="q-mt-md text-grey-7">
                No hay movimientos registrados.
              </div>
            </div>

            <q-list v-else separator>
              <q-item
                v-for="movimiento in movimientos"
                :key="movimiento.id_movimiento"
              >
                <q-item-section avatar>
                  <q-icon
                    :name="movimiento.tipo_movimiento === 'ENTRADA' ? 'south_west' : 'north_east'"
                    :color="movimiento.tipo_movimiento === 'ENTRADA' ? 'positive' : 'negative'"
                  />
                </q-item-section>

                <q-item-section>
                  <q-item-label class="text-weight-bold">
                    {{ movimiento.insumo?.nombre }}
                  </q-item-label>

                  <q-item-label caption>
                    {{ movimiento.tipo_movimiento }} / {{ movimiento.motivo }}
                  </q-item-label>

                  <q-item-label caption>
                    {{ movimiento.observacion || 'Sin observación' }}
                  </q-item-label>
                </q-item-section>

                <q-item-section side>
                  <div class="text-weight-bold">
                    {{ formatoCantidad(movimiento.cantidad) }}
                  </div>

                  <div class="text-caption text-grey-7">
                    {{ formatoCantidad(movimiento.stock_anterior) }}
                    →
                    {{ formatoCantidad(movimiento.stock_nuevo) }}
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </div>
</template>

<script src="./StockBebidasView.js"></script>

<style src="./StockBebidasView.css" scoped></style>