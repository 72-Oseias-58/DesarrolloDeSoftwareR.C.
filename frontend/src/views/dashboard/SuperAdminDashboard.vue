<template>
  <section class="superadmin-page q-pa-md">
    <div class="superadmin-hero q-pa-lg q-mb-lg">
      <div>
        <div class="text-caption text-weight-bold text-uppercase">
          Panel de control
        </div>

        <h3 class="q-ma-none">
          Bienvenido, Superadmin
        </h3>

        <p class="q-mt-sm q-mb-none">
          Control general de sucursales, administradores y configuración del sistema.
        </p>
      </div>

      <q-icon name="admin_panel_settings" size="80px" class="hero-icon" />
    </div>

    <div class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-md-4">
        <q-card class="stat-card">
          <q-card-section>
            <q-icon name="storefront" size="42px" color="primary" />
            <div class="text-h5 q-mt-sm">{{ totalSucursales }}</div>
            <div class="text-grey-7">Sucursales registradas</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card class="stat-card">
          <q-card-section>
            <q-icon name="verified_user" size="42px" color="green" />
            <div class="text-h5 q-mt-sm">{{ sucursalesActivas }}</div>
            <div class="text-grey-7">Sucursales activas</div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card class="stat-card">
          <q-card-section>
            <q-icon name="manage_accounts" size="42px" color="orange" />
            <div class="text-h5 q-mt-sm">{{ totalAdministradores }}</div>
            <div class="text-grey-7">Administradores</div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-md">
      <div class="col-12 col-md-6">
        <q-card class="action-card" @click="$router.push('/superadmin/sucursales')">
          <q-card-section class="row items-center no-wrap">
            <q-icon name="store" size="48px" color="primary" />

            <div class="q-ml-md">
              <div class="text-h6">Gestionar sucursales</div>
              <div class="text-grey-7">
                Crear, editar y activar/inactivar sucursales.
              </div>
            </div>

            <q-space />
            <q-icon name="arrow_forward_ios" color="grey" />
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-6">
        <q-card class="action-card" @click="$router.push('/superadmin/administradores')">
          <q-card-section class="row items-center no-wrap">
            <q-icon name="supervisor_account" size="48px" color="orange" />

            <div class="q-ml-md">
              <div class="text-h6">Gestionar administradores</div>
              <div class="text-grey-7">
                Crear administradores y asignarlos a sucursales.
              </div>
            </div>

            <q-space />
            <q-icon name="arrow_forward_ios" color="grey" />
          </q-card-section>
        </q-card>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../../api/axios'

const sucursales = ref([])
const administradores = ref([])

const totalSucursales = computed(() => sucursales.value.length)

const sucursalesActivas = computed(() =>
  sucursales.value.filter(s => s.estado === 'ACTIVA').length
)

const totalAdministradores = computed(() => administradores.value.length)

const cargarDatos = async () => {
  try {
    const resSucursales = await api.get('/sucursales')
    sucursales.value = resSucursales.data.sucursales || []

    const resAdministradores = await api.get('/administradores')
    administradores.value = resAdministradores.data.administradores || []
  } catch (error) {
    console.log('Error al cargar dashboard superadmin:', error)
  }
}

onMounted(() => {
  cargarDatos()
})
</script>