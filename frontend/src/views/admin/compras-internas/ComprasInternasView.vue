<template>
  <div class="compras-internas-page q-pa-md">
    <q-card class="compras-internas-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-md">
          <div>
            <div class="text-h5 text-weight-bold">
              Compras internas
            </div>

            <div class="text-grey-7">
              Controla dinero entregado, gastos, cambios y compras realizadas durante la jornada.
            </div>
          </div>

          <q-icon
            name="shopping_cart_checkout"
            size="54px"
            color="primary"
          />
        </div>
      </q-card-section>

      <q-separator />

        <div
          v-if="loadingInicial"
          class="column items-center q-pa-xl"
        >
          <q-spinner-dots
            color="primary"
            size="50px"
          />

          <div class="text-grey-7 q-mt-sm">
            Cargando compras internas...
          </div>
        </div>

        <template v-else>
          <q-banner
            v-if="!jornada"
            rounded
            class="bg-orange-1 text-orange-10"
          >
            No existe una jornada abierta para registrar compras internas.
          </q-banner>

          <template v-else>
            <q-card
              flat
              bordered
              class="jornada-card q-mb-md"
            >
              <q-card-section>
                <div class="row items-center justify-between q-gutter-md">
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
              <div class="col-12 col-sm-6 col-lg-3">
                <q-card flat bordered class="resumen-card">
                  <q-card-section>
                    <q-icon
                      name="pending_actions"
                      color="orange"
                      size="34px"
                    />

                    <div class="resumen-label">
                      Compras pendientes
                    </div>

                    <div class="resumen-valor">
                      {{ resumen.cantidadPendientes }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <div class="col-12 col-sm-6 col-lg-3">
                <q-card flat bordered class="resumen-card">
                  <q-card-section>
                    <q-icon
                      name="payments"
                      color="primary"
                      size="34px"
                    />

                    <div class="resumen-label">
                      Dinero entregado
                    </div>

                    <div class="resumen-valor">
                      Bs {{ formatoDinero(resumen.totalEntregado) }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <div class="col-12 col-sm-6 col-lg-3">
                <q-card flat bordered class="resumen-card">
                  <q-card-section>
                    <q-icon
                      name="shopping_bag"
                      color="negative"
                      size="34px"
                    />

                    <div class="resumen-label">
                      Gastos operativos
                    </div>

                    <div class="resumen-valor">
                      Bs {{ formatoDinero(resumen.totalGastado) }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>

              <div class="col-12 col-sm-6 col-lg-3">
                <q-card flat bordered class="resumen-card">
                  <q-card-section>
                    <q-icon
                      name="currency_exchange"
                      color="green"
                      size="34px"
                    />

                    <div class="resumen-label">
                      Cambio devuelto
                    </div>

                    <div class="resumen-valor">
                      Bs {{ formatoDinero(resumen.totalCambio) }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <div class="row justify-end q-mb-md">
              <q-btn
                v-if="authStore.tienePermiso('registrar_compras_internas')"
                unelevated
                color="primary"
                icon="add_shopping_cart"
                label="Nueva compra interna"
                @click="abrirDialogoNuevaCompra"
              />
            </div>

            <q-card flat bordered>
              <q-card-section>
                <div class="row items-center justify-between q-gutter-md">
                  <div>
                    <div class="text-h6 text-weight-bold">
                      Historial de compras
                    </div>

                    <div class="text-grey-7">
                      Compras pendientes, finalizadas y anuladas de la jornada.
                    </div>
                  </div>

                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="refresh"
                    :loading="loadingCompras"
                    @click="cargarDatos"
                  >
                    <q-tooltip>
                      Actualizar
                    </q-tooltip>
                  </q-btn>
                </div>
              </q-card-section>

              <q-separator />

              <q-card-section>
                <div class="row q-col-gutter-md q-mb-md">
                  <div class="col-12 col-md-6">
                    <q-select
                      v-model="filtros.estado"
                      outlined
                      clearable
                      emit-value
                      map-options
                      label="Estado"
                      :options="opcionesEstado"
                      @update:model-value="cargarCompras(1)"
                    />
                  </div>

                  <div class="col-12 col-md-6">
                    <q-select
                      v-model="filtros.categoria"
                      outlined
                      clearable
                      emit-value
                      map-options
                      label="Categoría"
                      :options="opcionesCategoria"
                      @update:model-value="cargarCompras(1)"
                    />
                  </div>
                </div>

                <q-table
                  flat
                  bordered
                  row-key="id_compra_interna"
                  :rows="compras"
                  :columns="columnas"
                  :loading="loadingCompras"
                  :pagination="{ rowsPerPage: 20 }"
                  hide-pagination
                  no-data-label="No existen compras internas registradas"
                >
                  <template #body-cell-salida="props">
                    <q-td :props="props">
                      {{ formatearFechaHora(props.row.fecha_hora_salida) }}
                    </q-td>
                  </template>

                  <template #body-cell-empleado="props">
                    <q-td :props="props">
                      <div class="text-weight-bold">
                        {{
                          props.row.empleado_comprador?.nombre ||
                          'Empleado no disponible'
                        }}
                      </div>

                      <div class="text-caption text-grey-7">
                        {{
                          props.row.empleado_comprador?.cargo ||
                          'Sin cargo'
                        }}
                      </div>
                    </q-td>
                  </template>

                  <template #body-cell-caja="props">
                    <q-td :props="props">
                      <div class="text-weight-bold">
                        Caja #{{ props.row.id_caja }}
                      </div>

                      <div class="text-caption text-grey-7">
                        {{
                          props.row.caja?.empleado?.nombre ||
                          'Cajero no disponible'
                        }}
                      </div>
                    </q-td>
                  </template>

                  <template #body-cell-categoria="props">
                    <q-td :props="props">
                      <q-chip
                        dense
                        color="blue-1"
                        text-color="blue-10"
                      >
                        {{ textoCategoria(props.row.categoria) }}
                      </q-chip>
                    </q-td>
                  </template>

                  <template #body-cell-dinero="props">
                    <q-td :props="props">
                      <div>
                        Inicial:
                        <b>
                          Bs {{ formatoDinero(props.row.monto_entregado_inicial) }}
                        </b>
                      </div>

                      <div v-if="Number(props.row.monto_adicional || 0) > 0">
                        Adicional:
                        <b class="text-orange">
                          Bs {{ formatoDinero(props.row.monto_adicional) }}
                        </b>
                      </div>

                      <div class="text-weight-bold">
                        Total:
                        Bs {{ formatoDinero(props.row.total_entregado) }}
                      </div>
                    </q-td>
                  </template>

                  <template #body-cell-resultado="props">
                    <q-td :props="props">
                      <template v-if="props.row.estado === 'FINALIZADA'">
                        <div>
                          Gastado:
                          <b class="text-negative">
                            Bs {{ formatoDinero(props.row.total_gastado) }}
                          </b>
                        </div>

                        <div>
                          Cambio:
                          <b class="text-positive">
                            Bs {{ formatoDinero(props.row.cambio_devuelto) }}
                          </b>
                        </div>
                      </template>

                      <template v-else-if="props.row.estado === 'ANULADA'">
                        <div>
                          Devuelto:
                          <b class="text-positive">
                            Bs {{ formatoDinero(props.row.cambio_devuelto) }}
                          </b>
                        </div>
                      </template>

                      <span v-else class="text-orange text-weight-bold">
                        Pendiente de rendición
                      </span>
                    </q-td>
                  </template>

                  <template #body-cell-estado="props">
                    <q-td :props="props">
                      <q-chip
                        dense
                        text-color="white"
                        :color="colorEstado(props.row.estado)"
                      >
                        {{ props.row.estado }}
                      </q-chip>
                    </q-td>
                  </template>

                  <template #body-cell-acciones="props">
                    <q-td :props="props">
                      <div class="row no-wrap q-gutter-xs">
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

                        <template v-if="props.row.estado === 'PENDIENTE'">
                          <q-btn
                            v-if="authStore.tienePermiso('registrar_compras_internas')"
                            flat
                            round
                            dense
                            color="orange"
                            icon="add_card"
                            @click="abrirDialogoDineroAdicional(props.row)"
                          >
                            <q-tooltip>
                              Agregar dinero
                            </q-tooltip>
                          </q-btn>

                          <q-btn
                            v-if="authStore.tienePermiso('registrar_compras_internas')"
                            flat
                            round
                            dense
                            color="green"
                            icon="task_alt"
                            @click="abrirDialogoFinalizar(props.row)"
                          >
                            <q-tooltip>
                              Finalizar compra
                            </q-tooltip>
                          </q-btn>

                          <q-btn
                            v-if="authStore.tienePermiso('registrar_compras_internas')"
                            flat
                            round
                            dense
                            color="negative"
                            icon="cancel"
                            @click="abrirDialogoAnular(props.row)"
                          >
                            <q-tooltip>
                              Anular compra
                            </q-tooltip>
                          </q-btn>
                        </template>
                      </div>
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
                    @update:model-value="cargarCompras"
                  />
                </div>
              </q-card-section>
            </q-card>
          </template>
        </template>
    </q-card>

    <!-- Nueva compra -->
    <q-dialog
      v-model="mostrarDialogoNuevaCompra"
      persistent
    >
      <q-card style="width: 720px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Nueva compra interna
          </div>

          <div class="text-grey-7">
            Entrega dinero a un empleado para realizar una compra.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-select
                v-model="formNuevaCompra.id_caja"
                outlined
                emit-value
                map-options
                label="Caja de origen"
                :options="opcionesCaja"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-select
                v-model="formNuevaCompra.id_empleado_comprador"
                outlined
                emit-value
                map-options
                label="Empleado comprador"
                :options="opcionesEmpleado"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-select
                v-model="formNuevaCompra.categoria"
                outlined
                emit-value
                map-options
                label="Categoría"
                :options="opcionesCategoria"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-input
                v-model.number="formNuevaCompra.monto_entregado_inicial"
                outlined
                type="number"
                min="0.01"
                step="0.01"
                prefix="Bs"
                label="Dinero entregado"
              />
            </div>

            <div class="col-12">
              <q-input
                v-model="formNuevaCompra.motivo"
                outlined
                maxlength="150"
                label="Motivo de la compra"
                hint="Ejemplo: Compra urgente de gas y esponjas"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-input
                v-model="formNuevaCompra.fecha_hora_salida"
                outlined
                type="datetime-local"
                label="Fecha y hora de salida"
              />
            </div>

            <div class="col-12">
              <q-input
                v-model="formNuevaCompra.observacion"
                outlined
                autogrow
                maxlength="1000"
                label="Observación"
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
            @click="cerrarDialogoNuevaCompra"
          />

          <q-btn
            unelevated
            color="primary"
            icon="save"
            label="Registrar"
            :loading="guardando"
            @click="registrarNuevaCompra"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Dinero adicional -->
    <q-dialog
      v-model="mostrarDialogoDinero"
      persistent
    >
      <q-card style="width: 520px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Agregar dinero
          </div>

          <div class="text-grey-7">
            Compra #{{ compraSeleccionada?.id_compra_interna }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-banner
            rounded
            class="bg-orange-1 text-orange-10 q-mb-md"
          >
            Total entregado actualmente:
            <b>
              Bs {{ formatoDinero(compraSeleccionada?.total_entregado) }}
            </b>
          </q-banner>

          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                v-model.number="formDineroAdicional.monto"
                outlined
                type="number"
                min="0.01"
                step="0.01"
                prefix="Bs"
                label="Monto adicional"
              />
            </div>

            <div class="col-12">
              <q-input
                v-model="formDineroAdicional.observacion"
                outlined
                autogrow
                maxlength="255"
                label="Motivo del dinero adicional"
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
            @click="cerrarDialogoDineroAdicional"
          />

          <q-btn
            unelevated
            color="orange"
            icon="add_card"
            label="Agregar dinero"
            :loading="guardando"
            @click="registrarDineroAdicional"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Finalizar compra -->
    <q-dialog
      v-model="mostrarDialogoFinalizar"
      persistent
    >
      <q-card style="width: 820px; max-width: 96vw">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Finalizar compra interna
          </div>

          <div class="text-grey-7">
            Registra los productos y el total real gastado.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-banner
            rounded
            class="bg-blue-1 text-blue-10 q-mb-md"
          >
            Total entregado:
            <b>
              Bs {{ formatoDinero(compraSeleccionada?.total_entregado) }}
            </b>
          </q-banner>

          <div
            v-for="(producto, index) in formFinalizar.productos"
            :key="index"
            class="producto-compra-row q-mb-md"
          >
            <div class="row q-col-gutter-md items-center">
              <div class="col-12 col-md-5">
                <q-input
                  v-model="producto.producto"
                  outlined
                  label="Producto comprado"
                  placeholder="Ejemplo: Garrafa"
                />
              </div>

              <div class="col-12 col-md-2">
                <q-input
                  v-model.number="producto.cantidad"
                  outlined
                  type="number"
                  min="0.01"
                  step="0.01"
                  label="Cantidad"
                />
              </div>

              <div class="col-12 col-md-2">
                <q-input
                  v-model.number="producto.precio_unitario"
                  outlined
                  type="number"
                  min="0"
                  step="0.01"
                  prefix="Bs"
                  label="Precio"
                />
              </div>

              <div class="col-10 col-md-2">
                <q-input
                  :model-value="subtotalProducto(producto)"
                  outlined
                  readonly
                  prefix="Bs"
                  label="Subtotal"
                />
              </div>

              <div class="col-2 col-md-1">
                <q-btn
                  flat
                  round
                  color="negative"
                  icon="delete"
                  :disable="formFinalizar.productos.length === 1"
                  @click="eliminarProducto(index)"
                />
              </div>
            </div>
          </div>

          <q-btn
            flat
            color="primary"
            icon="add"
            label="Agregar producto"
            class="q-mb-md"
            @click="agregarProducto"
          />

          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-4">
              <q-input
                :model-value="formatoDinero(totalProductos)"
                outlined
                readonly
                prefix="Bs"
                label="Total calculado"
              />
            </div>

            <div class="col-12 col-md-4">
              <q-input
                :model-value="formatoDinero(cambioCalculado)"
                outlined
                readonly
                prefix="Bs"
                label="Cambio esperado"
                :error="cambioCalculado < 0"
                :error-message="
                  cambioCalculado < 0
                    ? `Faltan Bs ${formatoDinero(Math.abs(cambioCalculado))}`
                    : ''
                "
              />
            </div>

            <div class="col-12 col-md-4">
              <q-input
                v-model="formFinalizar.fecha_hora_regreso"
                outlined
                type="datetime-local"
                label="Fecha y hora de regreso"
              />
            </div>

            <div class="col-12">
              <q-input
                v-model="formFinalizar.observacion"
                outlined
                autogrow
                maxlength="1000"
                label="Observación final"
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
            @click="cerrarDialogoFinalizar"
          />

          <q-btn
            unelevated
            color="green"
            icon="task_alt"
            label="Finalizar compra"
            :loading="guardando"
            :disable="cambioCalculado < 0"
            @click="finalizarCompra"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Anular -->
    <q-dialog
      v-model="mostrarDialogoAnular"
      persistent
    >
      <q-card style="width: 520px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Anular compra
          </div>

          <div class="text-grey-7">
            Todo el dinero entregado deberá regresar a la caja.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-input
            v-model="formAnular.observacion"
            outlined
            autogrow
            maxlength="1000"
            label="Motivo de la anulación"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            color="grey-8"
            label="Cancelar"
            :disable="guardando"
            @click="cerrarDialogoAnular"
          />

          <q-btn
            unelevated
            color="negative"
            icon="cancel"
            label="Anular compra"
            :loading="guardando"
            @click="anularCompra"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Detalle -->
    <q-dialog v-model="mostrarDialogoDetalle">
      <q-card style="width: 720px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Detalle de compra #{{ compraSeleccionada?.id_compra_interna }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section v-if="compraSeleccionada">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <div class="detalle-label">Empleado</div>
              <div class="detalle-value">
                {{
                  compraSeleccionada.empleado_comprador?.nombre ||
                  'No disponible'
                }}
              </div>
            </div>

            <div class="col-12 col-md-6">
              <div class="detalle-label">Categoría</div>
              <div class="detalle-value">
                {{ textoCategoria(compraSeleccionada.categoria) }}
              </div>
            </div>

            <div class="col-12">
              <div class="detalle-label">Motivo</div>
              <div class="detalle-value">
                {{ compraSeleccionada.motivo }}
              </div>
            </div>

            <div class="col-12 col-md-4">
              <div class="detalle-label">Entregado</div>
              <div class="detalle-value">
                Bs {{ formatoDinero(compraSeleccionada.total_entregado) }}
              </div>
            </div>

            <div class="col-12 col-md-4">
              <div class="detalle-label">Gastado</div>
              <div class="detalle-value">
                Bs {{ formatoDinero(compraSeleccionada.total_gastado) }}
              </div>
            </div>

            <div class="col-12 col-md-4">
              <div class="detalle-label">Cambio</div>
              <div class="detalle-value">
                Bs {{ formatoDinero(compraSeleccionada.cambio_devuelto) }}
              </div>
            </div>
          </div>

          <q-separator class="q-my-md" />

          <div class="text-subtitle1 text-weight-bold q-mb-sm">
            Productos comprados
          </div>

          <q-list
            v-if="compraSeleccionada.productos_comprados?.length"
            bordered
            separator
          >
            <q-item
              v-for="(producto, index) in compraSeleccionada.productos_comprados"
              :key="index"
            >
              <q-item-section>
                <q-item-label>
                  {{ producto.producto }}
                </q-item-label>

                <q-item-label caption>
                  {{ formatoCantidad(producto.cantidad) }}
                  × Bs {{ formatoDinero(producto.precio_unitario) }}
                </q-item-label>
              </q-item-section>

              <q-item-section side>
                Bs {{ formatoDinero(producto.subtotal) }}
              </q-item-section>
            </q-item>
          </q-list>

          <div
            v-else
            class="text-grey-6"
          >
            No existen productos registrados.
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

<script src="./ComprasInternasView.js"></script>

<style scoped src="./ComprasInternasView.css"></style>