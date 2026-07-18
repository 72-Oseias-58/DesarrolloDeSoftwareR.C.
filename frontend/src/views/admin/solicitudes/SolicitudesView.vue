<template>
  <div class="admin-module-page solicitudes-page">
    <q-card class="admin-module-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">
              Solicitudes
            </div>

            <div class="text-grey-7">
              Solicitudes enviadas al SUPERADMIN.
            </div>
          </div>

          <q-btn
            unelevated
            color="primary"
            icon="add_comment"
            label="Nueva solicitud"
            @click="abrirNueva"
          />
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <q-banner
          rounded
          class="bg-blue-1 text-blue-10 q-mb-md"
        >
          La solicitud permanece como no vista hasta que
          el SUPERADMIN abre su detalle.
        </q-banner>

        <q-table
          flat
          bordered
          row-key="id_solicitud"
          :rows="solicitudes"
          :columns="columnas"
          :loading="cargando"
          hide-pagination
          no-data-label="No existen solicitudes enviadas"
        >
          <template #body-cell-fecha="props">
            <q-td :props="props">
              {{ formatearFecha(props.row.fecha) }}
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
              <b>{{ props.row.asunto }}</b>
            </q-td>
          </template>

          <template #body-cell-insumos="props">
            <q-td :props="props">
              <span
                v-if="
                  Array.isArray(
                    props.row.detalles_inventario,
                  )
                  && props.row.detalles_inventario.length
                "
              >
                {{
                  props.row.detalles_inventario.length
                }}
                insumo(s)
              </span>

              <span v-else>
                No aplica
              </span>
            </q-td>
          </template>

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
                    : 'NO VISTA'
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

    <!-- Nueva solicitud -->
    <q-dialog
      v-model="mostrarDialogoNueva"
      persistent
    >
      <q-card class="solicitud-dialog">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Nueva solicitud
          </div>

          <div class="text-grey-7">
            Envía un requerimiento al SUPERADMIN.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-select
            v-model="form.tipo"
            outlined
            emit-value
            map-options
            label="Tipo de solicitud"
            :options="tipos"
            :loading="cargandoOpciones"
            @update:model-value="cambiarTipo"
          />

          <q-input
            v-model="form.asunto"
            outlined
            maxlength="150"
            class="q-mt-md"
            label="Asunto"
          />

          <template v-if="esReposicion">
            <q-select
              v-model="form.inventarios_seleccionados"
              outlined
              multiple
              use-input
              use-chips
              hide-selected
              fill-input
              input-debounce="250"
              class="q-mt-md"
              label="Buscar y seleccionar insumos"
              :options="opcionesInventarioFiltradas"
              :loading="cargandoOpciones"
              @filter="filtrarInsumos"
              @update:model-value="sincronizarDetalles"
            >
              <template #no-option>
                <q-item>
                  <q-item-section class="text-grey">
                    No se encontraron insumos.
                  </q-item-section>
                </q-item>
              </template>

              <template #option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>
                      {{ scope.opt.nombre }}
                    </q-item-label>

                    <q-item-label caption>
                      {{ scope.opt.categoria || 'Sin categoría' }}
                      —
                      Stock:
                      {{
                        formatoCantidad(
                          scope.opt.stock_actual,
                        )
                      }}
                      {{
                        mostrarUnidad(
                          scope.opt.unidad_medida,
                        )
                      }}
                    </q-item-label>
                  </q-item-section>

                  <q-item-section side>
                    <q-chip
                      v-if="scope.opt.requiere_reposicion"
                      dense
                      color="orange"
                      text-color="white"
                    >
                      Stock bajo
                    </q-chip>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>

            <q-banner
              v-if="
                form.detalles_inventario.length === 0
              "
              rounded
              class="bg-grey-2 text-grey-8 q-mt-md"
            >
              Busca y selecciona uno o varios insumos.
            </q-banner>

            <q-list
              v-else
              bordered
              separator
              class="rounded-borders q-mt-md"
            >
              <q-item
                v-for="detalle in form.detalles_inventario"
                :key="detalle.id_insumo"
              >
                <q-item-section>
                  <q-item-label class="text-weight-bold">
                    {{ detalle.nombre }}
                  </q-item-label>

                  <q-item-label caption>
                    Stock actual:
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

                    · Mínimo:
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

                  <div class="row q-col-gutter-md q-mt-xs">
                    <div class="col-8">
                      <q-input
                        v-model.number="
                          detalle.cantidad_solicitada
                        "
                        outlined
                        dense
                        type="number"
                        min="0.01"
                        step="0.01"
                        label="Cantidad solicitada"
                      />
                    </div>

                    <div class="col-4">
                      <q-input
                        :model-value="
                          mostrarUnidad(
                            detalle.unidad_medida,
                          )
                        "
                        outlined
                        dense
                        readonly
                        label="Unidad"
                      />
                    </div>
                  </div>
                </q-item-section>

                <q-item-section side>
                  <q-btn
                    flat
                    round
                    dense
                    color="negative"
                    icon="delete"
                    @click="
                      eliminarDetalle(
                        detalle.id_insumo,
                      )
                    "
                  >
                    <q-tooltip>
                      Quitar insumo
                    </q-tooltip>
                  </q-btn>
                </q-item-section>
              </q-item>
            </q-list>
          </template>

          <q-input
            v-model="form.descripcion"
            outlined
            autogrow
            maxlength="3000"
            class="q-mt-md"
            label="Descripción adicional"
            hint="Opcional"
          />
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
            icon="send"
            label="Enviar"
            :loading="guardando"
            @click="guardarSolicitud"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Detalle -->
    <q-dialog v-model="mostrarDialogoDetalle">
      <q-card
        v-if="solicitudSeleccionada"
        class="solicitud-dialog"
      >
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            {{ solicitudSeleccionada.asunto }}
          </div>

          <q-chip
            dense
            text-color="white"
            :color="
              colorTipo(solicitudSeleccionada.tipo)
            "
          >
            {{ textoTipo(solicitudSeleccionada.tipo) }}
          </q-chip>
        </q-card-section>

        <q-separator />

        <q-card-section>
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
            <span>Lectura</span>

            <q-chip
              dense
              text-color="white"
              :color="
                solicitudSeleccionada.visto
                  ? 'green'
                  : 'orange'
              "
            >
              {{
                solicitudSeleccionada.visto
                  ? 'VISTA'
                  : 'NO VISTA'
              }}
            </q-chip>
          </div>

          <div
            v-if="solicitudSeleccionada.visto_en"
            class="detalle-linea"
          >
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

            <div class="text-subtitle2 text-weight-bold q-mb-sm">
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
                    Cantidad solicitada:
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
                  </q-item-label>

                  <q-item-label caption>
                    Stock al solicitar:
                    {{
                      formatoCantidad(
                        detalle.stock_actual,
                      )
                    }}
                    /
                    mínimo:
                    {{
                      formatoCantidad(
                        detalle.stock_minimo,
                      )
                    }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </template>

          <template
            v-if="solicitudSeleccionada.descripcion"
          >
            <q-separator class="q-my-md" />

            <div class="text-subtitle2 text-weight-bold">
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
  </div>
</template>

<script src="./SolicitudesView.js"></script>

<style src="./SolicitudesView.css" scoped></style>