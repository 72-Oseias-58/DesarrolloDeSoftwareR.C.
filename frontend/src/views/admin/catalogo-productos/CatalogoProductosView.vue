<template>
  <div class="cajero-module-page catalogo-productos-page">
    <div class="catalogo-header q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Catálogo de productos</div>
        <div class="text-grey-7">
          Administración de platos, bebidas, imágenes, guarniciones y consumos de carne.
        </div>
      </div>

      <div class="row q-gutter-sm">
        <q-btn
          v-if="authStore.tienePermiso('crear_catalogo_pedidos')"
          color="primary"
          icon="restaurant"
          label="Nuevo plato"
          unelevated
          @click="abrirDialogoNuevoProducto('PLATO')"
        />

        <q-btn
          v-if="authStore.tienePermiso('crear_catalogo_pedidos')"
          color="blue"
          icon="local_drink"
          label="Nueva bebida"
          unelevated
          @click="abrirDialogoNuevoProducto('BEBIDA')"
        />

        <q-btn
          color="grey-8"
          icon="refresh"
          label="Actualizar"
          unelevated
          :loading="cargandoProductos"
          @click="cargarProductos"
        />
      </div>
    </div>

    <q-card class="cajero-module-card">
      <q-card-section>
        <div class="row items-center justify-between q-gutter-sm">
          <div>
            <div class="text-h6 text-weight-bold">Productos registrados</div>

            <div class="text-grey-7">Platos y bebidas que aparecen en nuevo pedido.</div>
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
            class="col-12 col-sm-6 col-md-4"
          >
            <q-card flat bordered class="producto-card">
              <q-img
                v-if="producto.imagen_url"
                :src="producto.imagen_url"
                height="170px"
                fit="cover"
              />

              <div v-else class="row flex-center bg-grey-2" style="height: 170px">
                <q-icon :name="iconoProducto(producto)" size="62px" color="grey-5" />
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

                    <div class="producto-precio q-mt-sm">
                      Bs {{ formatoDinero(producto.precio) }}
                    </div>

                    <div v-if="usaInventario(producto)" class="text-grey-8 q-mt-xs">
                      Stock:
                      {{
                        producto.stock_actual === null || producto.stock_actual === undefined
                          ? 'Sin inventario'
                          : formatoCantidad(producto.stock_actual)
                      }}
                    </div>

                    <div class="q-mt-sm row q-gutter-xs">
                      <q-chip
                        dense
                        color="blue-1"
                        text-color="blue-10"
                        :icon="esBebida(producto) ? 'local_drink' : 'restaurant'"
                      >
                        {{ producto.tipo_producto }}
                      </q-chip>

                      <q-chip dense color="orange-1" text-color="orange-10" icon="inventory">
                        {{ producto.prioridad_stock || 'SIN_STOCK' }}
                      </q-chip>

                      <q-chip
                        v-if="producto.consume_carne"
                        dense
                        color="red-1"
                        text-color="red-10"
                        icon="local_fire_department"
                      >
                        Producción diaria
                      </q-chip>

                      <q-chip
                        v-if="usaInventario(producto)"
                        dense
                        :color="Number(producto.stock_actual || 0) <= 0 ? 'red-1' : 'green-1'"
                        :text-color="
                          Number(producto.stock_actual || 0) <= 0 ? 'negative' : 'positive'
                        "
                        :icon="Number(producto.stock_actual || 0) <= 0 ? 'block' : 'check_circle'"
                      >
                        {{ Number(producto.stock_actual || 0) <= 0 ? 'Agotado' : 'Disponible' }}
                      </q-chip>
                    </div>
                  </div>
                </div>
              </q-card-section>

              <q-separator />

              <q-card-actions
                v-if="
                  authStore.tienePermiso('editar_catalogo_pedidos') ||
                  authStore.tienePermiso('eliminar_catalogo_pedidos')
                "
                align="right"
              >
                <q-btn
                  v-if="authStore.tienePermiso('editar_catalogo_pedidos')"
                  flat
                  color="primary"
                  icon="edit"
                  label="Editar"
                  @click="abrirDialogoEditarProducto(producto)"
                />

                <q-btn
                  v-if="authStore.tienePermiso('eliminar_catalogo_pedidos')"
                  flat
                  color="negative"
                  icon="delete"
                  label="Eliminar"
                  @click="confirmarEliminarProducto(producto)"
                />
              </q-card-actions>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-dialog v-model="mostrarDialogoProducto" persistent>
      <q-card style="width: 720px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            {{ tituloDialogoProducto }}
          </div>

          <div class="text-grey-7">Este producto aparecerá en la pantalla de nuevo pedido.</div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                v-model="formProducto.nombre"
                outlined
                label="Nombre"
                hint="Ej: Plato de chancho Bs 50, Coca Cola 2 litros, Chaquećan"
              />
            </div>

            <div class="col-12">
              <q-input
                v-model="formProducto.descripcion"
                outlined
                autogrow
                maxlength="255"
                label="Descripción"
                hint="Ej: Refresco de 2 litros, plato tradicional, etc."
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-input
                v-model.number="formProducto.precio"
                outlined
                type="number"
                min="0.01"
                step="0.01"
                label="Precio"
                prefix="Bs"
              />
            </div>

            <div class="col-12 col-sm-6">
              <q-file
                v-model="formProducto.imagen"
                outlined
                clearable
                accept=".jpg,.jpeg,.png,.webp,image/*"
                label="Imagen"
              >
                <template #prepend>
                  <q-icon name="image" />
                </template>
              </q-file>
            </div>

            <div v-if="formProducto.imagen_url" class="col-12">
              <div class="text-weight-bold q-mb-sm">Imagen actual</div>

              <q-img
                :src="formProducto.imagen_url"
                height="180px"
                fit="cover"
                class="rounded-borders"
              />

              <q-checkbox
                v-if="esDialogoEdicion"
                v-model="formProducto.eliminar_imagen"
                label="Eliminar imagen actual"
                color="negative"
                class="q-mt-sm"
              />
            </div>

            <template v-if="esDialogoPlato">
              <div class="col-12">
                <q-select
                  v-model="formProducto.modo_control"
                  outlined
                  emit-value
                  map-options
                  label="Tipo de control del plato"
                  :options="modosControlPlato"
                />
              </div>

              <template v-if="esPlatoConCarne">
                <div class="col-12">
                  <q-banner rounded class="bg-orange-1 text-orange-10">
                    Usar para platos de chancho o pollo a la cruz que descuentan producción diaria.
                  </q-banner>
                </div>

                <div class="col-12 col-sm-6">
                  <q-input
                    v-model.number="formProducto.consumo_chancho"
                    outlined
                    type="number"
                    min="0"
                    step="0.01"
                    label="Consumo de chancho"
                    hint="Cantidad de MinCostillas. Ej: 1.5, 2 o 3"
                  />
                </div>

                <div class="col-12 col-sm-6">
                  <q-input
                    v-model.number="formProducto.consumo_pollo"
                    outlined
                    type="number"
                    min="0"
                    step="0.01"
                    label="Consumo de pollo"
                    hint="Cantidad en pollos. Ej: 0.25, 0.5 o 1"
                  />
                </div>

                <div class="col-12">
                  <div class="text-weight-bold q-mb-sm">Guarniciones disponibles</div>

                  <q-option-group
                    v-model="formProducto.guarniciones"
                    type="checkbox"
                    color="primary"
                    :options="opcionesGuarnicionesCatalogo"
                  />
                </div>
              </template>

              <template v-if="esPlatoSinStock">
                <div class="col-12">
                  <q-banner rounded class="bg-grey-2 text-grey-9">
                    Usar para platos como chaquećan, sopa u otros platos que se venden sin controlar
                    stock.
                  </q-banner>
                </div>
              </template>
            </template>

            <template v-if="esDialogoBebida">
              <div class="col-12">
                <q-banner rounded class="bg-blue-1 text-blue-10">
                  La bebida tendrá control de stock. Se creará como producto de venta y también como
                  insumo de inventario para la sucursal actual.
                </q-banner>
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="formProducto.stock_inicial"
                  outlined
                  type="number"
                  min="0"
                  step="1"
                  label="Stock inicial"
                  hint="Ej: 24 unidades"
                  :disable="esDialogoEdicion"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model="formProducto.unidad_medida"
                  outlined
                  label="Unidad de medida"
                  hint="Ej: UNIDAD, botella, lata"
                />
              </div>

              <div v-if="esDialogoEdicion" class="col-12">
                <q-banner rounded class="bg-grey-2 text-grey-9">
                  Para aumentar o reducir stock usa el módulo Stock de bebidas.
                </q-banner>
              </div>
            </template>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey-8" @click="cerrarDialogoProducto" />

          <q-btn
            v-if="
              esDialogoEdicion
                ? authStore.tienePermiso('editar_catalogo_pedidos')
                : authStore.tienePermiso('crear_catalogo_pedidos')
            "
            unelevated
            color="primary"
            icon="save"
            :label="esDialogoEdicion ? 'Actualizar' : 'Guardar'"
            :loading="guardandoProducto"
            @click="guardarProductoCatalogo"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
</template>

<script src="./CatalogoProductosView.js"></script>

<style src="./CatalogoProductosView.css" scoped></style>
