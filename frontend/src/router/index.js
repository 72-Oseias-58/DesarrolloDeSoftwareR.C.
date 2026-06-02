import { createRouter, createWebHistory } from 'vue-router'

import HomeView from '../views/HomeView.vue'
import LoginView from '../views/auth/LoginView.vue'

import MainLayout from '../layouts/MainLayout.vue'

import SuperAdminDashboard from '../views/dashboard/SuperAdminDashboard.vue'
import AdminDashboard from '../views/dashboard/AdminDashboard.vue'
import CajeroDashboard from '../views/dashboard/CajeroDashboard.vue'

// ADMIN
import InventarioView from '../views/admin/InventarioView.vue'
import EmpleadosView from '../views/admin/EmpleadosView.vue'
import ReportesView from '../views/admin/ReportesView.vue'

// CAJERO
import NuevoPedidoView from '../views/cajero/NuevoPedidoView.vue'
import RegistrarPagoView from '../views/cajero/RegistrarPagoView.vue'
import HistorialPedidosView from '../views/cajero/HistorialPedidosView.vue'
import ReimprimirTicketView from '../views/cajero/ReimprimirTicketView.vue'

// SUPERADMIN
import SucursalesView from '../views/superadmin/SucursalesView.vue'
import AdministradoresView from '../views/superadmin/AdministradoresView.vue'

const obtenerNombreRol = (usuario) => {
  return (
    usuario?.role?.nombre_rol ||
    usuario?.rol?.nombre_rol ||
    usuario?.role?.nombre ||
    usuario?.rol?.nombre ||
    usuario?.nombre_rol ||
    usuario?.rol ||
    null
  )
}

const obtenerRutaPorRol = (rol) => {
  if (rol === 'SUPERADMIN') return '/superadmin/dashboard'
  if (rol === 'ADMIN') return '/admin/dashboard'
  if (rol === 'CAJERO') return '/cajero/dashboard'

  return '/login'
}

const routes = [
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/home',
    name: 'home',
    component: HomeView,
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: {
      requiereInvitado: true,
    },
  },

  // SUPERADMIN
  {
    path: '/superadmin',
    component: MainLayout,
    meta: {
      requiereAuth: true,
      roles: ['SUPERADMIN'],
    },
    children: [
      {
        path: '',
        redirect: '/superadmin/dashboard',
      },
      {
        path: 'dashboard',
        name: 'superadmin-dashboard',
        component: SuperAdminDashboard,
      },
      {
        path: 'sucursales',
        name: 'superadmin-sucursales',
        component: SucursalesView,
      },
      {
        path: 'administradores',
        name: 'superadmin-administradores',
        component: AdministradoresView,
      },
    ],
  },

  // ADMIN
  {
    path: '/admin',
    component: MainLayout,
    meta: {
      requiereAuth: true,
      roles: ['ADMIN'],
    },
    children: [
      {
        path: '',
        redirect: '/admin/dashboard',
      },
      {
        path: 'dashboard',
        name: 'admin-dashboard',
        component: AdminDashboard,
      },
      {
        path: 'inventario',
        name: 'admin-inventario',
        component: InventarioView,
      },
      {
        path: 'empleados',
        name: 'admin-empleados',
        component: EmpleadosView,
      },
      {
        path: 'reportes',
        name: 'admin-reportes',
        component: ReportesView,
      },
    ],
  },

  // CAJERO
  {
    path: '/cajero',
    component: MainLayout,
    meta: {
      requiereAuth: true,
      roles: ['CAJERO'],
    },
    children: [
      {
        path: '',
        redirect: '/cajero/dashboard',
      },
      {
        path: 'dashboard',
        name: 'cajero-dashboard',
        component: CajeroDashboard,
      },
      {
        path: 'nuevo-pedido',
        name: 'cajero-nuevo-pedido',
        component: NuevoPedidoView,
      },
      {
        path: 'registrar-pago',
        name: 'cajero-registrar-pago',
        component: RegistrarPagoView,
      },
      {
        path: 'historial',
        name: 'cajero-historial',
        component: HistorialPedidosView,
      },
      {
        path: 'reimprimir-ticket',
        name: 'cajero-reimprimir-ticket',
        component: ReimprimirTicketView,
      },
    ],
  },

  {
    path: '/:pathMatch(.*)*',
    redirect: '/login',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const usuarioGuardado = localStorage.getItem('usuario')

  let usuario = null

  if (usuarioGuardado) {
    try {
      usuario = JSON.parse(usuarioGuardado)
    } catch (error) {
      localStorage.removeItem('usuario')
      localStorage.removeItem('token')
      return next('/login')
    }
  }

  const rol = obtenerNombreRol(usuario)

  if (to.meta.requiereAuth && !token) {
    return next('/login')
  }

  if (to.meta.requiereInvitado && token) {
    return next(obtenerRutaPorRol(rol))
  }

  if (to.meta.roles && !to.meta.roles.includes(rol)) {
    return next(obtenerRutaPorRol(rol))
  }

  next()
})

export default router