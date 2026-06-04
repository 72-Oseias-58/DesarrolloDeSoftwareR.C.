<template>
  <section class="q-pa-md">
    <div class="row items-center justify-between q-mb-md">
      <div>
        <h4 class="q-ma-none">Empleados</h4>
        <p class="text-grey-7 q-ma-none">
          Gestión de empleados y cajeros de tu sucursal
        </p>
      </div>

      <q-btn
        color="primary"
        icon="person_add"
        label="Nuevo empleado"
        @click="abrirModalCrear"
      />
    </div>

    <q-card flat bordered class="q-pa-md">
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
              :color="props.row.cargo === 'CAJERO' ? 'orange' : 'primary'"
            >
              {{ props.row.cargo }}
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-usuario="props">
          <q-td :props="props">
            <q-chip
              dense
              :color="props.row.usuario ? 'green' : 'grey'"
              text-color="white"
            >
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
            <q-btn
              flat
              round
              dense
              icon="visibility"
              color="info"
              @click="verEmpleado(props.row)"
            />

            <q-btn
              flat
              round
              dense
              icon="edit"
              color="primary"
              @click="editarEmpleado(props.row)"
            />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <q-dialog v-model="modal">
      <q-card style="width: 720px; max-width: 95vw;">
        <q-card-section>
          <div class="text-h6">
            {{ editando ? 'Editar empleado' : 'Nuevo empleado' }}
          </div>
          <div class="text-grey-7">
            Los empleados se registran automáticamente en tu sucursal.
          </div>
        </q-card-section>

        <q-separator />

        <q-card-section class="q-gutter-md">
          <q-input
            v-model="form.nombre"
            label="Nombre completo"
            outlined
          />

          <q-select
            v-model="form.cargo"
            :options="cargos"
            label="Cargo"
            outlined
          />

          <q-input
            v-model="form.telefono"
            label="Teléfono"
            outlined
          />

          <q-input
            v-model="form.fecha_nacimiento"
            label="Fecha nacimiento"
            type="date"
            outlined
          />

          <q-input
            v-model="form.contacto_referencia"
            label="Contacto de emergencia"
            outlined
          />

          <q-input
            v-model="form.telefono_referencia"
            label="Teléfono de emergencia"
            outlined
          />

          <div v-if="form.cargo === 'CAJERO'">
            <q-separator class="q-my-md" />

            <div class="text-subtitle1 text-weight-bold q-mb-sm">
              Acceso al sistema
            </div>

            <q-input
              v-model="form.usuario"
              label="Usuario"
              outlined
            />

            <q-input
              v-model="form.email"
              label="Correo opcional"
              type="email"
              outlined
            />

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
      <q-card style="width: 620px; max-width: 95vw;">
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
          <p><strong>Usuario:</strong> {{ empleadoDetalle.usuario?.usuario || 'Sin acceso al sistema' }}</p>
          <p><strong>Teléfono:</strong> {{ empleadoDetalle.telefono || 'No registrado' }}</p>
          <p><strong>Fecha nacimiento:</strong> {{ empleadoDetalle.fecha_nacimiento || 'No registrado' }}</p>
          <p><strong>Contacto emergencia:</strong> {{ empleadoDetalle.contacto_referencia || 'No registrado' }}</p>
          <p><strong>Teléfono emergencia:</strong> {{ empleadoDetalle.telefono_referencia || 'No registrado' }}</p>
          <p><strong>Sucursal:</strong> {{ empleadoDetalle.sucursal?.nombre || 'Sucursal asignada' }}</p>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cerrar" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useQuasar } from 'quasar'
import api from '../../api/axios'

const $q = useQuasar()

const empleados = ref([])
const filtro = ref('')
const loading = ref(false)
const guardando = ref(false)

const modal = ref(false)
const modalDetalle = ref(false)
const editando = ref(false)

const empleadoEditId = ref(null)
const empleadoDetalle = ref(null)
const verPassword = ref(false)

const cargos = [
  'Cajero/a',
  'Cocinero/a',
  'Mesero/a',
  'Llamadoras/os',
]

const columns = [
  { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left' },
  { name: 'cargo', label: 'Cargo', field: 'cargo', align: 'left' },
  { name: 'usuario', label: 'Usuario', field: 'usuario', align: 'left' },
  { name: 'telefono', label: 'Teléfono', field: 'telefono', align: 'left' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
  { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'center' },
]

const form = ref({
  nombre: '',
  cargo: '',
  telefono: '',
  fecha_nacimiento: '',
  contacto_referencia: '',
  telefono_referencia: '',
  usuario: '',
  email: '',
  password: '',
})

const inicialesEmpleado = computed(() => {
  if (!empleadoDetalle.value?.nombre) return 'EM'

  return empleadoDetalle.value.nombre
    .split(' ')
    .slice(0, 2)
    .map(p => p[0])
    .join('')
    .toUpperCase()
})

const limpiarForm = () => {
  form.value = {
    nombre: '',
    cargo: '',
    telefono: '',
    fecha_nacimiento: '',
    contacto_referencia: '',
    telefono_referencia: '',
    usuario: '',
    email: '',
    password: '',
  }

  verPassword.value = false
}

const cargarEmpleados = async () => {
  loading.value = true

  try {
    const res = await api.get('/empleados')
    empleados.value = res.data.empleados || []
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Error al cargar empleados',
    })
  } finally {
    loading.value = false
  }
}

const abrirModalCrear = () => {
  editando.value = false
  empleadoEditId.value = null
  limpiarForm()
  modal.value = true
}

const guardarEmpleado = async () => {
  guardando.value = true

  try {
    if (editando.value) {
      await api.put(`/empleados/${empleadoEditId.value}`, form.value)

      $q.notify({
        type: 'positive',
        message: 'Empleado actualizado',
      })
    } else {
      await api.post('/empleados', form.value)

      $q.notify({
        type: 'positive',
        message: 'Empleado creado',
      })
    }

    modal.value = false
    limpiarForm()
    cargarEmpleados()
  } catch (error) {
    console.log('ERROR EMPLEADO:', error.response?.data || error)

    $q.notify({
      type: 'negative',
      message:
        error.response?.data?.error ||
        error.response?.data?.message ||
        'Error al guardar empleado',
    })
  } finally {
    guardando.value = false
  }
}

const editarEmpleado = (empleado) => {
  editando.value = true
  empleadoEditId.value = empleado.id_empleado

  form.value = {
    nombre: empleado.nombre || '',
    cargo: empleado.cargo || '',
    telefono: empleado.telefono || '',
    fecha_nacimiento: empleado.fecha_nacimiento || '',
    contacto_referencia: empleado.contacto_referencia || '',
    telefono_referencia: empleado.telefono_referencia || '',
    usuario: empleado.usuario?.usuario || '',
    email: empleado.usuario?.email || '',
    password: '',
  }

  verPassword.value = false
  modal.value = true
}

const verEmpleado = (empleado) => {
  empleadoDetalle.value = empleado
  modalDetalle.value = true
}

const cambiarEstado = async (empleado) => {
  const estadoAnterior = empleado.estado

  empleado.estado = empleado.estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO'

  try {
    await api.patch(`/empleados/${empleado.id_empleado}/estado`)

    $q.notify({
      type: 'positive',
      message: 'Estado actualizado',
    })
  } catch (error) {
    empleado.estado = estadoAnterior

    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Error al cambiar estado',
    })
  }
}

onMounted(() => {
  cargarEmpleados()
})
</script>
<!-- cargos  -->