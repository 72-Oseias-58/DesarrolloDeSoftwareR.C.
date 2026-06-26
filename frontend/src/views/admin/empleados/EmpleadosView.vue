<template>
  <section class="q-pa-md empleados-page">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <h4 class="q-ma-none">Empleados</h4>
        <p class="text-grey-7 q-ma-none">Gestión de empleados y cajeros de tu sucursal</p>
      </div>

      <q-btn color="primary" icon="person_add" label="Nuevo empleado" @click="abrirModalCrear" />
    </div>

    <q-card flat bordered class="q-pa-md empleados-card">
      <q-input
        v-model="filtro"
        outlined
        dense
        debounce="300"
        placeholder="Buscar empleado..."
        class="q-mb-md"
      >
        <template #prepend>
          <q-icon name="search" />
        </template>
      </q-input>

      <q-table
        flat
        bordered
        separator="cell"
        :rows="empleados"
        :columns="columns"
        row-key="id_empleado"
        :loading="loading"
        :filter="filtro"
        no-data-label="No hay empleados registrados"
      >
        <template #body-cell-cargo="props">
          <q-td :props="props">
            <q-chip
              dense
              text-color="white"
              :color="esCargoCajero(props.row.cargo) ? 'orange' : 'primary'"
            >
              {{ props.row.cargo }}
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-usuario="props">
          <q-td :props="props">
            <q-chip dense :color="props.row.usuario ? 'green' : 'grey'" text-color="white">
              {{ props.row.usuario?.usuario || 'Sin acceso' }}
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-btn
              dense
              unelevated
              size="sm"
              :color="props.row.estado === 'ACTIVO' ? 'green' : 'red'"
              :icon="props.row.estado === 'ACTIVO' ? 'check_circle' : 'block'"
              :label="props.row.estado"
              @click="cambiarEstado(props.row)"
            />
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props" class="q-gutter-xs">
            <q-btn flat round dense icon="visibility" color="info" @click="verEmpleado(props.row)">
              <q-tooltip>Ver detalle</q-tooltip>
            </q-btn>

            <q-btn flat round dense icon="edit" color="primary" @click="editarEmpleado(props.row)">
              <q-tooltip>Editar empleado</q-tooltip>
            </q-btn>

            <q-btn
              v-if="esCajeroConUsuario(props.row)"
              dense
              flat
              round
              color="purple"
              icon="admin_panel_settings"
              @click="abrirPermisosCajero(props.row)"
            >
              <q-tooltip>Gestionar permisos del cajero</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-dialog v-model="modal">
      <q-card style="width: 720px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6">
            {{ editando ? 'Editar empleado' : 'Nuevo empleado' }}
          </div>
          <div class="text-grey-7">Los empleados se registran automáticamente en tu sucursal.</div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-gutter-md">
          <q-input v-model="form.nombre" label="Nombre completo" outlined />

          <q-select
            v-model="form.cargo"
            :options="cargos"
            label="Cargo"
            outlined
            use-input
            fill-input
            hide-selected
            input-debounce="0"
            new-value-mode="add-unique"
            @new-value="agregarCargo"
          />

          <q-input v-model="form.telefono" label="Teléfono" outlined />

          <q-input v-model="form.fecha_nacimiento" label="Fecha nacimiento" type="date" outlined />

          <q-input v-model="form.contacto_referencia" label="Contacto de emergencia" outlined />

          <q-input v-model="form.telefono_referencia" label="Teléfono de emergencia" outlined />

          <div v-if="esCargoCajero(form.cargo)">
            <q-separator class="q-my-md" />

            <div class="text-subtitle1 text-weight-bold q-mb-sm">Acceso al sistema</div>

            <q-banner dense rounded class="bg-orange-1 text-orange-10 q-mb-md">
              Este cargo tendrá acceso al sistema como CAJERO.
            </q-banner>

            <q-input v-model="form.usuario" label="Usuario" outlined />

            <q-input v-model="form.email" label="Correo opcional" type="email" outlined />

            <q-input
              v-model="form.password"
              :type="verPassword ? 'text' : 'password'"
              :label="editando ? 'Nueva contraseña opcional' : 'Contraseña'"
              outlined
            >
              <template #append>
                <q-icon
                  :name="verPassword ? 'visibility_off' : 'visibility'"
                  class="cursor-pointer"
                  @click="verPassword = !verPassword"
                />
              </template>
            </q-input>
          </div>

          <q-banner v-else dense rounded class="bg-grey-2 text-grey-8">
            Este empleado no tendrá acceso al sistema. No necesita usuario ni contraseña.
          </q-banner>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="grey" v-close-popup />

          <q-btn
            color="primary"
            :label="editando ? 'Actualizar' : 'Guardar'"
            :loading="guardando"
            @click="guardarEmpleado"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <q-dialog v-model="modalDetalle">
      <q-card style="width: 620px; max-width: 95vw">
        <q-card-section class="row items-center">
          <q-avatar size="54px" color="primary" text-color="white">
            {{ inicialesEmpleado }}
          </q-avatar>

          <div class="q-ml-md">
            <div class="text-h6">{{ empleadoDetalle?.nombre }}</div>
            <div class="text-grey-7">{{ empleadoDetalle?.cargo }}</div>
          </div>

          <q-space />

          <q-chip
            v-if="empleadoDetalle"
            text-color="white"
            :color="empleadoDetalle.estado === 'ACTIVO' ? 'green' : 'red'"
          >
            {{ empleadoDetalle.estado }}
          </q-chip>
        </q-card-section>

        <q-separator />

        <q-card-section v-if="empleadoDetalle" class="q-gutter-sm">
          <p>
            <strong>Usuario:</strong>
            {{ empleadoDetalle.usuario?.usuario || 'Sin acceso al sistema' }}
          </p>
          <p><strong>Teléfono:</strong> {{ empleadoDetalle.telefono || 'No registrado' }}</p>
          <p>
            <strong>Fecha nacimiento:</strong>
            {{ empleadoDetalle.fecha_nacimiento || 'No registrado' }}
          </p>
          <p>
            <strong>Contacto emergencia:</strong>
            {{ empleadoDetalle.contacto_referencia || 'No registrado' }}
          </p>
          <p>
            <strong>Teléfono emergencia:</strong>
            {{ empleadoDetalle.telefono_referencia || 'No registrado' }}
          </p>
          <p>
            <strong>Sucursal:</strong> {{ empleadoDetalle.sucursal?.nombre || 'Sucursal asignada' }}
          </p>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cerrar" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </section>
</template>

<script src="./EmpleadosView.js"></script>

<style src="./EmpleadosView.css" scoped></style>