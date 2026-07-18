<template>
  <section class="pantallas-page q-pa-md">
    <div class="pantallas-header q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">
          Configuración de pantallas
        </div>

        <div class="text-grey-7">
          Registra las pantallas físicas de la sucursal,
          asigna sus áreas y selecciona cuál permite finalizar pedidos.
        </div>
      </div>

      <div class="row q-gutter-sm">
        <q-btn
          color="primary"
          icon="refresh"
          label="Actualizar"
          unelevated
          :loading="cargando"
          @click="cargarPantallas"
        />

        <q-btn
          color="deep-orange"
          icon="add_to_queue"
          label="Nueva pantalla"
          unelevated
          @click="abrirNuevaPantalla"
        />
      </div>
    </div>

    <div class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-sm-6">
        <q-card class="resumen-card">
          <q-card-section class="row items-center no-wrap">
            <q-icon
              name="connected_tv"
              size="44px"
              color="primary"
              class="q-mr-md"
            />

            <div>
              <div class="text-caption text-grey-7">
                Pantallas registradas
              </div>

              <div class="text-h5 text-weight-bold">
                {{ cantidadPantallas }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-sm-6">
        <q-card class="resumen-card">
          <q-card-section class="row items-center no-wrap">
            <q-icon
              name="touch_app"
              size="44px"
              color="positive"
              class="q-mr-md"
            />

            <div>
              <div class="text-caption text-grey-7">
                Pantalla para finalizar
              </div>

              <div class="text-subtitle1 text-weight-bold">
                {{
                  pantallaFinalizacion?.nombre ||
                  'No configurada'
                }}
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-card class="pantallas-card">
      <q-card-section>
        <div class="text-h6 text-weight-bold">
          Pantallas de la sucursal
        </div>

        <div class="text-grey-7">
          Cada pantalla puede mostrar una o varias áreas.
        </div>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div
          v-if="cargando"
          class="estado-vacio"
        >
          <q-spinner
            color="primary"
            size="46px"
          />

          <div class="q-mt-md text-grey-7">
            Cargando configuración...
          </div>
        </div>

        <div
          v-else-if="pantallas.length === 0"
          class="estado-vacio"
        >
          <q-icon
            name="desktop_windows"
            size="64px"
            color="grey-5"
          />

          <div class="text-h6 text-weight-bold q-mt-md">
            No existen pantallas registradas
          </div>

          <div class="text-grey-7 q-mt-xs">
            Registra la primera pantalla de la sucursal.
          </div>

          <q-btn
            color="primary"
            icon="add"
            label="Registrar pantalla"
            class="q-mt-md"
            unelevated
            @click="abrirNuevaPantalla"
          />
        </div>

        <div
          v-else
          class="row q-col-gutter-md"
        >
          <div
            v-for="pantalla in pantallas"
            :key="pantalla.id_pantalla"
            class="col-12 col-md-6 col-lg-4"
          >
            <q-card
              flat
              bordered
              class="pantalla-item-card"
            >
              <q-card-section>
                <div class="row items-start no-wrap">
                  <q-icon
                    name="desktop_windows"
                    size="42px"
                    color="primary"
                    class="q-mr-md"
                  />

                  <div class="col">
                    <div class="row items-center q-gutter-sm">
                      <div class="text-h6 text-weight-bold">
                        {{ pantalla.nombre }}
                      </div>

                      <q-chip
                        v-if="pantalla.permite_finalizar"
                        dense
                        color="green-1"
                        text-color="green-10"
                        icon="touch_app"
                      >
                        Finaliza pedidos
                      </q-chip>
                    </div>

                    <div class="text-grey-7 q-mt-xs">
                      {{ nombresAreas(pantalla) }}
                    </div>
                  </div>
                </div>

                <div class="q-mt-md">
                  <div class="text-caption text-grey-7 q-mb-sm">
                    Áreas asignadas
                  </div>

                  <div class="row q-gutter-xs">
                    <q-chip
                      v-for="area in pantalla.areas || []"
                      :key="area.id_area"
                      dense
                      :color="`${colorArea(area.nombre_area)}-1`"
                      :text-color="`${colorArea(area.nombre_area)}-10`"
                    >
                      {{ area.nombre_area }}
                    </q-chip>
                  </div>
                </div>
              </q-card-section>

              <q-separator />

              <q-card-actions align="right">
                <q-btn
                  flat
                  color="primary"
                  icon="edit"
                  label="Editar"
                  @click="abrirEditarPantalla(pantalla)"
                />

                <q-btn
                  flat
                  color="negative"
                  icon="delete"
                  label="Eliminar"
                  :disable="eliminando"
                  @click="confirmarEliminar(pantalla)"
                />
              </q-card-actions>
            </q-card>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-dialog
      v-model="mostrarDialogo"
      persistent
    >
      <q-card class="pantalla-dialog">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            {{
              modoEdicion
                ? 'Editar pantalla'
                : 'Nueva pantalla'
            }}
          </div>

          <div class="text-grey-7">
            Configura el nombre, las áreas mostradas y la
            capacidad de finalizar pedidos.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-input
            v-model="formulario.nombre"
            outlined
            label="Nombre de la pantalla"
            maxlength="100"
            counter
            class="q-mb-md"
          />

          <q-select
            v-model="formulario.areas"
            outlined
            multiple
            emit-value
            map-options
            use-chips
            label="Áreas que mostrará"
            :options="opcionesAreas"
            class="q-mb-md"
          />

          <q-toggle
            v-model="formulario.permite_finalizar"
            color="positive"
            icon="touch_app"
            label="Esta pantalla permite finalizar pedidos"
          />

          <q-banner
            v-if="formulario.permite_finalizar"
            rounded
            class="bg-green-1 text-green-10 q-mt-md"
          >
            Esta pantalla mostrará el botón
            <b>Finalizar pedido</b>.

            Si otra pantalla ya tenía esta función,
            será desactivada automáticamente.
          </q-banner>

          <q-banner
            rounded
            class="bg-blue-1 text-blue-10 q-mt-md"
          >
            Una pantalla puede mostrar Guarniciones,
            Carne, Bebidas o cualquier combinación de estas áreas.
          </q-banner>
        </q-card-section>

        <q-separator />

        <q-card-actions align="right">
          <q-btn
            flat
            color="grey-8"
            label="Cancelar"
            @click="cerrarDialogo"
          />

          <q-btn
            color="primary"
            icon="save"
            :label="
              modoEdicion
                ? 'Guardar cambios'
                : 'Registrar pantalla'
            "
            unelevated
            :loading="guardando"
            @click="guardarPantalla"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </section>
</template>

<script src="./PantallasView.js"></script>

<style scoped src="./PantallasView.css"></style>