<template>
  <div class="sucursales-page">
    <div class="row q-col-gutter-lg">
      <div class="col-12 col-md-4">
        <q-card class="module-card sucursales-form-card">
          <q-card-section>
            <div class="text-h5 text-weight-bold">Nueva Sucursal</div>
            <div class="text-grey-7">
              Registra una nueva sucursal para el sistema.
            </div>
          </q-card-section>

          <q-card-section>
            <q-form @submit.prevent="crearSucursal" class="q-gutter-md">
              <q-input
                v-model.trim="form.nombre"
                label="Nombre de la sucursal"
                outlined
                dense
                :disable="cargando"
                :rules="[(val) => !!val || 'El nombre es obligatorio']"
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

      <!-- TABLA -->
      <div class="col-12 col-md-8">
        <q-card class="module-card sucursales-table-card">
          <q-card-section class="row items-center justify-between">
            <div>
              <div class="text-h5 text-weight-bold">Sucursales</div>
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
              <q-tooltip>Actualizar listado</q-tooltip>
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
              <!-- ESTADO COMO BOTÓN -->
              <template #body-cell-estado="props">
                <q-td :props="props">
                  <q-btn
                    dense
                    unelevated
                    rounded
                    size="sm"
                    :label="props.row.estado"
                    :icon="props.row.estado === 'ACTIVA' ? 'check_circle' : 'block'"
                    :color="props.row.estado === 'ACTIVA' ? 'positive' : 'negative'"
                    @click="cambiarEstadoSucursal(props.row)"
                  >
                    <q-tooltip>
                      {{ props.row.estado === 'ACTIVA' ? 'Cambiar a INACTIVA' : 'Cambiar a ACTIVA' }}
                    </q-tooltip>
                  </q-btn>
                </q-td>
              </template>

              <!-- FECHA -->
              <template #body-cell-created_at="props">
                <q-td :props="props">
                  {{ formatearFecha(props.row.created_at) }}
                </q-td>
              </template>

              <!-- ACCIONES -->
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
                      <q-tooltip>Editar sucursal</q-tooltip>
                    </q-btn>
                  </div>
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- MODAL EDITAR -->
    <q-dialog v-model="modalEditar">
      <q-card class="sucursales-dialog-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold">Editar Sucursal</div>
          <div class="text-grey-7">Modifica los datos de la sucursal.</div>
        </q-card-section>

        <q-card-section>
          <q-form @submit.prevent="actualizarSucursal" class="q-gutter-md">
            <q-input
              v-model.trim="formEditar.nombre"
              label="Nombre"
              outlined
              dense
              :disable="cargandoEditar"
              :rules="[(val) => !!val || 'El nombre es obligatorio']"
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

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useQuasar } from 'quasar'
import api from '@/api/axios'

const $q = useQuasar()

const sucursales = ref([])
const cargando = ref(false)
const cargandoListado = ref(false)
const cargandoEditar = ref(false)
const modalEditar = ref(false)

const form = reactive({
  nombre: '',
  direccion: '',
  telefono: '',
})

const formEditar = reactive({
  id_sucursal: null,
  nombre: '',
  direccion: '',
  telefono: '',
})

const columnas = [
  {
    name: 'id_sucursal',
    label: 'ID',
    field: 'id_sucursal',
    align: 'left',
    sortable: true,
  },
  {
    name: 'nombre',
    label: 'Nombre',
    field: 'nombre',
    align: 'left',
    sortable: true,
  },
  {
    name: 'direccion',
    label: 'Dirección',
    field: 'direccion',
    align: 'left',
  },
  {
    name: 'telefono',
    label: 'Teléfono',
    field: 'telefono',
    align: 'left',
  },
  {
    name: 'estado',
    label: 'Estado',
    field: 'estado',
    align: 'center',
  },
  {
    name: 'created_at',
    label: 'Creado',
    field: 'created_at',
    align: 'left',
  },
  {
    name: 'acciones',
    label: 'Acciones',
    field: 'acciones',
    align: 'center',
  },
]

const obtenerSucursales = async () => {
  cargandoListado.value = true

  try {
    const response = await api.get('/sucursales')
    sucursales.value = response.data.sucursales || []
  } catch (error) {
    console.error('Error al obtener sucursales:', error)

    $q.notify({
      type: 'negative',
      message: 'No se pudieron cargar las sucursales',
      position: 'top',
    })
  } finally {
    cargandoListado.value = false
  }
}

const crearSucursal = async () => {
  if (!form.nombre) {
    $q.notify({
      type: 'warning',
      message: 'El nombre de la sucursal es obligatorio',
      position: 'top',
    })
    return
  }

  cargando.value = true

  try {
    const response = await api.post('/sucursales', {
      nombre: form.nombre,
      direccion: form.direccion || null,
      telefono: form.telefono || null,
    })

    const nuevaSucursal = response.data.sucursal

    if (nuevaSucursal) {
      sucursales.value.unshift(nuevaSucursal)
    } else {
      await obtenerSucursales()
    }

    $q.notify({
      type: 'positive',
      message: 'Sucursal creada correctamente',
      position: 'top',
      timeout: 1500,
    })

    limpiarFormulario()
  } catch (error) {
    console.error('Error al crear sucursal:', error)

    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'No se pudo crear la sucursal',
      position: 'top',
    })
  } finally {
    cargando.value = false
  }
}

const abrirEditar = (sucursal) => {
  formEditar.id_sucursal = sucursal.id_sucursal
  formEditar.nombre = sucursal.nombre || ''
  formEditar.direccion = sucursal.direccion || ''
  formEditar.telefono = sucursal.telefono || ''

  modalEditar.value = true
}

const actualizarSucursal = async () => {
  if (!formEditar.nombre) {
    $q.notify({
      type: 'warning',
      message: 'El nombre es obligatorio',
      position: 'top',
    })
    return
  }

  cargandoEditar.value = true

  try {
    const response = await api.put(`/sucursales/${formEditar.id_sucursal}`, {
      nombre: formEditar.nombre,
      direccion: formEditar.direccion || null,
      telefono: formEditar.telefono || null,
    })

    const sucursalActualizada = response.data.sucursal

    const index = sucursales.value.findIndex(
      (item) => item.id_sucursal === formEditar.id_sucursal,
    )

    if (index !== -1 && sucursalActualizada) {
      sucursales.value[index] = sucursalActualizada
    } else {
      await obtenerSucursales()
    }

    $q.notify({
      type: 'positive',
      message: 'Sucursal actualizada correctamente',
      position: 'top',
      timeout: 1500,
    })

    modalEditar.value = false
  } catch (error) {
    console.error('Error al actualizar sucursal:', error)

    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'No se pudo actualizar la sucursal',
      position: 'top',
    })
  } finally {
    cargandoEditar.value = false
  }
}

const cambiarEstadoSucursal = async (sucursal) => {
  const estadoAnterior = sucursal.estado
  const nuevoEstado = sucursal.estado === 'ACTIVA' ? 'INACTIVA' : 'ACTIVA'

  // Cambio visual inmediato sin recargar ni refrescar listado
  sucursal.estado = nuevoEstado

  try {
    const response = await api.patch(`/sucursales/${sucursal.id_sucursal}/estado`, {
      estado: nuevoEstado,
    })

    if (response.data.sucursal) {
      const index = sucursales.value.findIndex(
        (item) => item.id_sucursal === sucursal.id_sucursal,
      )

      if (index !== -1) {
        sucursales.value[index] = response.data.sucursal
      }
    }

    $q.notify({
      type: nuevoEstado === 'ACTIVA' ? 'positive' : 'warning',
      message: `Sucursal cambiada a ${nuevoEstado}`,
      position: 'top',
      timeout: 1500,
    })
  } catch (error) {
    // Si falla, vuelve al estado anterior
    sucursal.estado = estadoAnterior

    console.error('Error al cambiar estado:', error)

    $q.notify({
      type: 'negative',
      message: 'No se pudo cambiar el estado de la sucursal',
      position: 'top',
    })
  }
}

const limpiarFormulario = () => {
  form.nombre = ''
  form.direccion = ''
  form.telefono = ''
}

const formatearFecha = (fecha) => {
  if (!fecha) return '-'

  return new Date(fecha).toLocaleDateString('es-BO', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  })
}

onMounted(() => {
  obtenerSucursales()
})
</script>