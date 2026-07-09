import { createRouter, createWebHistory } from 'vue-router'

import PermisosUsuarioView from '../views/permisos/PermisosUsuarioView.vue'

import HomeView from '../views/HomeView.vue'
import LoginView from '../views/auth/LoginView.vue'

import MainLayout from '../layouts/MainLayout.vue'

import SuperAdminDashboard from '../views/dashboard/superadmin/SuperAdminDashboard.vue'
import AdminDashboard from '../views/dashboard/admin/AdminDashboard.vue'
import CajeroDashboard from '../views/dashboard/cajero/CajeroDashboard.vue'

import InventarioView from '../views/admin/inventario/InventarioView.vue'
import EmpleadosView from '../views/admin/empleados/EmpleadosView.vue'
import ReportesView from '../views/admin/reportes/ReportesView.vue'
import JornadasView from '../views/admin/jornadas/JornadasView.vue'
import CajasView from '../views/admin/cajas/CajasView.vue'
import SolicitudesView from '../views/admin/solicitudes/SolicitudesView.vue'

import NuevoPedidoView from '../views/cajero/nuevo-pedido/NuevoPedidoView.vue'
import RegistrarPagoView from '../views/cajero/registrar-pago/RegistrarPagoView.vue'
import HistorialPedidosView from '../views/cajero/historial-pedidos/HistorialPedidosView.vue'
import ReimprimirTicketView from '../views/cajero/reimprimir-ticket/ReimprimirTicketView.vue'
import CajaView from '../views/cajero/caja/CajaView.vue'
import StockBebidasView from '../views/cajero/stock-bebidas/StockBebidasView.vue'
import CatalogoProductosView from '../views/cajero/catalogo-productos/CatalogoProductosView.vue'


import SucursalesView from '../views/superadmin/sucursales/SucursalesView.vue'
import EstadisticasSucursalView from '../views/superadmin/estadisticas-sucursal/EstadisticasSucursalView.vue'
import AdministradoresView from '../views/superadmin/administradores/AdministradoresView.vue'

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

const obtenerPermisos = (usuario) => {
  if (!usuario) {
    return []
  }

  if (Array.isArray(usuario.permisos)) {
    return usuario.permisos
  }

  return []
}

const tienePermiso = (usuario, permisoRuta) => {
  if (!permisoRuta) {
    return true
  }

  const permisosUsuario = obtenerPermisos(usuario)

  if (Array.isArray(permisoRuta)) {
    return permisoRuta.some((permiso) => permisosUsuario.includes(permiso))
  }

  return permisosUsuario.includes(permisoRuta)
}

const obtenerRutaPorRol = (rol) => {
  if (rol === 'SUPERADMIN') {
    return '/superadmin/dashboard'
  }

  if (rol === 'ADMIN') {
    return '/admin/dashboard'
  }

  if (rol === 'CAJERO') {
    return '/cajero/dashboard'
  }

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
        meta: {
          permiso: 'ver_sucursales',
        },
      },
      {
        path: 'sucursales/:id/estadisticas',
        name: 'superadmin-sucursal-estadisticas',
        component: EstadisticasSucursalView,
        meta: {
          permiso: 'ver_sucursales',
        },
      },
      {
        path: 'administradores',
        name: 'superadmin-administradores',
        component: AdministradoresView,
        meta: {
          permiso: 'ver_administradores',
        },
      },
      {
        path: 'usuarios/:id/permisos',
        name: 'superadmin-usuario-permisos',
        component: PermisosUsuarioView,
        meta: {
          permiso: 'gestionar_permisos_admin',
        },
      },
    ],
  },
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
        meta: {
          permiso: 'ver_inventario',
        },
      },
      {
        path: 'empleados',
        name: 'admin-empleados',
        component: EmpleadosView,
        meta: {
          permiso: 'ver_empleados',
        },
      },
      {
        path: 'reportes',
        name: 'admin-reportes',
        component: ReportesView,
        meta: {
          permiso: 'crear_reportes',
        },
      },
      {
        path: 'usuarios/:id/permisos',
        name: 'admin-usuario-permisos',
        component: PermisosUsuarioView,
        meta: {
          permiso: 'gestionar_permisos_cajero',
        },
      },
      {
        path: 'jornadas',
        name: 'admin-jornadas',
        component: JornadasView,
        meta: {
          permiso: 'ver_jornadas',
        },
      },
      {
        path: 'cajas',
        name: 'admin-cajas',
        component: CajasView,
        meta: {
          permiso: 'ver_cajas',
        },
      },
      {
        path: 'solicitudes',
        name: 'admin-solicitudes',
        component: SolicitudesView,
        meta: {
          permiso: 'crear_solicitudes',
        },
      },
    ],
  },
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
        meta: {
          permiso: 'crear_pedidos',
        },
      },
      {
        path: 'catalogo-productos',
        name: 'cajero-catalogo-productos',
        component: CatalogoProductosView,
        meta: {
          requiereAuth: true,
          roles: ['CAJERO', 'ADMIN', 'SUPERADMIN'],
        },
      },
      {
        path: 'registrar-pago',
        name: 'cajero-registrar-pago',
        component: RegistrarPagoView,
        meta: {
          permiso: 'registrar_pagos',
        },
      },
      {
        path: 'historial',
        name: 'cajero-historial',
        component: HistorialPedidosView,
        meta: {
          permiso: 'ver_pedidos',
        },
      },
      {
        path: 'reimprimir-ticket',
        name: 'cajero-reimprimir-ticket',
        component: ReimprimirTicketView,
        meta: {
          permiso: 'reimprimir_ticket',
        },
      },
      {
        path: 'caja',
        name: 'cajero-caja',
        component: CajaView,
        meta: {
          permiso: 'ver_cajas',
        },
      },
      {
        path: 'stock-bebidas',
        name: 'cajero-stock-bebidas',
        component: StockBebidasView,
        meta: {
          permiso: 'ver_stock_bebidas',
        },
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

router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  const usuarioGuardado = localStorage.getItem('usuario')

  let usuario = null

  if (usuarioGuardado) {
    try {
      usuario = JSON.parse(usuarioGuardado)
    } catch (error) {
      console.error('Error al leer usuario guardado:', error)

      localStorage.removeItem('usuario')
      localStorage.removeItem('token')

      return '/login'
    }
  }

  const rol = obtenerNombreRol(usuario)

  if (to.meta.requiereAuth && !token) {
    return '/login'
  }

  if (to.meta.requiereInvitado && token) {
    return obtenerRutaPorRol(rol)
  }

  if (to.meta.roles && !to.meta.roles.includes(rol)) {
    return obtenerRutaPorRol(rol)
  }

  if (to.meta.permiso && !tienePermiso(usuario, to.meta.permiso)) {
    return obtenerRutaPorRol(rol)
  }

  return true
})

export default router