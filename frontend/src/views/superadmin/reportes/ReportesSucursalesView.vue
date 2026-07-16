<template>
  <section class="reportes-sucursales-page q-pa-md">
    <q-card class="reportes-sucursales-card">
      <q-card-section>
        <div class="row items-center justify-between">
          <div>
            <div class="text-h5 text-weight-bold">
              Reportes de sucursales
            </div>

            <div class="text-grey-7">
              Reportes automáticos enviados al cerrar cada jornada.
            </div>
          </div>

          <q-icon
            name="assessment"
            size="52px"
            color="primary"
          />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12 col-md-4">
            <q-select
              v-model="filtros.id_sucursal"
              outlined
              clearable
              emit-value
              map-options
              label="Sucursal"
              :options="opcionesSucursales()"
            />
          </div>

          <div class="col-12 col-md-3">
            <q-input
              v-model="filtros.fecha_desde"
              outlined
              type="date"
              label="Fecha desde"
            />
          </div>

          <div class="col-12 col-md-3">
            <q-input
              v-model="filtros.fecha_hasta"
              outlined
              type="date"
              label="Fecha hasta"
            />
          </div>

          <div class="col-12 col-md-2">
            <q-btn
              class="full-width"
              unelevated
              color="primary"
              icon="search"
              label="Consultar"
              :loading="cargando"
              @click="cargarReportes(1)"
            />
          </div>
        </div>

        <q-table
          flat
          bordered
          row-key="id_reporte"
          :rows="reportes"
          :columns="columnas"
          :loading="cargando"
          hide-pagination
          no-data-label="No existen reportes"
        >
          <template #body-cell-fecha="props">
            <q-td :props="props">
              {{ formatearFecha(props.row.fecha) }}
            </q-td>
          </template>

          <template #body-cell-sucursal="props">
            <q-td :props="props">
              <b>
                {{
                  props.row.sucursal?.nombre ||
                  'Sucursal'
                }}
              </b>
            </q-td>
          </template>

          <template #body-cell-ventas="props">
            <q-td :props="props">
              Bs {{ formatoDinero(props.row.total_ventas) }}
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
              />
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
            @update:model-value="cargarReportes"
          />
        </div>
      </q-card-section>
    </q-card>

    <q-dialog v-model="mostrarDetalle">
      <q-card
        v-if="reporteSeleccionado"
        class="detalle-superadmin-card"
      >
        <q-card-section>
          <div class="text-h5 text-weight-bold">
            Reporte de jornada
          </div>

          <div class="text-grey-7">
            {{
              reporteSeleccionado.sucursal?.nombre ||
              'Sucursal'
            }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="detalle-linea">
            <span>Ventas</span>
            <b>
              Bs {{
                formatoDinero(
                  reporteSeleccionado.total_ventas,
                )
              }}
            </b>
          </div>

          <div class="detalle-linea">
            <span>Efectivo</span>
            <b>
              Bs {{
                formatoDinero(
                  reporteSeleccionado.total_efectivo,
                )
              }}
            </b>
          </div>

          <div class="detalle-linea">
            <span>QR</span>
            <b>
              Bs {{
                formatoDinero(
                  reporteSeleccionado.total_qr,
                )
              }}
            </b>
          </div>

          <div class="detalle-linea">
            <span>Gastos</span>
            <b class="text-negative">
              Bs {{
                formatoDinero(
                  reporteSeleccionado
                    .total_gastos_reales,
                )
              }}
            </b>
          </div>

          <div class="detalle-linea resultado">
            <span>Resultado operativo</span>
            <b>
              Bs {{
                formatoDinero(
                  reporteSeleccionado
                    .resultado_operativo,
                )
              }}
            </b>
          </div>

          <q-separator class="q-my-md" />

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

              <div class="detalle-linea">
                <span>Efectivo estimado</span>
                <b>
                  Bs {{ formatoDinero(caja.efectivo_estimado) }}
                </b>
              </div>

              <div class="detalle-linea">
                <span>Efectivo contado</span>
                <b>
                  Bs {{ formatoDinero(caja.efectivo_fisico) }}
                </b>
              </div>

              <div class="detalle-linea">
                <span>Diferencia</span>
                <b
                  :class="
                    `text-${colorDiferencia(caja.diferencia)}`
                  "
                >
                  Bs {{ formatoDinero(caja.diferencia) }}
                </b>
              </div>

              <div
                v-if="caja.observacion"
                class="text-grey-8 q-mt-md"
              >
                <b>Observación:</b>
                {{ caja.observacion }}
              </div>
            </q-card-section>
          </q-card>
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
  </section>
</template>

<script src="./ReportesSucursalesView.js"></script>

<style src="./ReportesSucursalesView.css" scoped></style>