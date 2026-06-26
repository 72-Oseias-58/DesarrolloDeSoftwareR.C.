<template>
  <div class="cajero-module-page caja-page">
    <q-card class="cajero-module-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">Mi caja</div>
            <div class="text-grey-7">
              Apertura, control y cierre de caja del cajero durante la jornada.
            </div>
          </div>

          <q-icon name="point_of_sale" size="52px" color="primary" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div v-if="loading" class="caja-loading">
          <q-spinner-dots color="primary" size="48px" />
          <div class="text-grey-7 q-mt-sm">Cargando caja...</div>
        </div>

        <template v-else>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-4">
              <q-card flat bordered class="cajero-option-card">
                <q-card-section>
                  <q-icon name="visibility" size="34px" color="primary" />

                  <div class="text-subtitle1 text-weight-bold q-mt-sm">
                    Estado de caja
                  </div>

                  <div class="text-grey-7 q-mt-sm">
                    {{ textoCajaActual }}
                  </div>

                  <q-chip
                    v-if="cajaActual"
                    class="q-mt-md"
                    text-color="white"
                    :color="cajaActual.estado === 'ABIERTA' ? 'green' : 'red'"
                  >
                    {{ cajaActual.estado }}
                  </q-chip>

                  <q-chip
                    v-else
                    class="q-mt-md"
                    color="grey"
                    text-color="white"
                  >
                    SIN CAJA ABIERTA
                  </q-chip>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-4">
              <q-card flat bordered class="cajero-option-card">
                <q-card-section>
                  <q-icon name="play_circle" size="34px" color="green" />

                  <div class="text-subtitle1 text-weight-bold q-mt-sm">
                    Abrir caja
                  </div>

                  <div class="text-grey-7 q-mt-sm">
                    Inicia caja con monto inicial antes de registrar pedidos y pagos.
                  </div>

                  <q-input
                    v-if="authStore.tienePermiso('abrir_caja') && !cajaActual"
                    v-model.number="formAbrir.monto_inicial"
                    type="number"
                    min="0"
                    step="0.01"
                    label="Monto inicial"
                    outlined
                    dense
                    class="q-mt-md"
                  />

                  <q-btn
                    v-if="authStore.tienePermiso('abrir_caja')"
                    color="green"
                    icon="play_circle"
                    label="Abrir caja"
                    class="q-mt-md"
                    unelevated
                    :disable="!!cajaActual"
                    :loading="procesando"
                    @click="confirmarAbrirCaja"
                  />
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-4">
              <q-card flat bordered class="cajero-option-card">
                <q-card-section>
                  <q-icon name="stop_circle" size="34px" color="red" />

                  <div class="text-subtitle1 text-weight-bold q-mt-sm">
                    Cerrar caja
                  </div>

                  <div class="text-grey-7 q-mt-sm">
                    Cierre con monto final, efectivo esperado y diferencia.
                  </div>

                  <q-input
                    v-if="authStore.tienePermiso('cerrar_caja') && cajaActual"
                    v-model.number="formCerrar.monto_final"
                    type="number"
                    min="0"
                    step="0.01"
                    label="Monto final"
                    outlined
                    dense
                    class="q-mt-md"
                  />

                  <q-btn
                    v-if="authStore.tienePermiso('cerrar_caja')"
                    color="red"
                    icon="stop_circle"
                    label="Cerrar caja"
                    class="q-mt-md"
                    unelevated
                    :disable="!cajaActual || cajaActual.estado !== 'ABIERTA'"
                    :loading="procesando"
                    @click="confirmarCerrarCaja"
                  />
                </q-card-section>
              </q-card>
            </div>
          </div>

          <q-banner rounded class="bg-orange-1 text-orange-10 q-mt-md">
            La caja solo puede abrirse si existe una jornada abierta para la sucursal.
          </q-banner>

          <q-card v-if="cajaActual" flat bordered class="caja-detalle-card q-mt-md">
            <q-card-section>
              <div class="text-h6 text-weight-bold q-mb-md">
                Detalle de caja
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-3">
                  <div class="detalle-label">Sucursal</div>
                  <div class="detalle-value">
                    {{ cajaActual.jornada?.sucursal?.nombre || 'Sucursal asignada' }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">Cajero</div>
                  <div class="detalle-value">
                    {{ cajaActual.empleado?.nombre || 'Cajero asignado' }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">Monto inicial</div>
                  <div class="detalle-value">
                    Bs {{ formatoDinero(cajaActual.monto_inicial) }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">Hora apertura</div>
                  <div class="detalle-value">
                    {{ cajaActual.hora_apertura || 'No registrada' }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">Total efectivo</div>
                  <div class="detalle-value">
                    Bs {{ formatoDinero(cajaActual.total_efectivo) }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">Total QR</div>
                  <div class="detalle-value">
                    Bs {{ formatoDinero(cajaActual.total_qr) }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">Monto final</div>
                  <div class="detalle-value">
                    {{ cajaActual.monto_final ? `Bs ${formatoDinero(cajaActual.monto_final)}` : 'Pendiente' }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">Diferencia</div>
                  <div
                    class="detalle-value"
                    :class="{
                      'text-green': Number(cajaActual.diferencia || 0) === 0,
                      'text-red': Number(cajaActual.diferencia || 0) !== 0,
                    }"
                  >
                    {{ cajaActual.diferencia !== null ? `Bs ${formatoDinero(cajaActual.diferencia)}` : 'Pendiente' }}
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </template>
      </q-card-section>
    </q-card>
  </div>
</template>

<script src="./CajaView.js"></script>

<style src="./CajaView.css" scoped></style>