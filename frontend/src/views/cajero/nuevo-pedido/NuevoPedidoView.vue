<template>
  <div class="cajero-module-page nuevo-pedido-page">
    <div class="nuevo-pedido-header q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Nuevo pedido</div>
        <div class="text-grey-7">
          Registra productos y grupos con distintas guarniciones.
        </div>
      </div>

      <q-btn
        color="primary"
        icon="refresh"
        label="Actualizar"
        unelevated
        :loading="cargandoProductos"
        @click="cargarProductos"
      />
    </div>

    <div class="row q-col-gutter-lg">
      <div class="col-12 col-lg-7">
        <q-card class="cajero-module-card">
          <q-card-section>
            <div class="text-h6 text-weight-bold">
              Productos disponibles
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div v-if="cargandoProductos" class="estado-catalogo">
              <q-spinner color="primary" size="46px" />
              <div class="q-mt-md text-grey-7">
                Cargando productos...
              </div>
            </div>

            <q-banner
              v-else-if="errorProductos"
              rounded
              class="bg-red-1 text-negative"
            >
              {{ errorProductos }}
            </q-banner>

            <div
              v-else-if="productos.length === 0"
              class="estado-catalogo"
            >
              <q-icon name="inventory_2" size="54px" color="grey-5" />
              <div class="q-mt-md text-grey-7">
                No existen productos registrados.
              </div>
            </div>

            <div v-else class="row q-col-gutter-md">
              <div
                v-for="producto in productos"
                :key="producto.id_producto"
                class="col-12 col-sm-6"
              >
                <q-card flat bordered class="producto-card">
                  <q-card-section>
                    <div class="row no-wrap items-start">
                      <q-icon
                        :name="iconoProducto(producto)"
                        size="38px"
                        color="primary"
                        class="q-mr-md"
                      />

                      <div class="col">
                        <div class="text-subtitle1 text-weight-bold">
                          {{ producto.nombre }}
                        </div>

                        <div class="producto-descripcion">
                          {{ producto.descripcion }}
                        </div>
                      </div>
                    </div>

                    <div class="row items-center justify-between q-mt-md">
                      <div class="producto-precio">
                        Bs {{ formatoDinero(producto.precio) }}
                      </div>

                      <q-btn
                        color="primary"
                        icon="add_shopping_cart"
                        label="Agregar"
                        unelevated
                        @click="agregarProducto(producto)"
                      />
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-lg-5">
        <q-card class="cajero-module-card pedido-card">
          <q-card-section>
            <div class="row items-center justify-between">
              <div>
                <div class="text-h6 text-weight-bold">
                  Pedido actual
                </div>
                <div class="text-grey-7">
                  {{ cantidadProductos }} producto(s)
                </div>
              </div>

              <q-icon name="shopping_cart" size="36px" color="primary" />
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <q-select
              v-model="tipoConsumo"
              outlined
              emit-value
              map-options
              label="Tipo de consumo"
              :options="tiposConsumo"
              class="q-mb-md"
            />

            <div v-if="detalles.length === 0" class="pedido-vacio">
              <q-icon
                name="add_shopping_cart"
                size="56px"
                color="grey-5"
              />

              <div class="text-weight-bold q-mt-md">
                El pedido está vacío
              </div>

              <div class="text-grey-7">
                Agrega productos desde el catálogo.
              </div>
            </div>

            <q-card
              v-for="(detalle, indice) in detalles"
              v-else
              :key="detalle.uid"
              flat
              bordered
              class="detalle-card q-mb-md"
            >
              <q-card-section>
                <div class="row no-wrap items-start">
                  <div class="col">
                    <div class="text-subtitle1 text-weight-bold">
                      {{ detalle.producto.nombre }}
                    </div>

                    <div class="text-primary text-weight-bold">
                      Bs {{ formatoDinero(detalle.producto.precio) }}
                    </div>
                  </div>

                  <q-btn
                    flat
                    round
                    dense
                    icon="delete"
                    color="negative"
                    @click="eliminarDetalle(indice)"
                  >
                    <q-tooltip>Eliminar grupo</q-tooltip>
                  </q-btn>
                </div>

                <div class="cantidad-control q-mt-md">
                  <q-btn
                    round
                    flat
                    dense
                    icon="remove"
                    color="primary"
                    :disable="detalle.cantidad <= 1"
                    @click="disminuirCantidad(detalle)"
                  />

                  <div class="cantidad-valor">
                    {{ detalle.cantidad }}
                  </div>

                  <q-btn
                    round
                    flat
                    dense
                    icon="add"
                    color="primary"
                    :disable="detalle.cantidad >= 100"
                    @click="aumentarCantidad(detalle)"
                  />

                  <div class="subtotal">
                    Bs {{ formatoDinero(subtotalDetalle(detalle)) }}
                  </div>
                </div>

                <q-banner
                  rounded
                  class="bg-orange-1 text-orange-10 q-mt-md"
                >
                  Los {{ detalle.cantidad }} plato(s) de este grupo tendrán
                  la misma preparación.
                </q-banner>

                <div
                  v-if="tieneGuarniciones(detalle.producto)"
                  class="q-mt-md"
                >
                  <q-select
                    v-model="detalle.tipoPreparacion"
                    outlined
                    emit-value
                    map-options
                    label="Preparación"
                    :options="opcionesPreparacion"
                    @update:model-value="aplicarPreparacion(detalle)"
                  />

                  <div
                    v-if="detalle.tipoPreparacion !== 'PERSONALIZADO'"
                    class="q-mt-md"
                  >
                    <div class="text-weight-bold q-mb-sm">
                      Guarniciones incluidas
                    </div>

                    <div class="guarniciones-chips">
                      <q-chip
                        v-for="guarnicion in obtenerGuarnicionesSeleccionadas(
                          detalle
                        )"
                        :key="guarnicion.id_guarnicion"
                        color="orange-1"
                        text-color="orange-10"
                        icon="check"
                      >
                        {{ guarnicion.nombre }}
                      </q-chip>
                    </div>
                  </div>

                  <div v-else class="q-mt-md">
                    <div class="text-weight-bold q-mb-sm">
                      Seleccionar guarniciones
                    </div>

                    <q-option-group
                      v-model="detalle.guarniciones"
                      type="checkbox"
                      color="primary"
                      :options="opcionesGuarniciones(detalle.producto)"
                    />
                  </div>
                </div>

                <q-input
                  v-model="detalle.observacion"
                  outlined
                  autogrow
                  maxlength="255"
                  label="Observación opcional"
                  class="q-mt-md"
                />

                <q-btn
                  v-if="tieneGuarniciones(detalle.producto)"
                  flat
                  color="primary"
                  icon="call_split"
                  label="Agregar otra combinación"
                  class="full-width q-mt-md"
                  @click="duplicarCombinacion(detalle)"
                />
              </q-card-section>
            </q-card>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div class="row items-center justify-between">
              <div class="text-h6 text-weight-bold">Total</div>

              <div class="pedido-total">
                Bs {{ formatoDinero(totalPedido) }}
              </div>
            </div>

            <q-btn
              color="primary"
              icon="save"
              label="Registrar pedido"
              class="full-width q-mt-md"
              unelevated
              size="lg"
              :loading="registrandoPedido"
              :disable="detalles.length === 0"
              @click="confirmarPedido"
            />
          </q-card-section>
        </q-card>
      </div>
    </div>
  </div>
</template>

<script src="./NuevoPedidoView.js"></script>

<style src="./NuevoPedidoView.css" scoped></style>