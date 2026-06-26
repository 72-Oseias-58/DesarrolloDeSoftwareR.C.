<template>
  <div class="admin-module-page jornadas-page">
    <q-card class="admin-module-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">Jornadas</div>
            <div class="text-grey-7">
              Apertura, cierre y control de la jornada diaria de la sucursal.
            </div>
          </div>

          <q-icon name="event_available" size="52px" color="primary" />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div v-if="loading" class="jornadas-loading">
          <q-spinner-dots color="primary" size="48px" />
          <div class="text-grey-7 q-mt-sm">Cargando jornada...</div>
        </div>

        <template v-else>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-4">
              <q-card flat bordered class="admin-option-card">
                <q-card-section>
                  <q-icon name="today" size="34px" color="primary" />

                  <div class="text-subtitle1 text-weight-bold q-mt-sm">
                    Jornada actual
                  </div>

                  <div class="text-grey-7 q-mt-sm">
                    {{ textoJornadaActual }}
                  </div>

                  <q-chip
                    v-if="jornadaActual"
                    class="q-mt-md"
                    text-color="white"
                    :color="jornadaActual.estado === 'ABIERTA' ? 'green' : 'red'"
                  >
                    {{ jornadaActual.estado }}
                  </q-chip>

                  <q-chip
                    v-else
                    class="q-mt-md"
                    color="grey"
                    text-color="white"
                  >
                    SIN JORNADA
                  </q-chip>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-4">
              <q-card flat bordered class="admin-option-card">
                <q-card-section>
                  <q-icon name="play_circle" size="34px" color="green" />

                  <div class="text-subtitle1 text-weight-bold q-mt-sm">
                    Abrir jornada
                  </div>

                  <div class="text-grey-7 q-mt-sm">
                    Inicia la operación diaria antes de habilitar cajas, pedidos y pagos.
                  </div>

                  <q-btn
                    v-if="authStore.tienePermiso('abrir_jornada')"
                    color="green"
                    icon="play_circle"
                    label="Abrir jornada"
                    class="q-mt-md"
                    unelevated
                    :disable="!!jornadaActual"
                    :loading="procesando"
                    @click="confirmarAbrirJornada"
                  />
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-4">
              <q-card flat bordered class="admin-option-card">
                <q-card-section>
                  <q-icon name="stop_circle" size="34px" color="red" />

                  <div class="text-subtitle1 text-weight-bold q-mt-sm">
                    Cerrar jornada
                  </div>

                  <div class="text-grey-7 q-mt-sm">
                    Finaliza la operación cuando las cajas y ventas del día estén cerradas.
                  </div>

                  <q-btn
                    v-if="authStore.tienePermiso('cerrar_jornada')"
                    color="red"
                    icon="stop_circle"
                    label="Cerrar jornada"
                    class="q-mt-md"
                    unelevated
                    :disable="!jornadaActual || jornadaActual.estado !== 'ABIERTA'"
                    :loading="procesando"
                    @click="confirmarCerrarJornada"
                  />
                </q-card-section>
              </q-card>
            </div>
          </div>

          <q-banner rounded class="bg-blue-1 text-blue-10 q-mt-md">
            Una sucursal solo puede tener una jornada por día. Si la jornada ya fue cerrada,
            no se podrá abrir otra para la misma fecha.
          </q-banner>

          <q-card v-if="jornadaActual" flat bordered class="jornada-detalle-card q-mt-md">
            <q-card-section>
              <div class="text-h6 text-weight-bold q-mb-md">
                Detalle de la jornada
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-3">
                  <div class="detalle-label">Sucursal</div>
                  <div class="detalle-value">
                    {{ jornadaActual.sucursal?.nombre || 'Sucursal asignada' }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">Fecha</div>
                  <div class="detalle-value">
                    {{ formatearFecha(jornadaActual.fecha) }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">Hora inicio</div>
                  <div class="detalle-value">
                    {{ jornadaActual.hora_inicio || 'No registrada' }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">Hora cierre</div>
                  <div class="detalle-value">
                    {{ jornadaActual.hora_fin || 'Pendiente' }}
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <q-card flat bordered class="jornada-historial-card q-mt-md">
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div>
                  <div class="text-h6 text-weight-bold">Historial de jornadas</div>
                  <div class="text-grey-7">Últimas jornadas registradas en tu sucursal.</div>
                </div>

                <q-btn
                  flat
                  round
                  color="primary"
                  icon="refresh"
                  :loading="loadingHistorial"
                  @click="cargarHistorial"
                >
                  <q-tooltip>Actualizar historial</q-tooltip>
                </q-btn>
              </div>

              <q-table
                flat
                bordered
                :rows="jornadas"
                :columns="columns"
                row-key="id_jornada"
                :loading="loadingHistorial"
                no-data-label="No hay jornadas registradas"
              >
                <template #body-cell-fecha="props">
                  <q-td :props="props">
                    {{ formatearFecha(props.row.fecha) }}
                  </q-td>
                </template>

                <template #body-cell-estado="props">
                  <q-td :props="props">
                    <q-chip
                      dense
                      text-color="white"
                      :color="props.row.estado === 'ABIERTA' ? 'green' : 'red'"
                    >
                      {{ props.row.estado }}
                    </q-chip>
                  </q-td>
                </template>
              </q-table>
            </q-card-section>
          </q-card>
        </template>
      </q-card-section>
    </q-card>
  </div>
</template>

<script src="./JornadasView.js"></script>

<style src="./JornadasView.css" scoped></style>