<template>
  <div class="module-page inventario-page">
    <q-card class="module-card inventario-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">
              Inventario
            </div>

            <div class="text-grey-7">
              Control de insumos y stock de la sucursal.
            </div>
          </div>

          <q-icon
            name="inventory_2"
            size="48px"
            color="primary"
          />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md q-mb-md">
          <div class="col-12 col-sm-6 col-md-3">
            <q-card
              flat
              bordered
              class="inventario-mini-card"
            >
              <q-card-section>
                <q-icon
                  name="inventory"
                  color="primary"
                  size="32px"
                />

                <div class="text-caption text-grey-7 q-mt-sm">
                  Insumos registrados
                </div>

                <div class="text-h5 text-weight-bold">
                  {{ resumen.total }}
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-card
              flat
              bordered
              class="inventario-mini-card"
            >
              <q-card-section>
                <q-icon
                  name="check_circle"
                  color="positive"
                  size="32px"
                />

                <div class="text-caption text-grey-7 q-mt-sm">
                  Stock normal
                </div>

                <div class="text-h5 text-weight-bold text-positive">
                  {{ resumen.normales }}
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-card
              flat
              bordered
              class="inventario-mini-card"
            >
              <q-card-section>
                <q-icon
                  name="warning"
                  color="orange"
                  size="32px"
                />

                <div class="text-caption text-grey-7 q-mt-sm">
                  Stock bajo
                </div>

                <div class="text-h5 text-weight-bold text-orange">
                  {{ resumen.stock_bajo }}
                </div>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-sm-6 col-md-3">
            <q-card
              flat
              bordered
              class="inventario-mini-card"
            >
              <q-card-section>
                <q-icon
                  name="error"
                  color="negative"
                  size="32px"
                />

                <div class="text-caption text-grey-7 q-mt-sm">
                  Agotados
                </div>

                <div class="text-h5 text-weight-bold text-negative">
                  {{ resumen.agotados }}
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <q-card
          flat
          bordered
          class="filtros-card q-mb-md"
        >
          <q-card-section>
            <div class="row q-col-gutter-md items-center">
              <div class="col-12 col-md-3">
                <q-input
                  v-model="filtros.buscar"
                  outlined
                  clearable
                  debounce="400"
                  label="Buscar insumo"
                  @update:model-value="cargarInventario"
                >
                  <template #prepend>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-md-3">
                <q-input
                  v-model="filtros.categoria"
                  outlined
                  clearable
                  debounce="400"
                  label="Buscar categoría"
                  @update:model-value="cargarInventario"
                >
                  <template #prepend>
                    <q-icon name="category" />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-md-2">
                <q-select
                  v-model="filtros.alerta"
                  outlined
                  clearable
                  emit-value
                  map-options
                  label="Estado"
                  :options="opcionesAlerta"
                  @update:model-value="cargarInventario"
                />
              </div>

              <div class="col-12 col-md-4">
                <div class="row q-gutter-sm justify-end">
                  <q-btn
                    flat
                    color="grey-8"
                    icon="filter_alt_off"
                    label="Limpiar"
                    @click="limpiarFiltros"
                  />

                  <q-btn
                    outline
                    color="primary"
                    icon="history"
                    label="Movimientos"
                    @click="abrirHistorial"
                  />

                  <q-btn
                    unelevated
                    color="primary"
                    icon="add"
                    label="Nuevo insumo"
                    @click="abrirNuevo"
                  />
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-table
          flat
          bordered
          row-key="id_inventario"
          :rows="inventarios"
          :columns="columnas"
          :loading="cargando"
          no-data-label="No existen insumos en el inventario"
        >
          <template #body-cell-insumo="props">
            <q-td :props="props">
              <div class="text-weight-bold">
                {{ props.row.insumo?.nombre || 'Insumo' }}
              </div>
            </q-td>
          </template>

          <template #body-cell-categoria="props">
            <q-td :props="props">
              {{
                props.row.insumo?.categoria?.nombre
                || 'Sin categoría'
              }}
            </q-td>
          </template>

          <template #body-cell-unidad="props">
            <q-td :props="props">
              {{
                mostrarUnidad(
                  props.row.insumo?.unidad_medida,
                )
              }}
            </q-td>
          </template>

          <template #body-cell-prioridad="props">
            <q-td :props="props">
              <q-chip
                dense
                text-color="white"
                :color="
                  colorPrioridad(
                    props.row.insumo?.prioridad_stock,
                  )
                "
              >
                {{
                  props.row.insumo?.prioridad_stock
                  || 'SIN PRIORIDAD'
                }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-stock_actual="props">
            <q-td :props="props">
              <b>
                {{ formatoCantidad(props.row.stock_actual) }}
                {{
                  mostrarUnidad(
                    props.row.insumo?.unidad_medida,
                  )
                }}
              </b>
            </q-td>
          </template>

          <template #body-cell-stock_minimo="props">
            <q-td :props="props">
              {{ formatoCantidad(props.row.stock_minimo) }}
              {{
                mostrarUnidad(
                  props.row.insumo?.unidad_medida,
                )
              }}
            </q-td>
          </template>

          <template #body-cell-estado="props">
            <q-td :props="props">
              <q-chip
                dense
                text-color="white"
                :color="colorEstado(props.row)"
              >
                {{ textoEstado(props.row) }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-acciones="props">
            <q-td :props="props">
              <q-btn
                flat
                round
                dense
                color="green"
                icon="swap_vert"
                @click="abrirMovimiento(props.row)"
              >
                <q-tooltip>
                  Registrar entrada o salida
                </q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Crear insumo -->
    <q-dialog
      v-model="mostrarDialogoNuevo"
      persistent
    >
      <q-card class="inventario-dialog">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Crear insumo
          </div>

          <div class="text-grey-7">
            Registra el insumo y su inventario inicial.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                v-model="formNuevo.nombre"
                outlined
                maxlength="100"
                label="Nombre del insumo"
                hint="Ejemplo: Arroz, aceite, gas o servilletas"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model="formNuevo.unidad_medida"
                outlined
                maxlength="50"
                label="Unidad de medida"
                hint="Ejemplo: KG, L, BALON, PAQUETE"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model="formNuevo.categoria"
                outlined
                maxlength="100"
                label="Categoría"
                hint="Ejemplo: Alimentos o materiales"
              />
            </div>

            <div class="col-12">
              <q-input
                v-model="formNuevo.prioridad_stock"
                outlined
                maxlength="50"
                label="Prioridad"
                hint="Ejemplo: ALTA, MEDIA o BAJA"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model.number="formNuevo.stock_actual"
                outlined
                type="number"
                min="0"
                step="0.01"
                label="Stock actual"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model.number="formNuevo.stock_minimo"
                outlined
                type="number"
                min="0"
                step="0.01"
                label="Stock mínimo"
              />
            </div>

            <div class="col-12">
              <q-input
                v-model="formNuevo.observacion"
                outlined
                autogrow
                maxlength="255"
                label="Observación inicial"
                hint="Opcional"
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
            v-close-popup
          />

          <q-btn
            unelevated
            color="primary"
            icon="save"
            label="Guardar"
            :loading="guardando"
            @click="guardarNuevo"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Movimiento -->
    <q-dialog
      v-model="mostrarDialogoMovimiento"
      persistent
    >
      <q-card class="inventario-dialog">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Registrar movimiento
          </div>

          <div class="text-grey-7">
            {{ inventarioSeleccionado?.insumo?.nombre }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-banner
            rounded
            class="bg-blue-1 text-blue-10 q-mb-md"
          >
            Stock actual:
            <b>
              {{
                formatoCantidad(
                  inventarioSeleccionado?.stock_actual,
                )
              }}
              {{
                mostrarUnidad(
                  inventarioSeleccionado
                    ?.insumo?.unidad_medida,
                )
              }}
            </b>
          </q-banner>

          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <q-select
                v-model="formMovimiento.tipo_movimiento"
                outlined
                emit-value
                map-options
                label="Tipo"
                :options="opcionesTipoMovimiento"
                @update:model-value="cambiarTipoMovimiento"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-select
                v-model="formMovimiento.motivo"
                outlined
                emit-value
                map-options
                label="Motivo"
                :options="motivosMovimiento"
                :disable="!formMovimiento.tipo_movimiento"
              />
            </div>

            <div class="col-12">
              <q-input
                v-model.number="formMovimiento.cantidad"
                outlined
                type="number"
                min="0.01"
                step="0.01"
                label="Cantidad"
              />
            </div>

            <div class="col-12">
              <q-input
                v-model="formMovimiento.observacion"
                outlined
                autogrow
                maxlength="255"
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
            :disable="guardando"
            v-close-popup
          />

          <q-btn
            unelevated
            color="green"
            icon="save"
            label="Registrar"
            :loading="guardando"
            @click="guardarMovimiento"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Historial -->
    <q-dialog v-model="mostrarDialogoHistorial">
      <q-card class="historial-dialog">
        <q-card-section>
          <div class="row items-center justify-between">
            <div>
              <div class="text-h6 text-weight-bold">
                Movimientos de inventario
              </div>

              <div class="text-grey-7">
                Últimos movimientos de la sucursal.
              </div>
            </div>

            <q-btn
              flat
              round
              color="primary"
              icon="refresh"
              :loading="cargandoMovimientos"
              @click="cargarMovimientos"
            />
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-table
            flat
            bordered
            row-key="id_movimiento"
            :rows="movimientos"
            :columns="columnasMovimientos"
            :loading="cargandoMovimientos"
            no-data-label="No existen movimientos"
          >
            <template #body-cell-fecha="props">
              <q-td :props="props">
                {{ formatearFecha(props.row.created_at) }}
              </q-td>
            </template>

            <template #body-cell-insumo="props">
              <q-td :props="props">
                {{ props.row.insumo?.nombre || 'Insumo' }}
              </q-td>
            </template>

            <template #body-cell-tipo="props">
              <q-td :props="props">
                <q-chip
                  dense
                  text-color="white"
                  :color="
                    colorMovimiento(
                      props.row.tipo_movimiento,
                    )
                  "
                >
                  {{ props.row.tipo_movimiento }}
                </q-chip>
              </q-td>
            </template>

            <template #body-cell-motivo="props">
              <q-td :props="props">
                {{ props.row.motivo }}
              </q-td>
            </template>

            <template #body-cell-cantidad="props">
              <q-td :props="props">
                {{ formatoCantidad(props.row.cantidad) }}
                {{
                  mostrarUnidad(
                    props.row.insumo?.unidad_medida,
                  )
                }}
              </q-td>
            </template>

            <template #body-cell-stock="props">
              <q-td :props="props">
                {{
                  formatoCantidad(
                    props.row.stock_anterior,
                  )
                }}
                →
                {{
                  formatoCantidad(
                    props.row.stock_nuevo,
                  )
                }}
              </q-td>
            </template>

            <template #body-cell-usuario="props">
              <q-td :props="props">
                {{
                  props.row.usuario_creador?.name
                  || props.row.usuarioCreador?.name
                  || 'Usuario'
                }}
              </q-td>
            </template>
          </q-table>
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

<script src="./InventarioView.js"></script>

<style src="./InventarioView.css" scoped></style>