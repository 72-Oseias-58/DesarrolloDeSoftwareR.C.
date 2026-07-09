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
                    Inicia la operación diaria registrando las cruces de chancho y pollo disponibles.
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
                    @click="abrirDialogoApertura"
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
            Una sucursal solo puede tener una jornada por día. Antes de abrirla debes registrar
            las cruces de chancho y pollo disponibles para la venta.
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

          <q-card
            v-if="jornadaActual?.control_carne?.length"
            flat
            bordered
            class="jornada-detalle-card q-mt-md"
          >
            <q-card-section>
              <div class="text-h6 text-weight-bold q-mb-md">
                Carne disponible de la jornada
              </div>

              <div class="row q-col-gutter-md">
                <div
                  v-for="control in jornadaActual.control_carne"
                  :key="control.id_control_carne_jornada"
                  class="col-12 col-md-6"
                >
                  <q-card flat bordered>
                    <q-card-section>
                      <div class="row items-center no-wrap">
                        <q-icon
                          name="restaurant"
                          color="primary"
                          size="34px"
                          class="q-mr-md"
                        />

                        <div class="col">
                          <div class="text-subtitle1 text-weight-bold">
                            {{ control.tipo_carne?.nombre || 'Carne' }}
                          </div>

                          <div class="text-grey-7">
                            Cruces: {{ formatoCantidad(control.cantidad_cruces) }}
                          </div>

                          <div class="text-grey-7">
                            Inicial:
                            {{ formatoCantidad(control.cantidad_base_inicial) }}
                            {{ control.unidad_base }}
                          </div>

                          <div class="text-weight-bold q-mt-xs">
                            Actual:
                            {{ formatoCantidad(control.cantidad_base_actual) }}
                            {{ control.unidad_base }}
                          </div>
                        </div>
                      </div>
                    </q-card-section>
                  </q-card>
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

    <q-dialog v-model="mostrarDialogoApertura" persistent>
      <q-card style="width: 640px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Abrir jornada
          </div>

          <div class="text-grey-7">
            Registra cuántas cruces de chancho y pollo hay disponibles para vender hoy.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-banner rounded class="bg-orange-1 text-orange-10 q-mb-md">
            Conversión usada:
            1 cruz de chancho = 2 costillas/huesos.
            1 cruz de pollo = 2 pollos.
          </q-banner>

          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-card flat bordered>
                <q-card-section>
                  <div class="text-subtitle1 text-weight-bold q-mb-md">
                    Chancho
                  </div>

                  <q-input
                    v-model.number="formApertura.chancho_cruces"
                    outlined
                    type="number"
                    min="0.01"
                    step="0.01"
                    label="Cruces de chancho"
                    hint="Ej: 3 cruces"
                  />

                  <div class="text-grey-7 q-mt-sm">
                    Base inicial:
                    <b>{{ formatoCantidad(baseChancho) }} COSTILLA</b>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12 col-md-6">
              <q-card flat bordered>
                <q-card-section>
                  <div class="text-subtitle1 text-weight-bold q-mb-md">
                    Pollo
                  </div>

                  <q-input
                    v-model.number="formApertura.pollo_cruces"
                    outlined
                    type="number"
                    min="0.01"
                    step="0.01"
                    label="Cruces de pollo"
                    hint="Ej: 5 cruces"
                  />

                  <div class="text-grey-7 q-mt-sm">
                    Base inicial:
                    <b>{{ formatoCantidad(basePollo) }} POLLO</b>
                  </div>
                </q-card-section>
              </q-card>
            </div>

            <div class="col-12">
              <q-input
                v-model="formApertura.observacion"
                outlined
                autogrow
                maxlength="1000"
                label="Observación general"
                hint="Opcional"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="grey-8"
            :disable="procesando"
            @click="cerrarDialogoApertura"
          />

          <q-btn
            unelevated
            color="green"
            icon="play_circle"
            label="Abrir jornada"
            :loading="procesando"
            @click="confirmarAbrirJornada"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script src="./JornadasView.js"></script>

<style src="./JornadasView.css" scoped></style>