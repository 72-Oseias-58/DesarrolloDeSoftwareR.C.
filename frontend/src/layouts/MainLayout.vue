<template>
  <q-layout view="lHh Lpr lFf" class="main-layout">
    <!-- HEADER SUPERIOR -->
    <q-header elevated class="main-header">
      <q-toolbar>
        <q-btn flat dense round icon="menu" aria-label="Menú" @click="drawer = !drawer" />

        <q-toolbar-title>
          <div class="system-title">Sistema Rincón Chaqueño</div>
        </q-toolbar-title>

        <div class="user-info">
          <div class="user-name">
            {{ nombreUsuario }}
          </div>
          <div class="user-role">
            {{ rolUsuario }}
          </div>
        </div>

        <q-btn
          flat
          round
          :icon="$q.dark.isActive ? 'light_mode' : 'dark_mode'"
          @click="cambiarModoOscuro"
        >
          <q-tooltip> Cambiar modo </q-tooltip>
        </q-btn>

        <q-btn flat round icon="logout" color="negative" @click="cerrarSesion">
          <q-tooltip> Cerrar sesión </q-tooltip>
        </q-btn>
      </q-toolbar>
    </q-header>

    <!-- MENÚ LATERAL -->
    <q-drawer v-model="drawer" show-if-above bordered :width="260" class="main-drawer">
      <div class="drawer-header">
        <div class="drawer-logo">RC</div>
        <div>
          <div class="drawer-title">Panel del Sistema</div>
          <div class="drawer-role">
            {{ rolUsuario }}
          </div>
        </div>
      </div>

      <q-separator />

      <q-list padding>
        <q-item
          v-for="item in menuFiltrado"
          :key="item.label"
          clickable
          v-ripple
          :to="item.to"
          active-class="menu-active"
        >
          <q-item-section avatar>
            <q-icon :name="item.icon" />
          </q-item-section>

          <q-item-section>
            <q-item-label>{{ item.label }}</q-item-label>
            <q-item-label caption>
              {{ item.caption }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <!-- CONTENIDO DINÁMICO -->
    <q-page-container>
      <q-page class="main-page">
        <router-view />
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useAuthStore } from '@/stores/auth'
import { useAutoLogout } from '@/composables/useAutoLogout'

const router = useRouter()
const $q = useQuasar()
const authStore = useAuthStore()

const drawer = ref(true)

// Cierre automático por inactividad
useAutoLogout()

const usuario = computed(() => authStore.user || null)

const nombreUsuario = computed(() => {
  return usuario.value?.name || usuario.value?.nombre || 'Usuario'
})

const rolUsuario = computed(() => {
  return authStore.rol || usuario.value?.rol || usuario.value?.nombre_rol || 'SIN ROL'
})

const menuItems = [
  // SUPERADMIN
  {
    label: 'Dashboard',
    caption: 'Resumen general',
    icon: 'dashboard',
    to: '/superadmin/dashboard',
    roles: ['SUPERADMIN'],
  },
  {
    label: 'Sucursales',
    caption: 'Gestión de sucursales',
    icon: 'store',
    to: '/superadmin/sucursales',
    roles: ['SUPERADMIN'],
  },
  {
    label: 'Administradores',
    caption: 'Gestión de administradores',
    icon: 'admin_panel_settings',
    to: '/superadmin/administradores',
    roles: ['SUPERADMIN'],
  },

  // ADMIN
  {
    label: 'Dashboard',
    caption: 'Panel administrativo',
    icon: 'dashboard',
    to: '/admin/dashboard',
    roles: ['ADMIN'],
  },
  {
    label: 'Inventario',
    caption: 'Control de productos y stock',
    icon: 'inventory_2',
    to: '/admin/inventario',
    roles: ['ADMIN'],
  },
  {
    label: 'Empleados',
    caption: 'Gestión del personal',
    icon: 'groups',
    to: '/admin/empleados',
    roles: ['ADMIN'],
  },
  {
    label: 'Reportes',
    caption: 'Ventas, stock y actividad',
    icon: 'bar_chart',
    to: '/admin/reportes',
    roles: ['ADMIN'],
  },

  // CAJERO
  {
    label: 'Dashboard',
    caption: 'Panel de caja',
    icon: 'dashboard',
    to: '/cajero/dashboard',
    roles: ['CAJERO'],
  },
  {
    label: 'Nuevo Pedido',
    caption: 'Registrar venta o pedido',
    icon: 'point_of_sale',
    to: '/cajero/nuevo-pedido',
    roles: ['CAJERO'],
  },
  {
    label: 'Registrar Pago',
    caption: 'Confirmar pago del cliente',
    icon: 'payments',
    to: '/cajero/registrar-pago',
    roles: ['CAJERO'],
  },
  {
    label: 'Historial Pedidos',
    caption: 'Pedidos registrados',
    icon: 'receipt_long',
    to: '/cajero/historial',
    roles: ['CAJERO'],
  },
  {
    label: 'Reimprimir Ticket',
    caption: 'Volver a imprimir comprobante',
    icon: 'print',
    to: '/cajero/reimprimir-ticket',
    roles: ['CAJERO'],
  },
]

const menuFiltrado = computed(() => {
  return menuItems.filter((item) => item.roles.includes(rolUsuario.value))
})

const cambiarModoOscuro = () => {
  $q.dark.toggle()

  $q.notify({
    type: 'info',
    message: $q.dark.isActive ? 'Modo oscuro activado' : 'Modo claro activado',
    position: 'top',
    timeout: 1500,
  })
}

const cerrarSesion = () => {
  authStore.logout()

  $q.notify({
    type: 'positive',
    message: 'Sesión cerrada correctamente',
    position: 'top',
    timeout: 2000,
  })

  router.push('/login')
}
</script>
