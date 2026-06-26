<template>
  <q-page class="permisos-usuario-page q-pa-lg">
    <div class="permisos-usuario-header row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">
          Gestión de Permisos
        </div>

        <div class="text-grey-7">
          Dar o quitar permisos personalizados respetando la jerarquía del sistema.
        </div>
      </div>

      <q-btn
        flat
        color="grey-8"
        icon="arrow_back"
        label="Volver"
        @click="volver"
      />
    </div>

    <q-card
      v-if="usuario"
      bordered
      flat
      class="permisos-usuario-resumen q-mb-lg"
    >
      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-4">
            <div class="text-caption text-grey-7">
              Usuario
            </div>

            <div class="text-subtitle1 text-weight-bold">
              {{ usuario.name }}
            </div>
          </div>

          <div class="col-12 col-md-4">
            <div class="text-caption text-grey-7">
              Rol
            </div>

            <q-chip color="primary" text-color="white">
              {{ nombreRolUsuario }}
            </q-chip>
          </div>

          <div class="col-12 col-md-4">
            <div class="text-caption text-grey-7">
              Sucursal
            </div>

            <div class="text-subtitle1">
              {{ nombreSucursalUsuario }}
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-card
      bordered
      flat
      class="permisos-usuario-card"
    >
      <q-card-section>
        <div class="text-h6 text-weight-bold q-mb-sm">
          Asignación de Permisos
        </div>

        <div class="text-caption text-grey-7 q-mb-md">
          Los permisos marcados quedan activos para este usuario.
        </div>

        <q-table
          :rows="permisosAgrupados"
          :columns="columns"
          row-key="modulo"
          flat
          bordered
          hide-pagination
          :pagination="{ rowsPerPage: 0 }"
          :loading="loading"
          no-data-label="No existen permisos disponibles para este rol"
        >
          <template #body-cell-modulo="props">
            <q-td
              :props="props"
              class="permisos-usuario-modulo text-weight-bold"
            >
              {{ props.row.modulo }}
            </q-td>
          </template>

          <template #body-cell-permisos="props">
            <q-td :props="props">
              <div class="permisos-usuario-lista">
                <q-checkbox
                  v-for="permiso in props.row.permisos"
                  :key="permiso.slug"
                  v-model="permisosSeleccionados"
                  :val="permiso.slug"
                  :label="permiso.nombre || permiso.slug"
                  color="primary"
                  :disable="guardando"
                />
              </div>
            </q-td>
          </template>
        </q-table>
      </q-card-section>

      <q-separator />

      <q-card-actions
        align="right"
        class="permisos-usuario-acciones"
      >
        <q-btn
          flat
          color="grey-8"
          label="Cancelar"
          :disable="guardando"
          @click="volver"
        />

        <q-btn
          color="primary"
          icon="save"
          label="Guardar permisos"
          :loading="guardando"
          :disable="loading"
          @click="guardarPermisos"
        />
      </q-card-actions>
    </q-card>
  </q-page>
</template>

<script src="./PermisosUsuarioView.js"></script>

<style src="./PermisosUsuarioView.css" scoped></style>