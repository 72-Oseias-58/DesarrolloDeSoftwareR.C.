
<template>
  <section class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <h4 class="q-ma-none">Administradores</h4>

        <p class="text-grey-7 q-ma-none">
          Gestión, ascenso y degradación de usuarios administrativos
        </p>
      </div>

      <q-btn
        color="primary"
        icon="person_add"
        label="Nuevo administrador"
        @click="abrirModalCrear"
      />
    </div>

    <q-card flat bordered>
      <q-tabs
        v-model="tab"
        dense
        align="left"
        active-color="primary"
        indicator-color="primary"
        class="text-grey-7"
      >
        <q-tab
          name="administradores"
          icon="admin_panel_settings"
          label="Administradores"
        />

        <q-tab
          name="cajeros"
          icon="point_of_sale"
          label="Cajeros disponibles"
        />
      </q-tabs>

      <q-separator />

      <q-tab-panels v-model="tab" animated>
        <!-- ADMINISTRADORES -->
        <q-tab-panel name="administradores" class="q-pa-none">
          <q-table
            :rows="administradores"
            :columns="columns"
            row-key="id"
            :loading="loading"
            no-data-label="No hay administradores registrados"
          >
            <template #body-cell-rol="props">
              <q-td :props="props">
                <q-chip
                  dense
                  color="purple"
                  text-color="white"
                  icon="admin_panel_settings"
                >
                  ADMIN
                </q-chip>
              </q-td>
            </template>

            <template #body-cell-sucursal="props">
              <q-td :props="props">
                {{ props.row.empleado?.sucursal?.nombre || 'Sin sucursal' }}
              </q-td>
            </template>

            <template #body-cell-estado="props">
              <q-td :props="props">
                <q-btn
                  dense
                  unelevated
                  size="sm"
                  :color="props.row.empleado?.estado === 'ACTIVO' ? 'green' : 'red'"
                  :icon="
                    props.row.empleado?.estado === 'ACTIVO'
                      ? 'check_circle'
                      : 'block'
                  "
                  :label="props.row.empleado?.estado || 'SIN ESTADO'"
                  @click="cambiarEstado(props.row)"
                />
              </q-td>
            </template>

            <template #body-cell-acciones="props">
              <q-td :props="props" class="q-gutter-xs">
                <q-btn
                  dense
                  flat
                  round
                  color="info"
                  icon="visibility"
                  @click="abrirDetalles(props.row)"
                >
                  <q-tooltip>Ver detalles</q-tooltip>
                </q-btn>

                <q-btn
                  dense
                  flat
                  round
                  color="primary"
                  icon="edit"
                  @click="abrirModalEditar(props.row)"
                >
                  <q-tooltip>Editar administrador</q-tooltip>
                </q-btn>

                <q-btn
                  dense
                  flat
                  round
                  color="purple"
                  icon="admin_panel_settings"
                  @click="abrirPermisos(props.row)"
                >
                  <q-tooltip>Gestionar permisos</q-tooltip>
                </q-btn>

                <q-btn
                  v-if="authStore.tienePermiso('degradar_admin_cajero')"
                  dense
                  flat
                  round
                  color="orange"
                  icon="south"
                  :disable="cambiandoRol"
                  :loading="cambiandoRolId === props.row.id"
                  @click="confirmarDegradacion(props.row)"
                >
                  <q-tooltip>Degradar a cajero</q-tooltip>
                </q-btn>
              </q-td>
            </template>
          </q-table>
        </q-tab-panel>

        <!-- CAJEROS DISPONIBLES -->
        <q-tab-panel name="cajeros" class="q-pa-none">
          <q-table
            :rows="cajeros"
            :columns="columns"
            row-key="id"
            :loading="loading"
            no-data-label="No hay cajeros disponibles para ascender"
          >
            <template #body-cell-rol="props">
              <q-td :props="props">
                <q-chip
                  dense
                  color="orange"
                  text-color="white"
                  icon="point_of_sale"
                >
                  CAJERO
                </q-chip>
              </q-td>
            </template>

            <template #body-cell-sucursal="props">
              <q-td :props="props">
                {{ props.row.empleado?.sucursal?.nombre || 'Sin sucursal' }}
              </q-td>
            </template>

            <template #body-cell-estado="props">
              <q-td :props="props">
                <q-chip
                  dense
                  text-color="white"
                  :color="
                    props.row.empleado?.estado === 'ACTIVO'
                      ? 'green'
                      : 'red'
                  "
                >
                  {{ props.row.empleado?.estado || 'SIN ESTADO' }}
                </q-chip>
              </q-td>
            </template>

            <template #body-cell-acciones="props">
              <q-td :props="props" class="q-gutter-xs">
                <q-btn
                  dense
                  flat
                  round
                  color="info"
                  icon="visibility"
                  @click="abrirDetalles(props.row)"
                >
                  <q-tooltip>Ver detalles</q-tooltip>
                </q-btn>

                <q-btn
                  v-if="authStore.tienePermiso('ascender_cajero_admin')"
                  dense
                  flat
                  round
                  color="green"
                  icon="north"
                  :disable="cambiandoRol"
                  :loading="cambiandoRolId === props.row.id"
                  @click="confirmarAscenso(props.row)"
                >
                  <q-tooltip>Ascender a administrador</q-tooltip>
                </q-btn>
              </q-td>
            </template>
          </q-table>
        </q-tab-panel>
      </q-tab-panels>
    </q-card>

    <!-- MODAL CREAR / EDITAR ADMINISTRADOR -->
    <q-dialog v-model="modal">
      <q-card style="width: 650px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6">
            {{ editando ? 'Editar administrador' : 'Nuevo administrador' }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-gutter-md">
          <q-input
            v-model="form.name"
            label="Nombre completo"
            outlined
          />

          <q-input
            v-model="form.usuario"
            label="Usuario"
            outlined
          />

          <q-input
            v-model="form.email"
            label="Correo (opcional)"
            type="email"
            outlined
          />

          <q-input
            v-model="form.password"
            :label="
              editando
                ? 'Nueva contraseña (opcional)'
                : 'Contraseña'
            "
            :type="verPassword ? 'text' : 'password'"
            outlined
          >
            <template #append>
              <q-icon
                :name="
                  verPassword
                    ? 'visibility_off'
                    : 'visibility'
                "
                class="cursor-pointer"
                @click="verPassword = !verPassword"
              />
            </template>
          </q-input>

          <q-select
            v-model="form.id_sucursal"
            :options="sucursalesActivas"
            option-label="nombre"
            option-value="id_sucursal"
            emit-value
            map-options
            label="Sucursal"
            outlined
          />

          <q-input
            v-model="form.fecha_nacimiento"
            label="Fecha de nacimiento"
            type="date"
            outlined
          />

          <q-input
            v-model="form.telefono"
            label="Teléfono"
            outlined
          />

          <q-input
            v-model="form.contacto_referencia"
            label="Nombre del contacto de emergencia"
            outlined
          />

          <q-input
            v-model="form.telefono_referencia"
            label="Teléfono del contacto de emergencia"
            outlined
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cancelar"
            color="grey"
            v-close-popup
          />

          <q-btn
            color="primary"
            :label="editando ? 'Actualizar' : 'Guardar'"
            :loading="guardando"
            @click="guardarAdministrador"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- MODAL DETALLES -->
    <q-dialog v-model="modalDetalles">
      <q-card style="width: 600px; max-width: 95vw">
        <q-card-section class="row items-center">
          <div>
            <div class="text-h6">Datos del usuario</div>

            <div class="text-grey-7">
              Información del administrador o cajero
            </div>
          </div>

          <q-space />

          <q-chip
            v-if="adminDetalle"
            dense
            text-color="white"
            :color="
              obtenerNombreRol(adminDetalle) === 'ADMIN'
                ? 'purple'
                : 'orange'
            "
          >
            {{ obtenerNombreRol(adminDetalle) }}
          </q-chip>
        </q-card-section>

        <q-separator />

        <q-card-section
          v-if="adminDetalle"
          class="q-gutter-sm"
        >
          <p>
            <strong>Nombre:</strong>
            {{ adminDetalle.name }}
          </p>

          <p>
            <strong>Usuario:</strong>
            {{ adminDetalle.usuario }}
          </p>

          <p>
            <strong>Correo:</strong>
            {{ adminDetalle.email || 'Sin correo' }}
          </p>

          <p>
            <strong>Rol:</strong>
            {{ obtenerNombreRol(adminDetalle) }}
          </p>

          <p>
            <strong>Sucursal:</strong>
            {{
              adminDetalle.empleado?.sucursal?.nombre ||
              'Sin sucursal'
            }}
          </p>

          <p>
            <strong>Cargo:</strong>
            {{ adminDetalle.empleado?.cargo || 'Sin cargo' }}
          </p>

          <p>
            <strong>Estado:</strong>
            {{
              adminDetalle.empleado?.estado ||
              'Sin estado'
            }}
          </p>

          <p>
            <strong>Fecha nacimiento:</strong>
            {{
              adminDetalle.empleado?.fecha_nacimiento ||
              'No registrado'
            }}
          </p>

          <p>
            <strong>Teléfono:</strong>
            {{
              adminDetalle.empleado?.telefono ||
              'No registrado'
            }}
          </p>

          <p>
            <strong>Contacto de emergencia:</strong>
            {{
              adminDetalle.empleado?.contacto_referencia ||
              'No registrado'
            }}
          </p>

          <p>
            <strong>Teléfono emergencia:</strong>
            {{
              adminDetalle.empleado?.telefono_referencia ||
              'No registrado'
            }}
          </p>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn
            flat
            label="Cerrar"
            color="primary"
            v-close-popup
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </section>
</template>
<script src="./AdministradoresView.js"></script>

<style src="./AdministradoresView.css" scoped></style>