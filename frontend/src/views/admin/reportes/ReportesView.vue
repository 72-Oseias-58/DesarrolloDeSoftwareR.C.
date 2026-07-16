<template>
  <div class="module-page reportes-page">
    <q-card class="module-card reportes-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">
              Reportes de jornada
            </div>

            <div class="text-grey-7">
              Informes automáticos generados al cerrar cada jornada.
            </div>
          </div>

          <q-icon
            name="analytics"
            size="48px"
            color="primary"
          />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12 col-sm-6 col-md-3">
            <q-card flat bordered class="reporte-mini-card">
              <q-card-section>
                <q-icon
                  name="payments"
                  color="green"
                  size="32px"
                />

                <div class="text-caption text-grey-7 q-mt-sm">
                  Ventas
                </div>

                <div class="text-h6 text-weight-bold">
                  Bs {{ formatoDinero(totalVentas) }}
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-card flat bordered class="reporte-mini-card">
              <q-card-section>
                <q-icon
                  name="shopping_cart"
                  color="negative"
                  size="32px"
                />

                <div class="text-caption text-grey-7 q-mt-sm">
                  Gastos
                </div>

                <div class="text-h6 text-weight-bold">
                  Bs {{ formatoDinero(totalGastos) }}
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-card flat bordered class="reporte-mini-card">
              <q-card-section>
                <q-icon
                  name="account_balance_wallet"
                  color="primary"
                  size="32px"
                />

                <div class="text-caption text-grey-7 q-mt-sm">
                  Resultado operativo
                </div>

                <div class="text-h6 text-weight-bold">
                  Bs {{ formatoDinero(totalResultado) }}
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-card flat bordered class="reporte-mini-card">
              <q-card-section>
                <q-icon
                  name="difference"
                  :color="colorDiferencia(diferenciaGeneral)"
                  size="32px"
                />

                <div class="text-caption text-grey-7 q-mt-sm">
                  Diferencia acumulada
                </div>

                <div
                  class="text-h6 text-weight-bold"
                  :class="`text-${colorDiferencia(diferenciaGeneral)}`"
                >
                  Bs {{ formatoDinero(diferenciaGeneral) }}
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <q-card flat bordered class="filtros-card q-mb-md">
          <q-card-section>
            <div class="row q-col-gutter-md items-end">
              <div class="col-12 col-md-4">
                <q-input
                  v-model="filtros.fecha_desde"
                  outlined
                  type="date"
                  label="Fecha desde"
                />
              </div>

              <div class="col-12 col-md-4">
                <q-input
                  v-model="filtros.fecha_hasta"
                  outlined
                  type="date"
                  label="Fecha hasta"
                />
              </div>

              <div class="col-12 col-md-4">
                <div class="row q-gutter-sm">
                  <q-btn
                    unelevated
                    color="primary"
                    icon="search"
                    label="Consultar"
                    :loading="cargando"
                    @click="cargarReportes(1)"
                  />

                  <q-btn
                    flat
                    color="grey-8"
                    icon="filter_alt_off"
                    label="Limpiar"
                    @click="limpiarFiltros"
                  />
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-table
          flat
          bordered
          row-key="id_reporte"
          :rows="reportes"
          :columns="columnas"
          :loading="cargando"
          hide-pagination
          no-data-label="No existen reportes de jornada"
        >
          <template #body-cell-fecha="props">
            <q-td :props="props">
              {{ formatearFecha(props.row.fecha) }}
            </q-td>
          </template>

          <template #body-cell-ventas="props">
            <q-td :props="props">
              Bs {{ formatoDinero(props.row.total_ventas) }}
            </q-td>
          </template>

          <template #body-cell-efectivo="props">
            <q-td :props="props">
              Bs {{ formatoDinero(props.row.total_efectivo) }}
            </q-td>
          </template>

          <template #body-cell-qr="props">
            <q-td :props="props">
              Bs {{ formatoDinero(props.row.total_qr) }}
            </q-td>
          </template>

          <template #body-cell-gastos="props">
            <q-td :props="props">
              <span class="text-negative">
                Bs {{
                  formatoDinero(
                    props.row.total_gastos_reales,
                  )
                }}
              </span>
            </q-td>
          </template>

          <template #body-cell-resultado="props">
            <q-td :props="props">
              <b>
                Bs {{
                  formatoDinero(
                    props.row.resultado_operativo,
                  )
                }}
              </b>
            </q-td>
          </template>

          <template #body-cell-diferencia="props">
            <q-td :props="props">
              <q-chip
                dense
                text-color="white"
                :color="
                  colorDiferencia(
                    props.row.diferencia_total,
                  )
                "
              >
                Bs {{
                  formatoDinero(
                    props.row.diferencia_total,
                  )
                }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-acciones="props">
            <q-td :props="props">
              <q-btn
                flat
                round
                dense
                color="primary"
                icon="visibility"
                @click="abrirDetalle(props.row)"
              >
                <q-tooltip>
                  Ver reporte
                </q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>

        <div
          v-if="paginacion.last_page > 1"
          class="row justify-center q-mt-md"
        >
          <q-pagination
            v-model="paginacion.current_page"
            :max="paginacion.last_page"
            color="primary"
            boundary-numbers
            @update:model-value="cargarReportes"
          />
        </div>
      </q-card-section>
    </q-card>

    <q-dialog v-model="mostrarDetalle">
      <q-card
        v-if="reporteSeleccionado"
        class="detalle-reporte-card"
      >
        <q-card-section>
          <div class="text-h5 text-weight-bold">
            Reporte de cierre de jornada
          </div>

          <div class="text-grey-7">
            {{
              reporteSeleccionado.sucursal?.nombre ||
              'Sucursal'
            }}
          </div>

          <div class="text-grey-7">
            {{ formatearFecha(reporteSeleccionado.fecha) }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="seccion-reporte">
            <div class="titulo-seccion">
              Ventas
            </div>

            <div class="fila-reporte">
              <span>Ventas</span>
              <b>
                Bs {{
                  formatoDinero(
                    reporteSeleccionado.total_ventas,
                  )
                }}
              </b>
            </div>

            <div class="fila-reporte">
              <span>Ventas en efectivo</span>
              <b>
                Bs {{
                  formatoDinero(
                    reporteSeleccionado.total_efectivo,
                  )
                }}
              </b>
            </div>

            <div class="fila-reporte">
              <span>Ventas por QR</span>
              <b>
                Bs {{
                  formatoDinero(
                    reporteSeleccionado.total_qr,
                  )
                }}
              </b>
            </div>
          </div>

          <div class="seccion-reporte">
            <div class="titulo-seccion">
              Compras internas
            </div>

            <div class="fila-reporte">
              <span>Compras finalizadas</span>
              <b>
                {{
                  reporteSeleccionado
                    .cantidad_compras_internas
                }}
              </b>
            </div>

            <div class="fila-reporte">
              <span>Dinero inicial entregado</span>
              <b>
                Bs {{
                  formatoDinero(
                    reporteSeleccionado
                      .dinero_entregado_inicial,
                  )
                }}
              </b>
            </div>

            <div class="fila-reporte">
              <span>Dinero adicional</span>
              <b>
                Bs {{
                  formatoDinero(
                    reporteSeleccionado
                      .dinero_adicional_entregado,
                  )
                }}
              </b>
            </div>

            <div class="fila-reporte">
              <span>Gasto real</span>
              <b class="text-negative">
                Bs {{
                  formatoDinero(
                    reporteSeleccionado
                      .total_gastos_reales,
                  )
                }}
              </b>
            </div>

            <div class="fila-reporte">
              <span>Cambio devuelto</span>
              <b>
                Bs {{
                  formatoDinero(
                    reporteSeleccionado
                      .total_cambio_devuelto,
                  )
                }}
              </b>
            </div>
          </div>

          <div class="seccion-reporte">
            <div class="titulo-seccion">
              Resumen de cajas
            </div>

            <q-card
              v-for="caja in reporteSeleccionado.resumen_cajas || []"
              :key="caja.id_caja"
              flat
              bordered
              class="q-mb-md"
            >
              <q-card-section>
                <div class="text-subtitle1 text-weight-bold">
                  Caja #{{ caja.id_caja }} — {{ caja.cajero }}
                </div>

                <div class="fila-reporte q-mt-md">
                  <span>Monto inicial</span>
                  <b>
                    Bs {{ formatoDinero(caja.monto_inicial) }}
                  </b>
                </div>

                <div class="fila-reporte">
                  <span>+ Ventas en efectivo</span>
                  <b>
                    Bs {{ formatoDinero(caja.ventas_efectivo) }}
                  </b>
                </div>

                <div class="fila-reporte">
                  <span>= Efectivo antes de gastos</span>
                  <b>
                    Bs {{
                      formatoDinero(
                        caja.efectivo_antes_gastos,
                      )
                    }}
                  </b>
                </div>

                <div class="fila-reporte">
                  <span>- Gastos reales</span>
                  <b class="text-negative">
                    Bs {{ formatoDinero(caja.gastos_reales) }}
                  </b>
                </div>

                <div class="fila-reporte">
                  <span>= Efectivo estimado</span>
                  <b>
                    Bs {{
                      formatoDinero(
                        caja.efectivo_estimado,
                      )
                    }}
                  </b>
                </div>

                <div class="fila-reporte">
                  <span>Efectivo físico contado</span>
                  <b>
                    Bs {{
                      formatoDinero(
                        caja.efectivo_fisico,
                      )
                    }}
                  </b>
                </div>

                <div class="fila-reporte">
                  <span>Diferencia</span>

                  <q-chip
                    dense
                    text-color="white"
                    :color="colorDiferencia(caja.diferencia)"
                  >
                    Bs {{ formatoDinero(caja.diferencia) }}
                    — {{ textoDiferencia(caja.diferencia) }}
                  </q-chip>
                </div>

                <div
                  v-if="caja.observacion"
                  class="q-mt-md text-grey-8"
                >
                  <b>Observación:</b>
                  {{ caja.observacion }}
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="seccion-reporte">
            <div class="titulo-seccion">
              Resultado operativo
            </div>

            <div class="fila-reporte">
              <span>Ventas</span>
              <b>
                Bs {{
                  formatoDinero(
                    reporteSeleccionado.total_ventas,
                  )
                }}
              </b>
            </div>

            <div class="fila-reporte">
              <span>- Gastos</span>
              <b class="text-negative">
                Bs {{
                  formatoDinero(
                    reporteSeleccionado
                      .total_gastos_reales,
                  )
                }}
              </b>
            </div>

            <div class="fila-reporte resultado-final">
              <span>= Resultado operativo</span>
              <b>
                Bs {{
                  formatoDinero(
                    reporteSeleccionado
                      .resultado_operativo,
                  )
                }}
              </b>
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            color="primary"
            label="Cerrar"
            v-close-popup
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script src="./ReportesView.js"></script>

<style src="./ReportesView.css" scoped></style>