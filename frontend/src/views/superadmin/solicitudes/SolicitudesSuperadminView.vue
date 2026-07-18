<template>
  <section class="solicitudes-superadmin-page q-pa-md">
    <q-card class="solicitudes-superadmin-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">
              Solicitudes de sucursales
            </div>

            <div class="text-grey-7">
              Requerimientos enviados por los administradores.
            </div>
          </div>

          <div class="row items-center q-gutter-md">
            <q-chip
              color="orange"
              text-color="white"
              icon="mark_email_unread"
            >
              {{ cantidadNoVistas }} no vistas
            </q-chip>

            <q-icon
              name="inbox"
              size="48px"
              color="primary"
            />
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <q-card
          flat
          bordered
          class="filtros-card q-mb-md"
        >
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-4">
                <q-input
                  v-model="filtros.buscar"
                  outlined
                  clearable
                  debounce="400"
                  label="Buscar asunto o insumo"
                  @update:model-value="
                    cargarSolicitudes(1)
                  "
                >
                  <template #prepend>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>

              <div class="col-12 col-md-2">
                <q-select
                  v-model="filtros.id_sucursal"
                  outlined
                  clearable
                  emit-value
                  map-options
                  label="Sucursal"
                  :options="opcionesSucursales"
                />
              </div>

              <div class="col-12 col-md-2">
                <q-select
                  v-model="filtros.visto"
                  outlined
                  clearable
                  emit-value
                  map-options
                  label="Lectura"
                  :options="opcionesVisto"
                />
              </div>

              <div class="col-12 col-md-2">
                <q-select
                  v-model="filtros.tipo"
                  outlined
                  clearable
                  emit-value
                  map-options
                  label="Tipo"
                  :options="opcionesTipo"
                />
              </div>

              <div class="col-12 col-md-2">
                <div class="row q-gutter-sm">
                  <q-btn
                    unelevated
                    color="primary"
                    icon="search"
                    label="Consultar"
                    :loading="cargando"
                    @click="cargarSolicitudes(1)"
                  />

                  <q-btn
                    flat
                    color="grey-8"
                    icon="filter_alt_off"
                    @click="limpiarFiltros"
                  >
                    <q-tooltip>
                      Limpiar filtros
                    </q-tooltip>
                  </q-btn>
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-table
          flat
          bordered
          row-key="id_solicitud"
          :rows="solicitudes"
          :columns="columnas"
          :loading="cargando"
          hide-pagination
          no-data-label="No existen solicitudes"
        >
          <template #body-cell-lectura="props">
            <q-td :props="props">
              <q-chip
                dense
                text-color="white"
                :color="
                  props.row.visto
                    ? 'green'
                    : 'orange'
                "
              >
                {{
                  props.row.visto
                    ? 'VISTA'
                    : 'NUEVA'
                }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-fecha="props">
            <q-td :props="props">
              {{ formatearFecha(props.row.fecha) }}
            </q-td>
          </template>

          <template #body-cell-sucursal="props">
            <q-td :props="props">
              <b>
                {{
                  props.row.sucursal?.nombre
                  || 'Sucursal'
                }}
              </b>
            </q-td>
          </template>

          <template #body-cell-tipo="props">
            <q-td :props="props">
              <q-chip
                dense
                text-color="white"
                :color="colorTipo(props.row.tipo)"
              >
                {{ textoTipo(props.row.tipo) }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-asunto="props">
            <q-td :props="props">
              {{ props.row.asunto }}
            </q-td>
          </template>

          <template #body-cell-insumos="props">
            <q-td :props="props">
              <q-chip
                v-if="cantidadInsumos(props.row) > 0"
                dense
                color="blue"
                text-color="white"
              >
                {{ cantidadInsumos(props.row) }}
                insumo(s)
              </q-chip>

              <span v-else>
                No aplica
              </span>
            </q-td>
          </template>

          <template #body-cell-solicitante="props">
            <q-td :props="props">
              {{
                props.row.usuario_solicitante?.name
                || props.row.usuarioSolicitante?.name
                || 'Administrador'
              }}
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
                :loading="
                  cargandoDetalle
                  && solicitudSeleccionada
                    ?.id_solicitud
                    === props.row.id_solicitud
                "
                @click="abrirDetalle(props.row)"
              >
                <q-tooltip>
                  Ver detalle
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
            @update:model-value="cargarSolicitudes"
          />
        </div>
      </q-card-section>
    </q-card>

    <q-dialog v-model="mostrarDetalle">
      <q-card
        v-if="solicitudSeleccionada"
        class="detalle-solicitud-card"
      >
        <q-card-section>
          <div class="text-h5 text-weight-bold">
            {{ solicitudSeleccionada.asunto }}
          </div>

          <div class="text-grey-7">
            {{
              solicitudSeleccionada.sucursal?.nombre
              || 'Sucursal'
            }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="detalle-linea">
            <span>Tipo</span>

            <q-chip
              dense
              text-color="white"
              :color="
                colorTipo(
                  solicitudSeleccionada.tipo,
                )
              "
            >
              {{
                textoTipo(
                  solicitudSeleccionada.tipo,
                )
              }}
            </q-chip>
          </div>

          <div class="detalle-linea">
            <span>Fecha de envío</span>

            <b>
              {{
                formatearFecha(
                  solicitudSeleccionada.fecha,
                )
              }}
            </b>
          </div>

          <div class="detalle-linea">
            <span>Solicitante</span>

            <b>
              {{
                solicitudSeleccionada
                  .usuario_solicitante?.name
                || solicitudSeleccionada
                  .usuarioSolicitante?.name
                || 'Administrador'
              }}
            </b>
          </div>

          <div class="detalle-linea">
            <span>Vista el</span>

            <b>
              {{
                formatearFecha(
                  solicitudSeleccionada.visto_en,
                )
              }}
            </b>
          </div>

          <template
            v-if="
              Array.isArray(
                solicitudSeleccionada
                  .detalles_inventario,
              )
              && solicitudSeleccionada
                .detalles_inventario.length
            "
          >
            <q-separator class="q-my-md" />

            <div class="text-subtitle1 text-weight-bold q-mb-sm">
              Insumos solicitados
            </div>

            <q-list
              bordered
              separator
              class="rounded-borders"
            >
              <q-item
                v-for="
                  detalle in
                  solicitudSeleccionada
                    .detalles_inventario
                "
                :key="detalle.id_insumo"
              >
                <q-item-section>
                  <q-item-label class="text-weight-bold">
                    {{ detalle.nombre }}
                  </q-item-label>

                  <q-item-label caption>
                    Categoría:
                    {{ detalle.categoria || 'Sin categoría' }}
                  </q-item-label>

                  <q-item-label caption>
                    Stock al momento de solicitar:
                    {{
                      formatoCantidad(
                        detalle.stock_actual,
                      )
                    }}
                    {{
                      mostrarUnidad(
                        detalle.unidad_medida,
                      )
                    }}
                  </q-item-label>

                  <q-item-label caption>
                    Stock mínimo:
                    {{
                      formatoCantidad(
                        detalle.stock_minimo,
                      )
                    }}
                    {{
                      mostrarUnidad(
                        detalle.unidad_medida,
                      )
                    }}
                  </q-item-label>
                </q-item-section>

                <q-item-section side>
                  <div class="text-caption text-grey-7">
                    Cantidad solicitada
                  </div>

                  <div class="text-h6 text-weight-bold text-primary">
                    {{
                      formatoCantidad(
                        detalle.cantidad_solicitada,
                      )
                    }}
                    {{
                      mostrarUnidad(
                        detalle.unidad_medida,
                      )
                    }}
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </template>

          <template
            v-if="solicitudSeleccionada.descripcion"
          >
            <q-separator class="q-my-md" />

            <div class="text-subtitle1 text-weight-bold">
              Descripción adicional
            </div>

            <div class="descripcion-solicitud q-mt-sm">
              {{ solicitudSeleccionada.descripcion }}
            </div>
          </template>
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

<script src="./SolicitudesSuperadminView.js"></script>

<style src="./SolicitudesSuperadminView.css" scoped></style>