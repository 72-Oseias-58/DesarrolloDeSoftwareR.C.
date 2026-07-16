<template>
  <div class="movimientos-carne-page q-pa-md">
    <q-card class="movimientos-carne-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">
              Movimientos de carne
            </div>

            <div class="text-grey-7">
              Registra llegadas, salidas, ajustes y mermas de carne
              durante la jornada.
            </div>
          </div>

          <q-icon
            name="set_meal"
            size="54px"
            color="primary"
          />
        </div>
      </q-card-section>

      <q-separator />

        <q-card-section>
        <div
          v-if="loadingInicial"
          class="column items-center q-pa-xl"
        >
          <q-spinner-dots
            color="primary"
            size="50px"
          />

          <div class="text-grey-7 q-mt-sm">
            Cargando control de carne...
          </div>
        </div>

        <template v-else>
          <q-banner
            v-if="!jornada"
            rounded
            class="bg-orange-1 text-orange-10"
          >
            No existe una jornada abierta. Debes abrir la jornada antes
            de registrar movimientos de carne.
          </q-banner>

          <template v-else>
            <q-card
              flat
              bordered
              class="q-mb-md"
            >
              <q-card-section>
                <div class="row items-center justify-between">
                  <div>
                    <div class="text-subtitle1 text-weight-bold">
                      Jornada actual
                    </div>

                    <div class="text-grey-7">
                      Fecha: {{ formatearFecha(jornada.fecha) }}
                    </div>
                  </div>

                  <q-chip
                    color="green"
                    text-color="white"
                  >
                    {{ jornada.estado }}
                  </q-chip>
                </div>
              </q-card-section>
            </q-card>

            <div class="row q-col-gutter-md q-mb-md">
              <div
                v-for="control in controlesCarne"
                :key="control.id_control_carne"
                class="col-12 col-md-6"
              >
                <q-card
                  flat
                  bordered
                  class="control-carne-card"
                >
                  <q-card-section>
                    <div class="row items-start no-wrap">
                      <q-icon
                        name="restaurant"
                        size="40px"
                        color="primary"
                        class="q-mr-md"
                      />

                      <div class="col">
                        <div class="text-subtitle1 text-weight-bold">
                          {{ nombreCarne(control) }}
                        </div>

                        <div class="text-grey-7 q-mt-xs">
                          Cantidad inicial:
                          <b>
                            {{ formatoCantidad(control.cantidad_base_inicial) }}
                            {{ textoUnidad(control.unidad_base) }}
                          </b>
                        </div>

                        <div class="text-weight-bold q-mt-xs">
                          Cantidad actual:
                          <span
                            :class="
                              Number(control.cantidad_base_actual || 0) > 0
                                ? 'text-positive'
                                : 'text-negative'
                            "
                          >
                            {{ formatoCantidad(control.cantidad_base_actual) }}
                            {{ textoUnidad(control.unidad_base) }}
                          </span>
                        </div>

                        <div
                          v-if="esChancho(control)"
                          class="text-grey-7 q-mt-xs"
                        >
                          Equivalencia aproximada:
                          <b>
                            {{
                              formatoCantidad(
                                Number(control.cantidad_base_actual || 0) / 12
                              )
                            }}
                            CostillasGrandes
                          </b>
                        </div>
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <div class="row justify-end q-mb-md">
              <q-btn
                v-if="authStore.tienePermiso('registrar_movimientos_carne')"
                unelevated
                color="primary"
                icon="sync_alt"
                label="Registrar movimiento"
                @click="abrirDialogoMovimiento"
              />
            </div>

            <q-card
              flat
              bordered
            >
              <q-card-section>
                <div class="row items-center justify-between q-gutter-md">
                  <div>
                    <div class="text-h6 text-weight-bold">
                      Historial de movimientos
                    </div>

                    <div class="text-grey-7">
                      Entradas y salidas registradas durante la jornada.
                    </div>
                  </div>

                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="refresh"
                    :loading="loadingMovimientos"
                    @click="cargarDatos"
                  >
                    <q-tooltip>Actualizar</q-tooltip>
                  </q-btn>
                </div>
              </q-card-section>

              <q-separator />

              <q-card-section>
                <div class="row q-col-gutter-md q-mb-md">
                  <div class="col-12 col-md-4">
                    <q-select
                      v-model="filtros.id_tipo_carne"
                      outlined
                      clearable
                      emit-value
                      map-options
                      label="Tipo de carne"
                      :options="opcionesTipoCarne"
                      @update:model-value="cargarMovimientos(1)"
                    />
                  </div>

                  <div class="col-12 col-md-4">
                    <q-select
                      v-model="filtros.tipo_movimiento"
                      outlined
                      clearable
                      emit-value
                      map-options
                      label="Entrada o salida"
                      :options="opcionesTipoMovimiento"
                      @update:model-value="cargarMovimientos(1)"
                    />
                  </div>

                  <div class="col-12 col-md-4">
                    <q-select
                      v-model="filtros.motivo"
                      outlined
                      clearable
                      emit-value
                      map-options
                      label="Motivo"
                      :options="opcionesMotivoFiltro"
                      @update:model-value="cargarMovimientos(1)"
                    />
                  </div>
                </div>

                <q-table
                  flat
                  bordered
                  row-key="id_movimiento_carne"
                  :rows="movimientos"
                  :columns="columnas"
                  :loading="loadingMovimientos"
                  :pagination="{ rowsPerPage: 20 }"
                  hide-pagination
                  no-data-label="No existen movimientos registrados"
                >
                  <template #body-cell-fecha="props">
                    <q-td :props="props">
                      {{ formatearFechaHora(props.row.created_at) }}
                    </q-td>
                  </template>

                  <template #body-cell-carne="props">
                    <q-td :props="props">
                      <q-chip
                        dense
                        color="orange-2"
                        text-color="orange-10"
                      >
                        {{ props.row.tipo_carne?.nombre || 'Carne' }}
                      </q-chip>
                    </q-td>
                  </template>

                  <template #body-cell-tipo_movimiento="props">
                    <q-td :props="props">
                      <q-chip
                        dense
                        text-color="white"
                        :color="
                          props.row.tipo_movimiento === 'ENTRADA'
                            ? 'green'
                            : 'red'
                        "
                      >
                        {{ props.row.tipo_movimiento }}
                      </q-chip>
                    </q-td>
                  </template>

                  <template #body-cell-motivo="props">
                    <q-td :props="props">
                      {{ textoMotivo(props.row.motivo) }}
                    </q-td>
                  </template>

                  <template #body-cell-cantidad="props">
                    <q-td :props="props">
                      <div class="text-weight-bold">
                        {{ formatoCantidad(props.row.cantidad_registrada) }}
                        {{ textoUnidad(props.row.unidad_registrada) }}
                      </div>

                      <div class="text-caption text-grey-7">
                        Base:
                        {{ formatoCantidad(props.row.cantidad_base) }}
                        {{ textoUnidad(props.row.unidad_base) }}
                      </div>
                    </q-td>
                  </template>

                  <template #body-cell-cambio="props">
                    <q-td :props="props">
                      <div>
                        Antes:
                        {{ formatoCantidad(props.row.cantidad_anterior) }}
                      </div>

                      <div class="text-weight-bold">
                        Después:
                        {{ formatoCantidad(props.row.cantidad_nueva) }}
                      </div>
                    </q-td>
                  </template>

                  <template #body-cell-usuario="props">
                    <q-td :props="props">
                      {{ props.row.usuario_creador?.name || 'Sistema' }}
                    </q-td>
                  </template>
                </q-table>

                <div
                  v-if="paginacion.last_page > 1"
                  class="row justify-center q-mt-md"
                >
                  <q-pagination
                    v-model="paginacion.current_page"
                    color="primary"
                    :max="paginacion.last_page"
                    :max-pages="7"
                    boundary-numbers
                    @update:model-value="cargarMovimientos"
                  />
                </div>
              </q-card-section>
            </q-card>
          </template>
        </template>
      </q-card-section>
</q-card>
    <q-dialog
      v-model="mostrarDialogo"
      persistent
    >
      <q-card style="width: 680px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Registrar movimiento de carne
          </div>

          <div class="text-grey-7">
            Registra una llegada, salida, ajuste o merma.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-select
                v-model="form.id_tipo_carne"
                outlined
                emit-value
                map-options
                label="Tipo de carne"
                :options="opcionesTipoCarne"
                @update:model-value="cambiarTipoCarne"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-select
                v-model="form.tipo_movimiento"
                outlined
                emit-value
                map-options
                label="Tipo de movimiento"
                :options="opcionesTipoMovimiento"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-select
                v-model="form.motivo"
                outlined
                emit-value
                map-options
                label="Motivo"
                :options="opcionesMotivoRegistro"
                @update:model-value="ajustarMovimientoPorMotivo"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-select
                v-model="form.unidad_registrada"
                outlined
                emit-value
                map-options
                label="Unidad"
                :disable="!form.id_tipo_carne"
                :options="opcionesUnidad"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-input
                v-model.number="form.cantidad_registrada"
                outlined
                type="number"
                min="0.01"
                step="0.01"
                label="Cantidad"
              />
            </div>

            <div
              v-if="mostrarCantidadReal"
              class="col-12 col-md-6"
            >
              <q-input
                v-model.number="form.cantidad_base_real"
                outlined
                type="number"
                min="0.01"
                step="0.01"
                label="MinCostillas reales"
                hint="Opcional. Reemplaza la equivalencia aproximada."
              />
            </div>

            <div class="col-12">
              <q-banner
                rounded
                class="bg-blue-1 text-blue-10"
              >
                {{ textoConversion }}
              </q-banner>
            </div>

            <div class="col-12">
              <q-input
                v-model="form.observacion"
                outlined
                autogrow
                maxlength="1000"
                label="Observación"
                :hint="
                  requiereObservacion
                    ? 'Obligatoria para ajustes y mermas'
                    : 'Opcional'
                "
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            color="grey-8"
            label="Cancelar"
            :disable="guardando"
            @click="cerrarDialogoMovimiento"
          />

          <q-btn
            unelevated
            color="primary"
            icon="save"
            label="Registrar"
            :loading="guardando"
            @click="confirmarRegistro"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script src="./MovimientosCarneView.js"></script>

<style scoped src="./MovimientosCarneView.css"></style>