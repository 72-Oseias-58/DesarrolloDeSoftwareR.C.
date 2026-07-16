<template>
  <div class="admin-module-page jornadas-page">
    <q-card class="admin-module-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">
              Jornadas
            </div>

            <div class="text-grey-7">
              Apertura, cierre y control diario de la sucursal.
            </div>
          </div>

          <q-icon
            name="event_available"
            size="52px"
            color="primary"
          />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div
          v-if="loading"
          class="jornadas-loading"
        >
          <q-spinner-dots
            color="primary"
            size="48px"
          />

          <div class="text-grey-7 q-mt-sm">
            Cargando jornada...
          </div>
        </div>

        <template v-else>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-4">
              <q-card
                flat
                bordered
                class="admin-option-card"
              >
                <q-card-section>
                  <q-icon
                    name="today"
                    size="34px"
                    color="primary"
                  />

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
                    :color="
                      jornadaActual.estado === 'ABIERTA'
                        ? 'green'
                        : 'red'
                    "
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
              <q-card
                flat
                bordered
                class="admin-option-card"
              >
                <q-card-section>
                  <q-icon
                    name="play_circle"
                    size="34px"
                    color="green"
                  />

                  <div class="text-subtitle1 text-weight-bold q-mt-sm">
                    Abrir jornada
                  </div>

                  <div class="text-grey-7 q-mt-sm">
                    Registra la carne disponible para iniciar el día.
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
              <q-card
                flat
                bordered
                class="admin-option-card"
              >
                <q-card-section>
                  <q-icon
                    name="stop_circle"
                    size="34px"
                    color="red"
                  />

                  <div class="text-subtitle1 text-weight-bold q-mt-sm">
                    Cerrar jornada
                  </div>

                  <div class="text-grey-7 q-mt-sm">
                    Cierra las cajas y genera el reporte final.
                  </div>

                  <q-btn
                    v-if="authStore.tienePermiso('cerrar_jornada')"
                    color="red"
                    icon="stop_circle"
                    label="Cerrar jornada"
                    class="q-mt-md"
                    unelevated
                    :disable="
                      !jornadaActual ||
                      jornadaActual.estado !== 'ABIERTA'
                    "
                    :loading="preparandoCierre"
                    @click="prepararCierreJornada"
                  />
                </q-card-section>
              </q-card>
            </div>
          </div>

          <q-card
            v-if="jornadaActual"
            flat
            bordered
            class="jornada-detalle-card q-mt-md"
          >
            <q-card-section>
              <div class="text-h6 text-weight-bold q-mb-md">
                Detalle de la jornada
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-3">
                  <div class="detalle-label">
                    Sucursal
                  </div>

                  <div class="detalle-value">
                    {{
                      jornadaActual.sucursal?.nombre ||
                      'Sucursal asignada'
                    }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">
                    Fecha
                  </div>

                  <div class="detalle-value">
                    {{ formatearFecha(jornadaActual.fecha) }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">
                    Hora inicio
                  </div>

                  <div class="detalle-value">
                    {{
                      jornadaActual.hora_inicio ||
                      'No registrada'
                    }}
                  </div>
                </div>

                <div class="col-12 col-md-3">
                  <div class="detalle-label">
                    Hora cierre
                  </div>

                  <div class="detalle-value">
                    {{
                      jornadaActual.hora_fin ||
                      'Pendiente'
                    }}
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <q-card
            v-if="obtenerControlCarneJornada().length"
            flat
            bordered
            class="jornada-detalle-card q-mt-md"
          >
            <q-card-section>
              <div class="text-h6 text-weight-bold q-mb-md">
                Carne disponible
              </div>

              <div class="row q-col-gutter-md">
                <div
                  v-for="control in obtenerControlCarneJornada()"
                  :key="control.id_control_carne"
                  class="col-12 col-md-6"
                >
                  <q-card flat bordered>
                    <q-card-section>
                      <div class="row items-start no-wrap">
                        <q-icon
                          name="restaurant"
                          color="primary"
                          size="38px"
                          class="q-mr-md"
                        />

                        <div class="col">
                          <div class="text-subtitle1 text-weight-bold">
                            {{ nombreTipoCarne(control) }}
                          </div>

                          <div class="text-grey-7">
                            Cruces:
                            <b>
                              {{
                                formatoCantidad(
                                  control.cantidad_cruces,
                                )
                              }}
                            </b>
                          </div>

                          <div
                            v-if="esChanchoControl(control)"
                            class="text-grey-7 q-mt-xs"
                          >
                            CostillasGrandes:
                            <b>
                              {{
                                formatoCantidad(
                                  costillasGrandesChancho(control),
                                )
                              }}
                            </b>
                          </div>

                          <div
                            v-if="esChanchoControl(control)"
                            class="text-grey-7 q-mt-xs"
                          >
                            Estimado:
                            <b>
                              {{
                                rangoMinCostillasChancho(control)
                              }}
                            </b>
                          </div>

                          <div class="text-grey-7 q-mt-xs">
                            Inicial:
                            <b>
                              {{
                                formatoCantidad(
                                  control.cantidad_base_inicial,
                                )
                              }}
                              {{ unidadBaseCarne(control) }}
                            </b>
                          </div>

                          <div class="text-weight-bold q-mt-xs">
                            Actual:
                            {{
                              formatoCantidad(
                                control.cantidad_base_actual,
                              )
                            }}
                            {{ unidadBaseCarne(control) }}
                          </div>

                          <q-linear-progress
                            rounded
                            size="12px"
                            class="q-mt-md"
                            :value="
                              porcentajeRestanteCarne(control) /
                              100
                            "
                            :color="
                              Number(
                                control.cantidad_base_actual || 0,
                              ) <= 0
                                ? 'negative'
                                : 'positive'
                            "
                          />

                          <div class="text-caption text-grey-7 q-mt-xs">
                            Restante:
                            {{
                              formatoCantidad(
                                porcentajeRestanteCarne(control),
                              )
                            }}%
                          </div>
                        </div>
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <q-card
            flat
            bordered
            class="jornada-historial-card q-mt-md"
          >
            <q-card-section>
              <div class="row items-center justify-between q-mb-md">
                <div>
                  <div class="text-h6 text-weight-bold">
                    Historial de jornadas
                  </div>

                  <div class="text-grey-7">
                    Jornadas registradas en la sucursal.
                  </div>
                </div>

                <q-btn
                  flat
                  round
                  color="primary"
                  icon="refresh"
                  :loading="loadingHistorial"
                  @click="cargarHistorial"
                />
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
                      :color="
                        props.row.estado === 'ABIERTA'
                          ? 'green'
                          : 'red'
                      "
                    >
                      {{ props.row.estado }}
                    </q-chip>
                  </q-td>
                </template>

                <template #body-cell-reporte="props">
                  <q-td :props="props">
                    <q-chip
                      v-if="props.row.reporte"
                      dense
                      color="green"
                      text-color="white"
                    >
                      GENERADO
                    </q-chip>

                    <span v-else class="text-grey-6">
                      Sin reporte
                    </span>
                  </q-td>
                </template>
              </q-table>
            </q-card-section>
          </q-card>
        </template>
      </q-card-section>
    </q-card>

    <!-- Apertura -->
    <q-dialog
      v-model="mostrarDialogoApertura"
      persistent
    >
      <q-card style="width: 640px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Abrir jornada
          </div>

          <div class="text-grey-7">
            Registra las cruces de chancho y pollo.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-banner
            rounded
            class="bg-orange-1 text-orange-10 q-mb-md"
          >
            1 cruz de chancho equivale aproximadamente
            a 24 MinCostillas. 1 cruz de pollo equivale
            a 2 pollos.
          </q-banner>

          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-input
                v-model.number="formApertura.chancho_cruces"
                outlined
                type="number"
                min="0.01"
                step="0.01"
                label="Cruces de chancho"
              />

              <div class="text-grey-7 q-mt-sm">
                Base inicial:
                <b>
                  {{ formatoCantidad(baseChancho) }}
                  MinCostillas
                </b>
              </div>
            </div>

            <div class="col-12 col-md-6">
              <q-input
                v-model.number="formApertura.pollo_cruces"
                outlined
                type="number"
                min="0.01"
                step="0.01"
                label="Cruces de pollo"
              />

              <div class="text-grey-7 q-mt-sm">
                Base inicial:
                <b>
                  {{ formatoCantidad(basePollo) }}
                  pollos
                </b>
              </div>
            </div>

            <div class="col-12">
              <q-input
                v-model="formApertura.observacion"
                outlined
                autogrow
                maxlength="1000"
                label="Observación"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            color="grey-8"
            label="Cancelar"
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

    <!-- Compras pendientes -->
    <q-dialog
      v-model="mostrarDialogoBloqueo"
    >
      <q-card style="width: 700px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6 text-weight-bold text-negative">
            No se puede cerrar la jornada
          </div>

          <div class="text-grey-7">
            Existen compras internas pendientes.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-list bordered separator>
            <q-item
              v-for="compra in comprasPendientes"
              :key="compra.id_compra_interna"
            >
              <q-item-section>
                <q-item-label>
                  Compra #{{ compra.id_compra_interna }}
                </q-item-label>

                <q-item-label caption>
                  {{ compra.empleado_comprador }}
                </q-item-label>

                <q-item-label caption>
                  {{ compra.motivo }}
                </q-item-label>
              </q-item-section>

              <q-item-section side>
                Bs {{ formatoDinero(compra.total_entregado) }}
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            color="primary"
            label="Entendido"
            v-close-popup
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Cierre de cajas -->
    <q-dialog
      v-model="mostrarDialogoCierre"
      persistent
    >
      <q-card style="width: 920px; max-width: 97vw">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Cerrar cajas y jornada
          </div>

          <div class="text-grey-7">
            Registra el efectivo físico contado en cada caja.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div
            v-for="caja in cajasCierre"
            :key="caja.id_caja"
            class="caja-cierre-card q-mb-md"
          >
            <div class="text-subtitle1 text-weight-bold">
              Caja #{{ caja.id_caja }} — {{ caja.cajero }}
            </div>

            <div class="row q-col-gutter-md q-mt-sm">
              <div class="col-12 col-md-6">
                <div class="calculo-caja">
                  <div class="calculo-linea">
                    <span>Monto inicial</span>
                    <b>
                      Bs {{ formatoDinero(caja.monto_inicial) }}
                    </b>
                  </div>

                  <div class="calculo-linea">
                    <span>+ Ventas en efectivo</span>
                    <b>
                      Bs {{ formatoDinero(caja.ventas_efectivo) }}
                    </b>
                  </div>

                  <div class="calculo-linea total">
                    <span>= Efectivo antes de gastos</span>
                    <b>
                      Bs {{
                        formatoDinero(
                          caja.efectivo_antes_gastos,
                        )
                      }}
                    </b>
                  </div>

                  <div class="calculo-linea">
                    <span>- Compras internas</span>
                    <b class="text-negative">
                      Bs {{
                        formatoDinero(
                          caja.gastos_compras_internas,
                        )
                      }}
                    </b>
                  </div>

                  <div class="calculo-linea resultado">
                    <span>= Efectivo estimado</span>
                    <b>
                      Bs {{
                        formatoDinero(
                          caja.efectivo_estimado,
                        )
                      }}
                    </b>
                  </div>

                  <div class="calculo-linea">
                    <span>Ventas por QR</span>
                    <b>
                      Bs {{ formatoDinero(caja.ventas_qr) }}
                    </b>
                  </div>
                </div>
              </div>

              <div class="col-12 col-md-6">
                <q-input
                  v-model.number="caja.monto_fisico"
                  outlined
                  type="number"
                  min="0"
                  step="0.01"
                  prefix="Bs"
                  label="Efectivo físico contado"
                />

                <q-banner
                  rounded
                  class="q-mt-md"
                  :class="
                    `bg-${colorDiferenciaCaja(caja)}-1 ` +
                    `text-${colorDiferenciaCaja(caja)}`
                  "
                >
                  Diferencia:
                  <b>
                    Bs {{
                      formatoDinero(
                        calcularDiferenciaCaja(caja),
                      )
                    }}
                  </b>

                  — {{ estadoDiferenciaCaja(caja) }}
                </q-banner>

                <q-input
                  v-model="caja.observacion"
                  outlined
                  autogrow
                  maxlength="1000"
                  class="q-mt-md"
                  label="Observación"
                  :hint="
                    Math.abs(
                      calcularDiferenciaCaja(caja),
                    ) > 0.009
                      ? 'Obligatoria si existe diferencia'
                      : 'Opcional'
                  "
                />
              </div>
            </div>
          </div>

          <q-card
            flat
            bordered
            class="resumen-cierre-card"
          >
            <q-card-section>
              <div class="text-h6 text-weight-bold q-mb-md">
                Resumen general
              </div>

              <div class="row q-col-gutter-md">
                <div class="col-12 col-sm-6 col-md-3">
                  <div class="detalle-label">
                    Monto inicial
                  </div>

                  <div class="detalle-value">
                    Bs {{ formatoDinero(totalMontoInicial) }}
                  </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                  <div class="detalle-label">
                    Ventas en efectivo
                  </div>

                  <div class="detalle-value">
                    Bs {{ formatoDinero(totalVentasEfectivo) }}
                  </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                  <div class="detalle-label">
                    Gastos
                  </div>

                  <div class="detalle-value text-negative">
                    Bs {{ formatoDinero(totalGastos) }}
                  </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                  <div class="detalle-label">
                    Efectivo estimado
                  </div>

                  <div class="detalle-value">
                    Bs {{ formatoDinero(totalEstimado) }}
                  </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                  <div class="detalle-label">
                    Efectivo contado
                  </div>

                  <div class="detalle-value">
                    Bs {{ formatoDinero(totalFisico) }}
                  </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                  <div class="detalle-label">
                    Diferencia
                  </div>

                  <div class="detalle-value">
                    Bs {{ formatoDinero(diferenciaTotal) }}
                  </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                  <div class="detalle-label">
                    Ventas QR
                  </div>

                  <div class="detalle-value">
                    Bs {{ formatoDinero(totalVentasQr) }}
                  </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                  <div class="detalle-label">
                    Total ventas
                  </div>

                  <div class="detalle-value">
                    Bs {{
                      formatoDinero(
                        totalVentasEfectivo + totalVentasQr,
                      )
                    }}
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            color="grey-8"
            label="Cancelar"
            :disable="procesandoCierre"
            @click="cerrarDialogoCierre"
          />

          <q-btn
            unelevated
            color="negative"
            icon="stop_circle"
            label="Cerrar jornada"
            :loading="procesandoCierre"
            :disable="!puedeCerrarJornada"
            @click="cerrarJornada"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script src="./JornadasView.js"></script>

<style src="./JornadasView.css" scoped></style>