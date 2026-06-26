<template>
  <div class="sucursales-page">
    <div class="row q-col-gutter-lg">
      <div class="col-12 col-md-4">
        <q-card class="sucursales-module-card sucursales-form-card">
          <q-card-section>
            <div class="text-h5 text-weight-bold">
              Nueva Sucursal
            </div>

            <div class="text-grey-7">
              Registra una nueva sucursal para el sistema.
            </div>
          </q-card-section>

          <q-card-section>
            <q-form
              class="q-gutter-md"
              @submit.prevent="crearSucursal"
            >
              <q-input
                v-model.trim="form.nombre"
                label="Nombre de la sucursal"
                outlined
                dense
                :disable="cargando"
                :rules="[
                  (valor) =>
                    !!valor ||
                    'El nombre es obligatorio',
                ]"
              />

              <q-input
                v-model.trim="form.direccion"
                label="Dirección"
                outlined
                dense
                :disable="cargando"
              />

              <q-input
                v-model.trim="form.telefono"
                label="Teléfono"
                outlined
                dense
                :disable="cargando"
              />

              <q-btn
                type="submit"
                label="Crear sucursal"
                icon="add_business"
                color="primary"
                unelevated
                class="full-width"
                :loading="cargando"
              />
            </q-form>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-8">
        <q-card class="sucursales-module-card sucursales-table-card">
          <q-card-section class="row items-center justify-between">
            <div>
              <div class="text-h5 text-weight-bold">
                Sucursales
              </div>

              <div class="text-grey-7">
                Listado de sucursales registradas.
              </div>
            </div>

            <q-btn
              flat
              round
              icon="refresh"
              color="primary"
              :loading="cargandoListado"
              @click="obtenerSucursales"
            >
              <q-tooltip>
                Actualizar listado
              </q-tooltip>
            </q-btn>
          </q-card-section>

          <q-separator />

          <q-card-section>
            <q-table
              :rows="sucursales"
              :columns="columnas"
              row-key="id_sucursal"
              flat
              bordered
              :loading="cargandoListado"
              no-data-label="No hay sucursales registradas"
            >
              <template #body-cell-estado="props">
                <q-td :props="props">
                  <q-btn
                    dense
                    unelevated
                    rounded
                    size="sm"
                    :label="props.row.estado"
                    :icon="
                      props.row.estado === 'ACTIVA'
                        ? 'check_circle'
                        : 'block'
                    "
                    :color="
                      props.row.estado === 'ACTIVA'
                        ? 'positive'
                        : 'negative'
                    "
                    @click="cambiarEstadoSucursal(props.row)"
                  >
                    <q-tooltip>
                      {{
                        props.row.estado === 'ACTIVA'
                          ? 'Cambiar a INACTIVA'
                          : 'Cambiar a ACTIVA'
                      }}
                    </q-tooltip>
                  </q-btn>
                </q-td>
              </template>

              <template #body-cell-created_at="props">
                <q-td :props="props">
                  {{ formatearFecha(props.row.created_at) }}
                </q-td>
              </template>

              <template #body-cell-acciones="props">
                <q-td :props="props">
                  <div class="sucursales-actions">
                    <q-btn
                      dense
                      round
                      flat
                      icon="edit"
                      color="primary"
                      @click="abrirEditar(props.row)"
                    >
                      <q-tooltip>
                        Editar sucursal
                      </q-tooltip>
                    </q-btn>
                  </div>
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="modalEditar">
      <q-card class="sucursales-dialog-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold">
            Editar Sucursal
          </div>

          <div class="text-grey-7">
            Modifica los datos de la sucursal.
          </div>
        </q-card-section>

        <q-card-section>
          <q-form
            class="q-gutter-md"
            @submit.prevent="actualizarSucursal"
          >
            <q-input
              v-model.trim="formEditar.nombre"
              label="Nombre"
              outlined
              dense
              :disable="cargandoEditar"
              :rules="[
                (valor) =>
                  !!valor ||
                  'El nombre es obligatorio',
              ]"
            />

            <q-input
              v-model.trim="formEditar.direccion"
              label="Dirección"
              outlined
              dense
              :disable="cargandoEditar"
            />

            <q-input
              v-model.trim="formEditar.telefono"
              label="Teléfono"
              outlined
              dense
              :disable="cargandoEditar"
            />

            <div class="row justify-end q-gutter-sm">
              <q-btn
                label="Cancelar"
                flat
                color="grey-7"
                :disable="cargandoEditar"
                v-close-popup
              />

              <q-btn
                label="Guardar cambios"
                type="submit"
                color="primary"
                unelevated
                :loading="cargandoEditar"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script src="./SucursalesView.js"></script>

<style src="./SucursalesView.css" scoped></style>