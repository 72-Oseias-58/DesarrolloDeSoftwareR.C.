<template>
  <div class="cajero-module-page nuevo-pedido-page">
    <div class="nuevo-pedido-header q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Nuevo pedido</div>
        <div class="text-grey-7">
          Registra productos, bebidas, guarniciones y venta manual de pura carne.
        </div>
      </div>

      <div class="row q-gutter-sm">
        <q-btn
          color="deep-orange"
          icon="set_meal"
          label="Pura carne"
          unelevated
          @click="abrirDialogoPuraCarne"
        />

        <q-btn
          color="primary"
          icon="refresh"
          label="Actualizar"
          unelevated
          :loading="cargandoProductos || cargandoJornada"
          @click="actualizarPantalla"
        />
      </div>
    </div>

    <q-card class="cajero-module-card q-mb-md">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-sm">
          <div>
            <div class="text-h6 text-weight-bold">Producción diaria disponible</div>

            <div class="text-grey-7">Carne disponible para platos y venta manual.</div>
          </div>

          <q-btn
            flat
            round
            color="primary"
            icon="refresh"
            :loading="cargandoJornada"
            @click="cargarJornadaActual"
          >
            <q-tooltip>Actualizar producción diaria</q-tooltip>
          </q-btn>
        </div>

        <div v-if="cargandoJornada" class="q-mt-md">
          <q-spinner color="primary" size="32px" />
          <span class="q-ml-sm text-grey-7">Cargando producción...</span>
        </div>

        <q-banner v-else-if="errorJornada" rounded class="bg-red-1 text-negative q-mt-md">
          {{ errorJornada }}
        </q-banner>

        <q-banner v-else-if="!jornadaActual" rounded class="bg-orange-1 text-orange-10 q-mt-md">
          No existe una jornada abierta para hoy.
        </q-banner>

        <div v-else-if="obtenerControlCarneJornada().length" class="row q-col-gutter-md q-mt-sm">
          <div
            v-for="control in obtenerControlCarneJornada()"
            :key="control.id_control_carne"
            class="col-12 col-md-6"
          >
            <q-card flat bordered>
              <q-card-section>
                <div class="row items-start no-wrap">
                  <q-icon
                    name="local_fire_department"
                    color="deep-orange"
                    size="38px"
                    class="q-mr-md"
                  />

                  <div class="col">
                    <div class="text-subtitle1 text-weight-bold">
                      {{ nombreTipoCarne(control) }}
                    </div>

                    <div class="text-grey-7 q-mt-xs">
                      Cruces:
                      <b>{{ formatoCantidad(control.cantidad_cruces) }}</b>
                    </div>
                    <div v-if="rangoPlatosChancho(control)" class="text-grey-7 q-mt-xs">
                      Estimado:
                      <b>{{ rangoPlatosChancho(control) }}</b>
                    </div>

                    <div v-if="esChanchoControl(control)" class="text-grey-7 q-mt-xs">
                      CostillasGrandes:
                      <b>{{ formatoCantidad(costillasGrandesChancho(control)) }}</b>
                    </div>

                    <div v-if="esChanchoControl(control)" class="text-grey-7 q-mt-xs">
                      Estimado:
                      <b>{{ rangoMinCostillasChancho(control) }}</b>
                    </div>

                    <div class="text-grey-7 q-mt-xs">
                      Inicial:
                      <b>
                        {{ formatoCantidad(control.cantidad_base_inicial) }}
                        {{ unidadBaseCarne(control) }}
                      </b>
                    </div>

                    <div class="text-weight-bold q-mt-xs">
                      Actual:
                      <span
                        :class="
                          Number(control.cantidad_base_actual || 0) <= 0
                            ? 'text-negative'
                            : 'text-positive'
                        "
                      >
                        {{ formatoCantidad(control.cantidad_base_actual) }}
                        {{ unidadBaseCarne(control) }}
                      </span>
                    </div>

                    <q-linear-progress
                      rounded
                      size="12px"
                      class="q-mt-md"
                      :value="porcentajeRestanteCarne(control) / 100"
                      :color="
                        Number(control.cantidad_base_actual || 0) <= 0 ? 'negative' : 'positive'
                      "
                    />

                    <div class="text-caption text-grey-7 q-mt-xs">
                      Restante:
                      {{ formatoCantidad(porcentajeRestanteCarne(control)) }}%
                    </div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <q-banner v-else rounded class="bg-orange-1 text-orange-10 q-mt-md">
          La jornada existe, pero no tiene control de carne registrado.
        </q-banner>
      </q-card-section>
    </q-card>

    <div class="row q-col-gutter-lg">
      <div class="col-12 col-lg-7">
        <q-card class="cajero-module-card">
          <q-card-section>
            <div class="row items-center justify-between q-gutter-sm">
              <div>
                <div class="text-h6 text-weight-bold">Productos disponibles</div>

                <div class="text-grey-7">Platos y bebidas disponibles para vender.</div>
              </div>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div v-if="cargandoProductos" class="estado-catalogo">
              <q-spinner color="primary" size="46px" />
              <div class="q-mt-md text-grey-7">Cargando productos...</div>
            </div>

            <q-banner v-else-if="errorProductos" rounded class="bg-red-1 text-negative">
              {{ errorProductos }}
            </q-banner>

            <div v-else-if="productos.length === 0" class="estado-catalogo">
              <q-icon name="inventory_2" size="54px" color="grey-5" />

              <div class="q-mt-md text-grey-7">No existen productos registrados.</div>
            </div>

            <div v-else class="row q-col-gutter-md">
              <div
                v-for="producto in productos"
                :key="producto.id_producto"
                class="col-12 col-sm-6"
              >
                <q-card flat bordered class="producto-card">
                  <q-img
                    v-if="producto.imagen_url"
                    :src="producto.imagen_url"
                    height="150px"
                    fit="cover"
                  />

                  <div v-else class="row flex-center bg-grey-2" style="height: 150px">
                    <q-icon :name="iconoProducto(producto)" size="56px" color="grey-5" />
                  </div>

                  <q-card-section>
                    <div class="row no-wrap items-start">
                      <q-icon
                        :name="iconoProducto(producto)"
                        size="34px"
                        color="primary"
                        class="q-mr-md"
                      />

                      <div class="col">
                        <div class="text-subtitle1 text-weight-bold">
                          {{ producto.nombre }}
                        </div>

                        <div class="producto-descripcion">
                          {{ producto.descripcion || 'Sin descripción' }}
                        </div>

                        <div class="q-mt-sm row q-gutter-xs">
                          <q-chip dense color="orange-1" text-color="orange-10" icon="inventory">
                            {{ etiquetaControlProducto(producto) }}
                          </q-chip>

                          <q-chip
                            v-if="producto.consume_carne"
                            dense
                            color="red-1"
                            text-color="red-10"
                            icon="local_fire_department"
                          >
                            Consume carne
                          </q-chip>

                          <q-chip
                            v-if="esProductoIndependiente(producto)"
                            dense
                            color="green-1"
                            text-color="green-10"
                            icon="restaurant"
                          >
                            Independiente
                          </q-chip>
                        </div>

                        <div
                          v-if="textoStockProducto(producto)"
                          class="q-mt-sm text-caption"
                          :class="estaAgotado(producto) ? 'text-negative' : 'text-grey-7'"
                        >
                          {{ textoStockProducto(producto) }}
                        </div>
                      </div>
                    </div>

                    <div class="row items-center justify-between q-mt-md">
                      <div class="producto-precio">Bs {{ formatoDinero(producto.precio) }}</div>

                      <q-btn
                        color="primary"
                        icon="add_shopping_cart"
                        label="Agregar"
                        unelevated
                        dense
                        :disable="!puedeAgregarProducto(producto)"
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
                <div class="text-h6 text-weight-bold">Pedido actual</div>

                <div class="text-grey-7">{{ cantidadProductos }} item(s)</div>
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

            <q-btn
              color="deep-orange"
              icon="set_meal"
              label="Agregar pura carne"
              class="full-width q-mb-md"
              unelevated
              @click="abrirDialogoPuraCarne"
            />

            <div v-if="detalles.length === 0" class="pedido-vacio">
              <q-icon name="add_shopping_cart" size="56px" color="grey-5" />

              <div class="text-weight-bold q-mt-md">El pedido está vacío</div>

              <div class="text-grey-7">Agrega productos o pura carne al pedido.</div>
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
                <div v-if="detalle.esPuraCarne">
                  <div class="row no-wrap items-start">
                    <div class="col">
                      <div class="text-subtitle1 text-weight-bold">
                        Pura carne - {{ etiquetaTipoCarne(detalle.tipo_carne_manual) }}
                      </div>

                      <div class="text-deep-orange text-weight-bold">
                        Bs {{ formatoDinero(detalle.precio_unitario) }}
                      </div>

                      <div class="text-grey-7 q-mt-xs">
                        Descuento:
                        {{ formatoCantidad(consumoBasePuraCarne(detalle)) }}
                        {{ unidadBasePuraCarne(detalle.tipo_carne_manual) }}
                      </div>

                      <div class="text-caption text-grey-7">
                        Venta:
                        {{ formatoCantidad(detalle.cantidad_carne_manual) }}
                        {{
                          etiquetaUnidadCarne(
                            detalle.tipo_carne_manual,
                            detalle.unidad_carne_manual,
                          )
                        }}
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
                      <q-tooltip>Eliminar pura carne</q-tooltip>
                    </q-btn>
                  </div>

                  <div class="row q-col-gutter-sm q-mt-md">
                    <div class="col-12 col-sm-6">
                      <q-select
                        v-model="detalle.tipo_carne_manual"
                        outlined
                        emit-value
                        map-options
                        label="Tipo de carne"
                        :options="tiposCarneManual"
                        @update:model-value="cambiarTipoCarneDetalle(detalle)"
                      />
                    </div>

                    <div class="col-12 col-sm-6">
                      <q-select
                        v-model="detalle.unidad_carne_manual"
                        outlined
                        emit-value
                        map-options
                        label="Unidad"
                        :options="opcionesUnidadCarne(detalle.tipo_carne_manual)"
                      />
                    </div>

                    <div class="col-12 col-sm-6">
                      <q-input
                        v-model.number="detalle.cantidad_carne_manual"
                        outlined
                        type="number"
                        min="0.01"
                        step="0.01"
                        label="Cantidad"
                      />
                    </div>

                    <div class="col-12 col-sm-6">
                      <q-input
                        v-model.number="detalle.precio_unitario"
                        outlined
                        type="number"
                        min="0.01"
                        step="0.01"
                        label="Precio de venta"
                        prefix="Bs"
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
                </div>

                <div v-else>
                  <div class="row no-wrap items-start">
                    <div class="col">
                      <div class="text-subtitle1 text-weight-bold">
                        {{ detalle.producto.nombre }}
                      </div>

                      <div class="text-primary text-weight-bold">
                        Bs {{ formatoDinero(detalle.producto.precio) }}
                      </div>

                      <div
                        v-if="textoStockProducto(detalle.producto)"
                        class="text-caption text-grey-7 q-mt-xs"
                      >
                        {{ textoStockProducto(detalle.producto) }}
                      </div>

                      <div
                        v-if="textoConsumoProducto(detalle.producto)"
                        class="text-caption text-deep-orange q-mt-xs"
                      >
                        {{ textoConsumoProducto(detalle.producto) }}
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
                      :disable="!puedeAumentarCantidad(detalle)"
                      @click="aumentarCantidad(detalle)"
                    />

                    <div class="subtotal">Bs {{ formatoDinero(subtotalDetalle(detalle)) }}</div>
                  </div>

                  <q-banner
                    v-if="tieneGuarniciones(detalle.producto)"
                    rounded
                    class="bg-orange-1 text-orange-10 q-mt-md"
                  >
                    Los {{ detalle.cantidad }} plato(s) de este grupo tendrán la misma preparación.
                  </q-banner>

                  <div v-if="tieneGuarniciones(detalle.producto)" class="q-mt-md">
                    <q-select
                      v-model="detalle.tipoPreparacion"
                      outlined
                      emit-value
                      map-options
                      label="Preparación"
                      :options="opcionesPreparacion"
                      @update:model-value="aplicarPreparacion(detalle)"
                    />

                    <div v-if="detalle.tipoPreparacion !== 'PERSONALIZADO'" class="q-mt-md">
                      <div class="text-weight-bold q-mb-sm">Guarniciones incluidas</div>

                      <div class="guarniciones-chips">
                        <q-chip
                          v-for="guarnicion in obtenerGuarnicionesSeleccionadas(detalle)"
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
                      <div class="text-weight-bold q-mb-sm">Seleccionar guarniciones</div>

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
                </div>
              </q-card-section>
            </q-card>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <div class="row items-center justify-between">
              <div class="text-h6 text-weight-bold">Total</div>

              <div class="pedido-total">Bs {{ formatoDinero(totalPedido) }}</div>
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

    <q-dialog v-model="mostrarDialogoPuraCarne" persistent>
      <q-card style="width: 460px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6 text-weight-bold">Agregar pura carne</div>

          <div class="text-grey-7">Registra una venta manual y descuenta producción diaria.</div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-6">
              <q-select
                v-model="formPuraCarne.tipo_carne_manual"
                outlined
                emit-value
                map-options
                label="Tipo de carne"
                :options="tiposCarneManual"
                @update:model-value="cambiarTipoCarneForm"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-select
                v-model="formPuraCarne.unidad_carne_manual"
                outlined
                emit-value
                map-options
                label="Unidad"
                :options="opcionesUnidadCarne(formPuraCarne.tipo_carne_manual)"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model.number="formPuraCarne.cantidad_carne_manual"
                outlined
                type="number"
                min="0.01"
                step="0.01"
                label="Cantidad"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model.number="formPuraCarne.precio_unitario"
                outlined
                type="number"
                min="0.01"
                step="0.01"
                label="Precio de venta"
                prefix="Bs"
              />
            </div>

            <div class="col-12">
              <q-banner rounded class="bg-orange-1 text-orange-10">
                Se descontará:
                <b>
                  {{ formatoCantidad(consumoBaseFormPuraCarne) }}
                  {{ unidadBasePuraCarne(formPuraCarne.tipo_carne_manual) }}
                </b>
              </q-banner>
            </div>

            <div class="col-12">
              <q-input
                v-model="formPuraCarne.observacion"
                outlined
                autogrow
                maxlength="255"
                label="Observación opcional"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" @click="cerrarDialogoPuraCarne" />

          <q-btn
            unelevated
            label="Agregar"
            color="deep-orange"
            icon="add"
            @click="agregarPuraCarne"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script src="./NuevoPedidoView.js"></script>

<style src="./NuevoPedidoView.css" scoped></style>
