<template>
  <section class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <h4 class="q-ma-none">Administradores</h4>
        <p class="text-grey-7 q-ma-none">Gestión de administradores por sucursal</p>
      </div>

      <q-btn
        color="primary"
        icon="person_add"
        label="Nuevo administrador"
        @click="abrirModalCrear"
      />
    </div>
    <q-card flat bordered>
      <q-table
        :rows="administradores"
        :columns="columns"
        row-key="id"
        :loading="loading"
        no-data-label="No hay administradores registrados"
      >
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
              :icon="props.row.empleado?.estado === 'ACTIVO' ? 'check_circle' : 'block'"
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
            />

            <q-btn
              dense
              flat
              round
              color="primary"
              icon="edit"
              @click="abrirModalEditar(props.row)"
            />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- MODAL CREAR / EDITAR -->
    <q-dialog v-model="modal">
      <q-card style="width: 650px; max-width: 95vw">
        <q-card-section>
          <div class="text-h6">
            {{ editando ? 'Editar administrador' : 'Nuevo administrador' }}
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-gutter-md">
          <q-input v-model="form.name" label="Nombre completo" outlined />
          <q-input v-model="form.usuario" label="Usuario" outlined />
          <q-input v-model="form.email" label="Correo (opcional)" type="email" outlined />

          <q-input
            v-model="form.password"
            :label="editando ? 'Nueva contraseña (opcional)' : 'Contraseña'"
            :type="verPassword ? 'text' : 'password'"
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
          <q-input v-model="form.telefono" label="Teléfono" outlined />
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
          <q-btn flat label="Cancelar" color="grey" v-close-popup />
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
        <q-card-section>
          <div class="text-h6">Datos del administrador</div>
        </q-card-section>

        <q-separator />

        <q-card-section v-if="adminDetalle" class="q-gutter-sm">
          <p><strong>Nombre:</strong> {{ adminDetalle.name }}</p>
          <p><strong>Usuario:</strong> {{ adminDetalle.usuario }}</p>
          <p><strong>Correo:</strong> {{ adminDetalle.email || 'Sin correo' }}</p>
          <p>
            <strong>Rol:</strong>
            {{ adminDetalle.rol?.nombre_rol || adminDetalle.rol?.nombre || 'ADMIN' }}
          </p>
          <p>
            <strong>Sucursal:</strong>
            {{ adminDetalle.empleado?.sucursal?.nombre || 'Sin sucursal' }}
          </p>
          <p><strong>Cargo:</strong> {{ adminDetalle.empleado?.cargo || 'ADMIN' }}</p>
          <p><strong>Estado:</strong> {{ adminDetalle.empleado?.estado || 'Sin estado' }}</p>
          <p>
            <strong>Fecha nacimiento:</strong>
            {{ adminDetalle.empleado?.fecha_nacimiento || 'No registrado' }}
          </p>
          <p><strong>Teléfono:</strong> {{ adminDetalle.empleado?.telefono || 'No registrado' }}</p>
          <p>
            <strong>Contacto de emergencia:</strong>
            {{ adminDetalle.empleado?.contacto_referencia || 'No registrado' }}
          </p>
          <p>
            <strong>Teléfono emergencia:</strong>
            {{ adminDetalle.empleado?.telefono_referencia || 'No registrado' }}
          </p>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cerrar" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </section>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useQuasar } from 'quasar'
import api from '../../api/axios'

const $q = useQuasar()

const administradores = ref([])
const sucursales = ref([])
const loading = ref(false)
const guardando = ref(false)

const modal = ref(false)
const modalDetalles = ref(false)
const editando = ref(false)
const adminEditId = ref(null)
const adminDetalle = ref(null)
const verPassword = ref(false)

const form = ref({
  name: '',
  usuario: '',
  email: '',
  password: '',
  id_sucursal: null,
  fecha_nacimiento: '',
  telefono: '',
  contacto_referencia: '',
  telefono_referencia: '',
})

const columns = [
  { name: 'name', label: 'Nombre', field: 'name', align: 'left' },
  { name: 'usuario', label: 'Usuario', field: 'usuario', align: 'left' },
  { name: 'email', label: 'Correo', field: (row) => row.email || 'Sin correo', align: 'left' },
  { name: 'sucursal', label: 'Sucursal', field: 'sucursal', align: 'left' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
  { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'center' },
]

const sucursalesActivas = computed(() => sucursales.value.filter((s) => s.estado === 'ACTIVA'))

const limpiarForm = () => {
  form.value = {
    name: '',
    usuario: '',
    email: '',
    password: '',
    id_sucursal: null,
    fecha_nacimiento: '',
    telefono: '',
    contacto_referencia: '',
    telefono_referencia: '',
  }

  adminEditId.value = null
  editando.value = false
  verPassword.value = false
}

const cargarAdministradores = async () => {
  loading.value = true

  try {
    const res = await api.get('/administradores')
    administradores.value = res.data.administradores || []
  } catch (error) {
    $q.notify({
      type: 'negative',
      message:
        error.response?.data?.error ||
        error.response?.data?.message ||
        'Error al cargar administradores',
    })
  } finally {
    loading.value = false
  }
}

const cargarSucursales = async () => {
  try {
    const res = await api.get('/sucursales')
    sucursales.value = res.data.sucursales || []
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'Error al cargar sucursales',
    })
  }
}

const abrirModalCrear = () => {
  limpiarForm()
  modal.value = true
}

const abrirModalEditar = (admin) => {
  editando.value = true
  adminEditId.value = admin.id

  form.value = {
    name: admin.name || '',
    usuario: admin.usuario || '',
    email: admin.email || '',
    password: '',
    id_sucursal: admin.empleado?.sucursal?.id_sucursal || null,
    fecha_nacimiento: admin.empleado?.fecha_nacimiento || '',
    telefono: admin.empleado?.telefono || '',
    contacto_referencia: admin.empleado?.contacto_referencia || '',
    telefono_referencia: admin.empleado?.telefono_referencia || '',
  }

  verPassword.value = false
  modal.value = true
}

const abrirDetalles = (admin) => {
  adminDetalle.value = admin
  modalDetalles.value = true
}

const guardarAdministrador = async () => {
  guardando.value = true

  try {
    if (editando.value) {
      await api.put(`/administradores/${adminEditId.value}`, form.value)

      $q.notify({
        type: 'positive',
        message: 'Administrador actualizado',
      })
    } else {
      await api.post('/administradores', form.value)

      $q.notify({
        type: 'positive',
        message: 'Administrador creado',
      })
    }

    modal.value = false
    limpiarForm()
    cargarAdministradores()
  } catch (error) {
    console.log('ERROR COMPLETO:', error)
    console.log('RESPUESTA BACKEND:', error.response?.data)

    $q.notify({
      type: 'negative',
      message:
        error.response?.data?.error ||
        error.response?.data?.message ||
        'Error al guardar administrador',
    })
  } finally {
    guardando.value = false
  }
}

const cambiarEstado = async (admin) => {
  const estadoAnterior = admin.empleado?.estado

  if (!admin.empleado) {
    $q.notify({
      type: 'negative',
      message: 'Este administrador no tiene empleado registrado',
    })
    return
  }

  admin.empleado.estado = admin.empleado.estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO'

  try {
    await api.patch(`/administradores/${admin.id}/estado`)

    $q.notify({
      type: 'positive',
      message: 'Estado actualizado',
    })
  } catch (error) {
    admin.empleado.estado = estadoAnterior

    $q.notify({
      type: 'negative',
      message:
        error.response?.data?.error || error.response?.data?.message || 'Error al cambiar estado',
    })
  }
}

onMounted(() => {
  cargarAdministradores()
  cargarSucursales()
})
</script>
